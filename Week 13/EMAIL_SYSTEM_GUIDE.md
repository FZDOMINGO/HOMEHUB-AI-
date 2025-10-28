# HomeHub Email Notification System

## Overview
Complete email notification system for HomeHub with Gmail/SMTP integration, user preferences, and beautiful HTML templates.

## Features Implemented

### 1. Email Functions (`includes/email_functions.php`)
- ✅ Visit request notifications (landlord)
- ✅ Booking/reservation request notifications (landlord)
- ✅ Reservation approved notifications (tenant)
- ✅ Visit approved notifications (tenant)
- ✅ Property performance notifications (landlord)
- ✅ New message notifications
- ✅ Welcome emails (new users)
- ✅ Beautiful HTML email templates with HomeHub branding

### 2. Email Integration
- ✅ Integrated with `process-visit.php` - Sends emails when visit requests are made
- ✅ Integrated with `process-reservation.php` - Sends emails when reservations are made
- ✅ Automatic email sending on key events

### 3. Admin Email Settings (`admin/email-settings.php`)
- ✅ SMTP configuration (host, port, username, password, encryption)
- ✅ Email identity settings (from email, from name, reply-to)
- ✅ Toggle between SMTP and PHP mail()
- ✅ Test email functionality

### 4. Email Template Preview (`admin/email-preview.php`)
- ✅ Preview all 7 email templates
- ✅ Live preview with sample data
- ✅ Easy template selection

### 5. User Email Preferences (`api/email-preferences.php`)
- ✅ GET endpoint to retrieve user preferences
- ✅ POST endpoint to update preferences
- ✅ Automatic default preferences creation
- ✅ Per-notification type preferences:
  - Visit requests
  - Booking requests
  - Reservation updates
  - Visit updates
  - Property performance
  - Messages
  - System notifications
  - Marketing

### 6. Database Tables (`sql/email_tables.sql`)
- ✅ `email_preferences` - User notification preferences
- ✅ `email_config` - SMTP and email configuration

## Setup Instructions

### Step 1: Create Database Tables
Run the setup script:
```
http://localhost/HomeHub/setup_email.php
```

### Step 2: Configure SMTP Settings (Optional but Recommended)
1. Go to: `http://localhost/HomeHub/admin/email-settings.php`
2. Configure your SMTP settings:
   - **For Gmail:**
     - SMTP Host: `smtp.gmail.com`
     - SMTP Port: `587`
     - Encryption: `TLS`
     - Username: Your Gmail address
     - Password: App Password (not regular password)
     - Enable "Use SMTP"
   
   - **For Local Testing (Default):**
     - Leave "Use SMTP" unchecked
     - Uses PHP's mail() function

### Step 3: Test Email Delivery
1. Click "Send Test Email" button in Email Settings
2. Check your inbox

### Step 4: Preview Templates
Visit: `http://localhost/HomeHub/admin/email-preview.php`
- View all email templates
- See how emails will look
- Test different scenarios

### Step 5: User Preferences (Optional)
Users can manage their email preferences in settings:
- Add `includes/email_preferences_section.php` to tenant/landlord settings pages

## Gmail App Password Setup

1. Go to Google Account Settings
2. Security > 2-Step Verification (must be enabled)
3. App Passwords
4. Generate new app password for "Mail"
5. Copy the 16-character password
6. Use this password in SMTP settings

## Email Templates

All templates feature:
- 🎨 HomeHub purple gradient header
- 📱 Mobile responsive design
- 🔗 Action buttons
- 💡 Highlighted information sections
- ✉️ Professional footer

### Available Templates:
1. **Visit Request** - Notifies landlord of new visit requests
2. **Booking Request** - Notifies landlord of new reservations
3. **Reservation Approved** - Confirms reservation to tenant
4. **Visit Approved** - Confirms visit appointment to tenant
5. **Property Performance** - Trending property alerts
6. **New Message** - Message notifications
7. **Welcome Email** - New user welcome

## API Endpoints

### Get Email Preferences
```
GET /api/email-preferences.php
Returns: {success: true, preferences: {...}}
```

### Update Email Preferences
```
POST /api/email-preferences.php
Body: {receive_visit_requests: true, receive_booking_requests: true, ...}
Returns: {success: true, message: "..."}
```

### Send Test Email
```
GET /api/test-email.php
Returns: {success: true, message: "Test email sent to..."}
```

## Files Created/Modified

### New Files:
- `includes/email_functions.php` - Email sending functions
- `api/email-preferences.php` - Email preferences API
- `api/test-email.php` - Test email endpoint
- `admin/email-settings.php` - Admin email configuration
- `admin/email-preview.php` - Email template preview
- `includes/email_preferences_section.php` - User preferences UI
- `sql/email_tables.sql` - Database schema
- `setup_email.php` - Setup script

### Modified Files:
- `process-visit.php` - Added email notifications
- `process-reservation.php` - Added email notifications

## Usage Examples

### Send Visit Request Email
```php
require_once 'includes/email_functions.php';

sendVisitRequestEmail(
    'landlord@example.com',
    'John Doe',
    'Jane Smith',
    'Modern 2BR Apartment',
    '2025-11-15',
    '14:00:00',
    123
);
```

### Send Reservation Approved Email
```php
sendReservationApprovedEmail(
    'tenant@example.com',
    'Jane Smith',
    'Modern 2BR Apartment',
    '2025-12-01',
    35000
);
```

### Check User Preferences Before Sending
```php
$stmt = $conn->prepare("SELECT receive_visit_requests FROM email_preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$prefs = $result->fetch_assoc();

if ($prefs && $prefs['receive_visit_requests']) {
    sendVisitRequestEmail(...);
}
```

## Troubleshooting

### Emails Not Sending
1. Check SMTP settings in admin panel
2. Verify Gmail App Password (if using Gmail)
3. Check PHP mail() is configured (if not using SMTP)
4. Check error logs: `C:\xampp\apache\logs\error.log`

### Emails Going to Spam
1. Configure SPF/DKIM records (production only)
2. Use a verified domain email
3. Avoid spam trigger words in subject/content

### Test on Localhost
- Use services like Mailtrap, MailHog, or PaperCut SMTP for local testing
- Or use actual Gmail/SMTP credentials

## Production Recommendations

1. **Use SMTP** - More reliable than PHP mail()
2. **Use real domain email** - Not @gmail.com
3. **Configure SPF/DKIM** - Improve deliverability
4. **Use email service** - SendGrid, Mailgun, AWS SES
5. **Monitor bounces** - Track failed deliveries
6. **Respect preferences** - Always check user preferences before sending

## Next Steps

- [ ] Add email queue system for bulk sending
- [ ] Add email analytics (open rates, click rates)
- [ ] Add email templates customization in admin
- [ ] Add scheduled email digests
- [ ] Add unsubscribe links for marketing emails

## Support

For issues or questions about the email system, contact the development team.
