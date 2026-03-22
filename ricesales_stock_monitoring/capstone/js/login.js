// js/login.js
(function () {
  "use strict";

  function $(id) { return document.getElementById(id); }

  // Toggle password
  function initTogglePassword() {
    const pwd = $("password");
    const eye = $("eyeIcon");
    const toggle = $("togglePwdBtn"); // if you use a button
    const span = $("togglePwdSpan");  // if you kept <span onclick="...">

    const trigger = toggle || span;
    if (!pwd || !eye || !trigger) return;

    trigger.addEventListener("click", function () {
      const show = pwd.type === "password";
      pwd.type = show ? "text" : "password";
      eye.className = show ? "fa fa-eye-slash" : "fa fa-eye";
    });
  }

  // Disable login button only if valid
  function initDisableOnSubmit() {
    const form = document.querySelector("form.needs-validation");
    const btn  = $("loginBtn");
    if (!form || !btn) return;

    form.addEventListener("submit", function () {
      if (!form.checkValidity()) return;
      btn.disabled = true;
      btn.innerText = "Logging in...";
    });
  }

  // Bootstrap validation
  function initBootstrapValidation() {
    const forms = document.querySelectorAll(".needs-validation");
    Array.from(forms).forEach(form => {
      form.addEventListener("submit", event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      }, false);
    });
  }

  // Autofocus
  function initAutofocus() {
    $("username")?.focus();
  }

  // Service worker registration
  function initServiceWorker() {
    if (!("serviceWorker" in navigator)) return;
    navigator.serviceWorker.register("/service-worker.js").catch(() => {});
  }

  document.addEventListener("DOMContentLoaded", function () {
    initTogglePassword();
    initDisableOnSubmit();
    initBootstrapValidation();
    initAutofocus();
    initServiceWorker();
  });
})();
