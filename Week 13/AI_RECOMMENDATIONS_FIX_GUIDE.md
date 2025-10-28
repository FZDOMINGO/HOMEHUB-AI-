# AI Recommendations Not Showing - Fix Guide

## Problem
Recommended properties are not showing up in the AI Features page when clicking "Get Recommendations" button.

## Root Cause
The `recommendation_cache` table is likely **EMPTY** or has no **valid** recommendations (where `is_valid = 1`).

## Diagnostic Tools Created

### 1. check_ai_database.php
**Purpose:** Check all AI-related database tables and data

**How to use:**
- **Local:** http://localhost/HomeHub/check_ai_database.php
- **Production:** https://homehubai.shop/check_ai_database.php

**What it checks:**
- ✓ recommendation_cache table existence and data
- ✓ browsing_history table and user activity
- ✓ tenant_preferences table
- ✓ similarity_scores table
- ✓ Available properties count
- ✓ User/tenant counts

**What to look for:**
```
Total Recommendations: 0 ← PROBLEM! Should be > 0
Valid Recommendations: 0 ← PROBLEM! Should be > 0
```

### 2. generate_ai_recommendations.php
**Purpose:** Generate AI recommendations and populate the cache

**How to use:**
- **Local:** http://localhost/HomeHub/generate_ai_recommendations.php
- **Production:** https://homehubai.shop/generate_ai_recommendations.php

**What it does:**
1. Checks if all required tables exist
2. Shows statistics (users, properties, existing recommendations)
3. Click "Generate Recommendations Now" button
4. Analyzes each tenant's browsing history
5. Finds similar properties based on:
   - Property type
   - Price range (±20%)
   - Availability status
6. Generates recommendation scores (0.70 - 0.95)
7. Inserts into recommendation_cache table

## Step-by-Step Fix

### Step 1: Diagnose the Problem
```bash
1. Visit: http://localhost/HomeHub/check_ai_database.php
2. Look at "Recommendation Cache Data" section
3. Check "Valid Recommendations" count
```

**If count is 0 or very low** → Proceed to Step 2

### Step 2: Generate Recommendations
```bash
1. Visit: http://localhost/HomeHub/generate_ai_recommendations.php
2. Review statistics
3. Click "Generate Recommendations Now" button
4. Wait for completion (shows success message)
5. Check sample recommendations displayed
```

### Step 3: Verify Fix
```bash
1. Login as a TENANT user
2. Visit: http://localhost/HomeHub/ai-features.php
3. Click "Get Recommendations" button in modal
4. Should now display recommended properties!
```

## How AI Recommendations Work

### Data Flow:
```
1. User browses properties
   ↓
2. Property views saved to browsing_history table
   ↓
3. AI analyzes viewing patterns:
   - Property types viewed
   - Price ranges
   - Locations
   ↓
4. Finds similar properties:
   - Same property type
   - Similar price (±20%)
   - Status = 'available'
   - Not already viewed
   ↓
5. Calculates similarity score (0.0 - 1.0)
   ↓
6. Stores in recommendation_cache table
   ↓
7. API fetches cached recommendations
   ↓
8. Displays to user in AI Features page
```

### Database Tables Involved:

#### recommendation_cache
```sql
- user_id: The tenant getting recommendations
- property_id: The recommended property
- recommendation_score: Similarity score (0.70 - 0.95)
- is_valid: 1 = active, 0 = expired
- created_at: When generated
- updated_at: Last updated
```

#### browsing_history
```sql
- user_id: Who viewed the property
- property_id: Which property was viewed
- viewed_at: Timestamp
```

#### properties
```sql
- id: Property ID
- property_type: apartment, house, condo, etc.
- rent_amount: Monthly rent
- status: available, rented, pending
```

## Troubleshooting

### Issue: "No tables found"
**Solution:** Import the complete database schema from phpMyAdmin
```sql
-- Check if tables exist
SHOW TABLES LIKE 'recommendation_cache';
SHOW TABLES LIKE 'browsing_history';
```

