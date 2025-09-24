@component('mail::message')
# {{ $appName }} — Ringkasan Stok Minimum

Berikut daftar bahan baku yang berada pada atau di bawah batas minimum.

@if(empty($items))
Semua aman. Tidak ada bahan baku low-stock hari ini.
@else
| SKU | Nama | Stok | Minimum |
|:--- |:-----| ----:| -------:|
@foreach($items as $it)
| {{ $it['sku'] }} | {{ $it['name'] }} | {{ number_format($it['stock'], 4) }} | {{ number_format($it['min'], 4) }} |
@endforeach
@endif

@component('mail::subcopy')
Email ini dikirim otomatis oleh {{ $appName }}.
@endcomponent
@endcomponent

