@extends('layouts.app')

@section('title', 'Report')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">

            {{-- <div class="section-header">
                <h1>Profile</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Profile</a></div>
                    <div class="breadcrumb-item">Profile</div>
                </div>
            </div> --}}
            <div class="container-fluid profile-container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="card profile-card">
                            <div class="card-header text-center">
                                <h2>User Profile</h2>
                            </div>
                            <div class="card-body text-center">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Name:</strong> <span>{{ $user->name }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Email:</strong> <span>{{ $user->email }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Password:</strong> <span>********</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Phone:</strong> <span>{{ $user->phone ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Role:</strong> <span>{{ $user->roles }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-0">
                                        <strong>Registered At:</strong> <span>{{ $user->created_at->format('d M Y') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    {{-- <script src="assets/js/page/forms-advanced-forms.js"></script> --}}
    <script src="{{ asset('js/page/forms-advanced-forms.js') }}"></script>
@endpush