### Issue: "No recommendations generated"
**Causes:**
1. No tenants in database
2. No properties marked as 'available'
3. No browsing history

**Solution:**
1. Create at least 1 tenant account
2. Add at least 3 properties (status = 'available')
3. Browse some properties as tenant (creates browsing_history)
4. Run generate_ai_recommendations.php again

### Issue: "Recommendations generated but not showing"
**Check these:**

1. **JavaScript Console (F12)**
   ```javascript
   // Look for errors like:
   - Failed to fetch
   - JSON parse error
   - 401 Unauthorized
   ```

2. **Network Tab (F12)**
   ```
   Request: api/ai/get-recommendations.php
   Status: Should be 200 OK
   Response: Should contain JSON with recommendations
   ```

3. **Session Check**
   ```javascript
   // Run in console:
   fetch('api/check_session.php')
     .then(r => r.json())
     .then(console.log);
   
   // Should show:
   { loggedIn: true, user: { type: "tenant" } }
   ```

4. **API Response Check**
   ```javascript
   // Run in console:
   fetch('api/ai/get-recommendations.php')
     .then(r => r.json())
     .then(console.log);
   
   // Should show:
   { success: true, recommendations: [...] }
   ```

### Issue: "API returns error"

**Error: "not_logged_in"**
- Solution: Make sure you're logged in as a tenant

**Error: "invalid_user_type"**
- Solution: Recommendations only work for tenants, not landlords

**Error: "tenant_not_found"**
- Solution: Tenant profile not created properly. Check `tenants` table.

## Manual Database Check

### Check if recommendations exist:
```sql
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN is_valid = 1 THEN 1 END) as valid
FROM recommendation_cache;
```

**Expected:** `valid` > 0

### Check sample recommendations:
```sql
SELECT 
    rc.*,
    u.email as user_email,
    p.title as property_title
FROM recommendation_cache rc
JOIN users u ON u.id = rc.user_id
JOIN properties p ON p.id = rc.property_id
WHERE rc.is_valid = 1
ORDER BY rc.recommendation_score DESC
LIMIT 10;
```

### Manually insert test recommendation:
```sql
-- Get a user_id and property_id first
SELECT id FROM users WHERE user_type = 'tenant' LIMIT 1;
SELECT id FROM properties WHERE status = 'available' LIMIT 1;

-- Insert test recommendation
INSERT INTO recommendation_cache 
(user_id, property_id, recommendation_score, is_valid, created_at, updated_at)
VALUES 
(1, 1, 0.95, 1, NOW(), NOW());
```

## Production Deployment

### Files to Upload:
1. `check_ai_database.php` - Diagnostic tool
2. `generate_ai_recommendations.php` - Generator tool
3. `api/ai/get-recommendations.php` - API endpoint (should already exist)
4. `assets/js/ai-features.js` - Frontend script (should already exist)

### Steps:
1. Upload diagnostic files to https://homehubai.shop/
2. Run check_ai_database.php
3. If recommendation_cache is empty, run generate_ai_recommendations.php
4. Test AI features as tenant user

### Security Note:
⚠️ **Delete these files after fixing:**
- `check_ai_database.php`
- `generate_ai_recommendations.php`
- `production_test.php`

They expose database structure and should not be public.

## Quick Fix Summary

```bash
1. Visit: check_ai_database.php
   → Check if recommendation_cache is empty

2. Visit: generate_ai_recommendations.php
   → Click "Generate Recommendations Now"

3. Login as tenant
   → Visit ai-features.php
   → Click "Get Recommendations"
   → Should now work! ✓
```

## Still Not Working?

Contact information needed:
1. Screenshot of check_ai_database.php results
2. Screenshot of browser console (F12) errors
3. Screenshot of Network tab showing API call
4. Which step failed?

---

**Last Updated:** October 28, 2025  
**Status:** Diagnostic tools ready, awaiting user testing
