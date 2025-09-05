@extends('layouts.app')

@section('title', 'Kartu Stok Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Kartu Stok: {{ $material->name }}</h1>
      <div class="section-header-button">
        <a href="{{ route('raw-materials.adjust-form',$material) }}" class="btn btn-warning">Adjust</a>
      </div>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Type</th>
                  <th>Qty</th>
                  <th>Unit Cost</th>
                  <th>Ref</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($movements as $mv)
                <tr>
                  <td>{{ optional($mv->occurred_at)->toDateTimeString() }}</td>
                  <td>{{ $mv->type }}</td>
                  <td>{{ number_format($mv->qty_change, 2, ',', '.') }}</td>
                  <td>{{ number_format($mv->unit_cost, 2, ',', '.') }}</td>
                  <td>{{ $mv->reference_type }} #{{ $mv->reference_id }}</td>
                  <td>{{ $mv->notes }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $movements->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

