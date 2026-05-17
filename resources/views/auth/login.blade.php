@extends('layouts.guest')

@section('title', 'Login')

@push('styles')
<style>
    /* ============================================================
       PPMF Auth / Login Page - Internal CSS
       ============================================================ */

    .login-logo-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }

    .login-logo-icon {
        width: 42px;
        height: 42px;
        background: var(--gov-green);
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
    }

    .ppmf-login-alert {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        border-radius: var(--radius-sm);
        padding: 11px 14px;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .login-option-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin: 6px 0 22px;
        width: 100%;
        flex-wrap: nowrap;
    }

    .remember-check {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        white-space: nowrap;
        margin: 0;
        line-height: 1;
    }

    .remember-check input {
        width: 15px;
        height: 15px;
        accent-color: var(--gov-green);
        margin: 0;
        flex-shrink: 0;
    }

    .remember-check span {
        display: inline-block;
        line-height: 1;
    }

    .login-help-text {
        font-size: 12px;
        color: var(--text-muted);
        text-align: right;
        white-space: nowrap;
        line-height: 1.3;
    }

    .login-security-note {
        margin-top: 18px;
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(27, 67, 50, 0.06);
        border: 1px solid rgba(27, 67, 50, 0.12);
        color: #33584a;
        font-size: 13px;
        line-height: 1.45;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .login-security-note i {
        color: #1b4332;
        font-size: 16px;
        margin-top: 1px;
    }

    .form-input-ppmf.is-invalid {
        border-color: var(--danger);
        background-color: #fff;
    }

    .form-input-ppmf.is-invalid:focus {
        border-color: var(--danger);
        box-shadow: 0 0 0 3px rgba(217, 54, 62, .12);
    }

    @media (max-width: 480px) {
        .login-right {
            padding: 24px;
        }

        .login-option-row {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .login-help-text {
            text-align: right;
            font-size: 11.5px;
            white-space: normal;
        }
    }
</style>
@endpush

@section('content')

<div class="login-page">

    {{-- Left Branding Panel --}}
    <div class="login-left">
        <div class="login-left-content animate-in">

            <div class="login-govt-seal">
                <i class="bi bi-building-check"></i>
            </div>

            <h1 class="login-title">
                Punjab Performance<br>
                Management Framework
            </h1>

            <p class="login-sub">
                Government of Punjab · S&amp;GAD / PMRU Wing<br>
                Performance Monitoring Portal
            </p>

            <div class="login-feature animate-in delay-1">
                <i class="bi bi-speedometer2"></i>
                <div class="login-feature-text">
                    <p>Executive Dashboard</p>
                    <span>Punjab-wide performance overview and monitoring</span>
                </div>
            </div>

            <div class="login-feature animate-in delay-2">
                <i class="bi bi-clipboard-data"></i>
                <div class="login-feature-text">
                    <p>Inspections & KPI Reports</p>
                    <span>Track inspections, scorecards and district performance</span>
                </div>
            </div>

            <div class="login-feature animate-in delay-3">
                <i class="bi bi-geo-alt"></i>
                <div class="login-feature-text">
                    <p>Geo Tagging & Baseline Assets</p>
                    <span>Monitor field data with district and tehsil coverage</span>
                </div>
            </div>

        </div>
    </div>

    {{-- Right Login Form --}}
    <div class="login-right">
        <div class="login-form-card animate-in">

            <div class="login-form-logo">
                <div class="login-logo-row">
                    <div class="login-logo-icon">
                        <i class="bi bi-activity"></i>
                    </div>

                    <div>
                        <h1>PPMF Portal</h1>
                        <p>Secure sign in to continue</p>
                    </div>
                </div>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="alert alert-success ppmf-login-alert" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Login / Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger ppmf-login-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" novalidate>
                @csrf

                <div class="form-group-ppmf">
                    <label for="login">Username / Email</label>

                    <div class="form-input-wrap">
                        <i class="bi bi-person form-input-icon"></i>

                   <input
    type="text"
    name="login"
    id="login"
    value=""
    class="form-input-ppmf @error('login') is-invalid @enderror"
    placeholder="Enter your username or email"
    autocomplete="username"
    autofocus
    required
>
                    </div>

                    @error('login')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group-ppmf">
                    <label for="password">Password</label>

                    <div class="form-input-wrap">
                        <i class="bi bi-lock form-input-icon"></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input-ppmf @error('password') is-invalid @enderror"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="form-input-toggle"
                            onclick="togglePasswordVisibility()"
                            aria-label="Show or hide password"
                        >
                            <i class="bi bi-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>

                    @error('password')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="login-option-row">
                    <label class="remember-check">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Remember me</span>
                    </label>

                    <span class="login-help-text">
                        Forgot password? Contact admin
                    </span>
                </div>

                <button type="submit" class="btn-gov btn-gov-primary btn-gov-lg w-100 justify-content-center">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Sign In
                </button>
            </form>

            <div class="login-security-note">
                <i class="bi bi-shield-lock"></i>
                <span>
                    Authorized access only. Login activity may be monitored for security and audit purposes.
                </span>
            </div>

            <p class="login-footer-text">
                S&amp;GAD / PMRU Wing — Government of Punjab
            </p>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('passwordToggleIcon');

        if (!passwordInput || !toggleIcon) {
            return;
        }

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
</script>
@endpush