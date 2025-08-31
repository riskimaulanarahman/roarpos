@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Summary</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Summary</a></div>
                    <div class="breadcrumb-item">Summary</div>
                </div>
            </div>
            <div class="section-body">


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Summary</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('filterSummary.index') }}" method="GET">
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
                                            @error('date_to')
                                                <div class="alert alert-danger">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Periode</label>
                                                <select name="period" class="form-control" id="periodSelectSummary">
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
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary btn-lg mr-2" tabindex="4">Filter</button>
                                            <button type="button" id="btnResetSummary" class="btn btn-light btn-lg">Reset</button>
                                        </div>
                                    </div>

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

                                    @if ($totalRevenue ?? '')
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card card-statistic-1">
                                                    <div class="card-header"><h4>Total Revenue</h4></div>
                                                    <div class="card-body">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card card-statistic-1">
                                                    <div class="card-header"><h4>Total</h4></div>
                                                    <div class="card-body">Rp {{ number_format($total, 0, ',', '.') }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-lg-8 mb-4">
                                                <div class="card">
                                                    <div class="card-header"><h4>Revenue Trend</h4></div>
                                                    <div class="card-body">
                                                        <canvas id="summaryTrendChart" height="80"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-4">
                                                <div class="card">
                                                    <div class="card-header"><h4>Composition</h4></div>
                                                    <div class="card-body">
                                                        <canvas id="summaryCompositionChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <strong>Total Discount:</strong> <span>{{ number_format($totalDiscount, 0, ',', '.') }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <strong>Total Tax:</strong> <span>{{ number_format($totalTax, 0, ',', '.') }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <strong>Total Service Charge:</strong> <span>{{ number_format($totalServiceCharge, 0, ',', '.') }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <strong>Total Subtotal:</strong> <span>{{ number_format($totalSubtotal, 0, ',', '.') }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-0">
                                                        <strong>Total:</strong> <span>{{ number_format($total, 0, ',', '.') }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                </form>

                                {{-- <form action="{{ route('summary.index') }}" method="GET" class="mb-4">
                                    <div class="form-row">
                                        <div class="col">
                                            <input type="date" name="date_from" class="form-control @error('date_from') is-invalid @enderror" value="{{ old('date_from') ?? request('date_from') }}" required>
                                            @error('date_from')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col">
                                            <input type="date" name="date_to" class="form-control @error('date_to') is-invalid @enderror" value="{{ old('date_to') ?? request('date_to') }}" required>
                                            @error('date_to')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="col">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form> --}}

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function savePrefs(prefix){ const f=document.querySelector('form'); const names=['date_from','date_to','period','status','payment_method','category_id','product_id']; const data={}; names.forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }); localStorage.setItem(prefix, JSON.stringify(data)); }
        function loadPrefs(prefix){ const q=new URLSearchParams(location.search); if([...q.keys()].length) return; const raw=localStorage.getItem(prefix); if(!raw) return; const data=JSON.parse(raw); Object.entries(data).forEach(([k,v])=>{ const el=document.querySelector(`[name="${k}"]`); if(el && !el.value) el.value=v; }); }

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
        document.getElementById('periodSelectSummary')?.addEventListener('change', function(){
            const r=computeRange(this.value); if(!r) return; const df=document.querySelector('input[name="date_from"]'); const dt=document.querySelector('input[name="date_to"]'); if(df) df.value=r.from; if(dt) dt.value=r.to;
        });

        const trend = @json($chartTrend ?? null);
        const comp = @json($composition ?? null);
        if (trend) {
            const tctx = document.getElementById('summaryTrendChart').getContext('2d');
            new Chart(tctx, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: trend.revenue,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.3,
                    }]
                }
            });
        }
        if (comp) {
            const cctx = document.getElementById('summaryCompositionChart').getContext('2d');
            new Chart(cctx, {
                type: 'doughnut',
                data: {
                    labels: comp.labels,
                    datasets: [{
                        data: comp.values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)'
                        ]
                    }]
                }
            });
        }
        // reset + prefs
        document.getElementById('btnResetSummary')?.addEventListener('click', function(){ const f=document.querySelector('form'); f.querySelector('[name="period"]').value=''; ['status','payment_method','category_id','product_id'].forEach(n=>{ const el=f.querySelector(`[name="${n}"]`); if(el) el.value=''; }); const df=f.querySelector('[name="date_from"]'); const dt=f.querySelector('[name="date_to"]'); if(df) df.value=''; if(dt) dt.value=''; });
        loadPrefs('summary_filters');
        document.querySelector('form')?.addEventListener('submit', ()=>savePrefs('summary_filters'));
    </script>
@endpush
