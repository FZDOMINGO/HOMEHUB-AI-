document.addEventListener('DOMContentLoaded', function() {
    // Initialize hamburger menu
    initHamburgerMenu();
    
    // Load and update user profile information
    loadUserProfile();
    
    // Profile dropdown functionality
    initProfileDropdown();
    
    // Initialize filters
    initFilters();
    
    // Initialize load more functionality
    initLoadMore();
    
    // Set default date range
    setDefaultDateRange();
    
    // Add activity interactions
    initActivityInteractions();
});

// Hamburger Menu Functionality
function initHamburgerMenu() {
    const hamburger = document.getElementById('hamburger');
    const navMenuMobile = document.getElementById('nav-menu-mobile');

    if (hamburger && navMenuMobile) {
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Hamburger clicked');
            
            hamburger.classList.toggle('active');
            navMenuMobile.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (navMenuMobile.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });

        const mobileLinks = navMenuMobile.querySelectorAll('.nav-link-mobile');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navMenuMobile.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });

        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navMenuMobile.contains(e.target)) {
                hamburger.classList.remove('active');
                navMenuMobile.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }
}

// Profile dropdown functionality
function initProfileDropdown() {
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
}

// Function to load and update user profile
function loadUserProfile() {
    // Try to get user data from localStorage
    let currentUser = null;
    
    try {
        const userData = localStorage.getItem('user');
        if (userData) {
            currentUser = JSON.parse(userData);
            console.log('User data loaded from localStorage:', currentUser);
        } else {
            const currentUserData = localStorage.getItem('currentUser');
            if (currentUserData) {
                currentUser = JSON.parse(currentUserData);
                console.log('User data loaded from currentUser:', currentUser);
            }
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
            showNotification('👋 Logged out successfully!', 'success');
            
            setTimeout(() => {
                window.location.href = './pages/login.html';
            }, 1000);
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// Filter Functionality
function initFilters() {
    const activityFilter = document.getElementById('activity-filter');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');

    if (activityFilter) {
        activityFilter.addEventListener('change', applyFilters);
    }

    if (dateFrom) {
        dateFrom.addEventListener('change', applyFilters);
    }

    if (dateTo) {
        dateTo.addEventListener('change', applyFilters);
    }
}

function applyFilters() {
    const selectedCategory = document.getElementById('activity-filter').value;
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    
    const activities = document.querySelectorAll('.activity-item');
    let visibleCount = 0;

    activities.forEach(activity => {
        const category = activity.dataset.category;
        const activityDate = activity.dataset.date;
        
        let showActivity = true;

        // Category filter
        if (selectedCategory !== 'all' && category !== selectedCategory) {
            showActivity = false;
        }

        // Date range filter
        if (dateFrom && activityDate < dateFrom) {
            showActivity = false;
        }

        if (dateTo && activityDate > dateTo) {
            showActivity = false;
        }

        // Apply filter with animation
        if (showActivity) {
            activity.classList.remove('filtered-out');
            activity.classList.add('filtered-in');
            visibleCount++;
        } else {
            activity.classList.add('filtered-out');
            activity.classList.remove('filtered-in');
        }
    });

    // Show/hide empty state
    const emptyState = document.getElementById('empty-state');
    const timeline = document.querySelector('.activity-timeline');
    
    if (visibleCount === 0) {
        timeline.style.display = 'none';
        emptyState.style.display = 'block';
    } else {
        timeline.style.display = 'block';
        emptyState.style.display = 'none';
    }

    // Update load more button visibility
    updateLoadMoreVisibility();
}

function clearFilters() {
    document.getElementById('activity-filter').value = 'all';
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';
    applyFilters();
}

function setDefaultDateRange() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    // Set max date to today
    document.getElementById('date-from').max = today.toISOString().split('T')[0];
    document.getElementById('date-to').max = today.toISOString().split('T')[0];
    
    // Set default "from" date to 30 days ago
    document.getElementById('date-from').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('date-to').value = today.toISOString().split('T')[0];
}

// Load More Functionality
function initLoadMore() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreActivities);
    }
}

async function loadMoreActivities() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const originalText = loadMoreBtn.innerHTML;
    
    // Show loading state
    loadMoreBtn.innerHTML = `
        <span class="load-text">Loading...</span>
        <span class="load-icon" style="animation: spin 1s linear infinite;">⟳</span>
    `;
    loadMoreBtn.disabled = true;

    try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Generate additional activities
        const timeline = document.querySelector('.activity-timeline');
        const newActivities = generateAdditionalActivities();
        
        newActivities.forEach(activityHTML => {
            const activityElement = document.createElement('div');
            activityElement.innerHTML = activityHTML;
            timeline.appendChild(activityElement.firstElementChild);
        });

        // Reset button
        loadMoreBtn.innerHTML = originalText;
        loadMoreBtn.disabled = false;
        
        showNotification('Loaded more activities successfully!', 'success');
        
        // Re-apply current filters
        applyFilters();

    } catch (error) {
        console.error('Failed to load more activities:', error);
        showNotification('Failed to load more activities. Please try again.', 'error');
        
        loadMoreBtn.innerHTML = originalText;
        loadMoreBtn.disabled = false;
    }
}

