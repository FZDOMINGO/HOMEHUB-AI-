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
        
        // Use default user (profile loaded from PHP session in the main page)
        currentUser = defaultUser;
        updateProfileDisplay(currentUser);
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
            content = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading AI matches...</div>';
            modalTitle.textContent = title;
            modalContent.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            // Fetch real AI matches
            fetchAIMatches();
            break;
        case 'property-recommendations':
            title = 'Smart Property Recommendations';
            content = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading recommendations...</div>';
            modalTitle.textContent = title;
            modalContent.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            // Fetch real recommendations
            fetchRecommendations();
            break;
        case 'predictive-analytics':
            title = 'Predictive Analytics Dashboard';
            content = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading analytics...</div>';
            modalTitle.textContent = title;
            modalContent.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            // Fetch real analytics
            fetchAnalytics();
            break;
    }
}

// Fetch real AI matches from backend
async function fetchAIMatches() {
    const modalContent = document.getElementById('modalContent');
    
    try {
        const response = await fetch('api/ai/get-matches.php', {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', responseText.substring(0, 200));
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success && data.matches && data.matches.length > 0) {
            modalContent.innerHTML = generateTenantMatchingContent(data.matches);
        } else if (data.error === 'no_preferences') {
            modalContent.innerHTML = `
                <div class="no-preferences">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                    <h3>Set Your Preferences First</h3>
                    <p>To get AI-powered property matches, please set your preferences first.</p>
                    <a href="tenant/setup-preferences.php" class="btn-feature" style="display: inline-block; margin-top: 20px;">
                        Set Preferences Now
                    </a>
                </div>
            `;
        } else if (!data.success) {
            // Show server error message
            modalContent.innerHTML = `
                <div class="no-matches">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                    <h3>Error</h3>
                    <p>${data.message || 'An error occurred while fetching matches.'}</p>
                </div>
            `;
        } else {
            modalContent.innerHTML = `
                <div class="no-matches">
                    <i class="fas fa-search" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
                    <h3>No Matches Found</h3>
                    <p>We couldn't find any properties matching your preferences at the moment.</p>
                    <p style="color: #666; margin-top: 10px;">Try adjusting your preferences or check back later.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching AI matches:', error);
        modalContent.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                <h3>Unable to Load AI Matches</h3>
                <p>There was an error connecting to the AI service.</p>
                <p style="color: #666; margin-top: 10px;">Error: ${error.message}</p>
                <p style="color: #666; margin-top: 5px;">Please check the browser console for details.</p>
            </div>
        `;
    }
}

// Fetch recommendations from backend
async function fetchRecommendations() {
    const modalContent = document.getElementById('modalContent');
    
    try {
        const response = await fetch('api/ai/get-recommendations.php', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        console.log('Recommendations API response:', data);
        
        if (data.success && data.recommendations && data.recommendations.length > 0) {
            modalContent.innerHTML = generatePropertyRecommendationsContent(data.recommendations);
        } else if (data.error === 'not_logged_in') {
            modalContent.innerHTML = `
                <div class="no-preferences">
                    <i class="fas fa-sign-in-alt" style="font-size: 48px; color: #6c63ff; margin-bottom: 20px;"></i>
                    <h3>Login Required</h3>
                    <p>Please log in to get personalized property recommendations.</p>
                    <a href="login/login.html" class="btn-feature" style="display: inline-block; margin-top: 20px;">
                        Login Now
                    </a>
                </div>
            `;
        } else {
            console.log('No recommendations or other case:', data);
            modalContent.innerHTML = `
                <div class="no-matches">
                    <i class="fas fa-search" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
                    <h3>No Recommendations Yet</h3>
                    <p>Start browsing properties to get personalized recommendations!</p>
                    <a href="properties.php" class="btn-feature" style="display: inline-block; margin-top: 20px;">
                        Browse Properties
                    </a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching recommendations:', error);
        modalContent.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                <h3>Unable to Load Recommendations</h3>
                <p>There was an error fetching your recommendations.</p>
                <p style="color: #666; margin-top: 10px;">Please try again later.</p>
            </div>
        `;
    }
}

// Fetch analytics from backend
async function fetchAnalytics() {
    const modalContent = document.getElementById('modalContent');
    
    try {
        const response = await fetch('api/ai/get-analytics.php', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.analytics) {
            modalContent.innerHTML = generatePredictiveAnalyticsContent(data.analytics);
        } else if (data.error === 'not_logged_in') {
            modalContent.innerHTML = `
                <div class="no-preferences">
                    <i class="fas fa-sign-in-alt" style="font-size: 48px; color: #6c63ff; margin-bottom: 20px;"></i>
                    <h3>Login Required</h3>
                    <p>Please log in as a landlord to view analytics.</p>
                    <a href="login/login.html" class="btn-feature" style="display: inline-block; margin-top: 20px;">
                        Login Now
                    </a>
                </div>
            `;
        } else if (data.error === 'invalid_user_type') {
            modalContent.innerHTML = `
                <div class="no-preferences">
                    <i class="fas fa-user-slash" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                    <h3>Landlords Only</h3>
                    <p>Analytics are only available for landlord accounts.</p>
                </div>
            `;
        } else {
            modalContent.innerHTML = `
                <div class="no-matches">
                    <i class="fas fa-chart-line" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
                    <h3>No Data Available</h3>
                    <p>Not enough data to generate analytics yet.</p>
                    <p style="color: #666; margin-top: 10px;">Add properties to start tracking performance.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching analytics:', error);
        modalContent.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 20px;"></i>
                <h3>Unable to Load Analytics</h3>
                <p>There was an error fetching analytics data.</p>
                <p style="color: #666; margin-top: 10px;">Please try again later.</p>
            </div>
        `;
    }
}

function closeModal() {
    const modal = document.getElementById('modalOverlay');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

function generateTenantMatchingContent(matches = null) {
    // If no matches provided, show demo data
    if (!matches) {
        return `
            <div class="matching-results">
                <h3>AI Matching Demo</h3>
                <p>Log in as a tenant and set your preferences to see real AI-powered matches!</p>
                
                <div class="match-item">
                    <div class="match-details">
                        <h4>Modern Studio Apartment - Makati</h4>
                        <p>₱25,000/month • 1BR, 1BA<br>• Near MRT</p>
                    </div>
                    <div class="match-score">90%</div>
                </div>
                
                <p style="margin-top: 24px; color: #666; font-size: 14px;">
                    <a href="login/login.html" style="color: #6c63ff; text-decoration: underline;">Login</a> to see personalized matches
                </p>
            </div>
        `;
    }
    
    // Generate real matching results
    let matchesHTML = matches.map(match => {
        const score = Math.round(match.match_score * 100);
        const scoreClass = score >= 80 ? 'high' : score >= 60 ? 'medium' : 'low';
        
        return `
            <div class="match-item">
                <div class="match-details">
                    <h4>${match.title || 'Property #' + match.property_id}</h4>
                    <p>₱${Number(match.rent_amount).toLocaleString()}/month • 
                       ${match.bedrooms}BR, ${match.bathrooms}BA<br>
                       • ${match.city || match.location}</p>
                    ${match.feature_breakdown ? `
                        <div class="match-breakdown" style="font-size: 12px; color: #666; margin-top: 8px;">
                            Budget: ${Math.round((match.feature_breakdown.budget || 0) * 100)}% • 
                            Location: ${Math.round((match.feature_breakdown.location || 0) * 100)}% • 
                            Type: ${Math.round((match.feature_breakdown.property_type || 0) * 100)}% • 
                            Size: ${Math.round((match.feature_breakdown.size || 0) * 100)}%
                        </div>
                    ` : ''}
                </div>
                <div class="match-score ${scoreClass}">${score}%</div>
            </div>
        `;
    }).join('');
    
    return `
        <div class="matching-results">
            <h3>Your AI-Powered Matches</h3>
            <p>Our AI has analyzed your preferences and found these perfect matches:</p>
            
            ${matchesHTML}
            
            <p style="margin-top: 24px; color: #666; font-size: 14px;">
                Matching based on budget, location preferences, lifestyle, and amenity priorities
            </p>
            
            <a href="properties.php?ai_match=true" class="btn-feature" style="display: inline-block; margin-top: 15px;">
                View All Matched Properties
            </a>
        </div>
    `;
}

function generatePropertyRecommendationsContent(recommendations = null) {
    if (!recommendations || recommendations.length === 0) {
        return `
            <div class="recommendations-content">
                <h3>No Recommendations Yet</h3>
                <p>Start browsing properties to get personalized recommendations!</p>
            </div>
        `;
    }
    
    let recsHTML = recommendations.map(rec => {
        return `
            <div class="recommendation-card">
                <div class="rec-icon">
                    <i class="fas fa-${rec.property_type === 'apartment' ? 'building' : rec.property_type === 'house' ? 'home' : 'city'}"></i>
                </div>
                <div class="rec-details">
                    <h4>${rec.title || 'Property #' + rec.id}</h4>
                    <p>₱${Number(rec.rent_amount).toLocaleString()}/month • 
                       ${rec.bedrooms}BR, ${rec.bathrooms}BA</p>
                    <p class="rec-location"><i class="fas fa-map-marker-alt"></i> ${rec.city || rec.location}</p>
                    <span class="rec-reason">${rec.recommendation_reason || 'Recommended for you'}</span>
                </div>
                <a href="property-detail.php?id=${rec.id}" class="btn-view-rec">View</a>
            </div>
        `;
    }).join('');
    
    return `
        <div class="recommendations-content">
            <h3>Personalized for You</h3>
            <p>Based on your browsing history and preferences:</p>
            
            ${recsHTML}
            
            <p style="color: #666; font-size: 14px; text-align: center; margin-top: 20px;">
                Recommendations improve as you interact with more properties
            </p>
            
            <a href="properties.php" class="btn-feature" style="display: inline-block; margin-top: 15px;">
                Browse More Properties
            </a>
        </div>
    `;
}

function generatePredictiveAnalyticsContent(analytics = null) {
    if (!analytics) {
        return `
            <div class="analytics-content">
                <h3>No Analytics Available</h3>
                <p>Add properties to start tracking performance and get insights.</p>
            </div>
        `;
    }
    
    const occupancyRate = analytics.performance?.occupancy_rate || 0;
    const totalRevenue = analytics.performance?.total_revenue || 0;
    const trend = analytics.demand_forecast?.trend || 'stable';
    const trendIcon = trend === 'increasing' ? 'arrow-up' : trend === 'decreasing' ? 'arrow-down' : 'minus';
    const trendColor = trend === 'increasing' ? '#10b981' : trend === 'decreasing' ? '#ef4444' : '#6b7280';
    
    let topPropertiesHTML = '';
    if (analytics.top_viewed_properties && analytics.top_viewed_properties.length > 0) {
        topPropertiesHTML = analytics.top_viewed_properties.map(prop => `
            <div class="analytics-property-item">
                <div class="prop-info">
                    <h5>${prop.title || 'Property #' + prop.id}</h5>
                    <p>₱${Number(prop.rent_amount).toLocaleString()}/month</p>
                </div>
                <div class="prop-stats">
                    <span><i class="fas fa-eye"></i> ${prop.total_views || 0} views</span>
                    <span><i class="fas fa-users"></i> ${prop.unique_visitors || 0} visitors</span>
                </div>
            </div>
        `).join('');
    }
    
    return `
        <div class="analytics-content">
            <h3>AI-Powered Insights</h3>
            <p>Comprehensive analytics for your properties:</p>
            
            <div class="analytics-stats">
                <div class="stat-item">
                    <span class="stat-value">${analytics.properties?.total_properties || 0}</span>
                    <div class="stat-label">Total Properties</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value">${occupancyRate}%</span>
                    <div class="stat-label">Occupancy Rate</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value">₱${Number(totalRevenue).toLocaleString()}</span>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
                <div class="stat-item">
                    <span class="stat-value" style="color: ${trendColor}">
                        <i class="fas fa-${trendIcon}"></i> ${trend}
                    </span>
                    <div class="stat-label">Demand Trend</div>
                </div>
            </div>
            
            ${topPropertiesHTML ? `
                <div class="trend-info">
                    <h4>Top Viewed Properties (Last 30 Days)</h4>
                    ${topPropertiesHTML}
                </div>
            ` : ''}
            
            <div class="trend-info">
                <h4>Key Insights</h4>
                <ul>
                    <li><i class="fas fa-chart-line"></i> ${analytics.inquiries?.inquiries_this_week || 0} inquiries this week</li>
                    <li><i class="fas fa-eye"></i> Properties getting ${analytics.demand_forecast?.predicted_inquiries_next_week || 0} predicted views next week</li>
                    <li><i class="fas fa-dollar-sign"></i> Average rent: ₱${Number(analytics.revenue?.average_rent || 0).toLocaleString()}</li>
                    <li><i class="fas fa-calendar"></i> Average days to rent: ${analytics.performance?.average_days_to_rent || 'N/A'} days</li>
                </ul>
            </div>
            
            <a href="landlord/dashboard.php" class="btn-feature" style="display: inline-block; margin-top: 15px;">
                View Full Dashboard
            </a>
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
    // Removed engagement bonus message
}

// Add click tracking to feature buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-feature').forEach(button => {
        button.addEventListener('click', trackInteraction);
    });
});