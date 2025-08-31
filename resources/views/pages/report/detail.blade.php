@extends('layouts.app')

@section('title', 'Report Order Detail')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
@endpush

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Report Order - Detail</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="#">Reports</a></div>
                <div class="breadcrumb-item">Order Detail</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Filter</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('report.detail') }}" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Dari Tanggal</label>
                                    <input type="date" name="date_from" value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}" class="form-control">
                                </div>
                                @error('date_from')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ke Tanggal</label>
                                    <input type="date" name="date_to" value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}" class="form-control">
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
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($chart))
                <div class="card">
                    <div class="card-header"><h4>Revenue Trend</h4></div>
                    <div class="card-body">
                        <canvas id="detailTrendChart" height="80"></canvas>
                    </div>
                </div>
            @endif

            @if(isset($items) && $items->count() > 0)
                <div class="card">
                    <div class="card-header"><h4>Table - Order Items</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="detailTable" class="table table-striped table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction No</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td>{{ $item->created_at }}</td>
                                            <td>{{ optional($item->order)->transaction_number ?? '-' }}</td>
                                            <td>{{ optional($item->product)->name ?? '-' }}</td>
                                            <td>{{ optional(optional($item->product)->category)->name ?? '-' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ number_format($item->total_price, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="float-right">
                            {{ $items->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @elseif(isset($date_from, $date_to))
                <div class="alert alert-warning">No data found for the selected date range.</div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="{{ asset('library/datatables/media/js/jquery.dataTables.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function parseCurrency(str){ if(!str) return 0; return parseInt(String(str).replace(/[^0-9\-]/g,'')) || 0; }
        let detailChart;
        const trendData = @json($chart ?? null);
        if (trendData) {
            const ctx = document.getElementById('detailTrendChart').getContext('2d');
            detailChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{ label: 'Revenue', data: trendData.revenue, borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)', tension: 0.3 }]
                }
            });
        }
        $(function(){
            const dt = $('#detailTable').DataTable({ paging:true, info:true });
            function updateChart(){ if(!detailChart) return; const revByDate={};
                dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const date=$(tds.get(0)).text().trim().substring(0,10); const rev=parseCurrency($(tds.get(5)).text()); revByDate[date]=(revByDate[date]||0)+rev; });
                const labels = Object.keys(revByDate).sort();
                const revenue = labels.map(l=>revByDate[l]);
                detailChart.data.labels = labels; detailChart.data.datasets[0].data = revenue; detailChart.update('none');
            }
            dt.on('draw', updateChart);
        });
    </script>
@endpush

