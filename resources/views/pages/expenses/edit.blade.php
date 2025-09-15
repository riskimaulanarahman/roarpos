@extends('layouts.app')

@section('title', 'Edit Uang Keluar')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Uang Keluar</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <form action="{{ route('expenses.update',$expense) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="date" value="{{ old('date', optional($expense->date)->toDateString()) }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">-</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $expense->category_id==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Vendor</label>
                                    <input type="text" name="vendor" value="{{ $expense->vendor }}" class="form-control" list="vendor-suggestions">
                                    @if(isset($vendorSuggestions) && count($vendorSuggestions))
                                        <datalist id="vendor-suggestions">
                                            @foreach($vendorSuggestions as $v)
                                                <option value="{{ $v }}">
                                            @endforeach
                                        </datalist>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Jumlah</label>
                                    <input type="number" step="0.01" name="amount" value="{{ $expense->amount }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="notes" class="form-control">{{ $expense->notes }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Lampiran (JPG, PNG, PDF, maks 5MB)</label>
                                    @if($expense->attachment_path)
                                        <div class="mb-2">
                                            <a href="{{ Storage::url($expense->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Lihat lampiran saat ini</a>
                                        </div>
                                    @endif
                                    <input type="file" name="attachment" class="form-control-file" accept="image/*,.pdf">
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
