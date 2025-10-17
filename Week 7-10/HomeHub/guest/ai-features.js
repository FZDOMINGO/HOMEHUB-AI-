// Enhanced Mobile menu functionality with debugging
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    const hamburger = document.getElementById('hamburger');
    const navMenuMobile = document.getElementById('nav-menu-mobile');
    const modalOverlay = document.getElementById('modalOverlay');

    console.log('Hamburger element:', hamburger);
    console.log('Nav menu mobile element:', navMenuMobile);

    // Load and update user profile information
    loadUserProfile();
    
    // Profile dropdown functionality
    const profileBtn = document.getElementById('profile-btn');
    const dropdownMenu = document.getElementById('dropdown-menu');
    
    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            console.log('Profile dropdown toggled');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.profile-dropdown')) {
                dropdownMenu.classList.remove('show');
            }
        });

        document.querySelectorAll('.dropdown-item[data-section]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.currentTarget.dataset.section;
                
                // If section indicates dashboard navigation
                if (section && section !== 'logout') {
                    // Navigate to dashboard with specified section
                    window.location.href = `dashboard.html?section=${section}`;
                }
                
                dropdownMenu.classList.remove('show');
            });
        });
        
        // Logout functionality
        const logoutBtn = document.querySelector('.logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                handleLogout();
            });
        }
    }

    // Check if elements exist before adding event listeners
    if (hamburger && navMenuMobile) {
        console.log('Both elements found, adding event listeners');
        
        // Mobile menu toggle
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Hamburger clicked successfully!');
            
            const isActive = hamburger.classList.contains('active');
            console.log('Current active state:', isActive);
            
            hamburger.classList.toggle('active');
            navMenuMobile.classList.toggle('active');
            
            console.log('New active state:', hamburger.classList.contains('active'));
            
            // Prevent body scroll when menu is open
            if (navMenuMobile.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
                console.log('Menu opened, body scroll disabled');
            } else {
                document.body.style.overflow = 'auto';
                console.log('Menu closed, body scroll enabled');
            }
        });

        // Alternative event listener for testing
        hamburger.addEventListener('touchstart', function(e) {
            console.log('Touch start detected on hamburger');
        });

        hamburger.addEventListener('mousedown', function(e) {
            console.log('Mouse down detected on hamburger');
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link-mobile').forEach(link => {
            link.addEventListener('click', function() {
                console.log('Mobile nav link clicked');
                hamburger.classList.remove('active');
                navMenuMobile.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navMenuMobile.contains(e.target)) {
                hamburger.classList.remove('active');
                navMenuMobile.classList.remove('active');
                document.body.style.overflow = 'auto';
                console.log('Clicked outside, menu closed');
            }
        });
    } else {
        console.error('Hamburger or nav menu mobile element not found!');
        console.error('Hamburger:', hamburger);
        console.error('Nav menu mobile:', navMenuMobile);
    }

    // Close modal when clicking outside
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // Animate feature cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.feature-card').forEach(card => {
        observer.observe(card);
    });
});

// Function to load and update user profile
function loadUserProfile() {
    // Try to get user data from localStorage
    let currentUser = null;
    
    try {
        const userData = localStorage.getItem('user');
        if (userData) {
            currentUser = JSON.parse(userData);
            console.log('User data loaded from localStorage:', currentUser);
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
    
    // If no user data in localStorage, use default user
    if (!currentUser) {
        const defaultUser = {
            first_name: 'Mayrielle',
            last_name: '',
            profile_picture: null
        };
        
        // Try to fetch from API (simplified for demonstration)
        fetch('../backend/api/tenant/profile.php?action=get', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentUser = result.profile;
                localStorage.setItem('user', JSON.stringify(currentUser));
            } else {
                currentUser = defaultUser;
            }
            updateProfileDisplay(currentUser);
        })
        .catch(error => {
            console.error('API Error:', error);
            currentUser = defaultUser;
            updateProfileDisplay(currentUser);
        });
    } else {
        updateProfileDisplay(currentUser);
    }
}

