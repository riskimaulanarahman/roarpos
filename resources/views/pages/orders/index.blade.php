@extends('layouts.app')

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
                                            <tr>

                                                <td><a
                                                        href="{{ route('order.show', $order->id) }}">{{ $order->transaction_time }}</a>
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
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
