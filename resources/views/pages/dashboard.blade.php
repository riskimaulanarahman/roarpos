@extends('layouts.app')

@section('title', 'General Dashboard')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Dashboard - CASHIER POS</h1>
            </div>
            <div class="row">
                {{-- === USERS (ADMIN ONLY) === --}}
                @if(Auth::check() && Auth::user()->roles === 'admin')
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('user.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="far fa-user"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Users</h4>
                                </div>
                                <div class="card-body">
                                    {{ $users }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- === PRODUCT (semua user) === --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('product.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-bread-slice" style="color: #ffffff;"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Product</h4>
                                </div>
                                <div class="card-body">
                                    {{ $products }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- === CATEGORY (semua user) === --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('category.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success">
                                <i class="far fa-folder-open" style="color: #ffffff;"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Category</h4>
                                </div>
                                <div class="card-body">
                                    {{ $categories }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- === DISCOUNTS (ADMIN ONLY) === --}}
                @if(Auth::check() && Auth::user()->roles === 'admin' && isset($discounts))
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('discount.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Discounts</h4>
                                </div>
                                <div class="card-body">
                                    {{ $discounts }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- === ADDITIONAL CHARGES (ADMIN ONLY) === --}}
                @if(Auth::check() && Auth::user()->roles === 'admin' && isset($additional_charges))
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('additional_charge.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Additional Charges</h4>
                                </div>
                                <div class="card-body">
                                    {{ $additional_charges }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- === ORDERS (semua user) === --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('order.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="far fa-newspaper"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Orders</h4>
                                </div>
                                <div class="card-body">
                                    {{ $ordersLength }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- === REPORT (tetap seperti semula; jika perlu khusus admin, tinggal bungkus @if admin) === --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <a href="{{ route('report.index') }}">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Report</h4>
                                    <div class="card-body">
                                        3
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- === TABEL SALES HARI INI (semua user) === --}}
            <div>
                <div class="col-12">
                    <div class="card card-statistic-1">
                        <div class="card-wrap">
                            <div class="d-flex justify-content-between align-items-center m-4">
                                <h4 style="color: #3949AB; font-weight: 600">Total Sales Today</h4>
                                <h4 style="color: #3949AB; font-weight: bold">
                                    {{ number_format($totalPriceToday, 0, ',', '.') }}
                                </h4>
                            </div>
                            <div class="clearfix mb-3"></div>
                            <table class="table-striped table">
                                <tr>
                                    <th>Transaction Time</th>
                                    {{-- <th>Sub Total</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Service</th> --}}
                                    <th>Total Price</th>
                                    <th>Total Item</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Kasir</th>
                                </tr>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td>
                                            <a href="#" class="js-order-details" data-url="{{ route('order.details_json', $order->id) }}">{{ $order->transaction_time }}</a>
                                        </td>
                                        {{-- <td>{{ number_format($order->sub_total, 0, ',', '.') }}</td>
                                        <td>{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                        <td>{{ number_format($order->tax, 0, ',', '.') }}</td>
                                        <td>{{ number_format($order->service_charge, 0, ',', '.') }}</td> --}}
                                        <td>{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                        <td>{{ $order->total_item }}</td>
                                        <td>{{ $order->payment_method ?? '-' }}</td>
                                        <td>{{ ucfirst($order->status ?? '-') }}</td>
                                        <td>{{ $order->user->name }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="float-right">
                            {{ $orders->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- === BREAKDOWN BY PAYMENT METHOD (Today) === --}}
            <div>
                <div class="col-12">
                    <div class="card card-statistic-1">
                        <div class="card-wrap">
                            <div class="d-flex justify-content-between align-items-center m-4">
                                <h4 style="color: #3949AB; font-weight: 600">Breakdown by Payment Method (Today)</h4>
                            </div>
                            <div class="clearfix mb-3"></div>
                            @if(isset($paymentBreakdownToday) && $paymentBreakdownToday->count())
                                <table class="table-striped table">
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Revenue</th>
                                    </tr>
                                    @foreach ($paymentBreakdownToday as $pb)
                                        <tr>
                                            <td>{{ $pb->payment_method ?? 'Unknown' }}</td>
                                            <td>{{ number_format($pb->total_revenue, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <div class="m-4 text-muted">Belum ada transaksi hari ini.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- === PRODUK TERJUAL HARI INI (semua user) === --}}
            <div>
                <div class="col-12">
                    <div class="card card-statistic-1">
                        <div class="card-wrap">
                            <div class="d-flex justify-content-between align-items-center m-4">
                                <h4 style="color: #3949AB; font-weight: 600">Produk Terjual Hari Ini</h4>
                            </div>
                            <div class="clearfix mb-3"></div>
                            @if(isset($productSalesToday) && $productSalesToday->count())
                                <table class="table-striped table">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Jumlah</th>
                                    </tr>
                                    @foreach ($productSalesToday as $ps)
                                        <tr>
                                            <td>{{ $ps->product_name }}</td>
                                            <td>{{ $ps->total_quantity }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <div class="m-4 text-muted">Belum ada produk terjual hari ini.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- === GRAFIK SALES (semua user) === --}}
            <div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Grafik Sales</h4>
                            </div>
                            <div class="card-body">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="grafikSalesChart"></canvas>
                                    </div>
                                </div>

                            </div> {{-- card-body --}}
                        </div> {{-- card --}}
                    </div> {{-- col-12 --}}
                </div> {{-- row --}}
            </div>
        </section>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
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
                                <thead>
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
                            <div class="w-50">
                                <hr/>
                                <div class="d-flex justify-content-between font-weight-bold"><span>Total</span><span id="odTotal"></span></div>
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
    <!-- JS Libraies -->
    <script src="{{ asset('library/simpleweather/jquery.simpleWeather.min.js') }}"></script>
    <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('library/summernote/dist/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('library/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
    <script>
        function formatIDR(n){ if(n==null) return '-'; return (n).toLocaleString('id-ID'); }
        function renderOrderModal(data){
            document.getElementById('odTrx').textContent = data.transaction_number || data.id;
            document.getElementById('odTime').textContent = data.transaction_time || '';
            document.getElementById('odPayment').textContent = data.payment_method || '-';
            document.getElementById('odStatus').textContent = (data.status||'-');
            document.getElementById('odCashier').textContent = data.cashier || '-';
            document.getElementById('odTotal').textContent = formatIDR(data.total_price||0);
            const tbody = document.getElementById('odItems');
            tbody.innerHTML='';
            (data.items||[]).forEach(it=>{
                const tr=document.createElement('tr');
                tr.innerHTML = `<td>${it.product_name||'-'}</td>
                                <td class=\"text-center\">${formatIDR(it.price||0)}</td>
                                <td class=\"text-center\">${it.quantity||0}</td>
                                <td class=\"text-right\">${formatIDR(it.total_price||0)}</td>`;
                tbody.appendChild(tr);
            });
            $('#orderDetailsModal').modal('show');
        }

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

        async function renderSalesChart(params){
            const series = await loadSalesSeries(params);
            const stacked = !!params.segment_by;
            const ctx = document.getElementById('grafikSalesChart').getContext('2d');
            if(window.salesChart) window.salesChart.destroy();
            window.salesChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: series.labels, datasets: toDatasets(series.datasets, stacked) },
                options: {
                    responsive: true,
                    scales: { x: { stacked: stacked }, y: { stacked: stacked, beginAtZero: true } },
                    plugins: { tooltip: { callbacks: { label: (ctx)=>`Rp ${Number(ctx.parsed.y||0).toLocaleString('id-ID')}` } } }
                }
            });
        }

        // No filters on dashboard: fixed params handled below

        document.addEventListener('DOMContentLoaded', async function () {
            // Hook order detail links
            document.querySelectorAll('.js-order-details').forEach(a=>{
                a.addEventListener('click', function(e){ e.preventDefault(); const url=this.getAttribute('data-url'); if(!url) return;
                    fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }}).then(r=>r.json()).then(renderOrderModal).catch(()=>alert('Gagal mengambil detail order'));
                });
            });

            // Render daily revenue in current month grouped by payment method + status
            const now = new Date();
            const params = {
                period: 'harian',
                year: now.getFullYear(),
                month: now.getMonth() + 1,
                segment_by: 'method_status'
            };
            try { await renderSalesChart(params); } catch(e) { console.error(e); }
        });
    </script>
@endpush
