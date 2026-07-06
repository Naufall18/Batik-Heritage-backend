<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function isAvailable(): bool
    {
        $key = config('midtrans.server_key');
        return !empty($key) && $key !== 'SB-Mid-server-xxx';
    }

    public function createSnapToken(array $params): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Midtrans belum dikonfigurasi. Gunakan metode COD.');
        }
        return Snap::createTransaction($params)->redirect_url;
    }

    public function handleNotification(): array
    {
        return (array) Notification::getInstance();
    }

    public function checkTransaction(string $orderId): object
    {
        return Transaction::status($orderId);
    }
}
