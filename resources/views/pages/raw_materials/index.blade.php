@extends('layouts.app')

@section('title', 'Bahan Pokok')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Bahan Pokok</h1>
      <div class="section-header-button">
        <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">Tambah</a>
      </div>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <form class="form-inline mb-3" method="GET" action="{{ route('raw-materials.index') }}">
            <input type="text" class="form-control mr-2" name="search" placeholder="Cari nama/SKU" value="{{ request('search') }}">
            <button class="btn btn-primary">Cari</button>
          </form>
          @include('layouts.alert')
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Nama</th>
                  <th>Satuan</th>
                  <th>Avg Cost</th>
                  <th>Stock</th>
                  <th>Min Stock</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($materials as $m)
                <tr>
                  <td>{{ $m->sku }}</td>
                  <td>{{ $m->name }}</td>
                  <td>{{ $m->unit }}</td>
                  <td>{{ number_format($m->unit_cost, 2, ',', '.') }}</td>
                  <td>{{ number_format($m->stock_qty, 2, ',', '.') }}</td>
                  <td>{{ number_format($m->min_stock, 2, ',', '.') }}</td>
                  <td class="text-right">
                    <a href="{{ route('raw-materials.edit',$m) }}" class="btn btn-sm btn-info">Edit</a>
                    <a href="{{ route('raw-materials.adjust-form',$m) }}" class="btn btn-sm btn-warning">Adjust</a>
                    <a href="{{ route('raw-materials.movements',$m) }}" class="btn btn-sm btn-secondary">Movements</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $materials->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

