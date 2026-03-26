<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ setting('app_name', 'Pharmacy Management System') }} | Admin Login</title>

  @php
    $frontendCustomCssVersion = file_exists(public_path('assets/css/custom.css'))
        ? filemtime(public_path('assets/css/custom.css'))
        : time();
    $frontendCustomJsVersion = file_exists(public_path('assets/js/custom-login.js'))
        ? filemtime(public_path('assets/js/custom-login.js'))
        : time();
  @endphp

  <link rel="icon" href="{{ app_favicon_url() }}" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ $frontendCustomCssVersion }}">
</head>
<body class="login-page">
  <div class="login-shell">
    <aside class="login-visual">
      <div class="login-visual-top">
        <div class="login-brand">
          <img src="{{ app_logo_url() }}" alt="App Logo">
          <div>
            <span class="login-brand-label">Pharmacy Admin Panel</span>
            <h1>{{ setting('app_name', 'Pharmacy Management System') }}</h1>
          </div>
        </div>
        <p class="login-visual-text">
          Manage stock, purchase bills, expiry alerts, staff access, and settings from one clean dashboard.
        </p>
      </div>

      <div class="login-feature-grid">
        <div class="login-feature-card">
          <i class="fa-solid fa-boxes-stacked"></i>
          <strong>Batch Tracking</strong>
          <span>Batch no, expiry date, and supplier link kept together.</span>
        </div>
        <div class="login-feature-card">
          <i class="fa-solid fa-file-invoice-dollar"></i>
          <strong>Purchase Entry</strong>
          <span>Receive stock and update totals from one workflow.</span>
        </div>
        <div class="login-feature-card">
          <i class="fa-solid fa-chart-column"></i>
          <strong>Reports & Alerts</strong>
          <span>Low stock, expiry, and purchase overview with quick export.</span>
        </div>
      </div>

      <div class="login-visual-illustration">
        <img src="{{ asset('assets/img/login/pharmacy-login-visual.svg') }}" alt="Pharmacy Illustration">
      </div>
    </aside>

    <main class="login-panel">
      <div class="login-card">
        <div class="login-card-head">
          <span class="login-kicker">Secure Access</span>
          <h2>Sign in to continue</h2>
          <p class="login-text">Only admin and staff accounts can access the backend.</p>
        </div>

        @if (session('success'))
          <div class="message-box success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
          <div class="message-box error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
          <div class="message-box error">
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="login-form">
          @csrf

          <div class="login-form-group">
            <label for="email">Email address</label>
            <div class="login-input-wrap">
              <i class="fa-regular fa-envelope"></i>
              <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter email" required>
            </div>
          </div>

          <div class="login-form-group">
            <label for="password">Password</label>
            <div class="login-input-wrap password-wrap">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" id="password" placeholder="Enter password" required>
              <button type="button" class="password-toggle" data-password-toggle="#password" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="remember-row">
            <input type="checkbox" name="remember" id="remember" value="1">
            <label for="remember" class="remember-label">Keep me logged in on this browser</label>
          </div>

          <button type="submit" class="btn-login">Login to Dashboard</button>
        </form>

        <div class="login-demo-box">
          <div class="login-demo-head">
            <i class="fa-solid fa-circle-info"></i>
            <strong>Demo Accounts</strong>
          </div>

          <button type="button" class="demo-fill-btn" data-fill-email="admin@pharmacy.com" data-fill-password="admin12345">
            <span>
              <strong>Admin Login</strong>
              <small>admin@pharmacy.com / admin12345</small>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </button>

          <button type="button" class="demo-fill-btn" data-fill-email="staff@pharmacy.com" data-fill-password="staff12345">
            <span>
              <strong>Staff Login</strong>
              <small>staff@pharmacy.com / staff12345</small>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </button>
        </div>
      </div>
    </main>
  </div>

  <script src="{{ asset('assets/js/custom-login.js') }}?v={{ $frontendCustomJsVersion }}"></script>
</body>
</html>
