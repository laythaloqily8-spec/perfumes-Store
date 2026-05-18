/**
 * Register Form Validation
 * Validates all required fields and their formats:
 *   - Full Name: letters and spaces only
 *   - Login ID: exactly 5 chars, starts with a letter
 *   - Credit Card: digits only
 *   - Password: must match confirm password
 * Shows alert messages for invalid inputs.
 * Prevents form submission if validation fails.
 */
(function () {
    'use strict';

    var form = document.querySelector('.login-container form');
    if (!form) return;

    /**
     * Marks an input field as invalid by updating its border color.
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
        var firstName = document.getElementById('firstname');
        var lastName = document.getElementById('lastname');
        var emailUsername = document.getElementById('email-username');
        var loginId = document.getElementById('login-id');
        var address = document.getElementById('address');
        var password = document.getElementById('password');
        var confirmPassword = document.getElementById('confirm-password');
        var creditCard = document.getElementById('credit-card');

        clearErrors();

        var isValid = true;
        var errorMessages = [];

        // Helper to validate a required field is not empty
        function checkRequired(field, label) {
            if (!field.value.trim()) {
                errorMessages.push(label + ' is required.');
                markInvalid(field);
                isValid = false;
                return false;
            }
            return true;
        }

        // Helper to validate a field against a regex pattern
        function checkPattern(field, pattern, label, message) {
            if (field.value.trim() && !pattern.test(field.value.trim())) {
                errorMessages.push(label + ': ' + message);
                markInvalid(field);
                isValid = false;
            }
        }

        // --- Validate all required fields are filled ---

        checkRequired(firstName, 'First Name');
        checkRequired(lastName, 'Last Name');
        checkRequired(emailUsername, 'Email / Username');
        checkRequired(loginId, 'Login ID');
        checkRequired(address, 'Address');
        checkRequired(password, 'Password');
        checkRequired(confirmPassword, 'Confirm Password');
        checkRequired(creditCard, 'Credit Card Number');

        // --- Validate field formats (only if field is not empty) ---

        // A) Full Name: only letters and spaces allowed
        var namePattern = /^[A-Za-z\s]+$/;
        checkPattern(firstName, namePattern, 'First Name', 'Only letters and spaces allowed.');
        checkPattern(lastName, namePattern, 'Last Name', 'Only letters and spaces allowed.');

        // B) Login ID: exactly 5 chars, first must be a letter
        var loginPattern = /^[A-Za-z][A-Za-z0-9$_]{4}$/;
        checkPattern(loginId, loginPattern, 'Login ID', 'Must be exactly 5 characters, starting with a letter. Allowed: letters, numbers, _, $.');

        // C) Credit Card Number: digits only
        var digitsPattern = /^[0-9]+$/;
        checkPattern(creditCard, digitsPattern, 'Credit Card Number', 'Digits only.');

        // D) Password and Confirm Password must match
        if (password.value && confirmPassword.value && password.value !== confirmPassword.value) {
            errorMessages.push('Password and Confirm Password must match.');
            markInvalid(password);
            markInvalid(confirmPassword);
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
            alert('Please fix the following errors:\n\n- ' + errorMessages.join('\n- '));
        }
    });
})();
