<?php

namespace App\Services;

use App\Jobs\ProcessEOPayout;
use App\Jobs\SyncEOPayoutStatus;
use App\Models\Order;
use App\Models\Payout;
use App\Support\XenditPayoutChannels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Xendit\Configuration;
use Xendit\Payout\CreatePayoutRequest;
use Xendit\Payout\DigitalPayoutChannelProperties;
use Xendit\Payout\PayoutApi;
use Xendit\XenditSdkException;

class XenditEOPayoutService
{
    public function queue(Payout $payout, bool $newReference = false): void
    {
        if (! config('services.xendit.eo_auto_payout', true)) {
            throw new RuntimeException('Auto payout EO belum aktif.');
        }

        $payout->loadMissing(['event', 'organizer']);
        $this->ensurePayoutDestinationIsReady($payout);

        if (! in_array($payout->status, ['pending', 'failed'], true)) {
            throw new RuntimeException('Payout EO ini tidak bisa diproses otomatis.');
        }

        $referenceId = $newReference || ! $payout->xendit_payout_reference_id
            ? $this->newReferenceId($payout)
            : $payout->xendit_payout_reference_id;

        $payout->update([
            'status' => 'processing',
            'reviewed_at' => $payout->reviewed_at ?? now(),
            'xendit_payout_reference_id' => $referenceId,
            'xendit_payout_status' => 'QUEUED',
            'xendit_payout_failure_code' => null,
            'xendit_payout_requested_at' => now(),
            'xendit_payout_completed_at' => null,
        ]);

        ProcessEOPayout::dispatch($payout->id)->afterCommit();
    }

