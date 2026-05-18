/**
 * Login Form Validation
 * Validates that login/email and password fields are not empty.
 * Shows alert messages for empty fields.
 * Prevents form submission if validation fails.
 */
(function () {
    'use strict';

    var form = document.querySelector('.login-container form');
    if (!form) return;

    // Set autocomplete attributes as required
    document.getElementById('username').setAttribute('autocomplete', 'on');
    document.getElementById('password').setAttribute('autocomplete', 'off');

    /**
     * Marks an input field as invalid by updating its border color.
     * Does not permanently change the CSS; only applies inline style.
     */
    function markInvalid(element) {
        element.style.borderColor = '#e74c3c';
    }

    /**
     * Clears all inline error styles from form inputs.
     */
    function clearErrors() {
        var inputs = form.querySelectorAll('input');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].style.borderColor = '';
        }
    }

    form.addEventListener('submit', function (event) {
        var username = document.getElementById('username');
        var password = document.getElementById('password');

        clearErrors();

        var isValid = true;
        var errorMessages = [];

        // Validate username / email field
        if (!username.value.trim()) {
            errorMessages.push('Login / Email field is required.');
            markInvalid(username);
            isValid = false;
        }

        // Validate password field
        if (!password.value.trim()) {
            errorMessages.push('Password field is required.');
            markInvalid(password);
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
            alert('Please fix the following errors:\n\n- ' + errorMessages.join('\n- '));
        }
    });
})();
