@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/datatables/media/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
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
                                            <label>Periode <span class="text-muted" title="Pilih periode terlebih dahulu, lalu filter lainnya akan muncul">?</span></label>
                                                <select name="period" class="form-control" id="periodSelect">
                                                    <option value="harian" {{ request('period')=='harian' ? 'selected' : '' }}>Harian</option>
                                                    <option value="mingguan" {{ request('period')=='mingguan' ? 'selected' : '' }}>Mingguan</option>
                                                    <option value="bulanan" {{ request('period')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                                                    <option value="tahunan" {{ request('period')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="dateRangeContainer" class="col-md-5">
                                            <div class="form-row">
                                                <div class="col-md-6">
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
                                                <div class="col-md-6">
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
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="yearCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Tahun</label>
                                                @php($currentYear = (int) (old('year') ?? ($year ?? request('year') ?? now()->year)))
                                                <select name="year" id="yearSelect" class="form-control">
                                                    @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                                        <option value="{{ $y }}" {{ $currentYear==$y ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="monthCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Bulan</label>
                                                @php($currentMonth = (int) (old('month') ?? ($month ?? request('month') ?? now()->month)))
                                                @php($monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'])
                                                <select name="month" id="monthSelect" class="form-control">
                                                    @for($m=1;$m<=12;$m++)
                                                        <option value="{{ $m }}" {{ $currentMonth==$m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="weekCol" style="display:none;">
                                            <div class="form-group">
                                                <label>Opsi Mingguan</label>
                                                <select id="weekOptionSelect" class="form-control">
                                                    <option value="">Pilih...</option>
                                                    <optgroup label="Minggu di Bulan">
                                                        <option value="w1" {{ (request('week_in_month')=='w1')?'selected':'' }}>Minggu ke-1</option>
                                                        <option value="w2" {{ (request('week_in_month')=='w2')?'selected':'' }}>Minggu ke-2</option>
                                                        <option value="w3" {{ (request('week_in_month')=='w3')?'selected':'' }}>Minggu ke-3</option>
                                                        <option value="w4" {{ (request('week_in_month')=='w4')?'selected':'' }}>Minggu ke-4</option>
                                                        <option value="w5" {{ (request('week_in_month')=='w5')?'selected':'' }}>Minggu ke-5</option>
                                                    </optgroup>
                                                    <optgroup label="Hari Terakhir">
                                                        <option value="last_7" {{ (request('last_days')=='7')?'selected':'' }}>7 hari terakhir</option>
                                                        <option value="last_14" {{ (request('last_days')=='14')?'selected':'' }}>14 hari terakhir</option>
                                                        <option value="last_21" {{ (request('last_days')=='21')?'selected':'' }}>21 hari terakhir</option>
                                                        <option value="last_28" {{ (request('last_days')=='28')?'selected':'' }}>28 hari terakhir</option>
                                                    </optgroup>
                                                </select>
                                                <input type="hidden" name="week_in_month" id="weekInMonthInput" value="{{ request('week_in_month') }}">
                                                <input type="hidden" name="last_days" id="lastDaysInput" value="{{ request('last_days') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="">Semua</option>
                                                    @foreach(($statuses ?? []) as $s)
                                                        <option value="{{ $s }}" {{ ($status ?? request('status','completed')) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Metode Bayar</label>
                                                @php($selPays = collect(($paymentMethod ?? (array) request('payment_method', [])))->map(fn($v)=> (string)$v)->all())
                                                <select name="payment_method[]" class="form-control select2" multiple data-placeholder="Pilih metode bayar">
                                                    @foreach(($paymentMethods ?? []) as $pm)
                                                        <option value="{{ $pm }}" {{ in_array((string)$pm, $selPays, true) ? 'selected' : '' }}>{{ ucfirst($pm) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <!-- Produk filter removed -->

                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group d-flex">
                                                <button type="submit" class="btn btn-primary btn-lg mr-2" tabindex="4">Filter</button>
                                                <button type="button" id="btnResetFilters" class="btn btn-light btn-lg">Reset</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="mb-3">
                                    @php($chips = [])
                                    @if(request('period')) @php($chips[] = 'Periode: '.ucfirst(request('period'))) @endif
                                    @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
                                    @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
                                    @php($chips[] = 'Status: '.ucfirst(request('status','completed')))
                                    @php($pmSel = (array) request('payment_method', ($paymentMethod ?? [])))
                                    @if(!empty($pmSel)) @php($chips[] = 'Metode: '.implode(', ', array_map('ucfirst',$pmSel))) @endif
                                    @if(request('year')) @php($chips[] = 'Tahun: '.request('year')) @endif
                                    @if(request('month')) @php($chips[] = 'Bulan: '.($monthNames[(int)request('month')] ?? request('month'))) @endif
                                    @if(request('week_in_month')) @php($chips[] = 'Minggu: '.strtoupper(request('week_in_month'))) @endif
                                    @if(request('last_days')) @php($chips[] = 'Terakhir: '.request('last_days').' hari') @endif
                                    
                                    @if(count($chips))
                                        <div>
                                            @foreach($chips as $chip)
                                                <span class="badge badge-primary mr-2">{{ $chip }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

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
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div></div>
                                            <button type="button" class="btn btn-outline-primary" id="btnExportOrders">Export View (CSV)</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="ordersTable" class="table table-striped table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Transaction Time</th>
                                                    <th>Total Price</th>
                                                    <th>Total Item</th>
                                                    <th>Kasir</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse ($orders as $order)
                                                    <tr>
                                                        <td data-date="{{ optional($order->created_at)->format('Y-m-d') }}">
                                                            @php($trxIso = optional($order->transaction_time)->toIso8601String())
                                                            <a href="#"
                                                               class="js-order-details js-transaction-time-display"
                                                               data-url="{{ route('order.details_json', $order->id) }}"
                                                               data-time="{{ $trxIso }}">
                                                                {{ optional($order->transaction_time)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                                            </a>
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
                                                <tfoot>
                                                    <tr>
                                                        <th>Total</th>
                                                        <th id="ftTotalPrice"></th>
                                                        <th id="ftTotalItem"></th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
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
                                                    <input type="text" hidden name="status" value="{{ request()->query('status','completed') }}">
                                                </div>
                                                <div class="col-md-2">
                                                    @php($dlPm = (array) request()->query('payment_method', []))
                                                    @foreach($dlPm as $pmv)
                                                        <input type="text" hidden name="payment_method[]" value="{{ $pmv }}">
                                                    @endforeach
                                                </div>
                                                
                                                <!-- product filter removed -->
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
    <script src="{{ asset('library/select2/dist/js/select2.min.js') }}"></script>

    <!-- Page Specific JS File -->
    {{-- <script src="assets/js/page/forms-advanced-forms.js"></script> --}}
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
    <script src="{{ asset('library/datatables/media/js/jquery.dataTables.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            return parsed.local().format('LLL');
        }
        function renderOrderModal(data){
            const wrap = document.getElementById('orderDetailsModal'); if(!wrap) return;
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

        function computeRangeAdvanced(period, year, month, weekOpt){
            const pad=n=>String(n).padStart(2,'0');
            const toStr=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const clampMonth=(y,m)=>{ const s=new Date(y,m-1,1); const e=new Date(y,m,0); return {s,e}; };

            if(!period) return null;
            if(period==='harian'){
                // default to full selected month
                const {s,e} = clampMonth(year, month);
                return { from: toStr(s), to: toStr(e) };
            }
            if(period==='mingguan'){
                if(weekOpt && weekOpt.startsWith('last_')){
                    const days = parseInt(weekOpt.split('_')[1]);
                    const now=new Date();
                    const to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const from = new Date(to); from.setDate(to.getDate() - (days-1));
                    return { from: toStr(from), to: toStr(to), lastDays: days };
                }
                // week within month: w1..w5
                const idx = weekOpt && weekOpt.startsWith('w') ? parseInt(weekOpt.slice(1)) : 1;
                const firstDay = new Date(year, month-1, 1);
                // first Monday of the month
                const firstMonday = new Date(firstDay);
                const day = firstMonday.getDay(); // 0 Sun .. 6 Sat
                const diffToMon = (day===0?1: (day===1?0: (8-day)));
                firstMonday.setDate(1 + diffToMon);
                const start = new Date(firstMonday); start.setDate(firstMonday.getDate() + 7*(idx-1));
                const end = new Date(start); end.setDate(start.getDate()+6);
                const {s:ms, e:me} = clampMonth(year, month);
                const s = start < ms ? ms : start;
                const e = end > me ? me : end;
                return { from: toStr(s), to: toStr(e) };
            }
            if(period==='bulanan'){
                const {s,e} = clampMonth(year, month); return { from: toStr(s), to: toStr(e) };
            }
            if(period==='tahunan'){
                const s = new Date(year,0,1); const e = new Date(year,11,31); return { from: toStr(s), to: toStr(e) };
            }
            return null;
        }

        function updateVisibility(){
            const period = document.getElementById('periodSelect')?.value || '';
            const yearCol = document.getElementById('yearCol');
            const monthCol = document.getElementById('monthCol');
            const weekCol = document.getElementById('weekCol');
            const dateRange = document.getElementById('dateRangeContainer');
            const toggleOthers = (show)=>{
                ['payment_method[]','category_id[]','user_id'].forEach(n=>{
                    const el=document.querySelector(`[name="${n}"]`);
                    if(!el) return; const col=el.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-12');
                    if(col) col.style.display = show ? '' : 'none';
                });
            };
            if(!period){ // belum pilih periode => sembunyikan semua selain periode
                if(yearCol) yearCol.style.display='none';
                if(monthCol) monthCol.style.display='none';
                if(weekCol) weekCol.style.display='none';
                if(dateRange) dateRange.style.display='none';
                toggleOthers(false);
                return;
            }
            toggleOthers(true);
            if(yearCol) yearCol.style.display='block';
            if(monthCol) monthCol.style.display = (period==='tahunan') ? 'none' : 'block';
            if(weekCol) weekCol.style.display = (period==='mingguan') ? 'block' : 'none';
            if(dateRange) dateRange.style.display = (period==='harian') ? 'block' : 'none';
        }

        function recomputeRange(){
            const period = document.getElementById('periodSelect')?.value || '';
            const year = parseInt(document.getElementById('yearSelect')?.value || '{{ now()->year }}');
            const month = parseInt(document.getElementById('monthSelect')?.value || '{{ now()->month }}');
            const weekOpt = document.getElementById('weekOptionSelect')?.value || '';
            const r = computeRangeAdvanced(period, year, month, weekOpt);
            if(!r) return;
            const df=document.querySelector('input[name="date_from"]');
            const dt=document.querySelector('input[name="date_to"]');
            if(df && r.from) df.value=r.from;
            if(dt && r.to) dt.value=r.to;
            // sync hidden weekly inputs
            const wim = document.getElementById('weekInMonthInput');
            const ld = document.getElementById('lastDaysInput');
            if(wim) wim.value = (weekOpt.startsWith('w') ? weekOpt : '');
            if(ld) ld.value = (weekOpt.startsWith('last_') ? weekOpt.split('_')[1] : '');
        }

        document.getElementById('periodSelect')?.addEventListener('change', ()=>{ updateVisibility(); recomputeRange(); });
        document.getElementById('yearSelect')?.addEventListener('change', recomputeRange);
        document.getElementById('monthSelect')?.addEventListener('change', recomputeRange);
        document.getElementById('weekOptionSelect')?.addEventListener('change', recomputeRange);

        // Initialize on load (do not override server-provided dates)
        updateVisibility();
        (function(){
            const dfEl = document.querySelector('input[name="date_from"]');
            const dtEl = document.querySelector('input[name="date_to"]');
            const hasServerDates = !!((dfEl && dfEl.value) || (dtEl && dtEl.value));
            if(document.getElementById('periodSelect')?.value && !hasServerDates){
                recomputeRange();
            }
        })();

        function savePrefs(prefix){
            const f=document.querySelector('form[action*="filter"]')||document.querySelector('form'); if(!f) return;
            const data={};
            ['date_from','date_to','period','status'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; });
            const pmSel=f.querySelector('[name="payment_method[]"]'); if(pmSel){ data['payment_method'] = Array.from(pmSel.selectedOptions).map(o=>o.value); }
            localStorage.setItem(prefix, JSON.stringify(data));
        }
        function loadPrefs(prefix){
            const q = new URLSearchParams(window.location.search);
            if([...q.keys()].length) return; // jangan override jika datang dari query
            const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw);
            Object.entries(data).forEach(([k,v])=>{
                if(k==='payment_method' && Array.isArray(v)){
                    const el=document.querySelector('[name="payment_method[]"]'); if(el){ Array.from(el.options).forEach(o=>o.selected = v.includes(o.value)); }
                } else {
                    const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v;
                }
            });
        }
        function exportDataTableCSV(table, filename){
            const rows = [];
            const headers=[]; $(table.table().header()).find('th').each(function(){ headers.push($(this).text().trim()); });
            rows.push(headers.join(','));
            table.rows({search:'applied'}).every(function(){ const cols=[]; $(this.node()).find('td').each(function(){ cols.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); }); rows.push(cols.join(',')); });
            const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click();
        }

        // Hook order details links
        document.addEventListener('click', function(e){ const a=e.target.closest('.js-order-details'); if(!a) return; e.preventDefault(); const url=a.getAttribute('data-url'); if(!url) return; fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(renderOrderModal).catch(()=>alert('Gagal mengambil detail order')); });
        document.querySelectorAll('.js-transaction-time-display').forEach(el=>{
            const raw = el.getAttribute('data-time') || el.textContent;
            el.textContent = formatDateTime(raw);
        });

        $(function(){
            // Initialize Select2 for multi-select filters
            if ($.fn.select2) {
                $('[name="payment_method[]"]').select2({
                    width: '100%',
                    placeholder: $("[name='payment_method[]']").data('placeholder') || 'Pilih metode bayar',
                    allowClear: true
                });
            }
            loadPrefs('report_order_filters');
            const table = $('#ordersTable').DataTable({ paging: true, info: true });
            const serverRevenue = parseInt(@json($summary['total_revenue'] ?? 0));
            const serverItems = parseInt(@json($summary['total_items_sold'] ?? 0));
            function syncFooterWithServer(){
                $('#ftTotalPrice').text((serverRevenue||0).toLocaleString('id-ID'));
                $('#ftTotalItem').text((serverItems||0).toLocaleString('id-ID'));
            }
            table.on('draw', syncFooterWithServer);
            syncFooterWithServer();

            $('#btnExportOrders').on('click', ()=>exportDataTableCSV(table, 'report_orders_view.csv'));
            const REPORT_BASE_URL = "{{ route('report.index') }}";
            $('#btnResetFilters').on('click', function(){
                try { localStorage.removeItem('report_order_filters'); } catch(_){ }
                window.location.href = REPORT_BASE_URL;
            });
            document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('report_order_filters'));
        });
        // Modal markup (once per page)
    </script>

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endpush