    public function send(Payout $payout): void
    {
        $payout->refresh();
        $payout->loadMissing(['event', 'organizer']);

        if ($payout->status !== 'processing') {
            return;
        }

        try {
            $this->ensurePayoutDestinationIsReady($payout);
        } catch (Throwable $e) {
            $this->markPayoutFailed($payout, 'INVALID_PAYOUT_DESTINATION', $e->getMessage());

            return;
        }

        Configuration::setXenditKey(config('services.xendit.secret_key'));

        $payout->update([
            'xendit_payout_status' => 'PROCESSING',
            'xendit_payout_failure_code' => null,
        ]);

        try {
            $xenditPayout = (new PayoutApi)->createPayout(
                $payout->xendit_payout_reference_id,
                null,
                new CreatePayoutRequest([
                    'reference_id' => $payout->xendit_payout_reference_id,
                    'currency' => 'IDR',
                    'channel_code' => $this->destinationChannelCode($payout),
                    'channel_properties' => new DigitalPayoutChannelProperties([
                        'account_holder_name' => $payout->organizer->bank_account_name,
                        'account_number' => $payout->organizer->bank_account_number,
                    ]),
                    'amount' => (float) $payout->net_amount,
                    'description' => 'Payout EO EvenTour event #'.$payout->event_id,
                    'metadata' => (object) [
                        'payout_id' => (string) $payout->id,
                        'event_id' => (string) $payout->event_id,
                        'organizer_id' => (string) $payout->event_organizer_id,
                    ],
                ])
            );

            $status = strtoupper((string) ($xenditPayout->getStatus() ?? 'ACCEPTED'));

            if ($this->isSuccessfulStatus($status)) {
                $this->markPayoutSucceeded($payout, $xenditPayout->getId(), $status);

                return;
            }

            if ($this->isFailedStatus($status)) {
                $payout->update([
                    'xendit_payout_id' => $xenditPayout->getId(),
                    'xendit_payout_status' => $status,
                ]);

                $this->markPayoutFailed($payout, $xenditPayout->getFailureCode(), 'Xendit EO payout failed.');

                return;
            }

            $payout->update([
                'xendit_payout_id' => $xenditPayout->getId(),
                'xendit_payout_status' => $status,
                'xendit_payout_failure_code' => null,
            ]);

            $this->scheduleStatusSync($payout->refresh());
        } catch (Throwable $e) {
            $this->markPayoutFailed($payout, $this->failureCodeFromException($e), $e->getMessage());

            Log::error('Xendit EO payout failed', [
                'payout_id' => $payout->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function syncStatusFromXendit(Payout $payout, bool $scheduleAgain = false, int $attempt = 1): void
    {
        $payout->refresh();

        if ($payout->status !== 'processing') {
            return;
        }

        if (! $payout->xendit_payout_id && ! $payout->xendit_payout_reference_id) {
            return;
        }

        Configuration::setXenditKey(config('services.xendit.secret_key'));

        try {
            $xenditPayout = $this->fetchPayout($payout);

            if (! $xenditPayout) {
                if ($scheduleAgain) {
                    $this->scheduleStatusSync($payout, $attempt + 1);
                }

                return;
            }

            $payoutId = $this->readPayoutField($xenditPayout, 'id');
            $referenceId = $this->readPayoutField($xenditPayout, 'reference_id');
            $status = strtoupper((string) $this->readPayoutField($xenditPayout, 'status'));
            $failureCode = $this->readPayoutField($xenditPayout, 'failure_code');

            if ($this->isSuccessfulStatus($status)) {
                $this->markPayoutSucceeded($payout, $payoutId, $status);

                return;
            }

            if ($this->isFailedStatus($status)) {
                $payout->update([
                    'xendit_payout_id' => $payoutId ?? $payout->xendit_payout_id,
                    'xendit_payout_reference_id' => $referenceId ?? $payout->xendit_payout_reference_id,
                ]);

                $this->markPayoutFailed($payout->refresh(), $failureCode, 'Xendit EO payout status sync failed.');

                return;
            }

            $payout->update([
                'xendit_payout_id' => $payoutId ?? $payout->xendit_payout_id,
                'xendit_payout_reference_id' => $referenceId ?? $payout->xendit_payout_reference_id,
                'xendit_payout_status' => $status ?: $payout->xendit_payout_status,
                'xendit_payout_failure_code' => null,
            ]);

            if ($scheduleAgain) {
                $this->scheduleStatusSync($payout->refresh(), $attempt + 1);
            }
        } catch (Throwable $e) {
            Log::warning('Xendit EO payout status sync failed', [
                'payout_id' => $payout->id,
                'message' => $e->getMessage(),
            ]);

            if ($scheduleAgain) {
                $this->scheduleStatusSync($payout, $attempt + 1);
            }
        }
    }

    public function markPayoutSucceeded(Payout $payout, ?string $xenditPayoutId, ?string $status = 'SUCCEEDED'): void
    {
        DB::transaction(function () use ($payout, $xenditPayoutId, $status) {
            $payout->update([
                'status' => 'completed',
                'processed_at' => $payout->processed_at ?? now(),
                'xendit_payout_id' => $xenditPayoutId ?? $payout->xendit_payout_id,
                'xendit_payout_status' => $status ?: 'SUCCEEDED',
                'xendit_payout_failure_code' => null,
                'xendit_payout_completed_at' => now(),
            ]);

            Order::where('event_id', $payout->event_id)
                ->where('payment_status', 'paid')
                ->update(['payment_status' => 'disbursed']);
        });
    }

    public function markPayoutFailed(Payout $payout, ?string $failureCode, ?string $message = null): void
    {
        $payout->update([
            'status' => 'failed',
            'xendit_payout_status' => 'FAILED',
            'xendit_payout_failure_code' => $failureCode ?: 'PAYOUT_FAILED',
        ]);

        Log::warning('EO payout marked as failed', [
            'payout_id' => $payout->id,
            'failure_code' => $failureCode,
            'message' => $message,
        ]);
    }

    public function isSuccessfulStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            'SUCCEEDED',
            'SUCCESS',
            'SUCCESSFUL',
            'COMPLETED',
            'PAID',
            'SUCCESSFUL_DISBURSEMENT',
        ], true);
    }

    public function isFailedStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            'FAILED',
            'CANCELLED',
            'CANCELED',
            'REJECTED',
            'RETURNED',
            'REVERSED',
            'VOIDED',
        ], true);
    }

    private function ensurePayoutDestinationIsReady(Payout $payout): void
    {
        $payout->loadMissing('organizer');

        if (! $this->destinationChannelCode($payout)
            || ! $payout->organizer?->bank_account_number
            || ! $payout->organizer?->bank_account_name
        ) {
            throw new RuntimeException('EO belum punya data rekening payout yang lengkap.');
        }
    }

    private function destinationChannelCode(Payout $payout): ?string
    {
        $payout->loadMissing('organizer');

        return $payout->organizer?->bank_channel_code
            ?: XenditPayoutChannels::bankCodeForName($payout->organizer?->bank_name);
    }

    private function fetchPayout(Payout $payout): mixed
    {
        $payoutApi = new PayoutApi;

        if ($payout->xendit_payout_id) {
            return $payoutApi->getPayoutById($payout->xendit_payout_id);
        }

        $response = $payoutApi->getPayouts($payout->xendit_payout_reference_id, 1);

        if (is_object($response) && method_exists($response, 'getData')) {
            $data = $response->getData();
        } elseif (is_array($response)) {
            $data = $response['data'] ?? [];
        } else {
            $data = [];
        }

        return $data[0] ?? null;
    }

    private function readPayoutField(mixed $payout, string $field): mixed
    {
        $getter = 'get'.Str::studly($field);

        if (is_object($payout) && method_exists($payout, $getter)) {
            return $payout->{$getter}();
        }

        if (is_array($payout)) {
            return $payout[$field] ?? null;
        }

        return null;
    }

    private function scheduleStatusSync(Payout $payout, int $attempt = 1): void
    {
        if ($attempt > 8 || $payout->status !== 'processing') {
            return;
        }

        SyncEOPayoutStatus::dispatch($payout->id, $attempt)
            ->delay(now()->addSeconds(min(300, 15 * $attempt)));
    }

    private function newReferenceId(Payout $payout): string
    {
        return 'EO-PAYOUT-'.$payout->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }

    private function failureCodeFromException(Throwable $e): ?string
    {
        if ($e instanceof XenditSdkException) {
            $fullError = (array) ($e->getFullError() ?? []);

            return $e->getErrorCode()
                ?: ($fullError['error_code'] ?? null)
                ?: ($fullError['errorCode'] ?? null);
        }

        return null;
    }
}
