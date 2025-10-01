<?php

namespace App\Services;

use App\Jobs\SendCashierSummaryEmail;
use App\Models\CashierClosureReport;
use App\Models\CashierSession;
use App\Models\Order;

class CashierSummaryService
{
    public function generate(CashierSession $session): array
    {
        $start = $session->opened_at ?? $session->created_at;
        $end = $session->closed_at ?? now();

        $orders = Order::where('user_id', $session->user_id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $nonRefundOrders = $orders->filter(fn ($order) => $order->status !== 'refund');
        $refundOrders = $orders->filter(fn ($order) => $order->status === 'refund');

        $paymentBreakdown = [];
        $cashSales = 0.0;
        foreach ($nonRefundOrders->groupBy('payment_method') as $method => $collection) {
            $methodLabel = strtoupper($method ?? 'UNKNOWN');
            $amount = (float) $collection->sum('total_price');

            if ($methodLabel === 'CASH') {
                $cashSales = $amount;
            }

            $paymentBreakdown[] = [
                'method' => $methodLabel,
                'amount' => $amount,
                'transactions' => $collection->count(),
            ];
        }

        $totalSales = (float) $nonRefundOrders->sum('total_price');
        $refundTotal = (float) $refundOrders->sum(function ($order) {
            return $order->refund_nominal ?? $order->total_price ?? 0;
        });

        $cashRefunds = (float) $refundOrders
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
                'opened_at' => optional($session->opened_at)->toIso8601String(),
                'closed_at' => optional($session->closed_at)->toIso8601String(),
                'opening_balance' => $openingBalance,
                'closing_balance' => $countedCash,
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
