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