// Function to update profile display
function updateProfileDisplay(user) {
    if (!user) return;
    
    // Update profile name
    const profileName = document.querySelector('.profile-name');
    if (profileName) {
        const displayName = user.first_name + (user.last_name ? ' ' + user.last_name : '');
        profileName.textContent = displayName;
    }
    
    // Update profile image
    const profileImg = document.querySelector('.profile-img');
    if (profileImg) {
        let imageSrc = '../assets/default-avatar.png';
        
        if (user.profile_picture) {
            imageSrc = user.profile_picture.startsWith('/') || user.profile_picture.startsWith('http') 
                ? user.profile_picture 
                : '../backend/' + user.profile_picture;
        }
        
        // Test if image exists
        const testImg = new Image();
        testImg.onload = () => {
            profileImg.src = imageSrc;
        };
        testImg.onerror = () => {
            // Fallback to default avatar
            profileImg.src = '../assets/default-avatar.png';
        };
        testImg.src = imageSrc;
    }
}

// Logout handler function
function handleLogout() {
    if (!confirm('Are you sure you want to logout?')) return;

    try {
        fetch('./backend/auth/logout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(() => {
            localStorage.clear();
            sessionStorage.clear();
            
            // Show logout message
            const toast = document.createElement('div');
            toast.className = 'mayrielle-toast mayrielle-toast-success';
            toast.textContent = '👋 Logged out successfully!';
            
            Object.assign(toast.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '1rem 1.5rem',
                borderRadius: '12px',
                color: 'white',
                fontWeight: '600',
                zIndex: '10000',
                backgroundColor: '#10b981',
                boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                transform: 'translateY(-20px)',
                opacity: '0',
                transition: 'all 0.4s ease',
                maxWidth: '350px',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255,255,255,0.1)'
            });

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            }, 10);

            setTimeout(() => {
                window.location.href = './pages/login.html';
            }, 1000);
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// TEST FUNCTION - Call this from browser console to test
function testHamburger() {
    console.log('Testing hamburger manually...');
    const hamburger = document.getElementById('hamburger');
    const navMenuMobile = document.getElementById('nav-menu-mobile');
    
    if (hamburger && navMenuMobile) {
        hamburger.classList.toggle('active');
        navMenuMobile.classList.toggle('active');
        console.log('Manual toggle successful');
    } else {
        console.error('Elements not found in manual test');
    }
}

