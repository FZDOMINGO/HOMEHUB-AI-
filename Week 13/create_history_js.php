<?php
$content = <<<'EOD'
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
        timeline.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>Loading...</p></div>';
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
        
        const response = await fetch(`api/get-history.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            if (currentOffset === 0) {
                document.getElementById('activity-timeline').innerHTML = '';
            }
            
            if (data.activities.length === 0 && currentOffset === 0) {
                showEmptyState();
            } else {
                renderActivities(data.activities);
                hasMore = data.has_more;
                currentOffset += data.activities.length;
                
                const loadMoreSection = document.getElementById('load-more-section');
                if (loadMoreSection) {
                    loadMoreSection.style.display = hasMore ? 'block' : 'none';
                }
                hideEmptyState();
            }
        } else {
            showError(data.message || 'Failed to load activities');
        }
    } catch (error) {
        console.error('Error:', error);
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
        }
        description = `${activity.property_title} - ${activity.property_city}`;
    } else if (activity.activity_type === 'visit') {
        activityClass = 'info';
        icon = '📅';
        title = 'Property Visit';
        description = `${activity.property_title} - ${activity.property_city}`;
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
    document.getElementById('empty-state').style.display = 'block';
    document.getElementById('load-more-section').style.display = 'none';
}

function hideEmptyState() {
    document.getElementById('empty-state').style.display = 'none';
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
EOD;

file_put_contents('assets/js/history.js', $content);
echo "File created successfully!";
?>
