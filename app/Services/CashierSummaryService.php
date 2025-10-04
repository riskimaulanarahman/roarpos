<?php

namespace App\Services;

use App\Jobs\SendCashierSummaryEmail;
use App\Models\CashierClosureReport;
use App\Models\CashierSession;
use App\Models\Order;
use Illuminate\Support\Carbon;

class CashierSummaryService
{
    public function generate(
        CashierSession $session,
        ?string $timezone = null,
        ?int $timezoneOffset = null
    ): array {
        $timezone = $timezone ?: $session->getAttribute('timezone');
        $timezoneOffset = $timezoneOffset ?? $session->getAttribute('timezone_offset');

        $openedAtLocal = $this->convertToLocal($session->opened_at, $timezone, $timezoneOffset);
        $closedAtLocal = $this->convertToLocal($session->closed_at, $timezone, $timezoneOffset);

        if ($timezone && $timezoneOffset === null) {
            $timezoneOffset = $closedAtLocal?->utcOffset() ?? $openedAtLocal?->utcOffset();
        }

        if (!$timezone && $timezoneOffset === null) {
            $timezone = config('app.timezone');
            $openedAtLocal = $this->convertToLocal($session->opened_at, $timezone, null);
            $closedAtLocal = $this->convertToLocal($session->closed_at, $timezone, null);
            $timezoneOffset = $closedAtLocal?->utcOffset() ?? $openedAtLocal?->utcOffset();
        }

        $start = $session->opened_at ?? $session->created_at;
        $end = $session->closed_at ?? now();

        $orders = Order::where('user_id', $session->user_id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $nonRefundOrders = $orders->filter(fn ($order) => $order->status !== 'refund');
        $refundOrders = $orders->filter(fn ($order) => $order->status === 'refund');

        /**
         * Hitung total penjualan cash termasuk refund (refund dikurangi)
         */
        $cashSales = (float) $orders
            ->filter(fn ($order) => strtoupper($order->payment_method ?? '') === 'CASH')
            ->sum(fn ($order) => $order->status === 'refund'
                ? -($order->refund_nominal ?? $order->total_price ?? 0)
                : ($order->total_price ?? 0)
            );

        /**
         * Breakdown pembayaran (masih berdasarkan non-refund agar tetap terlihat rapi)
         */
        $paymentBreakdown = [];
        $cashSales = 0.0;
        foreach ($nonRefundOrders->groupBy('payment_method') as $method => $collection) {
            $methodLabel = strtoupper($method ?? 'UNKNOWN');
            $amount = (float) $collection->sum('total_price');

<<<<<<< ours
=======
            if ($methodLabel === 'CASH') {
                $cashSales = $amount;
            }

>>>>>>> theirs
            $paymentBreakdown[] = [
                'method' => $methodLabel,
                'amount' => $amount,
                'transactions' => $collection->count(),
            ];
        }

        /**
         * Hitung total penjualan dan refund
         */
        $totalSales = (float) $nonRefundOrders->sum('total_price');
        $refundTotal = (float) $refundOrders->sum(function ($order) {
            return $order->refund_nominal ?? $order->total_price ?? 0;
        });

        $cashRefunds = (float) $refundOrders
<<<<<<< ours
            ->filter(fn ($order) => strtolower($order->refund_method ?? '') === 'cash')
            ->sum(fn ($order) => $order->refund_nominal ?? $order->total_price ?? 0);

        $openingBalance = (float) ($session->opening_balance ?? 0);
        $countedCash = (float) ($session->closing_balance ?? 0);

        /**
         * Karena cashSales sudah memperhitungkan refund, expectedCash cukup:
         */
        $expectedCash = $openingBalance + $cashSales;
=======
            ->filter(function ($order) {
                return strtolower($order->refund_method ?? '') === 'cash';
            })
            ->sum(function ($order) {
                return $order->refund_nominal ?? $order->total_price ?? 0;
            });

        $openingBalance = (float) ($session->opening_balance ?? 0);
        $countedCash = (float) ($session->closing_balance ?? 0);
        $expectedCash = $openingBalance + $cashSales - $cashRefunds;

        $summary = [
            'session' => [
                'id' => $session->id,
                'opened_at' => $openedAtLocal?->toIso8601String(),
                'closed_at' => $closedAtLocal?->toIso8601String(),
                'opening_balance' => $openingBalance,
                'closing_balance' => $countedCash,
                'timezone' => $timezone,
                'timezone_offset' => $timezoneOffset,
            ],
            'totals' => [
                'sales' => $totalSales,
                'refunds' => $refundTotal,
                'net_sales' => $totalSales - $refundTotal,
            ],
            'payments' => $paymentBreakdown,
            'transactions' => [
                'total' => $orders->count(),
                'completed' => $nonRefundOrders->count(),
                'refunded' => $refundOrders->count(),
            ],
            'cash_balance' => [
                'opening' => $openingBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'expected' => $expectedCash,
                'counted' => $countedCash,
                'difference' => $countedCash - $expectedCash,
            ],
        ];

        return $summary;
    }

    private function convertToLocal(?Carbon $dateTime, ?string $timezone, ?int $timezoneOffset): ?Carbon
    {
        if (!$dateTime) {
            return null;
        }

        $instance = $dateTime->copy();

        if ($timezone) {
            return $instance->setTimezone($timezone);
        }

        if ($timezoneOffset !== null) {
            return $instance->setTimezone('UTC')->addMinutes($timezoneOffset);
        }

        return $instance;
    }

    public function createReport(
        CashierSession $session,
        array $summary,
        ?string $emailTo = null
    ): CashierClosureReport {
        return CashierClosureReport::updateOrCreate(
            ['cashier_session_id' => $session->id],
            [
                'user_id' => $session->user_id,
                'summary' => $summary,
                'email_to' => $emailTo,
                'email_status' => $emailTo ? 'pending' : 'skipped',
            ]
        );
    }

    public function dispatchEmail(CashierClosureReport $report): void
    {
        if (!$report->email_to) {
            return;
        }

        SendCashierSummaryEmail::dispatch($report->id);
    }
}
