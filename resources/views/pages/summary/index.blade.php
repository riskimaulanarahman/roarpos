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
                                            @if ($totalRevenue ?? '')
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

                                            @endif
                                        </div>
                                    </div>
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
@endpush
