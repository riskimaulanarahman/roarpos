@component('mail::message')
@php($netSales = $totals['net_sales'] ?? 0)
@php($salesTotal = $totals['sales'] ?? 0)
@php($refundTotal = $totals['refunds'] ?? 0)
@php($difference = $cashBalance['difference'] ?? 0)

@include('emails.partials.brand-header', [
    'title' => 'Ringkasan Tutup Kasir',
    'subtitle' => optional($report->session?->closed_at)->timezone(config('app.timezone'))->format('d F Y, H:i') ?? null,
])

<div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
    <p style="margin: 0 0 16px; font-size: 15px; color: #111827;">
        Halo <strong>{{ $report->user->name ?? 'Kasir' }}</strong>,<br>
        berikut ringkasan singkat sesi kasir yang baru saja ditutup.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Penjualan Bersih</div>
                <div style="font-size: 20px; font-weight: 700; color: #047857;">Rp{{ number_format($netSales, 0, ',', '.') }}</div>
            </td>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Total Transaksi</div>
                <div style="font-size: 20px; font-weight: 700; color: #1D4ED8;">{{ $transactions['total'] ?? 0 }}</div>
                <div style="font-size: 12px; color: #6B7280;">{{ $transactions['completed'] ?? 0 }} selesai • {{ $transactions['refunded'] ?? 0 }} refund</div>
            </td>
            <td style="width: 33.333%; padding: 12px; border-radius: 12px; background: #FFF; border: 1px solid #E5E7EB;">
                <div style="font-size: 12px; text-transform: uppercase; color: #6B7280; letter-spacing: 0.6px;">Selisih Kas</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $difference == 0 ? '#111827' : ($difference > 0 ? '#047857' : '#DC2626') }};">Rp{{ number_format($difference, 0, ',', '.') }}</div>
                <div style="font-size: 12px; color: #6B7280;">Kas dihitung: Rp{{ number_format($cashBalance['counted'] ?? 0, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Informasi Sesi</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            <tr><td style="padding: 4px 0; width: 45%;">Dibuka</td><td style="padding: 4px 0;">{{ optional($report->session?->opened_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}</td></tr>
            <tr><td style="padding: 4px 0;">Ditutup</td><td style="padding: 4px 0;">{{ optional($report->session?->closed_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}</td></tr>
            <tr><td style="padding: 4px 0;">Modal awal</td><td style="padding: 4px 0;">Rp{{ number_format($session['opening_balance'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Saldo akhir</td><td style="padding: 4px 0;">Rp{{ number_format($session['closing_balance'] ?? 0, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Ringkasan Penjualan</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            <tr><td style="padding: 4px 0; width: 45%;">Total penjualan</td><td style="padding: 4px 0;">Rp{{ number_format($salesTotal, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Total refund</td><td style="padding: 4px 0;">Rp{{ number_format($refundTotal, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Penjualan bersih</td><td style="padding: 4px 0;">Rp{{ number_format($netSales, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div style="margin-bottom: 16px;">
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Saldo Kas Tunai</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151;">
            <tr><td style="padding: 4px 0; width: 45%;">Modal awal</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['opening'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Penjualan cash</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['cash_sales'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Refund cash</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['cash_refunds'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Estimasi kas</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['expected'] ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td style="padding: 4px 0;">Kas dihitung</td><td style="padding: 4px 0;">Rp{{ number_format($cashBalance['counted'] ?? 0, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div>
        <div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px;">Rincian Pembayaran</div>
        @if(empty($payments))
            <p style="margin: 0; font-size: 14px; color: #6B7280;">Tidak ada transaksi pembayaran yang tercatat.</p>
        @else
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #374151; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Metode</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Transaksi</th>
                        <th align="right" style="border-bottom: 1px solid #E5E7EB; padding: 8px 0;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">{{ $payment['method'] }}</td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">{{ $payment['transactions'] }}</td>
                            <td align="right" style="padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">Rp{{ number_format($payment['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@component('mail::subcopy')
    <p style="margin: 0; color: #9CA3AF;">Email ini dikirim otomatis oleh {{ $appName }}.</p>
@endcomponent
@endcomponent
