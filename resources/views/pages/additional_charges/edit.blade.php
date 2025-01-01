@extends('layouts.app')

@section('title', ' Additional Charge Forms')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap-daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-tagsinput/dist/bootstrap-tagsinput.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Additional Charge Forms</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Forms</a></div>
                    <div class="breadcrumb-item">Additional Charges</div>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <form action="{{ route('additional_charge.update', $additionalCharge) }}" method="POST">
                        @csrf
                        @method('PUT')
                        {{-- <div class="card-header">
                            <h4>Input Text</h4>
                        </div> --}}
                        <div class="card-body">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" readonly
                                    class="form-control @error('name')
                                is-invalid
                            @enderror"
                                    name="name" value="{{ $additionalCharge->name }}">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>


                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <div class="selectgroup w-100">
                                    <label class="selectgroup-item">
                                        <input type="radio" name="type" value="percentage" class="selectgroup-input"
                                            @if ($additionalCharge->type == 'percentage') checked @endif>
                                        <span class="selectgroup-button">Percentage</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="type" value="fixed" class="selectgroup-input"
                                            @if ($additionalCharge->type == 'fixed') checked @endif>
                                        <span class="selectgroup-button">Fixed</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Value</label>
                                <input type="number"
                                    class="form-control @error('value')
                                is-invalid
                            @enderror"
                                    name="value" value="{{ $additionalCharge->value }}">
                                @error('value')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>


                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </div>
@endsection

@push('scripts')
@endpush
