@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Manage My Account</h4>
    </div>

    <div class="row">
       <!-- <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Profile Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('account.update') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="{{ $user->first_name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="{{ $user->last_name }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small text-muted">Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> -->

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Security Settings</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('account.password.update') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small text-muted">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small text-muted">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small text-muted">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-dark">Change Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($errors->any())
    @push('scripts')
    <script>
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    </script>
    @endpush
@endif
@endsection