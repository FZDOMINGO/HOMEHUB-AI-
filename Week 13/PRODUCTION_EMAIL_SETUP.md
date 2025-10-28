# Email Configuration for Production Deployment

## Overview
This guide helps you configure email notifications for your HomeHub application when deploying to a live server.

---

## Current Status
✅ Email system is built and working
⚠️ Just needs proper SMTP configuration for production

---

## Production Email Options

### Option 1: Gmail SMTP (Quick Start)
**Good for:** Small apps, getting started
**Limit:** 500 emails per day

**Configuration:**
```
SMTP Host: smtp.gmail.com
SMTP Port: 587
Encryption: TLS
Username: your-business-email@gmail.com
Password: Your Gmail App Password (16 characters)
```

**How to get App Password:**
1. Go to https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Click "App passwords"
4. Generate for "Mail"
5. Copy the 16-character code

**Pros:**
- Free and easy
- Works immediately
- Reliable delivery

**Cons:**
- 500 emails/day limit
- Not ideal for high-traffic apps

---

### Option 2: SendGrid (Recommended for Production)
**Good for:** Professional apps, startups
**Free Tier:** 100 emails/day
**Paid:** Starting at $15/month for 50,000 emails

**Setup:**
1. Sign up at https://sendgrid.com
2. Create an API Key or SMTP credentials
3. Verify your domain (optional but recommended)

**Configuration:**
```
SMTP Host: smtp.sendgrid.net
SMTP Port: 587
Encryption: TLS
Username: apikey (literally the word "apikey")
Password: Your SendGrid API Key
```

**Pros:**
- Professional service
- Excellent deliverability
- Good analytics
- Email template support

**Cons:**
- Requires account signup
- Free tier limited to 100/day

---

### Option 3: Mailgun (Great for Developers)
**Good for:** Developer-friendly apps
**Free Tier:** 5,000 emails/month (first 3 months)
**Paid:** $15/month for 50,000 emails

**Setup:**
1. Sign up at https://mailgun.com
2. Verify your domain
3. Get SMTP credentials

**Configuration:**
```
SMTP Host: smtp.mailgun.org
SMTP Port: 587
Encryption: TLS
Username: Your Mailgun SMTP username
Password: Your Mailgun SMTP password
```

**Pros:**
- Very developer-friendly
- Good free tier (5,000/month)
- Excellent documentation
- EU region available

**Cons:**
- Requires domain verification for full features

---

### Option 4: AWS SES (Best for Scale)
**Good for:** High-volume apps
**Cost:** $0.10 per 1,000 emails (very cheap!)

**Configuration:**
```
SMTP Host: email-smtp.{region}.amazonaws.com
SMTP Port: 587
Encryption: TLS
Username: Your AWS SMTP username
Password: Your AWS SMTP password
```

**Pros:**
- Very cheap at scale
- Highly reliable
- Integrates with AWS services

**Cons:**
- More complex setup
- Starts in sandbox mode (need to request production access)
- Requires AWS account

---

### Option 5: Your Hosting Provider
**Good for:** If your hosting includes email

Most hosting providers (cPanel, Plesk) offer SMTP:

**Configuration (varies by host):**
```
SMTP Host: mail.yourdomain.com or smtp.yourdomain.com
SMTP Port: 587 (TLS) or 465 (SSL)
Username: Your email address (e.g., noreply@yourdomain.com)
Password: Your email password
```

**Check with your hosting provider for exact settings.**

---

## Recommended Setup by App Size

### Small App (< 100 emails/day)
→ Use **Gmail SMTP** (Free, easy)

### Medium App (100-5,000 emails/month)
→ Use **SendGrid** or **Mailgun** (Professional, reliable)

### Large App (> 5,000 emails/month)
→ Use **AWS SES** (Cost-effective at scale)

---

## How to Configure in HomeHub

### Step 1: Login as Landlord or Tenant
```
http://yourdomain.com/login/login.html
```

### Step 2: Go to Email Settings
```
http://yourdomain.com/admin/email-settings.php
```

### Step 3: Fill in SMTP Details
1. ✅ Check "Use SMTP"
2. Enter your SMTP host
3. Enter port (usually 587)
4. Select encryption (usually TLS)
5. Enter username
6. Enter password
7. Set "From Email" (e.g., noreply@yourdomain.com)
8. Set "From Name" (e.g., HomeHub)
9. Click "Save Settings"

### Step 4: Test
Click "Send Test Email" to verify it works!

---

## Best Practices for Production

### 1. Use a Dedicated Email Address
```
noreply@yourdomain.com (for notifications)
support@yourdomain.com (for replies)
```

### 2. Set Up SPF Records
Add to your domain's DNS:
```
v=spf1 include:_spf.google.com ~all  (for Gmail)
v=spf1 include:sendgrid.net ~all      (for SendGrid)
v=spf1 include:mailgun.org ~all       (for Mailgun)
```

### 3. Set Up DKIM
Most email services provide DKIM records - add them to your DNS.

### 4. Monitor Delivery
- Check bounce rates
- Monitor spam complaints
- Keep email lists clean

### 5. Use Environment Variables (Security)
For production, don't hardcode credentials:
```php
// In your config
$smtpPassword = getenv('SMTP_PASSWORD');
```

---

## Migration Checklist

### Before Deploying to Production:

- [ ] Choose email service (SendGrid/Mailgun/Gmail)
- [ ] Sign up and verify account
- [ ] Get SMTP credentials
- [ ] Configure email settings in HomeHub
- [ ] Test email sending
- [ ] Set up SPF/DKIM records
- [ ] Test from production server
- [ ] Monitor first 24 hours of emails

---

## Troubleshooting Production Issues

### Emails Going to Spam
- Verify SPF and DKIM records
- Use a custom domain for "From" address
- Ensure consistent sender identity
- Don't send too many emails too quickly

### Connection Errors
- Check firewall allows outbound port 587
- Verify SMTP credentials are correct
- Check if hosting blocks outbound SMTP

### Rate Limiting
- Stay within your plan's limits
- Implement email queuing for high volume
- Consider upgrading your plan

---

## Quick Start for Production (5 Minutes)

### Using SendGrid (Recommended):

1. **Sign up:** https://sendgrid.com (Free account)
2. **Create API Key:** Settings → API Keys → Create API Key
3. **Configure in HomeHub:**
   - SMTP Host: `smtp.sendgrid.net`
   - Port: `587`
   - Username: `apikey`
   - Password: [Your API Key]
   - From Email: `noreply@yourdomain.com`
4. **Test it!**

Done! Your emails will now be delivered reliably in production.

---

## Support

If you need help with production email setup:
1. Check your email service's documentation
2. Test using their tools first
3. Verify DNS records are correct
4. Check server firewall settings

---

**Remember:** Email is critical for your app! Choose a reliable service for production. 📧
