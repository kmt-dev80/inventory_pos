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


document.addEventListener('DOMContentLoaded', function() {
    // Password match validation
    const form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
                return false;
            }
            
            if (password.length < 4) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                document.getElementById('password').focus();
                return false;
            }
            
            return true;
        });
    }
    
    // File input label update
    const fileInput = document.querySelector('.custom-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Choose file';
            this.nextElementSibling.textContent = fileName;
        });
    }
});