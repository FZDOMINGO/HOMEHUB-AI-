document.addEventListener('DOMContentLoaded', function() {
  // Get elements
  const tenantRole = document.getElementById('tenant-role');
  const landlordRole = document.getElementById('landlord-role');
  const signinMessage = document.getElementById('signin-message');
  const signinBtn = document.getElementById('signin-btn');
  const loginForm = document.getElementById('loginForm');
  const loginError = document.getElementById('login-error');
  
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
    signinMessage.textContent = 'Please sign in to your tenant account.';
    signinBtn.textContent = 'Sign in as Tenant';
    activeUserType = 'tenant';
  });
  
  landlordRole.addEventListener('click', function() {
    landlordRole.classList.add('active');
    tenantRole.classList.remove('active');
    signinMessage.textContent = 'Please sign in to your landlord account.';
    signinBtn.textContent = 'Sign in as Landlord';
    activeUserType = 'landlord';
  });
  
  // Handle login form submission
  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous error
    loginError.textContent = '';
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('remember').checked;
    
    // Validate form
    if (!email || !password) {
      loginError.textContent = 'Please enter both email and password';
      return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    formData.append('userType', activeUserType);
    formData.append('remember', rememberMe);
    
    // Show loading state
    signinBtn.disabled = true;
    signinBtn.textContent = 'Signing in...';
    
    // Send login request
    fetch('../api/login.php', {
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
        loginError.textContent = data.message || 'Login failed';
        signinBtn.disabled = false;
        signinBtn.textContent = activeUserType === 'tenant' ? 'Sign in as Tenant' : 'Sign in as Landlord';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      loginError.textContent = 'An error occurred. Please try again later.';
      signinBtn.disabled = false;
      signinBtn.textContent = activeUserType === 'tenant' ? 'Sign in as Tenant' : 'Sign in as Landlord';
    });
  });
  
  // Handle "Continue without an account" button click
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
  
  // Handle "Forgot Password" link
  document.querySelector('.forgot-link').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Password recovery functionality will be implemented in the future.');
  });
});