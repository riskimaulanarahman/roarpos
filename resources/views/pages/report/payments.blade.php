@extends('layouts.app')

@section('title', 'Report - Payments')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />
@endpush

@section('main')
<div class="main-content">
<section class="section">
  <div class="section-header">
    <h1>Payments Report</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Reports</div>
      <div class="breadcrumb-item active">Payments</div>
    </div>
  </div>

  <div class="section-body">
    <div class="card mb-4">
      <div class="card-header">
        <h4>Filter</h4>
      </div>
      <div class="card-body">
        <form action="{{ route('report.payments') }}" method="GET">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Dari Tanggal</label>
                <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Sampai Tanggal</label>
                <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                  <option value="">Semua</option>
                  @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="payment_method" class="form-control">
                  <option value="">Semua</option>
                  @foreach($paymentMethods as $pm)
                    <option value="{{ $pm }}" {{ ($methodFilter ?? '') === $pm ? 'selected' : '' }}>{{ $pm }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>User</label>
                <select name="user_id" class="form-control">
                  @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (int)$userId === (int)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary btn-lg mr-2"><i class="fas fa-search"></i> Filter</button>
              <a href="{{ route('report.payments') }}" class="btn btn-light btn-lg">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h4>Periode: {{ $date_from }} s/d {{ $date_to }}</h4>
      </div>
      <div class="card-body">
        <div class="mb-3">
          @php($chips = [])
          @if(request('date_from')) @php($chips[] = 'Dari: '.request('date_from')) @endif
          @if(request('date_to')) @php($chips[] = 'Ke: '.request('date_to')) @endif
          @if(request('status')) @php($chips[] = 'Status: '.ucfirst(request('status'))) @endif
          @if(request('payment_method')) @php($chips[] = 'Metode: '.request('payment_method')) @endif
          @if(request('user_id'))
              @php($u = ($users ?? collect())->firstWhere('id', request('user_id')))
              @if($u) @php($chips[] = 'User: '.$u->name) @endif
          @endif
          @if(count($chips))
              <div>
                  @foreach($chips as $chip)
                      <span class="badge badge-primary mr-2">{{ $chip }}</span>
                  @endforeach
              </div>
          @endif
        </div>

        <div class="mb-4">
          <canvas id="paymentsChart" height="80"></canvas>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Payment Method</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">AOV</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $r)
                <tr>
                  <td>{{ $r->payment_method ?? 'unknown' }}</td>
                  <td class="text-right">{{ number_format($r->orders_count) }}</td>
                  <td class="text-right">{{ number_format($r->revenue) }}</td>
                  <td class="text-right">{{ number_format($r->aov) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const paymentsPayload = @json($chart ?? null);
      if (paymentsPayload) {
        const ctx = document.getElementById('paymentsChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: paymentsPayload.labels,
            datasets: [
              { label: 'Revenue', data: paymentsPayload.revenue, backgroundColor: 'rgba(54,162,235,0.5)', borderColor: 'rgba(54,162,235,1)' },
              { label: 'Orders', data: paymentsPayload.orders, type: 'line', yAxisID: 'y2', borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)' }
            ]
          },
          options: { responsive:true, scales: { y: { beginAtZero: true }, y2: { beginAtZero: true, position:'right', grid:{ drawOnChartArea:false } } } }
        });
      }
    </script>
@endpush
