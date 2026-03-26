document.addEventListener("DOMContentLoaded", function () {
  var emailInput = document.getElementById("email");
  var passwordInput = document.getElementById("password");

  document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
    button.addEventListener("click", function () {
      var selector = button.getAttribute("data-password-toggle");
      var target = selector ? document.querySelector(selector) : null;
      var icon = button.querySelector("i");

      if (!target) {
        return;
      }

      var isPassword = target.getAttribute("type") === "password";
      target.setAttribute("type", isPassword ? "text" : "password");

      if (icon) {
        icon.className = isPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
      }
    });
  });

  document.querySelectorAll(".demo-fill-btn").forEach(function (button) {
    button.addEventListener("click", function () {
      if (emailInput) {
        emailInput.value = button.getAttribute("data-fill-email") || "";
      }

      if (passwordInput) {
        passwordInput.value = button.getAttribute("data-fill-password") || "";
      }
    });
  });
});
