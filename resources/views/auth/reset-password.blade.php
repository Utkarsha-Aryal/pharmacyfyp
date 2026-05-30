<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ setting('app_name', 'Pharmacy Management System') }} | Reset Password</title>

  @php
    $frontendCustomCssVersion = file_exists(public_path('assets/css/custom.css'))
        ? filemtime(public_path('assets/css/custom.css'))
        : time();
    $frontendCustomJsVersion = file_exists(public_path('assets/js/custom-login.js'))
        ? filemtime(public_path('assets/js/custom-login.js'))
        : time();
  @endphp

  <link rel="icon" href="{{ app_favicon_url() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ $frontendCustomCssVersion }}">
  <style>
    :root {
      --app-font-family: "Inter", sans-serif;
      --brand-font-family: "Poppins", sans-serif;
    }

    body,
    input,
    select,
    textarea,
    button {
      font-family: var(--app-font-family);
    }

    .login-brand h1,
    .login-brand-label {
      font-family: var(--brand-font-family);
    }
  </style>
</head>
<body class="login-page">
  <div class="login-shell">
    <main class="login-panel">
      <div class="login-card">
        <div class="login-card-head login-card-head-compact">
          <div class="login-brand login-brand-compact">
            <img src="{{ app_logo_url() }}" alt="App Logo">
            <div>
              <span class="login-brand-label">Verify OTP</span>
              <h1>{{ setting('app_name', 'Pharmacy Management System') }}</h1>
            </div>
          </div>
          <h2>Create a new password</h2>
          <p class="text-muted mb-0">Use the OTP from your email. It is valid for 15 minutes.</p>
        </div>

        <form action="{{ route('password.reset.update') }}" method="POST" class="login-form">
          @csrf

          <div class="login-form-group">
            <label for="email">Email address</label>
            <div class="login-input-wrap">
              <i class="fa-regular fa-envelope"></i>
              <input type="email" name="email" id="email" value="{{ old('email', $email) }}" placeholder="Enter email" required autofocus>
            </div>
          </div>

          <div class="login-form-group">
            <label for="otp">OTP code</label>
            <div class="login-input-wrap">
              <i class="fa-solid fa-key"></i>
              <input type="text" name="otp" id="otp" value="{{ old('otp') }}" placeholder="6 digit OTP" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6">
            </div>
          </div>

          <div class="login-form-group">
            <label for="password">New password</label>
            <div class="login-input-wrap password-wrap">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" id="password" placeholder="Minimum 8 characters" required>
              <button type="button" class="password-toggle" data-password-toggle="#password" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="login-form-group">
            <label for="password_confirmation">Confirm password</label>
            <div class="login-input-wrap password-wrap">
              <i class="fa-solid fa-shield-halved"></i>
              <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repeat new password" required>
              <button type="button" class="password-toggle" data-password-toggle="#password_confirmation" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-login">
            <i class="fa-solid fa-check me-1"></i> Reset Password
          </button>

          <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
            <a href="{{ route('password.request') }}" class="auth-secondary-link text-decoration-none">Send OTP again</a>
            <a href="{{ route('login') }}" class="auth-secondary-link text-decoration-none">Back to login</a>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="{{ asset('backpanel/assets/libs/toastr/toastr.min.js') }}"></script>
  @include('auth.partials.toastr')
  <script src="{{ asset('assets/js/custom-login.js') }}?v={{ $frontendCustomJsVersion }}"></script>
</body>
</html>
