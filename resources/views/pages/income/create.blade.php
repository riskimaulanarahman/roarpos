@extends('layouts.app')

@section('title', 'Tambah Uang Masuk')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Tambah Uang Masuk</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <form action="{{ route('income.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">-</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required>
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
