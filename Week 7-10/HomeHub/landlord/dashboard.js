document.addEventListener('DOMContentLoaded', function() {
  // Toggle mobile menu
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('nav-menu-mobile');
  
  hamburger.addEventListener('click', function() {
    this.classList.toggle('active');
    mobileMenu.classList.toggle('active');
  });
  
  // Logout functionality
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutBtnMobile = document.getElementById('logoutBtnMobile');
  
  const handleLogout = function(e) {
    e.preventDefault();
    
    fetch('../api/logout.php')
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect;
        } else {
          alert('Logout failed. Please try again.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during logout. Please try again.');
      });
  };
  
  if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
  if (logoutBtnMobile) logoutBtnMobile.addEventListener('click', handleLogout);
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', function(e) {
    if (mobileMenu.classList.contains('active') && 
        !mobileMenu.contains(e.target) && 
        !hamburger.contains(e.target)) {
      mobileMenu.classList.remove('active');
      hamburger.classList.remove('active');
    }
  });
  
  // Animation for stat cards
  const statCards = document.querySelectorAll('.stat-card');
  statCards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 100 * index);
  });
});

// Notification polling
let notificationInterval;

function fetchNotificationCount() {
    fetch('../api/get-notification-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function updateNotificationBadge(count) {
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
}

// Start notification polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initial fetch
    fetchNotificationCount();
    
    // Set up polling every 30 seconds
    notificationInterval = setInterval(fetchNotificationCount, 30000);
    
    // Clean up on page unload
    window.addEventListener('unload', () => {
        clearInterval(notificationInterval);
    });
});

function updateNotificationBadge(count) {
    if (notificationBadge) {
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.style.display = 'inline-block';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
}

function updateNotificationsDropdown(notifications) {
    if (!notificationsDropdown) return;
    
    if (notifications.length === 0) {
        notificationsDropdown.innerHTML = '<div class="no-notifications">No notifications</div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        let iconClass = getNotificationIcon(notification.type);
        let timeAgo = formatTimeAgo(notification.created_at);
        
        html += `
            <div class="dropdown-notification ${notification.is_read ? 'read' : 'unread'}" 
                data-notification-id="${notification.id}" data-type="${notification.type}" data-related-id="${notification.related_id || ''}">
                <div class="notification-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.content}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    });
    
    html += `
        <div class="notification-footer">
            <a href="notifications.php" class="view-all">View All Notifications</a>
        </div>
    `;
    
    notificationsDropdown.innerHTML = html;
    
    // Add click event to notifications
    document.querySelectorAll('.dropdown-notification').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            const type = this.getAttribute('data-type');
            const relatedId = this.getAttribute('data-related-id');
            
            markNotificationAsRead(notificationId);
            
            // Navigate based on notification type
            navigateToNotificationTarget(type, relatedId);
        });
    });
}

function markNotificationAsRead(notificationId) {
    fetch('api/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    });
}

function navigateToNotificationTarget(type, relatedId) {
    switch (type) {
        case 'visit_request':
        case 'booking_request':
            window.location.href = 'bookings.php?view=' + type + '&id=' + relatedId;
            break;
        case 'property_performance':
            window.location.href = 'manage-properties.php?id=' + relatedId;
            break;
        case 'message':
            window.location.href = 'messages.php?message=' + relatedId;
            break;
        default:
            window.location.href = 'notifications.php';
    }
}

function getNotificationIcon(type) {
    switch (type) {
        case 'visit_request': return 'fa-calendar-check';
        case 'booking_request': return 'fa-file-contract';
        case 'property_performance': return 'fa-chart-line';
        case 'message': return 'fa-envelope';
        case 'system': return 'fa-bell';
        default: return 'fa-circle-info';
    }
}

function formatTimeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval > 1) {
        return interval + " years ago";
    }
    
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) {
        return interval + " months ago";
    }
    
    interval = Math.floor(seconds / 86400);
    if (interval > 1) {
        return interval + " days ago";
    }
    if (interval === 1) {
        return "yesterday";
    }
    
    interval = Math.floor(seconds / 3600);
    if (interval > 1) {
        return interval + " hours ago";
    }
    if (interval === 1) {
        return interval + " hour ago";
    }
    
    interval = Math.floor(seconds / 60);
    if (interval > 1) {
        return interval + " minutes ago";
    }
    if (interval === 1) {
        return interval + " minute ago";
    }
    
    return "just now";
}


// Start notification polling if user is logged in
if (document.querySelector('.user-greeting')) {
    // Initial fetch
    fetchNotifications();
    
    // Set up polling every 30 seconds
    notificationInterval = setInterval(fetchNotifications, 30000);
    
    // Clean up on page unload
    window.addEventListener('unload', () => {
        clearInterval(notificationInterval);
    });
}