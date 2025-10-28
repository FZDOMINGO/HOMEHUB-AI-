# 📧 HomeHub Email Notifications - How It Works

## Overview
The email notification system automatically sends emails to landlords and tenants when important events occur in your HomeHub application. All emails are sent via Gmail SMTP using your configured account.

---

## 🔄 Automatic Email Triggers

### 1. **Visit Request Notifications**
**When it happens:** A tenant requests to visit a property

**Process Flow:**
1. Tenant fills out visit request form on property detail page
2. Form submits to `process-visit.php`
3. System saves the visit request to the database
4. System automatically calls `sendVisitRequestEmail()` function
5. Email is sent to the **landlord** who owns the property

**Email Contains:**
- Tenant's name
- Property title
- Requested visit date and time
- Link to landlord's bookings dashboard to approve/decline

**Code Location:** `process-visit.php` (line 146)
```php
sendVisitRequestEmail($landlordEmail, $landlordName, $tenantName, 
                      $propertyTitle, $visitDate, $visitTime, $visitId);
```

---

### 2. **Booking/Reservation Request Notifications**
**When it happens:** A tenant submits a reservation/booking request for a property

**Process Flow:**
1. Tenant fills out booking request form
2. Form submits to `process-reservation.php`
3. System saves the reservation to the database
4. System automatically calls `sendBookingRequestEmail()` function
5. Email is sent to the **landlord** who owns the property

**Email Contains:**
- Tenant's name
- Property title
- Requested move-in date
- Lease duration (months)
- Link to landlord's bookings dashboard to approve/decline

**Code Location:** `process-reservation.php` (line 219)
```php
sendBookingRequestEmail($landlordEmail, $landlordName, $tenantName, 
                        $propertyTitle, $moveInDate, $leaseDuration, $reservationId);
```

---

## 🔧 How the Email System Works

### Backend Process:

1. **Configuration Check**
   - System reads settings from `email_config` table in database
   - Checks if SMTP is enabled (`use_smtp = 1`)
   - Retrieves Gmail SMTP settings (host, port, username, password)

2. **Email Function Called**
   - When an event happens (visit request, booking, etc.)
   - Application calls appropriate email function (e.g., `sendVisitRequestEmail()`)
   - Function is located in `includes/email_functions.php`

3. **Email Template Generation**
   - Function creates HTML email content using `getEmailTemplate()`
   - Template includes:
     - Professional HomeHub header/branding
     - Event-specific content (property details, dates, etc.)
     - Action buttons (View Request, Dashboard links)
     - Footer with unsubscribe/settings links

4. **Email Sending via Gmail**
   - System uses PHPMailer library
   - Connects to Gmail SMTP (smtp.gmail.com:587)
   - Authenticates with your Gmail account and App Password
   - Sends HTML email to recipient
   - Logs success/failure to PHP error log

5. **Silent Operation**
   - Emails send in the background
   - Users see their success message (e.g., "Visit request submitted!")
   - Email notification happens automatically without user interaction

---

## 📬 Email Types & Recipients

| Event | Email Type | Recipient | Function |
|-------|-----------|-----------|----------|
| Tenant requests visit | Visit Request | Landlord | `sendVisitRequestEmail()` |
| Tenant books property | Booking Request | Landlord | `sendBookingRequestEmail()` |
| User registers | Welcome Email | New User | `sendWelcomeEmail()` |
| Request approved | Approval Notice | Tenant | (Future) |
| Property performance | Monthly Report | Landlord | (Future) |
| New message | Message Alert | Recipient | (Future) |

---

## 🎯 User Experience

### For Tenants:
1. Tenant browses properties and finds one they like
2. Clicks "Request Visit" or "Book Now"
3. Fills out form with their details and preferred dates
4. Clicks submit
5. **Sees success message:** "Visit request submitted successfully!"
6. **Behind the scenes:** Landlord automatically receives email notification
7. Tenant can check status in their dashboard

### For Landlords:
1. Landlord receives email notification: "New Visit Request for [Property Name]"
2. Email shows tenant details and requested date/time
3. Clicks "View Request" button in email
4. Takes them directly to their bookings dashboard
5. Can approve or decline the request
6. **(Future)** Tenant automatically receives approval/decline email

---

## ⚙️ Email Settings & Configuration

### Current Configuration:
- **SMTP Enabled:** ✅ Yes
- **SMTP Provider:** Gmail (smtp.gmail.com)
- **Port:** 587 (TLS encryption)
- **From Email:** zachdomingojavellana@gmail.com
- **Authentication:** Gmail App Password

### User Email Preferences:
Currently, all email notifications are **enabled by default** for all landlords and tenants.

**Future Enhancement:** Users will be able to control their email preferences:
- Visit request notifications (ON/OFF)
- Booking request notifications (ON/OFF)
- Message notifications (ON/OFF)
- Monthly reports (ON/OFF)

Settings page location: `admin/email-settings.php`

---

## 📝 Email Content Example

