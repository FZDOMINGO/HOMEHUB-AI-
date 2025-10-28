<!-- Email Preferences Section -->
<div class="settings-section">
    <h2>📧 Email Notifications</h2>
    <p class="section-description">Choose which notifications you'd like to receive via email</p>
    
    <form id="email-preferences-form">
        <div class="preference-group">
            <label class="preference-item">
                <input type="checkbox" name="receive_visit_requests" id="receive_visit_requests">
                <span class="preference-label">
                    <strong>Visit Requests</strong>
                    <span class="preference-desc">Get notified when someone requests to visit your property</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_booking_requests" id="receive_booking_requests">
                <span class="preference-label">
                    <strong>Booking/Reservation Requests</strong>
                    <span class="preference-desc">Get notified when someone wants to reserve your property</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_reservation_updates" id="receive_reservation_updates">
                <span class="preference-label">
                    <strong>Reservation Updates</strong>
                    <span class="preference-desc">Get notified about reservation status changes</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_visit_updates" id="receive_visit_updates">
                <span class="preference-label">
                    <strong>Visit Updates</strong>
                    <span class="preference-desc">Get notified about visit appointment status changes</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_property_performance" id="receive_property_performance">
                <span class="preference-label">
                    <strong>Property Performance</strong>
                    <span class="preference-desc">Get notified when your property is trending</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_messages" id="receive_messages">
                <span class="preference-label">
                    <strong>New Messages</strong>
                    <span class="preference-desc">Get notified when you receive new messages</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_system_notifications" id="receive_system_notifications">
                <span class="preference-label">
                    <strong>System Notifications</strong>
                    <span class="preference-desc">Important updates and announcements from HomeHub</span>
                </span>
            </label>
            
            <label class="preference-item">
                <input type="checkbox" name="receive_marketing" id="receive_marketing">
                <span class="preference-label">
                    <strong>Marketing & Tips</strong>
                    <span class="preference-desc">Receive tips, guides, and promotional content</span>
                </span>
            </label>
        </div>
        
        <button type="submit" class="btn-save">Save Email Preferences</button>
    </form>
</div>

<style>
.preference-group {
    margin: 20px 0;
}

.preference-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.preference-item:hover {
    background: #f0f1f3;
}

.preference-item input[type="checkbox"] {
    margin-right: 15px;
    margin-top: 3px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.preference-label {
    flex: 1;
}

.preference-label strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.preference-desc {
    display: block;
    font-size: 13px;
    color: #666;
}

.btn-save {
    background: #8b5cf6;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-save:hover {
    background: #7c3aed;
    transform: translateY(-2px);
}
</style>

<script>
// Load email preferences
async function loadEmailPreferences() {
    try {
        const response = await fetch('../api/email-preferences.php');
        const data = await response.json();
        
        if (data.success && data.preferences) {
            const prefs = data.preferences;
            document.getElementById('receive_visit_requests').checked = prefs.receive_visit_requests == 1;
            document.getElementById('receive_booking_requests').checked = prefs.receive_booking_requests == 1;
            document.getElementById('receive_reservation_updates').checked = prefs.receive_reservation_updates == 1;
            document.getElementById('receive_visit_updates').checked = prefs.receive_visit_updates == 1;
            document.getElementById('receive_property_performance').checked = prefs.receive_property_performance == 1;
            document.getElementById('receive_messages').checked = prefs.receive_messages == 1;
            document.getElementById('receive_system_notifications').checked = prefs.receive_system_notifications == 1;
            document.getElementById('receive_marketing').checked = prefs.receive_marketing == 1;
        }
    } catch (error) {
        console.error('Error loading email preferences:', error);
    }
}

// Save email preferences
document.getElementById('email-preferences-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const preferences = {
        receive_visit_requests: document.getElementById('receive_visit_requests').checked,
        receive_booking_requests: document.getElementById('receive_booking_requests').checked,
        receive_reservation_updates: document.getElementById('receive_reservation_updates').checked,
        receive_visit_updates: document.getElementById('receive_visit_updates').checked,
        receive_property_performance: document.getElementById('receive_property_performance').checked,
        receive_messages: document.getElementById('receive_messages').checked,
        receive_system_notifications: document.getElementById('receive_system_notifications').checked,
        receive_marketing: document.getElementById('receive_marketing').checked
    };
    
    try {
        const response = await fetch('../api/email-preferences.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(preferences)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Email preferences saved successfully!');
        } else {
            alert('Failed to save preferences: ' + data.message);
        }
    } catch (error) {
        console.error('Error saving email preferences:', error);
        alert('An error occurred while saving preferences');
    }
});

// Load preferences on page load
loadEmailPreferences();
</script>
