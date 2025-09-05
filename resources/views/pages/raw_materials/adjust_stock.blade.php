@extends('layouts.app')

@section('title', 'Adjust Stok Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Adjust Stok: {{ $material->name }}</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.adjust',$material) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>Qty Change (boleh negatif)</label>
                  <input type="number" step="0.0001" name="qty_change" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Unit Cost (opsional, untuk pembelian/positif)</label>
                  <input type="number" step="0.0001" name="unit_cost" class="form-control">
                </div>
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea name="notes" class="form-control"></textarea>
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

