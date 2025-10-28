// History Page JavaScript
let currentOffset = 0;
const limit = 20;
let currentFilter = 'all';
let currentDateFrom = '';
let currentDateTo = '';
let isLoading = false;
let hasMore = true;

document.addEventListener('DOMContentLoaded', function() {
    console.log('History page loaded');
    loadActivities();
    setupEventListeners();
});

function setupEventListeners() {
    const filterSelect = document.getElementById('activity-filter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            currentFilter = this.value;
            resetAndLoad();
        });
    }
    
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    
    if (dateFrom) {
        dateFrom.addEventListener('change', function() {
            currentDateFrom = this.value;
            resetAndLoad();
        });
    }
    
    if (dateTo) {
        dateTo.addEventListener('change', function() {
            currentDateTo = this.value;
            resetAndLoad();
        });
    }
    
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            loadActivities();
        });
    }
}

function resetAndLoad() {
    currentOffset = 0;
    hasMore = true;
    const timeline = document.getElementById('activity-timeline');
    if (timeline) {
        timeline.innerHTML = '';
    }
    loadActivities();
}

async function loadActivities() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    
    try {
        const params = new URLSearchParams({
            category: currentFilter,
            offset: currentOffset,
            limit: limit
        });
        
        if (currentDateFrom) params.append('date_from', currentDateFrom);
        if (currentDateTo) params.append('date_to', currentDateTo);
        
        console.log('Fetching activities with params:', params.toString());
        
        // Add timeout to fetch
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
        
        const response = await fetch(`api/get-history.php?${params}`, {
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        console.log('Success:', data.success);
        console.log('Activities array:', data.activities);
        console.log('Activities count:', data.activities ? data.activities.length : 0);
        console.log('Current offset:', currentOffset);
        
        if (data.success) {
            if (currentOffset === 0) {
                // Clear only activity items, not the empty state
                const timeline = document.getElementById('activity-timeline');
                const activityItems = timeline.querySelectorAll('.activity-item');
                console.log('Clearing', activityItems.length, 'existing activity items');
                activityItems.forEach(item => item.remove());
            }
            
            if (data.activities.length === 0 && currentOffset === 0) {
                console.log('No activities found - showing empty state');
                // Hide the timeline container when showing empty state
                const timeline = document.getElementById('activity-timeline');
                if (timeline) {
                    timeline.style.display = 'none';
                }
                showEmptyState();
            } else if (data.activities.length > 0) {
                console.log('Rendering', data.activities.length, 'activities');
                // Show the timeline container when rendering activities
                const timeline = document.getElementById('activity-timeline');
                if (timeline) {
                    timeline.style.display = 'block';
                }
                hideEmptyState();
                renderActivities(data.activities);
                hasMore = data.has_more;
                currentOffset += data.activities.length;
                
                const loadMoreSection = document.getElementById('load-more-section');
                if (loadMoreSection) {
                    loadMoreSection.style.display = hasMore ? 'block' : 'none';
                }
            }
        } else {
            console.error('API returned error:', data.message);
            showError(data.message || 'Failed to load activities');
        }
    } catch (error) {
        console.error('Error loading activities:', error);
        showError('An error occurred while loading activities');
    } finally {
        isLoading = false;
    }
}

function renderActivities(activities) {
    const timeline = document.getElementById('activity-timeline');
    activities.forEach(activity => {
        const elem = createActivityElement(activity);
        if (elem) timeline.appendChild(elem);
    });
}

function createActivityElement(activity) {
    const div = document.createElement('div');
    const currentUserType = typeof userType !== 'undefined' ? userType : 'guest';
    
    let activityClass = 'neutral';
    let icon = '📋';
    let title = 'Activity';
    let description = '';
    
    if (activity.activity_type === 'search_summary') {
        icon = '👁️';
        title = 'Properties Viewed';
        description = `Viewed ${activity.properties_count} properties. ${activity.saved_count} saved.`;
    } else if (activity.activity_type === 'reservation') {
        if (activity.status === 'approved') {
            activityClass = 'success';
            icon = '✅';
            title = 'Reservation Confirmed';
        } else if (activity.status === 'pending') {
            activityClass = 'warning';
            icon = '⏳';
            title = 'Reservation Pending';
        } else if (activity.status === 'cancelled') {
            activityClass = 'error';
            icon = '❌';
            title = 'Reservation Cancelled';
        }
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'visit') {
        activityClass = 'info';
        icon = '📅';
        title = 'Property Visit';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'save') {
        activityClass = 'success';
        icon = '💾';
        title = 'Property Saved';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'unsave') {
        activityClass = 'neutral';
        icon = '🗑️';
        title = 'Property Unsaved';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'contact') {
        activityClass = 'info';
        icon = '📧';
        title = 'Landlord Contacted';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'share') {
        activityClass = 'info';
        icon = '🔗';
        title = 'Property Shared';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'review') {
        activityClass = 'success';
        icon = '⭐';
        title = 'Review Posted';
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'ai_recommendation') {
        activityClass = 'info';
        icon = '🤖';
        title = 'AI Recommendations Updated';
        description = `${activity.recommendations_count} new recommendations with ${activity.avg_score}% match`;
    } else if (activity.activity_type === 'property_views') {
        activityClass = 'info';
        icon = '📊';
        title = 'Property Interest';
        description = `${activity.property_title} - ${activity.views_count} views, ${activity.saves_count} saves`;
    }
    
    const date = new Date(activity.activity_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    div.className = `activity-item ${activityClass}`;
    div.innerHTML = `
        <div class="activity-connector"></div>
        <div class="activity-icon ${activityClass}"><span>${icon}</span></div>
        <div class="activity-content">
            <div class="activity-header">
                <h3>${title}</h3>
                <span class="activity-date">${date}</span>
            </div>
            <div class="activity-description"><p>${description}</p></div>
        </div>
    `;
    
    return div;
}

function showEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const loadMoreSection = document.getElementById('load-more-section');
    
    // Show empty state
    if (emptyState) {
        emptyState.style.display = 'block';
    }
    
    // Hide load more button
    if (loadMoreSection) {
        loadMoreSection.style.display = 'none';
    }
}

function hideEmptyState() {
    const emptyState = document.getElementById('empty-state');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
}

function showError(message) {
    const timeline = document.getElementById('activity-timeline');
    timeline.innerHTML = `<div style="text-align:center;padding:40px;"><p>❌ ${message}</p></div>`;
}

function clearFilters() {
    currentFilter = 'all';
    currentDateFrom = '';
    currentDateTo = '';
    document.getElementById('activity-filter').value = 'all';
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';
    resetAndLoad();
}