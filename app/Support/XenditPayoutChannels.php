<?php

namespace App\Support;

class XenditPayoutChannels
{
    public static function bankChannels(): array
    {
        return [
            'ID_BCA' => 'BCA',
            'ID_BNI' => 'BNI',
            'ID_BRI' => 'BRI',
            'ID_MANDIRI' => 'Mandiri',
            'ID_BSI' => 'BSI',
            'ID_CIMB' => 'CIMB Niaga',
            'ID_DANAMON' => 'Danamon',
            'ID_PERMATA' => 'Permata',
        ];
    }

    public static function refundChannels(): array
    {
        return [
            'bank' => [
                'label' => 'Bank',
                'channels' => self::bankChannels(),
            ],
            'ewallet' => [
                'label' => 'E-wallet',
                'channels' => [
                    'ID_DANA' => 'DANA',
                    'ID_OVO' => 'OVO',
                    'ID_GOPAY' => 'GoPay',
                    'ID_LINKAJA' => 'LinkAja',
                    'ID_SHOPEEPAY' => 'ShopeePay',
                ],
            ],
        ];
    }

    public static function flatRefundChannels(): array
    {
        $flatChannels = [];

        foreach (self::refundChannels() as $type => $group) {
            foreach ($group['channels'] as $code => $label) {
                $flatChannels[$code] = [
                    'type' => $type,
                    'label' => $label,
                ];
            }
        }

        return $flatChannels;
    }

    public static function bankNameFor(?string $channelCode): ?string
    {
        return self::bankChannels()[$channelCode] ?? null;
    }

    public static function bankCodeForName(?string $bankName): ?string
    {
        if (! $bankName) {
            return null;
        }

        $normalizedBankName = strtolower(trim($bankName));

        foreach (self::bankChannels() as $channelCode => $label) {
            if (strtolower($label) === $normalizedBankName) {
                return $channelCode;
            }
        }

        return null;
    }
}
