@extends('layouts.app')

@section('title', 'Report Order by Category')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
@endpush

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Report Order - By Category</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="#">Reports</a></div>
                <div class="breadcrumb-item">Order by Category</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Filter</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('report.byCategory') }}" method="GET">
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

            @if(isset($categorySales) && $categorySales->count() > 0)
                <div class="card">
                    <div class="card-header"><h4>Category Performance</h4></div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="100"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h4>Table - Category Summary</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="categoryTable" class="table table-striped table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Category</th>
                                        <th>Total Quantity</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categorySales as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->category_name }}</td>
                                            <td>{{ $row->total_quantity }}</td>
                                            <td>{{ number_format($row->total_price, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
        let categoryChart;
        const catData = @json($chart ?? null);
        if (catData) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: catData.labels,
                    datasets: [
                        { label: 'Quantity', data: catData.quantity, backgroundColor: 'rgba(75,192,192,0.5)', borderColor: 'rgba(75,192,192,1)', yAxisID: 'y1' },
                        { label: 'Revenue', data: catData.revenue, backgroundColor: 'rgba(153,102,255,0.5)', borderColor: 'rgba(153,102,255,1)', yAxisID: 'y2' }
                    ]
                },
                options: { responsive: true, scales: { y1:{ type:'linear', position:'left'}, y2:{ type:'linear', position:'right', grid:{ drawOnChartArea:false}} } }
            });
        }

        $(function(){
            const dt = $('#categoryTable').DataTable({ paging:true, info:true });
            function updateChart(){ if(!categoryChart) return; const qtyByLabel={}, revByLabel={};
                dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const label=$(tds.get(1)).text().trim(); const qty=parseInt($(tds.get(2)).text())||0; const rev=parseCurrency($(tds.get(3)).text()); qtyByLabel[label]=(qtyByLabel[label]||0)+qty; revByLabel[label]=(revByLabel[label]||0)+rev; });
                const labels = Object.keys(qtyByLabel);
                categoryChart.data.labels = labels;
                categoryChart.data.datasets[0].data = labels.map(l=>qtyByLabel[l]);
                categoryChart.data.datasets[1].data = labels.map(l=>revByLabel[l]);
                categoryChart.update('none');
            }
            dt.on('draw', updateChart);
        });
    </script>
@endpush

