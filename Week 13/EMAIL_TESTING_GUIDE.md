# Email Notification Testing Guide

## How to Check if Email Notifications Are Working

### Method 1: Use the Test Email Tool (Recommended)
The easiest way to test your email notifications:

1. **Open the test tool in your browser:**
   ```
   http://localhost/HomeHub/test_email_notifications.php
   ```

2. **Check the System Status** section to verify:
   - PHP Mail Function is available ✅
   - Email Configuration exists ✅
   - Email tables are created ✅

3. **Run Quick Tests:**
   - Click "📨 Send Simple Test Email" - Basic email functionality
   - Click "🏠 Test Visit Notification" - Visit request email template
   - Click "📅 Test Booking Notification" - Booking request email template
   - Click "🔧 API Test Email" - Full API test

4. **Check your email inbox** (and spam folder!)

---

### Method 2: Admin Email Settings Test
Use the built-in admin test feature:

1. **Go to Email Settings:**
   ```
   http://localhost/HomeHub/admin/email-settings.php
   ```

2. **Configure your SMTP settings** (if needed):
   - For **Gmail**:
     - SMTP Host: `smtp.gmail.com`
     - SMTP Port: `587`
     - Encryption: `TLS`
     - Username: Your Gmail address
     - Password: Your Gmail App Password
     - ✓ Enable "Use SMTP"
   
   - For **Local Testing** (default):
     - Leave "Use SMTP" unchecked
     - Uses PHP's mail() function

3. **Click "Send Test Email"** button

4. **Check your inbox** for the test email

---

### Method 3: Test Real Notifications
Test the actual workflow:

1. **Visit Request Test:**
   - Go to a property detail page as a guest/tenant
   - Submit a visit request
   - Check if the landlord receives an email

2. **Booking Request Test:**
   - Submit a reservation/booking request
   - Check if the landlord receives an email

3. **Approval Test:**
   - As landlord, approve a visit or booking
   - Check if the tenant receives confirmation email

---

### Method 4: Preview Email Templates
See how emails will look:

1. **Go to Email Preview:**
   ```
   http://localhost/HomeHub/admin/email-preview.php
   ```

2. **Select different email types** from the dropdown:
   - Visit Request Notification
   - Booking Request Notification
   - Reservation Approved
   - Visit Approved
   - Property Performance
   - New Message
   - Welcome Email

3. **View the rendered HTML** email template

---

## Common Issues & Solutions

### ❌ Emails Not Being Received

**Check spam/junk folder first!**

Then verify:

1. **Check PHP mail configuration:**
   - Open `php.ini` in your XAMPP installation
   - Look for these settings:
     ```
     SMTP = localhost
     smtp_port = 25
     sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
     ```

2. **Check sendmail configuration** (for XAMPP):
   - Open `C:\xampp\sendmail\sendmail.ini`
   - Verify settings for your email provider

3. **Check error logs:**
   - Look at `error_log.txt` in your HomeHub folder
   - Check XAMPP error logs at `C:\xampp\apache\logs\error.log`

4. **Test with a real email service:**
   - Configure Gmail SMTP in Email Settings
   - Use an App Password (not regular password)

### ❌ Email Tables Missing

Run the setup script:
```
http://localhost/HomeHub/setup_email.php
```

This will create:
- `email_config` table
- `email_preferences` table

### ❌ SMTP Authentication Failed

**For Gmail users:**
1. Enable 2-Step Verification on your Google Account
2. Go to: Security → App Passwords
3. Generate a new app password for "Mail"
4. Use this 16-character password in SMTP settings

**For other providers:**
- Verify your username and password
- Check if the SMTP host and port are correct
- Ensure encryption matches (TLS for port 587, SSL for port 465)

---

## Quick Reference: Test Files

| File | Purpose |
|------|---------|
| `test_email_notifications.php` | Complete testing dashboard with all tests |
| `api/test-email.php` | Simple API endpoint for test emails |
| `admin/email-settings.php` | Configure SMTP and email settings |
| `admin/email-preview.php` | Preview all email templates |
| `setup_email.php` | Create email database tables |

---

## Test Checklist

- [ ] Email tables created (`setup_email.php`)
- [ ] SMTP configured (if using Gmail/external SMTP)
- [ ] Test email sent successfully
- [ ] Email received in inbox (not spam)
- [ ] Visit notification test passed
- [ ] Booking notification test passed
- [ ] Email templates render correctly
- [ ] Real workflow test (actual booking/visit)

---

## Need Help?

1. **Check the error log:**
   ```
   http://localhost/HomeHub/error_log.txt
   ```

2. **Review the Email System Guide:**
   ```
   http://localhost/HomeHub/EMAIL_SYSTEM_GUIDE.md
   ```

3. **Verify PHP mail settings:**
   ```php
   <?php phpinfo(); ?>
   ```
   Look for "sendmail" configuration

4. **Test with a simple PHP mail script:**
   ```php
   <?php
   $result = mail('your@email.com', 'Test', 'This is a test');
   echo $result ? 'Success!' : 'Failed!';
   ?>
   ```

---

## Production Recommendations

For a production environment:

1. ✅ Use SMTP (not PHP mail())
2. ✅ Use a professional email service (Gmail, SendGrid, Mailgun, etc.)
3. ✅ Set up SPF and DKIM records for your domain
4. ✅ Use a real domain email (not @localhost)
5. ✅ Monitor email delivery logs
6. ✅ Implement email queuing for high volume
7. ✅ Add retry logic for failed emails

---

**Good luck with your email testing! 📧**
