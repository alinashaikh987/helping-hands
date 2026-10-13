document.addEventListener('DOMContentLoaded', function () {
  var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var phoneRe = /^[0-9+\- ]{7,20}$/;

  function check(field) {
    var v = field.value.trim();
    if (field.required && v === '') return 'This field is required.';
    if (v === '') return '';
    if (field.type === 'email' && !emailRe.test(v)) return 'Please enter a valid email address.';
    if (field.name === 'phone' && !phoneRe.test(v)) return 'Use 7 to 20 digits, spaces, + or -.';
    if (field.type === 'number') {
      var n = parseFloat(v);
      if (isNaN(n) || n < 1) return 'Amount must be at least 1.';
    }
    var min = field.dataset.minlength;
    if (min && v.length < parseInt(min, 10)) return 'Please enter at least ' + min + ' characters.';
    return '';
  }

  function show(field, message) {
    var box = field.parentNode.querySelector('.invalid-feedback');
    if (!box) {
      box = document.createElement('div');
      box.className = 'invalid-feedback';
      field.parentNode.appendChild(box);
    }
    box.textContent = message;
    field.classList.toggle('is-invalid', message !== '');
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var fields = form.querySelectorAll('input:not([type=hidden]):not([name=website]), select, textarea');

    form.addEventListener('submit', function (e) {
      var firstBad = null;
      fields.forEach(function (f) {
        var msg = check(f);
        show(f, msg);
        if (msg && !firstBad) firstBad = f;
      });
      if (firstBad) {
        e.preventDefault();
        firstBad.focus();
      }
    });

    fields.forEach(function (f) {
      f.addEventListener('blur', function () { show(f, check(f)); });
      f.addEventListener('input', function () {
        if (f.classList.contains('is-invalid')) show(f, check(f));
      });
    });
  });
});