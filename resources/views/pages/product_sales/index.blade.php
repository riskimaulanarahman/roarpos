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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>dari Tanggal</label>
                                                <input type="date" name="date_from"
                                                    value="{{ old('date_from') ?? request()->query('date_from') }}"
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
                                                <label>ke Tanggal</label>
                                                <input type="date" name="date_to"
                                                    value="{{ old('date_to') ?? request()->query('date_to') }}"
                                                    class="form-control datepicker">
                                            </div>
                                            @error('date_to')
                                                <div class="alert alert-danger">
                                                    {{ $message }}
                                                </div>
                                            @enderror
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


                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered text-center">
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
                                                                value="{{ old('date_from') ?? request()->query('date_from') }}"
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
                                                                value="{{ old('date_to') ?? request()->query('date_to') }}"
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
@endpush
