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
                                    <label>Periode</label>
                                    <select name="period" class="form-control" id="periodSelectDetail">
                                        <option value="">Custom</option>
                                        <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                        <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                        <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                        <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                    </select>
                                </div>
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg mr-2">Filter</button>
                                <button type="button" id="btnResetDetail" class="btn btn-light btn-lg">Reset</button>
                            </div>
                        </div>
                    </form>

                    <div class="mb-3">
                        @php($chips = [])
                        @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                        @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                        @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                        @if(request('status')) @php($chips[] = 'Status: '.ucfirst(request('status'))) @endif
                        @if(request('payment_method')) @php($chips[] = 'Metode: '.ucfirst(request('payment_method'))) @endif
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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div></div>
                            <button type="button" id="btnExportDetail" class="btn btn-outline-primary">Export View (CSV)</button>
                        </div>
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
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Total</th>
                                        <th id="ftQtyDetail"></th>
                                        <th id="ftRevDetail"></th>
                                    </tr>
                                </tfoot>
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
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
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
        document.getElementById('periodSelectDetail')?.addEventListener('change', function(){
            const r=computeRange(this.value); if(!r) return; const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df) df.value=r.from; if(dt) dt.value=r.to;
        });

        function savePrefs(prefix){ const f=document.querySelector('form'); const names=['date_from','date_to','period','status','payment_method','category_id','product_id']; const data={}; names.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }); localStorage.setItem(prefix, JSON.stringify(data)); }
        function loadPrefs(prefix){ const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw); Object.entries(data).forEach(([k,v])=>{ const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v; }); }
        function exportDataTableCSV(table, filename){ const rows=[]; const headers=[]; $(table.table().header()).find('th').each(function(){ headers.push($(this).text().trim()); }); rows.push(headers.join(',')); table.rows({search:'applied'}).every(function(){ const cols=[]; $(this.node()).find('td').each(function(){ cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); }); rows.push(cols.join(',')); }); const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); }

        $(function(){
            loadPrefs('report_detail_filters');
            const dt = $('#detailTable').DataTable({ paging:true, info:true });
            function recompute(){ if(detailChart){ const revByDate={}; dt.rows({ search:'applied' }).every(function(){ const $r=$(this.node()); const tds=$r.find('td'); const date=$(tds.get(0)).text().trim().substring(0,10); const rev=parseCurrency($(tds.get(5)).text()); revByDate[date]=(revByDate[date]||0)+rev; }); const labels = Object.keys(revByDate).sort(); const revenue = labels.map(l=>revByDate[l]); detailChart.data.labels = labels; detailChart.data.datasets[0].data = revenue; detailChart.update('none'); }
                let tq=0,tr=0; dt.rows({search:'applied'}).every(function(){ const tds=$(this.node()).find('td'); tq+=parseInt($(tds.get(4)).text())||0; tr+=parseCurrency($(tds.get(5)).text()); }); $('#ftQtyDetail').text(tq.toLocaleString('id-ID')); $('#ftRevDetail').text(tr.toLocaleString('id-ID')); }
            dt.on('draw', recompute); recompute();
            $('#btnExportDetail').on('click', ()=>exportDataTableCSV(dt,'report_detail_view.csv'));
            $('#btnResetDetail').on('click', function(){ const f=document.querySelector('form'); f.querySelector('[name="period"]').value=''; ['status','payment_method','category_id','product_id'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) el.value=''; }); const df=f.querySelector('[name="date_from"]'); const dtm=f.querySelector('[name="date_to"]'); if(df) df.value=''; if(dtm) dtm.value=''; });
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('report_detail_filters'));
        });
    </script>
@endpush

