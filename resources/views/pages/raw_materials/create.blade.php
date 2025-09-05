@extends('layouts.app')

@section('title', 'Tambah Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Tambah Bahan</h1>
    </div>
    <div class="section-body">
      @include('components.help_panel', [
        'id' => 'help-raw-create',
        'title' => 'Panduan singkat • Bahan Pokok',
        'items' => [
          'Isi SKU, Nama, dan Satuan. Jika mengisi stok awal, isi juga Avg Cost (harga/unit) agar HPP awal akurat.',
          'Untuk pembelian/penambahan stok berikutnya gunakan menu <em>Adjust Stok</em> dan isi Unit Cost saat Qty positif.',
          'Tampilan angka dibulatkan 2 desimal. Perhitungan internal tetap presisi.',
        ],
      ])
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.store') }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>SKU</label>
                  <input type="text" name="sku" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Nama</label>
                  <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Satuan</label>
                  <select name="unit" class="form-control">
                    <option value="g">g</option>
                    <option value="ml">ml</option>
                    <option value="pcs">pcs</option>
                    <option value="kg">kg</option>
                    <option value="l">l</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Avg Cost</label>
                  <input type="number" step="0.0001" name="unit_cost" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Stock</label>
                  <input type="number" step="0.0001" name="stock_qty" class="form-control">
                </div>
                <div class="form-group">
                  <label>Min Stock</label>
                  <input type="number" step="0.0001" name="min_stock" class="form-control">
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

