@extends('layouts.app')

@section('title', 'Edit Uang Masuk')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Uang Masuk</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <form action="{{ route('income.update', $income->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="date" value="{{ $income->date }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">-</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $income->category_id==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah</label>
                                    <input type="number" step="0.01" name="amount" value="{{ $income->amount }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="notes" class="form-control">{{ $income->notes }}</textarea>
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
