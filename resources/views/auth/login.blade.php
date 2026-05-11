<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — PPMF Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('css/ppmf-style.css') }}">
</head>
<body>

<div class="login-page">

  {{-- Left branding panel --}}
  <div class="login-left">
    <div class="login-left-content animate-in">

      <div class="login-govt-seal">
        <i class="bi bi-shield-check"></i>
      </div>

      <h1 class="login-title">Punjab Performance<br>Management Framework</h1>
      <p class="login-sub">Government of Punjab · Performance Monitoring Portal<br>Fiscal Year 2024–25</p>

      <div class="login-feature animate-in delay-1">
        <i class="bi bi-graph-up-arrow"></i>
        <div class="login-feature-text">
          <p>Real-time KPI Tracking</p>
          <span>Monitor 234 KPIs across 36 districts</span>
        </div>
      </div>

      <div class="login-feature animate-in delay-2">
        <i class="bi bi-layers"></i>
        <div class="login-feature-text">
          <p>Tier-wise Governance Scorecard</p>
          <span>CM Scorecard with district ranking</span>
        </div>
      </div>

      <div class="login-feature animate-in delay-3">
        <i class="bi bi-file-earmark-bar-graph"></i>
        <div class="login-feature-text">
          <p>Advanced Reports & Analytics</p>
          <span>Export PDF, Excel reports instantly</span>
        </div>
      </div>

    </div>
  </div>

  {{-- Right login form --}}
  <div class="login-right">
    <div class="login-form-card animate-in">

      <div class="login-form-logo">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <div style="width:38px;height:38px;background:var(--gov-green);border-radius:9px;display:grid;place-items:center;color:#fff;font-size:17px;">
            <i class="bi bi-activity"></i>
          </div>
          <h1>PPMF Portal</h1>
        </div>
        <p>Sign in to access the monitoring dashboard</p>
      </div>

      @if(session('error'))
        <div style="background:var(--danger-light);color:var(--danger);border:1px solid #f5c6cb;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
        </div>
      @endif

      <form action="{{ route('login.post') }}" method="POST">
        @csrf

        <div class="form-group-ppmf">
          <label for="username">Username / Email</label>
          <div class="form-input-wrap">
            <i class="bi bi-person form-input-icon"></i>
            <input
              type="text"
              id="username"
              name="username"
              class="form-input-ppmf"
              placeholder="Enter your username"
              value="{{ old('username') }}"
              required
              autocomplete="username"
            >
          </div>
          @error('username')
            <div style="font-size:11.5px;color:var(--danger);margin-top:4px;">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group-ppmf">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <label for="password">Password</label>
<a href="#" style="font-size:11.5px;color:var(--gov-green);text-decoration:none;">              Forgot password?
            </a>
          </div>
          <div class="form-input-wrap">
            <i class="bi bi-lock form-input-icon"></i>
            <input
              type="password"
              id="password"
              name="password"
              class="form-input-ppmf"
              placeholder="Enter your password"
              required
              autocomplete="current-password"
              style="padding-right:42px;"
            >
            <button type="button" class="form-input-toggle" onclick="
              const p=document.getElementById('password');
              const i=this.querySelector('i');
              if(p.type==='password'){p.type='text';i.className='bi bi-eye-slash';}
              else{p.type='password';i.className='bi bi-eye';}
            ">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          @error('password')
            <div style="font-size:11.5px;color:var(--danger);margin-top:4px;">{{ $message }}</div>
          @enderror
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;color:var(--text-secondary);">
            <input type="checkbox" name="remember" style="accent-color:var(--gov-green);width:15px;height:15px;">
            Remember me
          </label>
        </div>

        <button type="submit" class="btn-gov btn-gov-primary btn-gov-lg" style="width:100%;justify-content:center;">
          <i class="bi bi-box-arrow-in-right"></i> Sign In to Portal
        </button>

      </form>

      <div class="login-divider"><span>Secure Government Portal</span></div>

      <div style="display:flex;align-items:center;justify-content:center;gap:20px;">
        <span style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-muted);">
          <i class="bi bi-shield-lock" style="color:var(--gov-green);font-size:14px;"></i> SSL Secured
        </span>
        <span style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-muted);">
          <i class="bi bi-eye-slash" style="color:var(--gov-green);font-size:14px;"></i> Privacy Protected
        </span>
        <span style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-muted);">
          <i class="bi bi-clock-history" style="color:var(--gov-green);font-size:14px;"></i> Session Timeout
        </span>
      </div>

      <div class="login-footer-text">
        For access requests, contact:
        <a href="mailto:pmru@punjab.gov.pk" style="color:var(--gov-green);">pmru@punjab.gov.pk</a>
        <br>
        &copy; {{ date('Y') }} Government of Punjab — PPMF v2.0
      </div>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
