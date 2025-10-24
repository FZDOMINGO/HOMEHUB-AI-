document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const tenantRole = document.getElementById('tenant-role');
    const landlordRole = document.getElementById('landlord-role');
    const signupMessage = document.getElementById('signup-message');
    const signupBtn = document.getElementById('signup-btn');
    const registerForm = document.getElementById('registerForm');
    const registerError = document.getElementById('register-error');
    
    // Default active user type
    let activeUserType = 'tenant';
    
    // Check if user is already logged in
    fetch('../api/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                // Redirect to appropriate dashboard
                window.location.href = '../' + data.user.type + '/dashboard.php';
            }
        })
        .catch(error => {
            console.error('Error checking session:', error);
        });
    
    // Toggle between tenant and landlord roles
    tenantRole.addEventListener('click', function() {
        tenantRole.classList.add('active');
        landlordRole.classList.remove('active');
        signupMessage.textContent = 'Please create your tenant account.';
        signupBtn.textContent = 'Sign up as Tenant';
        activeUserType = 'tenant';
    });
    
    landlordRole.addEventListener('click', function() {
        landlordRole.classList.add('active');
        tenantRole.classList.remove('active');
        signupMessage.textContent = 'Please create your landlord account.';
        signupBtn.textContent = 'Sign up as Landlord';
        activeUserType = 'landlord';
    });
    
    // Handle registration form submission
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous error
        registerError.textContent = '';
        
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const phone = document.getElementById('phone').value;
        const termsAgreed = document.getElementById('terms').checked;
        
        // Validate form
        if (!fullName || !email || !password || !confirmPassword || !phone) {
            registerError.textContent = 'Please fill in all fields';
            return;
        }
        
        if (password !== confirmPassword) {
            registerError.textContent = 'Passwords do not match';
            return;
        }
        
        if (password.length < 8) {
            registerError.textContent = 'Password must be at least 8 characters long';
            return;
        }
        
        if (!termsAgreed) {
            registerError.textContent = 'You must agree to the terms and privacy policy';
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('fullName', fullName);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('phone', phone);
        formData.append('userType', activeUserType);
        
        // Show loading state
        signupBtn.disabled = true;
        signupBtn.textContent = 'Signing up...';
        
        // Send registration request
        fetch('../api/register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Handle response
            if (data.status === 'success') {
                // Redirect to dashboard
                window.location.href = data.redirect;
            } else {
                // Show error message
                registerError.textContent = data.message || 'Registration failed';
                signupBtn.disabled = false;
                signupBtn.textContent = activeUserType === 'tenant' ? 'Sign up as Tenant' : 'Sign up as Landlord';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            registerError.textContent = 'An error occurred. Please try again later.';
            signupBtn.disabled = false;
            signupBtn.textContent = activeUserType === 'tenant' ? 'Sign up as Tenant' : 'Sign up as Landlord';
        });
    });

    document.querySelectorAll('.social-btn').forEach(button => {
  button.addEventListener('click', function() {
    if (this.textContent.trim() === 'Continue without an account') {
      // Redirect to guest homepage
      window.location.href = '../guest/index.html';
    } else if (this.textContent.trim() === 'Google') {
      // Google login functionality (placeholder)
      alert('Google login functionality will be implemented in the future.');
    } else if (this.textContent.trim() === 'Facebook') {
      // Facebook login functionality (placeholder)
      alert('Facebook login functionality will be implemented in the future.');
    }
  });
});
    
    // Handle social login buttons (placeholders)
    document.querySelectorAll('.social-btn').forEach(button => {
        button.addEventListener('click', function() {
            alert('Social login functionality will be implemented in the future.');
        });
    });
});