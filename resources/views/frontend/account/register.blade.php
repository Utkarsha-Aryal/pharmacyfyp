<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pharmacy Register</title>

  <!-- Font Awesome (for icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="login-page">
  <div class="login-box">
    <h2>Create a New Account</h2>
    <form action="{{ route('register') }}" method="POST">
      @csrf

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" placeholder="Enter full name" required>
      </div>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" name="email" id="email" placeholder="Enter email" required>
      </div>

      <div class="form-group position-relative">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" required>
      </div>

      <div class="form-group position-relative">
        <label for="password_confirmation">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm password" required>
      </div>

      <button type="submit" class="btn-login">Register</button>

      <div class="register-link">
        Already have an account?
        <a href="{{ route('login') }}">Login</a>
      </div>
    </form>
  </div>
</body>
</html>
