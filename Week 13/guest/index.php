<?php 
require_once __DIR__ . '/../config/env.php';
initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HomeHub AI - Smart Rental Platform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include '../includes/admin-preview-banner.php'; ?>
  
  <!-- Navigation Header -->
  <nav class="navbar">
    <div class="nav-container">
      <!-- Logo -->
      <div class="nav-logo">
        <img src="../assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
      </div>
      
      <!-- Desktop Navigation (hidden on mobile) -->
      <div class="nav-center">
        <a href="index.html" class="nav-link active">Home</a>
        <a href="../properties.php" class="nav-link">Properties</a>
        <a href="../login/login.html" class="nav-link">Dashboard</a>
        <a href="../bookings.php" class="nav-link">Bookings</a>
        <a href="../login/login.html" class="nav-link">History</a>
        <a href="../ai-features.php" class="nav-link">AI Features</a>
      </div>
      
      <!-- Desktop Buttons (hidden on mobile) -->
      <div class="nav-right">
        <a href="../login/login.html" class="btn-login">Login</a>
        <a href="../login/register.html" class="btn-signup">Sign Up</a>
      </div>
      
      <!-- Mobile Navigation Buttons -->
      <div class="nav-buttons-mobile">
        <a href="../login/login.html" class="btn-login-mobile">Login</a>
        <a href="../login/register.html" class="btn-signup-mobile">Sign Up</a>
      </div>
      
      <!-- Hamburger Menu -->
      <div class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    
    <!-- Mobile Menu -->
    <div class="nav-menu-mobile" id="nav-menu-mobile">
      <a href="index.html" class="nav-link-mobile active">Home</a>
      <a href="../properties.php" class="nav-link-mobile">Properties</a>
      <a href="../login/login.html" class="nav-link-mobile">Dashboard</a>
      <a href="../bookings.php" class="nav-link-mobile">Bookings</a>
      <a href="../login/login.html" class="nav-link-mobile">History</a>
      <a href="../ai-features.php" class="nav-link-mobile">AI Features</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <main class="main-content">
    <section class="hero">
      <div class="hero-container">
        <div class="hero-content">
          <h1 class="hero-title">Find Your Perfect Home with AI</h1>
          <p class="hero-subtitle">
            HomeHub AI connects tenants and landlords through intelligent
            matching, making rental experiences seamless and personalized.
          </p>
          <div class="hero-buttons">
        <a href="../properties.php" class= "btn-primary" class="nav-link">Properties</a>
            <a href="../login/register.html" class="btn-secondary">Get Started</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
      <div class="features-container">
        <div class="feature-card">
          <div class="feature-icon">🤖</div>
          <h3 class="feature-title">AI-Powered Matching</h3>
          <p class="feature-description">
            HomeHub AI connects tenants and landlords through intelligent
            matching, making rental experiences seamless and personalized.
          </p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">🔍</div>
          <h3 class="feature-title">Smart Search</h3>
          <p class="feature-description">
            Advanced filters and personalized recommendations help you
            discover properties you'll love.
          </p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3 class="feature-title">Predictive Analytics</h3>
          <p class="feature-description">
            Landlords get insights on demand forecasting, optimal pricing, and
            property performance.
          </p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3 class="feature-title">Seamless Communication</h3>
          <p class="feature-description">
            Integrated messaging and booking system streamlines tenant-landlord
            interactions.
          </p>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
      <div class="stats-container">
        <div class="stat-item">
          <div class="stat-number">10,000+</div>
          <div class="stat-label">Properties Listed</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">5,000+</div>
          <div class="stat-label">Happy Tenants</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">2,500+</div>
          <div class="stat-label">Landlords</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">98%</div>
          <div class="stat-label">Match Success</div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
      <div class="cta-container">
        <h2 class="cta-title">Ready to Find Your Perfect Match?</h2>
        <p class="cta-subtitle">Join thousands of satisfied tenants and landlords using HomeHub AI</p>
        <div class="cta-buttons">
          <a href="../login/register.html" class="btn-cta-primary">I'm a Tenant</a>
          <a href="../login/register.html" class="btn-cta-secondary">I'm a Landlord</a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-container">
        <div class="footer-content">
          <div class="footer-section">
            <div class="footer-logo">
              <span class="footer-logo-text">HomeHub AI</span>
            </div>
            <p class="footer-description">
              Revolutionizing the rental market with AI-powered matching and seamless experiences.
            </p>
          </div>
          <div class="footer-section">
            <h4 class="footer-title">Platform</h4>
<a href="../properties.php" class="nav-link">Properties</a>
            <a href="../ai-features.php" class="footer-link">AI Features</a>
            <a href="pricing.html" class="footer-link">Pricing</a>
          </div>
          <div class="footer-section">
            <h4 class="footer-title">Support</h4>
            <a href="help.html" class="footer-link">Help Center</a>
            <a href="contact.html" class="footer-link">Contact Us</a>
            <a href="faq.html" class="footer-link">FAQ</a>
          </div>
          <div class="footer-section">
            <h4 class="footer-title">Legal</h4>
            <a href="privacy.html" class="footer-link">Privacy Policy</a>
            <a href="terms.html" class="footer-link">Terms of Service</a>
            <a href="cookies.html" class="footer-link">Cookie Policy</a>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2025 HomeHub AI. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </main>

  <script src="script.js"></script>
</body>
</html>