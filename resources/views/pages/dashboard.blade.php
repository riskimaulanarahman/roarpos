@extends('layouts.app')

@section('title', 'General Dashboard')

@push('style')
    <!-- CSS Libraries (tetap, tanpa CSS custom tambahan) -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="h4 h3-md mb-0">Dashboard - TOGA POS ({{ Auth::user()->store_name }})</h1>
            </div>

            {{-- ===== KPI SECTION ===== --}}
            <div class="row">
                {{-- Admin: Users --}}
                @if(Auth::check() && Auth::user()->roles === 'admin')
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
                    <a href="{{ route('user.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3 d-flex align-items-center justify-content-center bg-primary text-white rounded px-3 py-2">
                                    <i class="far fa-user"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Users</div>
                                    <div class="h4 mb-0">{{ $users }}</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Revenue (Completed) --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 d-flex align-items-center justify-content-center bg-success text-white rounded px-3 py-2">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <div>This Month</div>
                                <div class="text-muted small">Revenue (Completed)</div>
                                <div class="h4 mb-0">{{ number_format($monthlyCompletedRevenue ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Orders (Completed) --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 d-flex align-items-center justify-content-center bg-primary text-white rounded px-3 py-2">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div>
                                <div>This Month</div>
                                <div class="text-muted small">Orders (Completed)</div>
                                <div class="h4 mb-0">{{ number_format($monthlyCompletedOrders ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AOV (Completed) --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 d-flex align-items-center justify-content-center bg-info text-white rounded px-3 py-2">
                                <i class="fas fa-divide"></i>
                            </div>
                            <div>
                                <div>This Month</div>
                                <div class="text-muted small">AOV (Completed)</div>
                                <div class="h4 mb-0">{{ number_format($monthlyAov ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Methods --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 d-flex align-items-center justify-content-center bg-warning text-white rounded px-3 py-2">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div>
                                <div>This Month</div>
                                <div class="text-muted small">Payment Methods</div>
                                <div class="h4 mb-0">{{ number_format($monthlyPaymentMethods ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TABEL SALES HARI INI ===== --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                                <h4 class="text-primary mb-2 mb-md-0">Total Sales Today</h4>
                                <h4 class="text-primary font-weight-bold mb-0">
                                    {{ number_format($totalPriceToday, 0, ',', '.') }}
                                </h4>
                            </div>
                            <p class="text-muted small mb-3">
                                @if(($sessionRange['hasSession'] ?? false) && !empty($sessionRange['sessionId']))
                                    Sesi kasir #{{ $sessionRange['sessionId'] }} ({{ ucfirst($sessionRange['status'] ?? '-') }}):
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] }}">{{ $sessionRange['start'] }}</span>
                                    -
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] }}">{{ $sessionRange['end'] }}</span>
                                @else
                                    Rentang hari ini:
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] ?? null }}">{{ $sessionRange['start'] ?? '-' }}</span>
                                    -
                                    <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] ?? null }}">{{ $sessionRange['end'] ?? '-' }}</span>
                                @endif
                            </p>

                            <div class="table-responsive">
                                <table class="table table-striped mb-3">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Transaction Time</th>
                                            <th>Total Price</th>
                                            <th>Total Item</th>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                            <th>Kasir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            <tr>
                                                <td>
                                                    <a href="#" class="js-order-details"
                                                       data-url="{{ route('order.details_json', $order->id) }}"
                                                       data-transaction-time="{{ $order->transaction_time_iso }}">
                                                        <span class="js-transaction-time-display"
                                                              data-time="{{ $order->transaction_time_iso ?: $order->transaction_time_display }}">
                                                            {{ $order->transaction_time_display ?? '-' }}
                                                        </span>
                                                    </a>
                                                </td>
                                                <td>{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                                <td>{{ $order->total_item }}</td>
                                                <td>{{ $order->payment_method ?? '-' }}</td>
                                                <td>{{ ucfirst($order->status ?? '-') }}</td>
                                                <td>{{ $order->user->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                {{ $orders->withQueryString()->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== BREAKDOWN BY PAYMENT METHOD (Today) ===== --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0 text-primary">Breakdown by Payment Method (Today)</h4>
                            </div>
                            <p class="text-muted small">
                                Rentang:
                                <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] ?? null }}">{{ $sessionRange['start'] ?? '-' }}</span>
                                -
                                <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] ?? null }}">{{ $sessionRange['end'] ?? '-' }}</span>
                            </p>

                            @if(isset($paymentBreakdownToday) && $paymentBreakdownToday->count())
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Payment Method</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($paymentBreakdownToday as $pb)
                                                <tr>
                                                    <td>{{ $pb->payment_method ?? 'Unknown' }}</td>
                                                    <td>{{ number_format($pb->total_revenue, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted">Belum ada transaksi hari ini.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== CASHIER SESSION SUMMARY ===== --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0 text-primary">Cashier Sessions (Terbaru)</h4>
                            </div>

                            @if(isset($cashierSessionSummaries) && $cashierSessionSummaries->count())
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Session</th>
                                                <th>Dibuka</th>
                                                <th>Ditutup</th>
                                                <th>Status</th>
                                                <th>Net Sales</th>
                                                <th>Transaksi</th>
                                                <th>Selisih Kas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($cashierSessionSummaries as $session)
                                                <tr>
                                                    <td>
                                                        #{{ $session['id'] }}<br>
                                                        <small class="text-muted">{{ $session['opened_by'] ?? '—' }}
                                                            @if(!empty($session['closed_by']))
                                                                → {{ $session['closed_by'] }}
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @if(!empty($session['opened_at_display']))
                                                            <span class="js-transaction-time-display"
                                                                  data-time="{{ $session['opened_at_iso'] }}">
                                                                {{ $session['opened_at_display'] }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($session['closed_at_display']))
                                                            <span class="js-transaction-time-display"
                                                                  data-time="{{ $session['closed_at_iso'] }}">
                                                                {{ $session['closed_at_display'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-warning">Masih berjalan</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ ucfirst($session['status'] ?? '-') }}</td>
                                                    <td>{{ number_format($session['totals']['net_sales'] ?? 0, 0, ',', '.') }}</td>
                                                    <td>
                                                        {{ $session['transactions']['completed'] ?? 0 }} selesai
                                                        @if(($session['transactions']['refunded'] ?? 0) > 0)
                                                            <br><small class="text-muted">{{ $session['transactions']['refunded'] }} refund</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ number_format($session['cash_balance']['difference'] ?? 0, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted">Belum ada sesi kasir yang terekam.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== PRODUK TERJUAL HARI INI ===== --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0 text-primary">Products Sold Today</h4>
                            </div>
                            <p class="text-muted small">
                                Rentang:
                                <span class="js-transaction-time-display" data-time="{{ $sessionRange['start_iso'] ?? null }}">{{ $sessionRange['start'] ?? '-' }}</span>
                                -
                                <span class="js-transaction-time-display" data-time="{{ $sessionRange['end_iso'] ?? null }}">{{ $sessionRange['end'] ?? '-' }}</span>
                            </p>

                            @if(isset($productSalesToday) && $productSalesToday->count())
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Produk</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($productSalesToday as $ps)
                                                <tr>
                                                    <td>{{ $ps->product_name }}</td>
                                                    <td>{{ $ps->total_quantity }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    {{ $productSalesToday->withQueryString()->links() }}
                                </div>
                            @else
                                <div class="text-muted">Belum ada produk terjual hari ini.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== GRAFIK SALES ===== --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            {{-- <h4 class="mb-0">Grafik Sales</h4> --}}
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0 text-primary">Grafik Sales (This Month)</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card border-0">
                                <div class="card-body">
                                    <canvas id="grafikSalesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="mb-2"><strong>Transaction #:</strong> <span id="odTrx"></span></div>
                        <div class="mb-2 d-flex flex-wrap">
                            <div class="mr-4"><strong>Time:</strong> <span id="odTime"></span></div>
                            <div class="mr-4"><strong>Payment:</strong> <span id="odPayment"></span></div>
                            <div class="mr-4"><strong>Status:</strong> <span id="odStatus"></span></div>
                            <div class="mr-4"><strong>Cashier:</strong> <span id="odCashier"></span></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="odItems"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            <div class="w-100 w-md-50">
                                <hr class="my-2"/>
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Total</span><span id="odTotal"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraries -->
    <script src="{{ asset('library/simpleweather/jquery.simpleWeather.min.js') }}"></script>
    <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('library/summernote/dist/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('library/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
    <script>
        const userLocale = navigator.language || navigator.userLanguage || 'en';
        if (typeof moment === 'function' && typeof moment.locale === 'function') {
            moment.locale(userLocale);
        }

        function formatIDR(n){ if(n==null) return '-'; return (n).toLocaleString('id-ID'); }
        function formatDateTime(value){
            if(!value) return '-';
            if(typeof moment !== 'function') return value;
            let parsed = moment.parseZone(value);
            if(!parsed.isValid()){ parsed = moment(value); }
            if(!parsed.isValid()) return value;
            return parsed.local().format('YYYY-MM-DD HH:mm:ss');
        }
        function renderOrderModal(data){
            document.getElementById('odTrx').textContent = data.transaction_number || data.id;
            const trxTime = data.transaction_time_iso || data.transaction_time || '';
            document.getElementById('odTime').textContent = formatDateTime(trxTime);
            document.getElementById('odPayment').textContent = data.payment_method || '-';
            document.getElementById('odStatus').textContent = (data.status||'-');
            document.getElementById('odCashier').textContent = data.cashier || '-';
            document.getElementById('odTotal').textContent = formatIDR(data.total_price||0);
            const tbody = document.getElementById('odItems');
            tbody.innerHTML='';
            (data.items||[]).forEach(it=>{
                const tr=document.createElement('tr');
                tr.innerHTML = `<td>${it.product_name||'-'}</td>
                                <td class="text-center">${formatIDR(it.price||0)}</td>
                                <td class="text-center">${it.quantity||0}</td>
                                <td class="text-right">${formatIDR(it.total_price||0)}</td>`;
                tbody.appendChild(tr);
            });
            $('#orderDetailsModal').modal('show');
        }
        document.querySelectorAll('.js-transaction-time-display').forEach(el=>{
            const raw = el.getAttribute('data-time') || el.textContent;
            el.textContent = formatDateTime(raw);
        });

        async function loadSalesSeries(params){
            const qs = new URLSearchParams(params).toString();
            const res = await fetch(`{{ route('dashboard.sales_series') }}?${qs}`, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
            if(!res.ok) throw new Error('Gagal memuat data grafik');
            return res.json();
        }

        function toDatasets(datasets, stacked){
            const palette = [
                'rgba(57,73,171,0.5)', 'rgba(255,99,132,0.5)', 'rgba(75,192,192,0.5)',
                'rgba(255,159,64,0.5)', 'rgba(153,102,255,0.5)'
            ];
            return datasets.map((ds,i)=>({
                label: ds.label,
                data: ds.data,
                backgroundColor: palette[i % palette.length],
                borderColor: palette[i % palette.length].replace('0.5','1'),
                borderWidth: 1,
                stack: stacked ? 'revenue' : undefined,
            }));
        }

        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();
        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { labels: series.labels, datasets: toDatasets(series.datasets, stacked) },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             scales: { xAxes: [{ stacked }], yAxes: [{ stacked, ticks: { beginAtZero: true } }] },
        //             tooltips: { callbacks: { label: (item)=>`Rp ${Number(item.yLabel||0).toLocaleString('id-ID')}` } }
        //         }
        //     });
        // }
        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();
        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { labels: series.labels, datasets: toDatasets(series.datasets, stacked) },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             scales: { 
        //                 xAxes: [{ stacked }], 
        //                 yAxes: [{ stacked, ticks: { beginAtZero: true } }] 
        //             },
        //             tooltips: {
        //                 mode: 'index',       // << tampilkan semua dataset di index yg sama
        //                 intersect: false,    // << tidak harus tepat di titik bar
        //                 callbacks: { 
        //                     label: (item)=>`Rp ${Number(item.yLabel||0).toLocaleString('id-ID')}` 
        //                 }
        //             }
        //         }
        //     });
        // }
        // async function renderSalesChart(params){
        //     const series = await loadSalesSeries(params);
        //     const stacked = !!params.segment_by;
        //     const ctx = document.getElementById('grafikSalesChart').getContext('2d');
        //     if(window.salesChart) window.salesChart.destroy();

        //     window.salesChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: { 
        //             labels: series.labels, 
        //             datasets: toDatasets(series.datasets, stacked) 
        //         },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: true,
        //             interaction: {
        //                 mode: 'index',      // tampilkan semua dataset di index yang sama
        //                 intersect: false
        //             },
        //             plugins: {
        //                 tooltip: {
        //                     callbacks: {
        //                         // Teks di tooltip
        //                         label: function(ctx){
        //                             // label dataset = Payment Method
        //                             let method = ctx.dataset.label || 'Metode';
        //                             let value = ctx.parsed.y || 0;
        //                             return `${method}: Rp ${Number(value).toLocaleString('id-ID')}`;
        //                         },
        //                         // Judul tooltip = label sumbu X (misal tanggal/hari)
        //                         title: function(ctx){
        //                             return ctx[0].label;
        //                         }
        //                     }
        //                 }
        //             },
        //             scales: { 
        //                 xAxes: [{ stacked }], 
        //                 yAxes: [{ stacked, ticks: { beginAtZero: true } }] 
        //             },
        //         }
        //     });
        // }

        async function renderSalesChart(params){
            const series = await loadSalesSeries(params);
            const stacked = !!params.segment_by;
            const canvasCtx = document.getElementById('grafikSalesChart').getContext('2d');

            if (window.salesChart) window.salesChart.destroy();

            window.salesChart = new Chart(canvasCtx, {
                type: 'bar',
                data: {
                    labels: series.labels,
                    datasets: toDatasets(series.datasets, stacked)
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        xAxes: [{ stacked: stacked }],
                        yAxes: [{ stacked: stacked, ticks: { beginAtZero: true } }]
                    },
                    tooltips: {
                        mode: 'index',     // tampilkan semua dataset pada index yang sama
                        intersect: false,  // tidak harus tepat di batangnya
                        callbacks: {
                            // Judul tooltip (opsional): label sumbu X, mis. tanggal/hari
                            title: function(tooltipItems, data){
                                return tooltipItems.length ? tooltipItems[0].label : '';
                            },
                            // Baris per dataset
                            label: function(tooltipItem, data){
                                const ds = data.datasets[tooltipItem.datasetIndex] || {};
                                const method = ds.label || 'Metode';
                                const val = Number(tooltipItem.yLabel || 0);
                                return `${method} : Rp ${val.toLocaleString('id-ID')}`;
                            },
                            // Footer (opsional): total semua payment method pada index tsb
                            footer: function(tooltipItems, data){
                                const idx = tooltipItems.length ? tooltipItems[0].index : -1;
                                if (idx < 0) return '';
                                const sum = data.datasets.reduce((acc, ds) => acc + (Number(ds.data[idx]) || 0), 0);
                                return `Total: Rp ${sum.toLocaleString('id-ID')}`;
                            }
                        }
                    }
                }
            });
        }


        document.addEventListener('DOMContentLoaded', async function () {
            // Hook order detail links
            document.querySelectorAll('.js-order-details').forEach(a=>{
                a.addEventListener('click', function(e){ e.preventDefault(); const url=this.getAttribute('data-url'); if(!url) return;
                    fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }}).then(r=>r.json()).then(renderOrderModal).catch(()=>alert('Gagal mengambil detail order'));
                });
            });

            // Render daily revenue (current month), grouped by payment method, completed only
            const now = new Date();
            const params = {
                period: 'harian',
                year: now.getFullYear(),
                month: now.getMonth() + 1,
                segment_by: 'payment_method',
                status: 'completed'
            };
            try { await renderSalesChart(params); } catch(e) { console.error(e); }
        });
    </script>
@endpush
