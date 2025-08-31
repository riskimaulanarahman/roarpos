@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Report</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Report</a></div>
                    <div class="breadcrumb-item">Report Data</div>
                </div>
            </div>
            <div class="section-body">


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Semua Order</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('filter.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Dari Tanggal</label>
                                                <input type="date" name="date_from"
                                                    value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                    class="form-control datepicker">
                                            </div>
                                            @error('date_from')
                                                <div class="alert alert-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Ke Tanggal</label>
                                                <input type="date" name="date_to"
                                                    value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}"
                                                    class="form-control datepicker">
                                            </div>
                                            @error('date_to')
                                                <div class="alert alert-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($statuses ?? []) as $s)
                                                        <option value="{{ $s }}" {{ ($status ?? request('status')) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Metode Bayar</label>
                                                <select name="payment_method" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($paymentMethods ?? []) as $pm)
                                                        <option value="{{ $pm }}" {{ ($paymentMethod ?? request('payment_method')) == $pm ? 'selected' : '' }}>{{ ucfirst($pm) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select name="category_id" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($categories ?? []) as $cat)
                                                        <option value="{{ $cat->id }}" {{ ($categoryId ?? request('category_id')) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Produk</label>
                                                <select name="product_id" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($products ?? []) as $prod)
                                                        <option value="{{ $prod->id }}" {{ ($productId ?? request('product_id')) == $prod->id ? 'selected' : '' }}>{{ $prod->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-lg btn-block"
                                                    tabindex="4">
                                                    Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                @if (!empty($summary))
                                    <div class="row mt-4">
                                        <div class="col-md-3 mb-3">
                                            <div class="card card-statistic-1">
                                                <div class="card-header"><h4>Total Revenue</h4></div>
                                                <div class="card-body">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card card-statistic-1">
                                                <div class="card-header"><h4>Orders</h4></div>
                                                <div class="card-body">{{ $summary['orders_count'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card card-statistic-1">
                                                <div class="card-header"><h4>Items Sold</h4></div>
                                                <div class="card-body">{{ $summary['total_items_sold'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card card-statistic-1">
                                                <div class="card-header"><h4>AOV</h4></div>
                                                <div class="card-body">Rp {{ number_format($summary['aov'], 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <canvas id="ordersLineChart" height="80"></canvas>
                                        </div>
                                    </div>
                                @endif

                                @if ($orders ?? '')
                                    @if (count($orders) === 0)
                                        <div class="alert alert-warning" role="alert">
                                            No Order in this range date
                                        </div>
                                    @endif
                                    @if (count($orders) > 0)
                                        <div class="table-responsive">
                                            <table id="ordersTable" class="table table-striped table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Transaction Time</th>
                                                    <th>Sub Total</th>
                                                    <th>Discount</th>
                                                    <th>Tax</th>
                                                    <th>Service</th>
                                                    <th>Total Price</th>
                                                    <th>Total Item</th>
                                                    <th>Kasir</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse ($orders as $order)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('order.show', $order->id) }}">{{ $order->transaction_time }}</a>
                                                        </td>
                                                        <td>
                                                            {{ number_format($order->sub_total, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{ number_format($order->discount_amount, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{ number_format($order->tax, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{ number_format($order->service_charge, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{ number_format($order->total_price, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{ $order->total_item }}
                                                        </td>
                                                        <td>
                                                            {{ optional($order->user)->name ?? ($order->cashier_name ?? '-') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>"No Order in this Date!"</td>
                                                    </tr>
                                                @endforelse

                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="float-right">
                                            {{ $orders->withQueryString()->links() }}
                                        </div>

                                        <form action="{{ route('report.download') }}" method="GET">

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <input type="date" hidden name="date_from" value="{{ old('date_from') ?? request()->query('date_from') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="date" hidden name="date_to" value="{{ old('date_to') ?? request()->query('date_to') }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" hidden name="status" value="{{ request()->query('status') }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" hidden name="payment_method" value="{{ request()->query('payment_method') }}">
                                                </div>
                                                <div class="col-md-1">
                                                    <input type="text" hidden name="category_id" value="{{ request()->query('category_id') }}">
                                                </div>
                                                <div class="col-md-1">
                                                    <input type="text" hidden name="product_id" value="{{ request()->query('product_id') }}">
                                                </div>
                                            </div>

                                            <div class="row float-right w-100">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-primary btn-lg btn-block"
                                                            tabindex="4">
                                                            Download
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    {{-- <script src="assets/js/page/forms-advanced-forms.js"></script> --}}
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
    <script src="{{ asset('library/datatables/media/js/jquery.dataTables.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function parseCurrency(str){ if(!str) return 0; return parseInt(String(str).replace(/[^0-9\-]/g,'')) || 0; }
        function toDateKey(str){ if(!str) return ''; const s= String(str).trim(); if(s.length>=10) return s.substring(0,10); return s; }

        let ordersChart;
        const chartPayload = @json($chart ?? null);
        if (chartPayload) {
            const ctx = document.getElementById('ordersLineChart').getContext('2d');
            ordersChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartPayload.labels,
                    datasets: [
                        { label: 'Revenue', data: chartPayload.revenue, borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)', yAxisID: 'y1', tension: 0.3 },
                        { label: 'Orders', data: chartPayload.orders, borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)', yAxisID: 'y2', type: 'bar' }
                    ]
                },
                options: { responsive: true, scales: { y1: { type:'linear', position:'left', title:{ display:true, text:'Revenue'} }, y2:{ type:'linear', position:'right', grid:{ drawOnChartArea:false}, title:{ display:true, text:'Orders'} } } }
            });
        }

        $(function(){
            const table = $('#ordersTable').DataTable({ paging: true, info: true });
            function recomputeFromTable(){
                if(!ordersChart) return;
                const revByDate = {};
                const countByDate = {};
                table.rows({ search:'applied' }).every(function(){
                    const $row = $(this.node());
                    const tds = $row.find('td');
                    const dateText = $(tds.get(0)).text();
                    const dateKey = toDateKey(dateText);
                    const totalPrice = parseCurrency($(tds.get(5)).text());
                    revByDate[dateKey] = (revByDate[dateKey] || 0) + totalPrice;
                    countByDate[dateKey] = (countByDate[dateKey] || 0) + 1;
                });
                const labels = Object.keys(revByDate).sort();
                const revenue = labels.map(l => revByDate[l]);
                const orders = labels.map(l => countByDate[l] || 0);
                ordersChart.data.labels = labels;
                ordersChart.data.datasets[0].data = revenue;
                ordersChart.data.datasets[1].data = orders;
                ordersChart.update('none');
            }
            table.on('draw', recomputeFromTable);
        });
    </script>
@endpush
