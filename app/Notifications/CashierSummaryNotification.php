<?php

namespace App\Notifications;

use App\Models\CashierClosureReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CashierSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CashierClosureReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->report->summary ?? [];
        $session = $summary['session'] ?? [];
        $totals = $summary['totals'] ?? [];
        $payments = $summary['payments'] ?? [];
        $transactions = $summary['transactions'] ?? [];
        $cashBalance = $summary['cash_balance'] ?? [];
        $date = Carbon::now()->translatedFormat('d F Y');

        return (new MailMessage)
            ->subject('Ringkasan Tutup Kasir - '.($this->report->user->store_name ?? config('app.name')).' ('.$date.')')
            ->markdown('emails.cashier_summary', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'report' => $this->report,
                'session' => $session,
                'totals' => $totals,
                'payments' => $payments,
                'transactions' => $transactions,
                'cashBalance' => $cashBalance,
            ]);
    }
}
