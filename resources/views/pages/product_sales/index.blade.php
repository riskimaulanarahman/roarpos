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
                                                <label>Periode</label>
                                                <select name="period" class="form-control" id="periodSelectPS">
                                                    <option value="">Custom</option>
                                                    <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                    <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                                </select>
                                            </div>
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
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary btn-lg mr-2" tabindex="4">Filter</button>
                                            <button type="button" id="btnResetPS" class="btn btn-light btn-lg">Reset</button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        @php($chips = [])
                                        @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                                        @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                                        @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                                        @if(request('category_id'))
                                            @php($c = ($categories ?? collect())->firstWhere('id', request('category_id')))
                                            @if($c) @php($chips[] = 'Kategori: '.$c->name) @endif
                                        @endif
                                        @if(request('product_id'))
                                            @php($p = ($products ?? collect())->firstWhere('id', request('product_id')))
                                            @if($p) @php($chips[] = 'Produk: '.$p->name) @endif
                                        @endif
                                        @if(count($chips))
                                            <div>
                                                @foreach($chips as $chip)
                                                    <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="card">
                                        <div class="card-body">
                                            @if ($totalProductSold ?? '')
                                                <div class="mb-4">
                                                    <canvas id="productSalesChart" height="100"></canvas>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div></div>
                                                    <button type="button" id="btnExportPS" class="btn btn-outline-primary">Export View (CSV)</button>
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
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="2" class="text-right">Total</th>
                                                                <th id="ftQtyPS"></th>
                                                                <th id="ftRevPS"></th>
                                                            </tr>
                                                        </tfoot>
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
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
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
        function computeRange(period){
            const pad=n=>String(n).padStart(2,'0');
            const toStr=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const now=new Date(); let s,e;
            if(period==='harian'){ s=new Date(now.getFullYear(),now.getMonth(),now.getDate()); e=new Date(s); }
            else if(period==='mingguan'){ const day=(now.getDay()+6)%7; s=new Date(now.getFullYear(),now.getMonth(),now.getDate()-day); e=new Date(s); e.setDate(s.getDate()+6); }
            else if(period==='bulanan'){ s=new Date(now.getFullYear(),now.getMonth(),1); e=new Date(now.getFullYear(),now.getMonth()+1,0); }
            else if(period==='tahunan'){ s=new Date(now.getFullYear(),0,1); e=new Date(now.getFullYear(),11,31); }
            else return null; return {from:toStr(s), to:toStr(e)};
        }
        document.getElementById('periodSelectPS')?.addEventListener('change', function(){
            const r=computeRange(this.value); if(!r) return; const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df) df.value=r.from; if(dt) dt.value=r.to;
        });

        function savePrefs(prefix){ const f=document.querySelector('form'); const names=['date_from','date_to','period','category_id','product_id']; const data={}; names.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }); localStorage.setItem(prefix, JSON.stringify(data)); }
        function loadPrefs(prefix){ const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw); Object.entries(data).forEach(([k,v])=>{ const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v; }); }
        function exportDataTableCSV(table, filename){ const rows=[]; const headers=[]; $(table.table().header()).find('th').each(function(){ headers.push($(this).text().trim()); }); rows.push(headers.join(',')); table.rows({search:'applied'}).every(function(){ const cols=[]; $(this.node()).find('td').each(function(){ cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); }); rows.push(cols.join(',')); }); const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); }

        $(function(){
            loadPrefs('product_sales_filters');
            const dt = $('#productSalesTable').DataTable({ paging:true, info:true });
            function updateAll(){ if(!psChart) return; const qtyBy={}, revBy={};
                dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const label=$(tds.get(1)).text().trim(); const qty=parseInt($(tds.get(2)).text())||0; const rev=parseCurrency($(tds.get(3)).text()); qtyBy[label]=(qtyBy[label]||0)+qty; revBy[label]=(revBy[label]||0)+rev; });
                const labels = Object.keys(qtyBy);
                psChart.data.labels = labels;
                psChart.data.datasets[0].data = labels.map(l=>qtyBy[l]);
                psChart.data.datasets[1].data = labels.map(l=>revBy[l]);
                psChart.update('none');
                let tq=0,tr=0; dt.rows({search:'applied'}).every(function(){ const tds=$(this.node()).find('td'); tq+=parseInt($(tds.get(2)).text())||0; tr+=parseCurrency($(tds.get(3)).text()); }); $('#ftQtyPS').text(tq.toLocaleString('id-ID')); $('#ftRevPS').text(tr.toLocaleString('id-ID'));
            }
            dt.on('draw', updateAll); updateAll();
            $('#btnExportPS').on('click', ()=>exportDataTableCSV(dt,'product_sales_view.csv'));
            $('#btnResetPS').on('click', function(){ const f=document.querySelector('form'); f.querySelector('[name="period"]').value=''; ['category_id','product_id'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) el.value=''; }); const df=f.querySelector('[name="date_from"]'); const dtm=f.querySelector('[name="date_to"]'); if(df) df.value=''; if(dtm) dtm.value=''; });
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('product_sales_filters'));
        });
    </script>
@endpush