function generateAdditionalActivities() {
    const additionalActivities = [
        {
            type: 'info',
            category: 'searches',
            date: '2025-09-16',
            title: 'Property Search Completed',
            description: 'Completed search for 1BR apartments in Manila with budget ₱25,000-₱35,000.',
            details: [
                { icon: '🔍', text: '12 Properties Found' },
                { icon: '💰', text: '₱25K-₱35K Budget' },
                { icon: '📍', text: 'Manila Area' },
                { icon: '⭐', text: '2 Properties Favorited' }
            ]
        },
        {
            type: 'success',
            category: 'ai-activity',
            date: '2025-09-15',
            title: 'AI Profile Updated',
            description: 'AI learned from your recent activities and updated your preference profile for better matches.',
            details: [
                { icon: '🤖', text: 'Profile Accuracy: 98%' },
                { icon: '📊', text: 'New preferences learned' },
                { icon: '🎯', text: 'Better targeting enabled' },
                { icon: '🔔', text: 'Smart notifications active' }
            ]
        }
    ];

    return additionalActivities.map(activity => 
        createActivityHTML(activity.type, activity.category, activity.date, activity.title, activity.description, activity.details)
    );
}

function createActivityHTML(type, category, date, title, description, details) {
    const icons = {
        success: '✅',
        info: '📅',
        neutral: '👁️',
        error: '❌',
        warning: '⏳',
        ai: '🤖'
    };

    const detailsHTML = details.map(detail => 
        `<div class="detail-item">
            <span class="detail-icon">${detail.icon}</span>
            <span class="detail-text">${detail.text}</span>
        </div>`
    ).join('');

    return `
        <div class="activity-item ${type}" data-category="${category}" data-date="${date}">
            <div class="activity-connector"></div>
            <div class="activity-icon ${type}">
                <span>${icons[type]}</span>
            </div>
            <div class="activity-content">
                <div class="activity-header">
                    <h3>${title}</h3>
                    <span class="activity-date">${formatDate(date)}</span>
                </div>
                <div class="activity-description">
                    <p>${description}</p>
                </div>
                <div class="activity-details">
                    ${detailsHTML}
                </div>
                <div class="activity-actions">
                    <button class="action-btn primary">View Details</button>
                    <button class="action-btn secondary">Related Actions</button>
                </div>
            </div>
        </div>
    `;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function updateLoadMoreVisibility() {
    const visibleActivities = document.querySelectorAll('.activity-item:not(.filtered-out)');
    const loadMoreBtn = document.getElementById('load-more-btn');
    
    // Hide load more if few activities are visible
    if (visibleActivities.length < 3) {
        loadMoreBtn.style.display = 'none';
    } else {
        loadMoreBtn.style.display = 'flex';
    }
}

// Activity Interactions
function initActivityInteractions() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('action-btn')) {
            handleActivityAction(e.target);
        }
    });
}

function handleActivityAction(button) {
    const action = button.textContent.trim();
    const activityItem = button.closest('.activity-item');
    const activityTitle = activityItem.querySelector('.activity-header h3').textContent;

    // Handle different actions
    switch(action) {
        case 'View Details':
            showNotification(`Opening details for: ${activityTitle}`, 'info');
            break;
        case 'Contact Landlord':
            showNotification('Opening landlord contact information...', 'info');
            break;
        case 'View Property':
            showNotification('Redirecting to property page...', 'info');
            break;
        case 'Reschedule':
            showNotification('Opening reschedule options...', 'info');
            break;
        case 'View Saved Properties':
            showNotification('Loading your saved properties...', 'info');
            break;
        case 'New Search':
            showNotification('Starting new property search...', 'info');
            break;
        case 'View Alternatives':
            showNotification('Loading alternative properties...', 'info');
            break;
        case 'Find Similar':
            showNotification('Searching for similar properties...', 'info');
            break;
        case 'View Recommendations':
            showNotification('Loading AI recommendations...', 'info');
            break;
        case 'Update Preferences':
            showNotification('Opening preference settings...', 'info');
            break;
        case 'Check Status':
            showNotification('Checking application status...', 'info');
            break;
        case 'Upload Documents':
            showNotification('Opening document upload...', 'info');
            break;
        default:
            showNotification(`Action: ${action}`, 'info');
    }
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">
                ${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}
            </span>
            <span class="notification-message">${message}</span>
        </div>
    `;

    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 90px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 1rem;
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 400px;
            }
            .notification.show {
                transform: translateX(0);
            }
            .notification.success {
                border-left: 4px solid #10b981;
            }
            .notification.error {
                border-left: 4px solid #ef4444;
            }
            .notification.info {
                border-left: 4px solid #3b82f6;
            }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .notification-message {
                font-size: 0.9rem;
                color: #374151;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @media (max-width: 480px) {
                .notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                    top: 80px;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Initialize user data if not present
function initializeUserData() {
    const userData = {
        name: 'Mayrielle',
        userId: 'user_mayrielle_' + Date.now(),
        joinDate: '2025-09-01',
        preferences: {
            budget: { min: 25000, max: 45000 },
            location: ['Makati', 'BGC', 'Ortigas'],
            type: ['Studio', '1BR', '2BR'],
            amenities: ['Parking', 'Gym', 'Pool']
        }
    };
    
    if (!localStorage.getItem('currentUser')) {
        localStorage.setItem('currentUser', JSON.stringify(userData));
    }
}

// Initialize on load
initializeUserData();