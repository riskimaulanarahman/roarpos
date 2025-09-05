@extends('layouts.app')

@section('title', 'Create Product')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Create Product</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Products</div>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header"><h4>Product Details</h4></div>
                    <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Price</label>
                                <input type="number" step="1" class="form-control @error('price') is-invalid @enderror" name="price" value="{{ old('price') }}">
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Stock</label>
                                <input type="number" step="1" class="form-control @error('stock') is-invalid @enderror" name="stock" value="{{ old('stock') }}">
                                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-control @error('category_id') is-invalid @enderror" name="category_id">
                                    <option value="">Choose a category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Photo</label>
                                <input type="file" class="form-control @error('image') is-invalid @enderror" name="image">
                                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('product.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

