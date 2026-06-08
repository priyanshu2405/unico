@extends('layouts.auth')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0" style="border-radius: 20px;">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-envelope-open-text fa-4x text-primary animate__animated animate__bounceIn"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Verify Your Email</h2>
                    <p class="text-muted mb-4">
                        Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                    </p>

                    @if (session('message'))
                    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                        {{ session('message') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="d-grid gap-3">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" style="border-radius: 10px;">
                                Resend Verification Email
                            </button>
                        </form>

                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100 fw-bold" style="border-radius: 10px;">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
    }

    .card {
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }
</style>
@endsection