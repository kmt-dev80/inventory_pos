/*document.addEventListener('DOMContentLoaded', function() {
    // Handle both register and edit forms with one function
    function setupFormValidation(formId, passwordId, confirmPasswordId) {
        const form = document.getElementById(formId);
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById(passwordId).value;
                const confirmPassword = document.getElementById(confirmPasswordId).value;
                
                // Only validate if password field has value (for edit form)
                if (passwordId === 'new_password' && !password) {
                    return true; // Skip validation if not changing password
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    document.getElementById(confirmPasswordId).focus();
                    return false;
                }
                return true;
            });
        }
    }

    // Initialize validation for both forms
    setupFormValidation('registerForm', 'password', 'confirm_password');
    setupFormValidation('editUserForm', 'new_password', 'confirm_password');
});*/


$(document).ready(function() {
    // Password visibility toggle for all password fields
    $('.password-toggle').on('click', function() {
        const input = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Combined form validation for both editUserForm and registerForm
    $('#editUserForm, #registerForm').on('submit', function(e) {
        const password = $('#password').val();
        const confirm_password = $('#confirm_password').val();
        
        // Check if passwords match
        if (password !== confirm_password) {
            e.preventDefault();
            alert('Passwords do not match!');
            $('#confirm_password').focus();
            return false;
        }
        
        // Password strength validation
        if (password) {
            if (password.length < 4) {
                e.preventDefault();
                alert('Password must be at least 4 characters');
                $('#password').focus();
                return false;
            }
            
           /* if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter');
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number');
                return false;
            }*/
        }
        
        return true;
    });

    // File input label update
    $('.custom-file-input').on('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'Choose file';
        $(this).next('.custom-file-label').text(fileName);
    });
});