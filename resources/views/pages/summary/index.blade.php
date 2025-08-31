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
                                            @error('date_to')<div class="alert alert-danger">{{ $message }}</div>@enderror
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
    </script>
@endpush
