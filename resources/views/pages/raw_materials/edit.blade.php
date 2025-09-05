@extends('layouts.app')

@section('title', 'Edit Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Edit Bahan</h1>
    </div>
    <div class="section-body">
      @include('components.help_panel', [
        'id' => 'help-raw-edit',
        'title' => 'Panduan singkat • Bahan Pokok',
        'items' => [
          'Perbarui data yang diperlukan. Untuk pembelian/penambahan stok gunakan menu <em>Adjust Stok</em>.',
          'Saat menambah stok (positif), isi Unit Cost (harga/unit) agar harga rata-rata berjalan diperbarui dengan benar.',
          'Tampilan angka dibulatkan 2 desimal. Perhitungan internal tetap presisi.',
        ],
      ])
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.update',$material) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label>SKU</label>
                  <input type="text" name="sku" class="form-control" value="{{ $material->sku }}" required>
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" value="{{ $material->name }}" required>
                </div>
                <div class="form-group">
                  <label>Satuan</label>
                  <select name="unit" class="form-control">
                    @foreach(['g','ml','pcs','kg','l'] as $u)
                      <option value="{{ $u }}" {{ $material->unit==$u?'selected':'' }}>{{ $u }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label>Avg Cost</label>
                  <input type="number" step="0.0001" name="unit_cost" class="form-control" value="{{ $material->unit_cost }}" required>
                </div>
                <div class="form-group">
                  <label>Stock</label>
                  <input type="number" step="0.0001" name="stock_qty" class="form-control" value="{{ $material->stock_qty }}" required>
                </div>
                <div class="form-group">
                  <label>Min Stock</label>
                  <input type="number" step="0.0001" name="min_stock" class="form-control" value="{{ $material->min_stock }}" required>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

