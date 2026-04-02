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

  <link rel="icon" href="{{ app_favicon_url() }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ $frontendCustomCssVersion }}">
</head>
<body class="login-page">
  <div class="login-shell">
    <main class="login-panel">
      <div class="login-card">
        <div class="login-card-head login-card-head-compact">
          <div class="login-brand login-brand-compact">
            <img src="{{ app_logo_url() }}" alt="App Logo">
            <div>
              <span class="login-brand-label">Pharmacy Admin Panel</span>
              <h1>{{ setting('app_name', 'Pharmacy Management System') }}</h1>
            </div>
          </div>
          <h2>Sign in to continue</h2>
        </div>

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

          <button type="button" class="demo-fill-btn" data-fill-email="staff@email.com" data-fill-password="password">
            <span>
              <strong>Staff Login</strong>
              <small>staff@email.com / password</small>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </button>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="{{ asset('backpanel/assets/libs/toastr/toastr.min.js') }}"></script>
  <script>
    // Login page also uses toastr now, so auth messages behave same like the admin area.
    toastr.options = {
      closeButton: true,
      progressBar: true,
      newestOnTop: true,
      positionClass: 'toast-top-right',
      timeOut: 3200,
      extendedTimeOut: 900,
      preventDuplicates: true,
      showDuration: 180,
      hideDuration: 180
    };

    @if (session('success'))
      toastr.success(@json(session('success')));
    @endif

    @if (session('error'))
      toastr.error(@json(session('error')));
    @endif

    @if ($errors->any())
      toastr.error(@json($errors->first()));
    @endif
  </script>
  <script src="{{ asset('assets/js/custom-login.js') }}?v={{ $frontendCustomJsVersion }}"></script>
</body>
</html>
