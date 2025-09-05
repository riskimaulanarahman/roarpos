@extends('layouts.app')

@section('title', 'Produksi Produk')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Produksi: {{ $product->name }}</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('product-recipes.produce',$product) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label>Batches</label>
                  <input type="number" min="1" name="batches" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea name="notes" class="form-control"></textarea>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-success">Proses</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

