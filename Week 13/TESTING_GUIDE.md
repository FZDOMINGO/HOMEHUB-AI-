# 🧪 Testing Guide - Phase 2 & 3 Updates

## Quick Test Checklist

### Step 1: Database Setup ✅
```
http://localhost/HomeHub/setup/database_setup.php
```
**Expected:** All 13 tables created successfully (green checkmarks)

---

### Step 2: Test Authentication 🔐

#### Test Registration
1. Visit: `http://localhost/HomeHub/login/register.html`
2. Fill form:
   - Full Name: Test User
   - Email: test@example.com
   - Password: password123
   - Phone: 1234567890
   - Type: Tenant
3. Click Register

**Expected:** Redirect to tenant dashboard

#### Test Login
1. Visit: `http://localhost/HomeHub/login/login.html`
2. Enter credentials from registration
3. Click Login

**Expected:** Redirect to tenant dashboard

#### Test Logout
1. Click user menu → Logout

**Expected:** Redirect to homepage

---

### Step 3: Test Tenant Features 👤

#### Dashboard
```
http://localhost/HomeHub/tenant/dashboard.php
```
**Expected:**
- Stats showing (saved properties, scheduled visits, viewed properties)
- Welcome message with tenant name
- AI recommendations section

#### Browse Properties
```
http://localhost/HomeHub/properties.php
```
**Expected:**
- Property listings displayed
- Search and filter working
- Can click property for details

#### Property Details
```
http://localhost/HomeHub/property-detail.php?id=1
```
**Expected:**
- Property information loaded
- Images displayed
- Can save property
- Can request visit/reservation

#### Saved Properties
```
http://localhost/HomeHub/tenant/saved.php
```
**Expected:**
- List of saved properties
- Can remove properties

#### Profile
```
http://localhost/HomeHub/tenant/profile.php
```
**Expected:**
- User details displayed
- Can edit profile
- Can update preferences

#### History
```
http://localhost/HomeHub/tenant/history.php
```
**Expected:**
- Browsing history displayed
- Recently viewed properties

---

### Step 4: Test Landlord Features 🏠

#### Register as Landlord
1. Logout from tenant
2. Register new account with user type: Landlord
3. Login

#### Dashboard
```
http://localhost/HomeHub/landlord/dashboard.php
```
**Expected:**
- Stats showing (total properties, visits, reservations)
- Analytics graphs
- Recent activity

#### Add Property
```
http://localhost/HomeHub/landlord/add-property.php
```
**Expected:**
- Form to add new property
- Can upload images
- Form submission works

#### Manage Properties
```
http://localhost/HomeHub/landlord/manage-properties.php
```
**Expected:**
- List of landlord's properties
- Can edit/delete properties
- Property status visible

---

### Step 5: Test AI Features 🤖

#### AI Dashboard
```
http://localhost/HomeHub/ai-features.php
```
**Expected:**
- Smart Recommendations section
- Property Matching section
- Analytics section (landlords only)

#### Get Recommendations (Tenant)
1. Login as tenant
2. Visit AI Features
3. Click "Get Recommendations"

**Expected:**
- API call to `/api/ai/get-recommendations.php`
- Property recommendations displayed
- Match scores shown

#### Get Matches (Tenant)
1. Setup preferences first
2. Click "Find Matching Properties"

**Expected:**
- API call to `/api/ai/get-matches.php`
- Matching properties displayed
- Compatibility scores shown

#### Analytics (Landlord)
1. Login as landlord
2. Visit AI Features
3. View Analytics section

**Expected:**
- API call to `/api/ai/get-analytics.php`
- Property performance metrics
- Visitor statistics
- Engagement graphs

---

### Step 6: Test Notifications 🔔

#### Get Notifications
```
http://localhost/HomeHub/tenant/notifications.php
```
**Expected:**
- List of notifications
- Unread count in navbar
- Can mark as read

#### Notification Count API
**Browser Console:**
```javascript
fetch('/api/get-notification-count.php')
  .then(r => r.json())
  .then(console.log)
```

**Expected:** `{ success: true, count: X }`

---

### Step 7: Test Admin Panel 👨‍💼

#### Admin Login
```
http://localhost/HomeHub/admin/login.php
```
**Default Credentials:**
- Username: `admin`
- Password: `admin123`