// Modal functionality
function openModal(featureType) {
    const modal = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    let title = '';
    let content = '';
    
    switch(featureType) {
        case 'tenant-matching':
            title = 'Intelligent Tenant Matching';
            content = generateTenantMatchingContent();
            break;
        case 'property-recommendations':
            title = 'Smart Property Recommendations';
            content = generatePropertyRecommendationsContent();
            break;
        case 'predictive-analytics':
            title = 'Predictive Analytics Dashboard';
            content = generatePredictiveAnalyticsContent();
            break;
    }
    
    modalTitle.textContent = title;
    modalContent.innerHTML = content;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('modalOverlay');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

function generateTenantMatchingContent() {
    return `
        <div class="matching-results">
            <h3>AI Matching Results</h3>
            <p>Our AI has analyzed your preferences and found these perfect matches:</p>
            
            <div class="match-item">
                <div class="match-details">
                    <h4>Modern Studio Apartment - Makati</h4>
                    <p>₱25,000/month • 1BR, 1BA<br>• Near MRT</p>
                </div>
                <div class="match-score">90%</div>
            </div>
            
            <div class="match-item">
                <div class="match-details">
                    <h4>Luxury Penthouse - Ortigas</h4>
                    <p>₱45,000/month • 3BR, 3BA<br>• Premium amenities</p>
                </div>
                <div class="match-score">80%</div>
            </div>
            
            <div class="match-item">
                <div class="match-details">
                    <h4>Cozy 2BR Condo - BGC</h4>
                    <p>₱35,000/month • 2BR, 2BA<br>• High-rise building</p>
                </div>
                <div class="match-score">70%</div>
            </div>
            
            <p style="margin-top: 24px; color: #666; font-size: 14px;">
                Matching based on budget, location preferences, lifestyle, and previous interactions
            </p>
        </div>
    `;
}

function generatePropertyRecommendationsContent() {
    return `
        <div class="recommendations-content">
            <h3>Personalized for You</h3>
            <p>Based on your browsing history and saved properties:</p>
            
            <div class="property-grid">
                <div class="property-item">
                    <i class="fas fa-building"></i>
                    <h4>Downtown Loft</h4>
                    <p>Similar to your saved items</p>
                </div>
                <div class="property-item">
                    <i class="fas fa-home"></i>
                    <h4>Garden House</h4>
                    <p>Matches your preferences</p>
                </div>
                <div class="property-item">
                    <i class="fas fa-city"></i>
                    <h4>Executive Suite</h4>
                    <p>Within your budget range</p>
                </div>
                <div class="property-item">
                    <i class="fas fa-tree"></i>
                    <h4>Modern Villa</h4>
                    <p>Premium recommendation</p>
                </div>
            </div>
            
            <p style="color: #666; font-size: 14px; text-align: center;">
                Recommendations improve as you interact with more properties
            </p>
        </div>
    `;
}

function generatePredictiveAnalyticsContent() {
    return `
        <div class="analytics-content">
            <h3>AI-Powered Insights</h3>
            <p>Comprehensive analytics for your properties:</p>
            
            <div class="analytics-stats">
                <div class="stat-item">
                    <span class="stat-value">₱35K</span>
                    <div class="stat-label">Optimal Rent Price</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value">92%</span>
                    <div class="stat-label">Expected Occupancy</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value">14 Days</span>
                    <div class="stat-label">Time to Rent</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value">High</span>
                    <div class="stat-label">Market Demand</div>
                </div>
            </div>
            
            <div class="trend-info">
                <h4>Market Trends</h4>
                <ul>
                    <li><i class="fas fa-arrow-up"></i> Rental demand increasing by 12% in your area</li>
                    <li><i class="fas fa-chart-line"></i> Similar properties renting 20% faster</li>
                    <li><i class="fas fa-star"></i> Peak season approaching for better visibility</li>
                    <li><i class="fas fa-bullseye"></i> Your pricing strategy has 95% success rate</li>
                </ul>
            </div>
        </div>
    `;
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading animation to buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-feature').forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.textContent;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.disabled = true;
            
            setTimeout(() => {
                this.textContent = originalText;
                this.disabled = false;
            }, 1000);
        });
    });
});

// Keyboard navigation for modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Add hover effects to feature cards
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Dynamic content updates based on user interaction
let userInteractions = 0;

function trackInteraction() {
    userInteractions++;
    if (userInteractions >= 3) {
        showEngagementBonus();
    }
}

function showEngagementBonus() {
    const bonus = document.createElement('div');
    bonus.className = 'engagement-bonus';
    bonus.innerHTML = `
        <div style="background: #8b5cf6; color: white; padding: 16px; border-radius: 8px; margin: 16px 0; text-align: center;">
            🎉 Great engagement! You've unlocked premium AI insights.
        </div>
    `;
    
    const firstFeatureCard = document.querySelector('.feature-card');
    if (firstFeatureCard) {
        firstFeatureCard.appendChild(bonus);
        
        setTimeout(() => {
            if (bonus.parentNode) {
                bonus.remove();
            }
        }, 5000);
    }
}

// Add click tracking to feature buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-feature').forEach(button => {
        button.addEventListener('click', trackInteraction);
    });
});