### Visit Request Email:
```
Subject: New Visit Request for Modern Apartment in Manila

Hello Juan Dela Cruz,

Jane Smith has requested to visit your property:

Property: Modern Apartment in Manila
Visit Date: Monday, October 28, 2025
Visit Time: 2:00 PM

Please log in to your HomeHub account to approve or decline this request.

[View Request] (button linking to bookings dashboard)

Best regards,
The HomeHub Team
```

---

## 🔍 Troubleshooting

### Emails Not Sending?
Check these files:
1. `test_gmail_auth.php` - Test Gmail SMTP connection
2. `test_email_notifications.php` - Send test emails manually
3. `admin/email-settings.php` - Verify SMTP settings

### Check Email Configuration:
```php
// Run this to see current settings:
php debug_email_config.php
```

### View Email Logs:
Check `error_log.txt` for email sending logs:
- "Email sent successfully via SMTP to: [email]" ✅ Success
- "SMTP Email error: [error]" ❌ Error

---

## 🚀 Production Deployment

When you upload to your live server:

1. **Update Email Settings:**
   - Go to `admin/email-settings.php`
   - Update "From Email" to your production domain email
   - Or keep Gmail SMTP (works fine for production)

2. **Update Links in Emails:**
   - Edit `includes/email_functions.php`
   - Replace `http://localhost/HomeHub/` with your live domain
   - Example: `https://yourdomain.com/`

3. **Test After Deployment:**
   - Upload all files to live server
   - Access `https://yourdomain.com/test_gmail_auth.php`
   - Verify SMTP connection works
   - Send test emails via `test_email_notifications.php`

4. **Alternative Email Providers (Optional):**
   - Gmail works great for small to medium usage
   - For high volume (1000+ emails/day), consider:
     - SendGrid (free tier: 100 emails/day)
     - Mailgun (free tier: 5,000 emails/month)
     - AWS SES (very cheap, highly scalable)
   - See `PRODUCTION_EMAIL_SETUP.md` for details

---

## 📊 Email Sending Limits

**Gmail SMTP Limits:**
- **Free Gmail:** 500 emails per day
- **Google Workspace:** 2,000 emails per day

**Your Usage Estimate:**
- 10 properties listed
- Average 5 visit/booking requests per day
- = 5 emails per day (well under limit) ✅

**Tips to Stay Under Limit:**
- Gmail limit resets every 24 hours
- Emails are only sent for actual user actions (visit/booking requests)
- No spam or mass emails sent by the system

---

## 🔐 Security & Privacy

### Email Security:
✅ Gmail App Password (not your main password)  
✅ TLS encryption for all emails  
✅ Passwords stored in database (not in code)  
✅ PHPMailer prevents email header injection attacks  

### User Privacy:
- User emails only used for notifications, never shared
- Users can't see other users' email addresses
- Email preferences respected (when implemented)

---

## 📚 Technical Details

### Key Files:
- **`includes/email_functions.php`** - All email sending functions
- **`process-visit.php`** - Handles visit requests, triggers visit emails
- **`process-reservation.php`** - Handles bookings, triggers booking emails
- **`includes/PHPMailer/*`** - Email library for SMTP
- **`config/db_connect.php`** - Database connection

### Database Tables:
- **`email_config`** - SMTP settings (host, port, password, etc.)
- **`email_preferences`** - User email notification preferences (future)
- **`visit_requests`** - Stores visit requests
- **`reservations`** - Stores booking requests

### Email Functions:
```php
// Main email sending function
sendEmail($to, $subject, $message);

// Specific notification functions
sendVisitRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $visitDate, $visitTime, $visitId);
sendBookingRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $moveInDate, $leaseDuration, $reservationId);
sendWelcomeEmail($userEmail, $userName, $userType);
```

---

## ✅ System Status

**Current Status:** 🟢 FULLY OPERATIONAL

- ✅ Gmail SMTP configured correctly
- ✅ 16-character App Password saved
- ✅ PHPMailer installed and working
- ✅ Visit request emails working
- ✅ Booking request emails working
- ✅ Email templates styled and professional
- ✅ Ready for production deployment

---

## 🎉 Summary

**The email system is now completely automated!**

1. **You don't need to do anything manually**
2. **Emails send automatically** when tenants request visits/bookings
3. **Landlords get instant notifications** about requests
4. **No testing tools needed** - it just works in production
5. **Emails look professional** with HomeHub branding
6. **System is secure** with Gmail SMTP and TLS encryption

**Next Steps for You:**
- Deploy to production server
- Update domain links in email templates
- Test with real users
- Monitor `error_log.txt` for any issues
- (Optional) Implement email preference settings for users

---

**Need Help?**
- Test emails: `http://localhost/HomeHub/test_email_notifications.php`
- Check SMTP: `http://localhost/HomeHub/test_gmail_auth.php`
- View settings: `http://localhost/HomeHub/admin/email-settings.php`
- Email logs: Check `error_log.txt` in HomeHub folder
