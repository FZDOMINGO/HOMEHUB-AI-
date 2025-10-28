# HomeHub Production Testing Guide

## Issues Found & Fixed

### ✅ Fixed on October 28, 2025

1. **landlord/index.php** (Line 162)
   - **Problem:** Account Settings button had `http://localhost:3000/landlord/profile.php`
   - **Fixed to:** `profile.php` (relative URL)
   - **Impact:** Landlords can now access profile settings when logged in

## Comprehensive Code Review Results

### ✓ All Clear - No Issues Found

- **includes/navbar.php** - All links use relative paths
- **tenant/** folder - All dashboards and pages OK
- **landlord/** folder - All files checked (except the one fix above)
- **api/** folder - No hardcoded localhost URLs
- **assets/js/** - All JavaScript uses relative fetch() paths
- **includes/email_functions.php** - Already fixed (uses homehubai.shop)

## Production Testing Steps

### Step 1: Upload Fixed Files
Upload the entire HomeHub folder to your hosting server at homehubai.shop

**Critical file to upload:**
- `landlord/index.php` (fixed)
- `production_test.php` (new diagnostic tool)

### Step 2: Run Diagnostic Test
1. Visit: **https://homehubai.shop/production_test.php**
2. Review all test results:
   - ✓ Database Connection
   - ✓ Session System
   - ✓ Required Tables
   - ✓ Critical Files
   - ✓ Sample Data
   - ✓ API Endpoints
   - ✓ URL Configuration
   - ✓ PHP Configuration

### Step 3: Test Tenant Functionality

#### A. Registration & Login
1. Go to: https://homehubai.shop/login/register.html
2. Register a new tenant account
3. Check if you receive welcome email
4. Login at: https://homehubai.shop/login/login.html

#### B. Browse Properties
1. Go to: https://homehubai.shop/properties.php
2. Check if properties load
3. Click on a property card
4. Verify property details modal opens

#### C. Save Property Feature
1. While browsing properties, click the ❤️ (heart) icon
2. Check if "Property saved successfully" message appears
3. Go to: https://homehubai.shop/tenant/saved.php
4. Verify the saved property shows up

#### D. Request Visit
1. On property detail page, click "Schedule Visit"
2. Fill in visit date and time
3. Submit the request
4. Check if success message appears
5. Go to: https://homehubai.shop/bookings.php
6. Verify the visit request shows up

#### E. Dashboard Stats
1. Go to: https://homehubai.shop/tenant/dashboard.php
2. Check if these numbers are correct:
   - Saved Properties count
   - Scheduled Visits count
   - Properties Viewed count
   - AI Recommendations count

### Step 4: Test Landlord Functionality

#### A. Login
1. Login as landlord at: https://homehubai.shop/login/login.html
2. Verify redirect to: https://homehubai.shop/landlord/dashboard.php

#### B. Dashboard Navigation
1. Click "Manage Properties"
2. Click "Account Settings" (the button we just fixed!)
3. Verify all navigation links work

#### C. Property Management
1. Go to: https://homehubai.shop/landlord/manage-properties.php
2. Click "Add Property" button
3. Try adding a new property with images
4. Verify property appears in list

#### D. Bookings Management
1. Go to: https://homehubai.shop/landlord/bookings.php
2. Check if visit requests from tenants appear
3. Try approving/declining a request
4. Verify tenant receives email notification

### Step 5: Test Email Notifications

Email notifications should work if SMTP is configured. Test:

1. **Welcome Email** - Register new account
2. **Visit Request Email** - Tenant requests visit → Landlord receives email
3. **Visit Approved Email** - Landlord approves visit → Tenant receives email
4. **Reservation Request** - Tenant reserves property → Landlord receives email

All email links should point to: **https://homehubai.shop/** (not localhost)

## Common Issues & Solutions

### Issue: "Nothing works when logged in"

**Possible Causes:**
1. **Session not persisting** - Check `production_test.php` section 2
2. **Database connection using wrong credentials** - Check `config/db_connect.php`
3. **Wrong database imported** - Verify tables exist
4. **API endpoints returning errors** - Check browser console (F12)

**Debugging:**
```javascript
// Open browser console (F12) and run:
fetch('api/check_session.php')
  .then(r => r.json())
  .then(data => console.log(data));
```

Should return:
```json
{
  "status": "success",
  "loggedIn": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "type": "tenant"
  }
}
```

### Issue: "Can't save properties"

**Check:**
1. Are you logged in? (Check navbar shows "Welcome, [Name]")
2. Open browser console (F12) → Check for JavaScript errors
3. Check `production_test.php` → Verify `saved_properties` table exists

**Manual Test:**
```javascript
// In browser console:
fetch('save-property.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({propertyId: 1})
})
.then(r => r.json())
.then(data => console.log(data));
```

### Issue: "Dashboard shows wrong stats"

**Causes:**
- Database has no data for your user
- Foreign key relationships broken
- Using wrong user_id vs tenant_id

**Solution:**
Run this SQL in phpMyAdmin:
```sql
-- Check if saved properties are linked correctly
SELECT sp.*, t.user_id 
FROM saved_properties sp
JOIN tenants t ON t.id = sp.tenant_id
WHERE t.user_id = YOUR_USER_ID;
```

### Issue: "Landlord can't see bookings"

**Check:**
1. Does the property belong to this landlord?
   ```sql
   SELECT * FROM properties WHERE landlord_id = YOUR_LANDLORD_ID;
   ```
2. Are there any booking requests?
   ```sql
   SELECT * FROM booking_visits WHERE property_id IN 
   (SELECT id FROM properties WHERE landlord_id = YOUR_LANDLORD_ID);
   ```

## Browser Console Debugging

Open Developer Tools (F12) and check:

### 1. Network Tab
- Are API requests failing (red)?
- Check response status codes (should be 200)
- Click failed requests → Preview → See error message

### 2. Console Tab
- Look for JavaScript errors (red text)
- Common errors:
  - `Cannot read property of undefined` - Missing data
  - `Failed to fetch` - API endpoint issue
  - `Unexpected token` - JSON parsing error

### 3. Application Tab → Storage
- **Cookies** - Check if session cookie exists
- **Local Storage** - Check for any saved data

## Files Changed (Summary)

### October 28, 2025 - Production URL Fixes
- ✅ `includes/email_functions.php` - 8 URLs fixed
- ✅ `admin/email-preview.php` - 7 URLs fixed
- ✅ `api/test-email.php` - 1 URL fixed
- ✅ `process-reservation-clean.php` - Deprecated PHP code fixed
- ✅ `process-booking.php` - Deprecated PHP code fixed
- ✅ `process-visit.php` - Deprecated PHP code fixed
- ✅ `process-reservation.php` - Deprecated PHP code fixed
- ✅ `tenant/profile.php` - Deprecated PHP code fixed
- ✅ `landlord/index.php` - Localhost URL fixed (TODAY)
- ✅ `.htaccess` - Security configuration created
- ✅ `config/db_connect.PRODUCTION.php` - Template created

## Important URLs

### Test & Diagnostic
- Production Test: https://homehubai.shop/production_test.php

### Public Pages
- Homepage: https://homehubai.shop/
- Properties: https://homehubai.shop/properties.php
- Login: https://homehubai.shop/login/login.html
- Register: https://homehubai.shop/login/register.html

### Tenant Pages (must be logged in)
- Dashboard: https://homehubai.shop/tenant/dashboard.php
- Saved Properties: https://homehubai.shop/tenant/saved.php
- Profile: https://homehubai.shop/tenant/profile.php

### Landlord Pages (must be logged in)
- Dashboard: https://homehubai.shop/landlord/dashboard.php
- Manage Properties: https://homehubai.shop/landlord/manage-properties.php
- Bookings: https://homehubai.shop/landlord/bookings.php
- Profile: https://homehubai.shop/landlord/profile.php

### Admin
- Admin Login: https://homehubai.shop/admin/login.php
- Admin Dashboard: https://homehubai.shop/admin/dashboard.php

## What to Report Back

If something doesn't work, please provide:

1. **What action you tried** (e.g., "Tried to save a property")
2. **What happened** (e.g., "Nothing happened, no error message")
3. **Browser console errors** (F12 → Console → copy red error messages)
4. **Production test results** (which test failed in production_test.php)
5. **Your user type** (tenant or landlord)

## Security Note

⚠️ **After testing, delete these files from production:**
- `production_test.php` (reveals system information)
- All `test_*.php` files
- All `check_*.php` files
- All `debug_*.php` files

## Success Checklist

- [ ] production_test.php shows all green checks
- [ ] Can register new account
- [ ] Can login successfully
- [ ] Properties page loads and displays properties
- [ ] Can click on property to view details
- [ ] Can save a property (heart icon works)
- [ ] Saved properties show in tenant dashboard
- [ ] Can request a visit
- [ ] Visit request appears in bookings
- [ ] Landlord can see visit requests
- [ ] Landlord can approve/decline requests
- [ ] Email notifications are sent
- [ ] All email links point to homehubai.shop

---

**Last Updated:** October 28, 2025
**Status:** Code fixes complete, ready for production testing
