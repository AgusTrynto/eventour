<?php

namespace App\Services;

use App\Jobs\ProcessRefundPayout;
use App\Models\Order;
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

class XenditRefundPayoutService
{
    public function queue(Order $order, bool $newReference = false): void
    {
        if (! config('services.xendit.refund_auto_payout', true)) {
            throw new RuntimeException('Auto payout refund belum aktif.');
        }

        $this->ensurePayoutDestinationIsReady($order);

        $referenceId = $newReference || ! $order->xendit_payout_reference_id
            ? $this->newReferenceId($order)
            : $order->xendit_payout_reference_id;

        $order->update([
            'payment_status' => 'refund_payout_pending',
            'xendit_payout_reference_id' => $referenceId,
            'xendit_payout_status' => 'QUEUED',
            'xendit_payout_failure_code' => null,
            'xendit_payout_requested_at' => now(),
            'xendit_payout_completed_at' => null,
        ]);

        ProcessRefundPayout::dispatch($order->id)->afterCommit();
    }

    public function send(Order $order): void
    {
        $order->refresh();

        if ($order->payment_status !== 'refund_payout_pending') {
            return;
        }

        try {
            $this->ensurePayoutDestinationIsReady($order);
        } catch (Throwable $e) {
            $this->markPayoutFailed($order, 'INVALID_REFUND_DESTINATION', $e->getMessage());

            return;
        }

        Configuration::setXenditKey(config('services.xendit.secret_key'));

        $order->update([
            'xendit_payout_status' => 'PROCESSING',
            'xendit_payout_failure_code' => null,
        ]);

        try {
            $payout = (new PayoutApi)->createPayout(
                $order->xendit_payout_reference_id,
                null,
                new CreatePayoutRequest([
                    'reference_id' => $order->xendit_payout_reference_id,
                    'currency' => 'IDR',
                    'channel_code' => $order->refund_destination_channel_code,
                    'channel_properties' => new DigitalPayoutChannelProperties([
                        'account_holder_name' => $order->refund_destination_account_name,
                        'account_number' => $order->refund_destination_account_number,
                    ]),
                    'amount' => (float) $order->total_amount,
                    'description' => 'Refund EvenTour order #'.$order->id,
                    'metadata' => (object) [
                        'order_id' => (string) $order->id,
                        'event_id' => (string) $order->event_id,
                        'refund_reason' => $order->refund_reason,
                    ],
                ])
            );

            $status = strtoupper((string) ($payout->getStatus() ?? 'ACCEPTED'));

            if ($this->isSuccessfulStatus($status)) {
                $this->markPayoutSucceeded($order, $payout->getId(), $status);

                return;
            }

            if ($this->isFailedStatus($status)) {
                $order->update([
                    'xendit_payout_id' => $payout->getId(),
                    'xendit_payout_status' => $status,
                ]);

                $this->markPayoutFailed($order, $payout->getFailureCode(), 'Xendit payout failed.');

                return;
            }

            $order->update([
                'xendit_payout_id' => $payout->getId(),
                'xendit_payout_status' => $status,
                'xendit_payout_failure_code' => null,
            ]);
        } catch (Throwable $e) {
            $this->markPayoutFailed($order, $this->failureCodeFromException($e), $e->getMessage());

            Log::error('Xendit refund payout failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function markPayoutSucceeded(Order $order, ?string $payoutId, ?string $status = 'SUCCEEDED'): void
    {
        DB::transaction(function () use ($order, $payoutId, $status) {
            $order->update([
                'payment_status' => 'refunded',
                'refunded_at' => $order->refunded_at ?? now(),
                'xendit_payout_id' => $payoutId ?? $order->xendit_payout_id,
                'xendit_payout_status' => $status ?: 'SUCCEEDED',
                'xendit_payout_failure_code' => null,
                'xendit_payout_completed_at' => now(),
            ]);

            $order->tickets()->update([
                'status' => 'cancelled',
                'checked_in_at' => null,
                'checked_in_by' => null,
            ]);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
        });
    }

    public function markPayoutFailed(Order $order, ?string $failureCode, ?string $message = null): void
    {
        $order->update([
            'payment_status' => 'refund_payout_failed',
            'xendit_payout_status' => 'FAILED',
            'xendit_payout_failure_code' => $failureCode ?: 'PAYOUT_FAILED',
        ]);

        app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());

        Log::warning('Refund payout marked as failed', [
            'order_id' => $order->id,
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
            'VOIDED',
        ], true);
    }

    private function ensurePayoutDestinationIsReady(Order $order): void
    {
        if (! $order->refund_destination_channel_code
            || ! $order->refund_destination_account_number
            || ! $order->refund_destination_account_name
        ) {
            throw new RuntimeException('Order belum punya data tujuan payout refund.');
        }
    }

    private function newReferenceId(Order $order): string
    {
        return 'PAYOUT-REFUND-ORDER-'.$order->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
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