#### Admin Analytics
```
http://localhost/HomeHub/admin/analytics.php
```
**Expected:**
- Platform-wide statistics
- User metrics
- Property metrics
- Activity graphs

---

## API Testing (Browser Console)

### Test Session Check
```javascript
fetch('/api/check_session.php')
  .then(r => r.json())
  .then(console.log)
```

### Test Get Recommendations
```javascript
fetch('/api/ai/get-recommendations.php')
  .then(r => r.json())
  .then(console.log)
```

### Test Get Property Details
```javascript
fetch('/api/get-property-details.php?id=1')
  .then(r => r.json())
  .then(console.log)
```

### Test Get Notifications
```javascript
fetch('/api/get-notifications.php?limit=5')
  .then(r => r.json())
  .then(console.log)
```

---

## Error Checking

### Check PHP Error Log
```
c:\xampp\htdocs\HomeHub\error_log.txt
```

### Check Browser Console
- Open Developer Tools (F12)
- Check Console tab for JavaScript errors
- Check Network tab for failed requests

### Check Database
Open phpMyAdmin:
```
http://localhost/phpmyadmin
```

**Verify tables exist:**
- users
- tenants
- landlords
- properties
- property_images
- tenant_preferences
- similarity_scores
- browsing_history
- property_reservations
- booking_visits
- saved_properties
- notifications
- recommendation_cache

---

## Common Issues and Solutions

### Issue: "Database connection failed"
**Solution:** 
1. Make sure XAMPP MySQL is running
2. Check `config/env.php` database credentials
3. Verify database `homehub` exists

### Issue: "Not authenticated" errors
**Solution:**
1. Clear browser cookies
2. Logout and login again
3. Check `initSession()` is called in file

### Issue: "Property not found"
**Solution:**
1. Add test properties in landlord panel
2. Or run: `http://localhost/HomeHub/create_test_properties.php`

### Issue: Redirects to wrong URL
**Solution:**
1. Verify `redirect()` helper is used (not `header()`)
2. Check `APP_URL` constant in `config/env.php`

### Issue: API returns 500 error
**Solution:**
1. Check `error_log.txt`
2. Verify file includes `env.php` and `database.php`
3. Check database connection is established

---

## Success Indicators ✅

**All Working If:**
- ✅ Can register and login
- ✅ Tenant dashboard loads with stats
- ✅ Landlord dashboard loads with stats
- ✅ Can add properties
- ✅ Can browse properties
- ✅ Property details load
- ✅ AI recommendations work
- ✅ Notifications load
- ✅ Admin analytics load
- ✅ No errors in console
- ✅ No errors in error_log.txt

---

## Performance Test

### Check Page Load Times
**Expected: < 2 seconds for all pages**

Use browser Network tab to monitor:
- Initial HTML load
- API response times
- Database query performance

---

## Hostinger Testing (After Deployment)

### Update Testing URLs
Replace `localhost/HomeHub` with `homehubai.shop`:

```
https://homehubai.shop/setup/database_setup.php
https://homehubai.shop/
https://homehubai.shop/tenant/dashboard.php
https://homehubai.shop/landlord/dashboard.php
https://homehubai.shop/ai-features.php
```

### Verify Environment Detection
Check that:
- APP_ENV is automatically set to "production"
- Debug mode is OFF
- Production database is used
- Error messages don't show sensitive info

---

## Test Report Template

```markdown
## Test Results - [Date]

### Environment
- Platform: [Localhost / Hostinger]
- PHP Version: [Version]
- Browser: [Browser Name]

### Authentication
- [ ] Registration works
- [ ] Login works
- [ ] Logout works
- [ ] Session persists

### Tenant Features
- [ ] Dashboard loads
- [ ] Properties browse works
- [ ] Property details load
- [ ] Can save properties
- [ ] Profile update works
- [ ] History displays

### Landlord Features  
- [ ] Dashboard loads
- [ ] Add property works
- [ ] Manage properties works
- [ ] Can view visits/reservations

### AI Features
- [ ] Recommendations work
- [ ] Property matching works
- [ ] Analytics display (landlord)

### Admin Panel
- [ ] Login works
- [ ] Analytics display
- [ ] Can view users

### Issues Found
[List any issues]

### Overall Status
[Pass / Fail]
```

---

**Happy Testing!** 🎉

**Report any issues and I'll help fix them immediately!**
