@extends('layouts.app')

@php
    use Carbon\Carbon;
    use Carbon\CarbonInterface;
@endphp

@section('title', 'Orders')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Orders</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Orders</a></div>
                    <div class="breadcrumb-item">All Orders</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>




                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <h4 class="ml-4 mt-4" style="color: #3949AB;">All Orders</h4>
                            {{-- <div class="card-body"> --}}
                                {{-- <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0">Orders</h4>
                                    <form method="GET" action="{{ route('order.index') }}" class="mb-0">
                                        <div class="form-row align-items-center">
                                            <div class="col-auto">
                                                <label for="date_filter" class="sr-only">Filter:</label>
                                                <select name="date_filter" id="date_filter" class="form-control" onchange="this.form.submit()">
                                                    <option value="today" {{ request()->query('date_filter', 'today') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                                                    <option value="all" {{ request()->query('date_filter') == 'all' ? 'selected' : '' }}>Semua</option>
                                                </select>
                                            </div>
                                        </div>
                                    </form>
                                </div> --}}

                                {{-- <div class="float-right">
                                    <form method="GET" action="{{ route('product.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div> --}}

                                <div class="clearfix mb-3"></div>
                                    <table class="table-striped table">
                                        <tr>

                                            <th>Transaction Time</th>
                                            <th>Sub Total</th>
                                            {{-- <th>Discount</th>
                                            <th>Tax</th>
                                            <th>Service</th> --}}
                                            <th>Total Price</th>
                                            <th>Total Item</th>
                                            <th>Kasir</th>
                                        </tr>
                                        @foreach ($orders as $order)
                                            @php
                                                $transactionTime = $order->transaction_time;
                                                if ($transactionTime instanceof CarbonInterface) {
                                                    $transactionTimeIso = $transactionTime->toIso8601String();
                                                    $transactionTimeFallback = $transactionTime->toDateTimeString();
                                                } elseif ($transactionTime) {
                                                    $transactionTimeIso = Carbon::parse($transactionTime, config('app.timezone'))->toIso8601String();
                                                    $transactionTimeFallback = $transactionTime;
                                                } else {
                                                    $transactionTimeIso = '';
                                                    $transactionTimeFallback = '-';
                                                }
                                            @endphp
                                            <tr>

                                                <td>
                                                    <a href="#"
                                                       class="js-order-details"
                                                       data-url="{{ route('order.details_json', $order->id) }}"
                                                       data-transaction-time="{{ $transactionTimeIso }}">
                                                        <span class="js-transaction-time-display"
                                                              data-time="{{ $transactionTimeIso ?: $transactionTimeFallback }}">
                                                            {{ $transactionTimeFallback }}
                                                        </span>
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ number_format($order->sub_total, 0, ',', '.') }}
                                                </td>
                                                {{-- <td>
                                                    {{ number_format($order->discount_amount, 0, ',', '.') }}

                                                </td>
                                                <td>
                                                    {{ number_format($order->tax, 0, ',', '.') }}

                                                </td>
                                                <td>
                                                    {{ number_format($order->service_charge, 0, ',', '.') }}

                                                </td> --}}
                                                <td>
                                                    {{ number_format($order->total_price, 0, ',', '.') }}

                                                </td>
                                                <td>
                                                    {{ $order->total_item }}
                                                </td>
                                                <td>
                                                    {{ $order->user->name }}

                                                </td>

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
                </div>
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
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
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
        document.querySelectorAll('.js-order-details').forEach(a=>{
            a.addEventListener('click', function(e){ e.preventDefault(); const url=this.getAttribute('data-url'); if(!url) return;
                fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }}).then(r=>r.json()).then(renderOrderModal).catch(()=>alert('Gagal mengambil detail order'));
            });
        });
    </script>
@endpush
