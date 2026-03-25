<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>

  <!-- Font Awesome (for icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <style>
    .login-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f3f7f4;
      padding: 20px;
    }

    .login-box {
      width: 100%;
      max-width: 460px;
      background: #fff;
      border-radius: 14px;
      padding: 30px;
      box-shadow: 0 15px 35px rgba(26, 71, 42, 0.12);
    }

    .login-box h2 {
      margin-bottom: 10px;
    }

    .login-text {
      color: #5a6772;
      margin-bottom: 22px;
      line-height: 1.6;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .form-group input {
      width: 100%;
      height: 46px;
      border: 1px solid #d8e1da;
      border-radius: 8px;
      padding: 0 14px;
    }

    .btn-login {
      width: 100%;
      border: 0;
      border-radius: 8px;
      height: 46px;
      background: #2b9348;
      color: #fff;
      font-weight: 600;
    }

    .message-box {
      border-radius: 8px;
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: 14px;
    }

    .message-box.error {
      background: #fff1f2;
      color: #b42318;
      border: 1px solid #fecdd3;
    }

    .message-box.success {
      background: #ecfdf3;
      color: #067647;
      border: 1px solid #abefc6;
    }

    .help-box {
      margin-top: 18px;
      border-radius: 8px;
      background: #f7faf7;
      border: 1px dashed #b7c9bb;
      padding: 14px;
      font-size: 14px;
      line-height: 1.7;
    }

    .remember-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
    }

    .remember-row input {
      width: auto;
      height: auto;
    }
  </style>
</head>
<body class="login-page">
  <div class="login-box">
    <h2>Admin Login</h2>
    <p class="login-text">This page is for inventory and purchase management dashboard login.</p>

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

    <form action="{{ route('login.submit') }}" method="POST">
      @csrf

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" required>
      </div>

      <div class="remember-row">
        <input type="checkbox" name="remember" id="remember" value="1">
        <label for="remember" style="margin: 0;">Keep me login on this browser</label>
      </div>

      <button type="submit" class="btn-login">Login</button>

      <div class="help-box">
        <strong>Demo login</strong><br>
        Email: <code>admin@pharmacy.com</code><br>
        Password: <code>admin12345</code>
      </div>
    </form>
  </div>
</body>
</html>
