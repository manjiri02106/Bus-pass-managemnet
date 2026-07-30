/* ============================================================================
   BusPass Auth — client-side validation & UX helpers
   (Server-side validation in PHP is always authoritative; this is UX polish.)
   ============================================================================ */

(function () {
  'use strict';

  /* ---- Password visibility toggle -------------------------------------- */
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.dataset.togglePassword);
      if (!target) return;
      var show = target.type === 'password';
      target.type = show ? 'text' : 'password';
      btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  /* ---- Password strength meter ------------------------------------------ */
  function scorePassword(pw) {
    var s = 0;
    if (pw.length >= 8) s++;
    if (pw.length >= 12) s++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    return Math.min(s, 4);
  }
  var STRENGTH = [
    { w: '0%',   c: '#e2e8f0', t: '' },
    { w: '25%',  c: '#ef4444', t: 'Weak' },
    { w: '50%',  c: '#f59e0b', t: 'Fair' },
    { w: '75%',  c: '#3b82f6', t: 'Good' },
    { w: '100%', c: '#22c55e', t: 'Strong' }
  ];

  var meter = document.getElementById('strengthBar');
  var label = document.getElementById('strengthLabel');
  var pwInput = document.getElementById('password');
  if (meter && pwInput) {
    pwInput.addEventListener('input', function () {
      var s = scorePassword(pwInput.value);
      var st = STRENGTH[pwInput.value ? s : 0];
      meter.style.width = st.w;
      meter.style.background = st.c;
      if (label) label.textContent = st.t;
    });
  }

  /* ---- Confirm-password live match --------------------------------------- */
  var confirmInput = document.getElementById('confirm_password');
  if (confirmInput) {
    confirmInput.addEventListener('input', function () {
      var pw = (document.getElementById('password') || {}).value || '';
      confirmInput.setCustomValidity(confirmInput.value && confirmInput.value !== pw ? 'Passwords do not match.' : '');
    });
  }

  /* ---- Bootstrap validation styling for all [data-validate] forms -------- */
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var valid = true;
      form.querySelectorAll('[required]').forEach(function (el) {
        var bad = !el.checkValidity() || el.value.trim() === '';
        el.classList.toggle('is-invalid', bad);
        if (bad) valid = false;
      });
      // role dropdown specific (only if role field is present)
      var role = form.querySelector('select[name="role"]');
      if (role && !role.value) { role.classList.add('is-invalid'); valid = false; }

      if (!valid) {
        e.preventDefault();
        e.stopPropagation();
        var firstBad = form.querySelector('.is-invalid');
        if (firstBad) firstBad.focus();
      }
      form.classList.add('was-validated');
    });
    // clear error state on input
    form.querySelectorAll('input, select').forEach(function (el) {
      el.addEventListener('input', function () { el.classList.remove('is-invalid'); });
      el.addEventListener('change', function () { el.classList.remove('is-invalid'); });
    });
  });

  /* ---- Auto-hide alerts --------------------------------------------------- */
  setTimeout(function () {
    document.querySelectorAll('.alert-dismissible').forEach(function (a) {
      var inst = bootstrap.Alert.getOrCreateInstance(a);
      if (inst) inst.close();
    });
  }, 6000);
})();
