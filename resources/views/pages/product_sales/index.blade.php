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
                <h1>Product Sales</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Product Sales</a></div>
                    <div class="breadcrumb-item">Product Sales</div>
                </div>
            </div>
            <div class="section-body">


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Product Sales</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('productSales.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Dari Tanggal</label>
                                                <input type="date" name="date_from"
                                                    value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                    class="form-control datepicker">
                                            </div>
                                            @error('date_from')<div class="alert alert-danger">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Ke Tanggal</label>
                                                <input type="date" name="date_to"
                                                    value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}"
                                                    class="form-control datepicker">
                                            </div>
                                            @error('date_to')<div class="alert alert-danger">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
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

                                    <div class="card">
                                        <div class="card-body">
                                            @if ($totalProductSold ?? '')
                                                <div class="mb-4">
                                                    <canvas id="productSalesChart" height="100"></canvas>
                                                </div>

                                                <div class="table-responsive">
                                                    <table id="productSalesTable" class="table table-striped table-bordered text-center">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Product</th>
                                                                <th>Total Quantity</th>
                                                                <th>Total Price</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($totalProductSold as $productSold)
                                                                <tr>
                                                                    <td>{{ $loop->iteration }}</td>
                                                                    <td>{{ $productSold->product_name }}</td>
                                                                    <td>{{ $productSold->total_quantity }}</td>
                                                                    <td>{{ number_format($productSold->total_price, 0, ',', '.') }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <input type="date" hidden name="date_from"
                                                                value="{{ old('date_from') ?? ($date_from ?? request()->query('date_from')) }}"
                                                                class="form-control datepicker">
                                                        </div>
                                                        @error('date_from')
                                                            <div class="alert alert-danger">
                                                                {{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <input type="date" hidden name="date_to"
                                                                value="{{ old('date_to') ?? ($date_to ?? request()->query('date_to')) }}"
                                                                class="form-control datepicker">
                                                        </div>
                                                        @error('date_to')
                                                            <div class="alert alert-danger">
                                                                {{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>
                                                </div>

                                            </form>

                                            @endif
                                        </div>
                                    </div>
                                    <div class="row float-right w-100">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <a href="{{ route('productSales.download', ['date_from' => request()->query('date_from'), 'date_to' => request()->query('date_to')]) }}"
                                                   class="btn btn-primary btn-lg btn-block">
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>


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
        let psChart;
        const psData = @json($chart ?? null);
        if (psData) {
            const pctx = document.getElementById('productSalesChart').getContext('2d');
            psChart = new Chart(pctx, {
                type: 'bar',
                data: {
                    labels: psData.labels,
                    datasets: [
                        { label: 'Quantity', data: psData.quantity, backgroundColor: 'rgba(54,162,235,0.5)', borderColor: 'rgba(54,162,235,1)', yAxisID: 'y1' },
                        { label: 'Revenue', data: psData.revenue, backgroundColor: 'rgba(255,159,64,0.5)', borderColor: 'rgba(255,159,64,1)', yAxisID: 'y2' }
                    ]
                },
                options: { responsive: true, scales: { y1:{ type:'linear', position:'left', title:{ display:true, text:'Qty'} }, y2:{ type:'linear', position:'right', grid:{ drawOnChartArea:false}, title:{ display:true, text:'Revenue'} } } }
            });
        }
        $(function(){
            const dt = $('#productSalesTable').DataTable({ paging:true, info:true });
            function updateChart(){ if(!psChart) return; const qtyBy={}, revBy={};
                dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const label=$(tds.get(1)).text().trim(); const qty=parseInt($(tds.get(2)).text())||0; const rev=parseCurrency($(tds.get(3)).text()); qtyBy[label]=(qtyBy[label]||0)+qty; revBy[label]=(revBy[label]||0)+rev; });
                const labels = Object.keys(qtyBy);
                psChart.data.labels = labels;
                psChart.data.datasets[0].data = labels.map(l=>qtyBy[l]);
                psChart.data.datasets[1].data = labels.map(l=>revBy[l]);
                psChart.update('none');
            }
            dt.on('draw', updateChart);
        });
    </script>
@endpush
