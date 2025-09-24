@component('mail::message')
# {{ $appName }} — Peringatan Stok Minimum

Beberapa bahan baku telah melewati batas minimum:

| SKU | Nama | Stok | Minimum |
|:--- |:-----| ----:| -------:|
@foreach($items as $it)
| {{ $it['sku'] }} | {{ $it['name'] }} | {{ number_format($it['stock'], 4) }} | {{ number_format($it['min'], 4) }} |
@endforeach

Segera lakukan pengadaan untuk menghindari kehabisan stok.

@component('mail::subcopy')
Email ini dikirim otomatis oleh {{ $appName }}.
@endcomponent
@endcomponent

