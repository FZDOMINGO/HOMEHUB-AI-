-- HomeHub AI Features Database Schema
-- This file contains all tables and modifications needed for AI features

-- =====================================================
-- 1. TENANT PREFERENCES TABLE
-- Stores tenant preferences for intelligent matching
-- =====================================================
CREATE TABLE IF NOT EXISTS tenant_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    
    -- Budget preferences
    min_budget DECIMAL(10, 2) DEFAULT 0,
    max_budget DECIMAL(10, 2) NOT NULL,
    budget_flexibility INT DEFAULT 10 COMMENT 'Flexibility percentage',
    
    -- Location preferences (vectorized)
    preferred_cities JSON COMMENT 'Array of preferred cities',
    preferred_areas JSON COMMENT 'Array of specific areas',
    max_distance_from_work DECIMAL(5, 2) COMMENT 'Max distance in km',
    work_location_lat DECIMAL(10, 8),
    work_location_lng DECIMAL(11, 8),
    
    -- Property type preferences
    preferred_property_types JSON COMMENT 'Array of property types',
    min_bedrooms INT DEFAULT 1,
    max_bedrooms INT DEFAULT 5,
    min_bathrooms DECIMAL(3, 1) DEFAULT 1,
    
    -- Lifestyle preferences (vectorized for cosine similarity)
    lifestyle_quiet_active INT DEFAULT 5 COMMENT '1-10 scale: 1=quiet, 10=active',
    lifestyle_family_single INT DEFAULT 5 COMMENT '1-10 scale: 1=single, 10=family',
    lifestyle_work_home INT DEFAULT 5 COMMENT '1-10 scale: 1=work from office, 10=work from home',
    pet_friendly_required BOOLEAN DEFAULT FALSE,
    furnished_preference ENUM('furnished', 'unfurnished', 'either') DEFAULT 'either',
    
    -- Amenities preferences (vectorized)
    amenities_preferences JSON COMMENT 'Object with amenity weights',
    
    -- Transportation preferences
    near_public_transport BOOLEAN DEFAULT FALSE,
    parking_required BOOLEAN DEFAULT FALSE,
    
    -- Additional preferences
    lease_duration_min INT DEFAULT 6 COMMENT 'Minimum months',
    lease_duration_max INT DEFAULT 12 COMMENT 'Maximum months',
    move_in_date DATE,
    
    -- Preference vector (for cosine similarity)
    preference_vector JSON COMMENT 'Normalized vector representation',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PROPERTY VECTORS TABLE
-- Stores vectorized property attributes for AI matching
-- =====================================================
CREATE TABLE IF NOT EXISTS property_vectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    
    -- Normalized property features for cosine similarity
    price_normalized DECIMAL(5, 4) COMMENT '0-1 normalized price',
    location_score DECIMAL(5, 4) COMMENT 'Location desirability score',
    size_normalized DECIMAL(5, 4) COMMENT 'Normalized square footage',
    
    -- Lifestyle compatibility scores
    quiet_score DECIMAL(5, 4) COMMENT '0-1: quiet area score',
    family_friendly_score DECIMAL(5, 4) COMMENT '0-1: family suitability',
    work_from_home_score DECIMAL(5, 4) COMMENT '0-1: WFH suitability',
    
    -- Amenities vector
    amenities_vector JSON COMMENT 'Vector of amenity presence (0-1 for each)',
    
    -- Transportation scores
    public_transport_score DECIMAL(5, 4),
    parking_score DECIMAL(5, 4),
    
    -- Complete feature vector for cosine similarity
    feature_vector JSON COMMENT 'Complete normalized feature vector',
    
    -- Metadata
    vector_version INT DEFAULT 1 COMMENT 'Version of vectorization algorithm',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BROWSING HISTORY TABLE
-- Tracks user property viewing behavior
-- =====================================================
CREATE TABLE IF NOT EXISTS browsing_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    
    -- Interaction details
    view_duration INT COMMENT 'Seconds spent viewing',
    scroll_depth INT COMMENT 'Percentage of page scrolled',
    images_viewed INT DEFAULT 0,
    contact_clicked BOOLEAN DEFAULT FALSE,
    saved BOOLEAN DEFAULT FALSE,
    
    -- Context
    source VARCHAR(50) COMMENT 'search, recommendation, featured, etc',
    search_query TEXT COMMENT 'Original search query if applicable',
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    
    -- Timestamp
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    INDEX idx_user_time (user_id, viewed_at),
    INDEX idx_property_time (property_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. USER INTERACTIONS TABLE
-- Comprehensive tracking of all user actions
-- =====================================================
CREATE TABLE IF NOT EXISTS user_interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT,
    
    -- Interaction type
    interaction_type ENUM(
        'view', 'save', 'unsave', 'contact', 
        'reserve', 'visit_request', 'search', 
        'filter_apply', 'share', 'review'
    ) NOT NULL,
    
    -- Interaction weight (for recommendation algorithm)
    weight DECIMAL(3, 2) DEFAULT 1.0 COMMENT 'Importance weight: view=1.0, save=2.0, reserve=5.0',
    
    -- Additional data
    interaction_data JSON COMMENT 'Additional context data',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
    
    INDEX idx_user_interaction (user_id, interaction_type, created_at),
    INDEX idx_property_interaction (property_id, interaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. RENTAL ANALYTICS TABLE
-- Historical rental data for predictive analytics
-- =====================================================
CREATE TABLE IF NOT EXISTS rental_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    
    -- Time period
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Metrics
    views_count INT DEFAULT 0,
    inquiries_count INT DEFAULT 0,
    visit_requests_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    
    -- Conversion metrics
    view_to_inquiry_rate DECIMAL(5, 4),
    inquiry_to_visit_rate DECIMAL(5, 4),
    visit_to_application_rate DECIMAL(5, 4),
    
    -- Market data
    average_market_price DECIMAL(10, 2) COMMENT 'Average price in area',
    days_on_market INT,
    was_rented BOOLEAN DEFAULT FALSE,
    final_rent_amount DECIMAL(10, 2),
    
    -- Seasonal data
    month INT,
    quarter INT,
    season ENUM('spring', 'summer', 'fall', 'winter'),
    
    -- Created timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    INDEX idx_property_period (property_id, period_start, period_end),
    INDEX idx_time_metrics (period_start, was_rented)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. RECOMMENDATION CACHE TABLE
-- Caches AI-generated recommendations for performance
-- =====================================================
CREATE TABLE IF NOT EXISTS recommendation_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Recommendations
    recommended_properties JSON COMMENT 'Array of property IDs with scores',
    
    -- Recommendation metadata
    algorithm_version VARCHAR(20),
    confidence_score DECIMAL(5, 4) COMMENT 'Overall confidence in recommendations',
    based_on_interactions INT COMMENT 'Number of interactions used',
    
    -- Cache management
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_valid (user_id, is_valid, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. PROPERTY DEMAND FORECAST TABLE
-- Stores predictive analytics results
-- =====================================================
CREATE TABLE IF NOT EXISTS property_demand_forecast (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    
    -- Forecast period
    forecast_date DATE NOT NULL,
    forecast_period ENUM('week', 'month', 'quarter') NOT NULL,
    
    -- Predictions
    predicted_views INT,
    predicted_inquiries INT,
    predicted_applications INT,
    
    -- Optimal pricing
    suggested_rent_min DECIMAL(10, 2),
    suggested_rent_optimal DECIMAL(10, 2),
    suggested_rent_max DECIMAL(10, 2),
    
    -- Demand indicators
    demand_score DECIMAL(5, 4) COMMENT '0-1: low to high demand',
    competition_level ENUM('low', 'medium', 'high'),
    days_to_rent_estimate INT,
    
    -- Model metadata
    model_version VARCHAR(20),
    confidence_level DECIMAL(5, 4),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    INDEX idx_property_forecast (property_id, forecast_date),
    UNIQUE KEY unique_property_forecast (property_id, forecast_date, forecast_period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. SIMILARITY SCORES TABLE
-- Stores pre-computed similarity scores for performance
-- =====================================================
CREATE TABLE IF NOT EXISTS similarity_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    
    -- Similarity metrics
    cosine_similarity DECIMAL(5, 4) COMMENT '0-1: similarity score',
    feature_breakdown JSON COMMENT 'Breakdown by feature category',
    
    -- Overall score (weighted)
    match_score DECIMAL(5, 4) COMMENT '0-1: final weighted match score',
    match_percentage INT COMMENT '0-100: user-friendly percentage',
    
    -- Ranking
    rank_for_tenant INT COMMENT 'Rank among all properties for this tenant',
    
    -- Metadata
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE COMMENT 'FALSE if preferences/property changed',
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    INDEX idx_tenant_score (tenant_id, match_score DESC, is_valid),
    INDEX idx_property_score (property_id, match_score DESC),
    UNIQUE KEY unique_tenant_property (tenant_id, property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. SEARCH QUERIES TABLE
-- Logs search queries for pattern analysis
-- =====================================================
CREATE TABLE IF NOT EXISTS search_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    
    -- Query details
    query_text TEXT,
    filters_applied JSON COMMENT 'All filters applied in search',
    
    -- Results
    results_count INT,
    results_clicked JSON COMMENT 'Array of property IDs clicked',
    
    -- Timestamp
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_search (user_id, searched_at),
    FULLTEXT INDEX idx_query_text (query_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. USER SIMILARITY TABLE
-- Stores user-to-user similarity for collaborative filtering
-- =====================================================
CREATE TABLE IF NOT EXISTS user_similarity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id_1 INT NOT NULL,
    user_id_2 INT NOT NULL,
    
    -- Similarity metrics
    interaction_similarity DECIMAL(5, 4) COMMENT 'Based on interaction patterns',
    preference_similarity DECIMAL(5, 4) COMMENT 'Based on preferences',
    overall_similarity DECIMAL(5, 4) COMMENT 'Weighted combination',
    
    -- Common interactions
    common_properties_viewed INT DEFAULT 0,
    common_properties_saved INT DEFAULT 0,
    
    -- Metadata
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id_1) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id_2) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user1_similarity (user_id_1, overall_similarity DESC, is_valid),
    INDEX idx_user2_similarity (user_id_2, overall_similarity DESC, is_valid),
    UNIQUE KEY unique_user_pair (user_id_1, user_id_2),
    CHECK (user_id_1 < user_id_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================

-- Add indexes to existing properties table
ALTER TABLE properties 
ADD INDEX idx_status_created (status, created_at DESC),
ADD INDEX idx_city_price (city, rent_amount),
ADD INDEX idx_bedrooms_bathrooms (bedrooms, bathrooms);

-- Add indexes to existing users table  
ALTER TABLE users 
ADD INDEX idx_user_type (user_type, created_at);

-- =====================================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =====================================================

DELIMITER //

-- Trigger to invalidate similarity scores when tenant preferences change
CREATE TRIGGER tr_invalidate_similarity_on_preference_update
AFTER UPDATE ON tenant_preferences
FOR EACH ROW
BEGIN
    UPDATE similarity_scores 
    SET is_valid = FALSE 
    WHERE tenant_id = NEW.tenant_id;
    
    UPDATE recommendation_cache 
    SET is_valid = FALSE 
    WHERE user_id = (SELECT user_id FROM tenants WHERE id = NEW.tenant_id);
END//

-- Trigger to invalidate similarity scores when property changes
CREATE TRIGGER tr_invalidate_similarity_on_property_update
AFTER UPDATE ON properties
FOR EACH ROW
BEGIN
    UPDATE similarity_scores 
    SET is_valid = FALSE 
    WHERE property_id = NEW.id;
END//

-- Trigger to create browsing history from user interactions
CREATE TRIGGER tr_create_browsing_history_on_view
AFTER INSERT ON user_interactions
FOR EACH ROW
BEGIN
    IF NEW.interaction_type = 'view' AND NEW.property_id IS NOT NULL THEN
        INSERT INTO browsing_history (user_id, property_id, source)
        VALUES (NEW.user_id, NEW.property_id, 'interaction')
        ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- INITIAL DATA SETUP
-- =====================================================

-- Insert default interaction weights
-- These can be adjusted based on business logic
-- view = 1.0, save = 2.0, contact = 3.0, visit_request = 4.0, reserve = 5.0

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- View: Top performing properties
CREATE OR REPLACE VIEW vw_top_properties AS
SELECT 
    p.id,
    p.title,
    p.rent_amount,
    COUNT(DISTINCT bh.user_id) as unique_viewers,
    COUNT(DISTINCT ui.user_id) as total_interactions,
    AVG(ss.match_score) as avg_match_score
FROM properties p
LEFT JOIN browsing_history bh ON p.id = bh.property_id 
    AND bh.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN user_interactions ui ON p.id = ui.property_id 
    AND ui.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN similarity_scores ss ON p.id = ss.property_id AND ss.is_valid = TRUE
WHERE p.status = 'available'
GROUP BY p.id
ORDER BY total_interactions DESC, unique_viewers DESC;

-- View: Tenant matching dashboard
CREATE OR REPLACE VIEW vw_tenant_match_summary AS
SELECT 
    t.id as tenant_id,
    u.first_name,
    u.last_name,
    tp.max_budget,
    COUNT(ss.id) as total_matches,
    AVG(ss.match_score) as avg_match_score,
    MAX(ss.match_score) as best_match_score
FROM tenants t
JOIN users u ON t.user_id = u.id
LEFT JOIN tenant_preferences tp ON t.id = tp.tenant_id
LEFT JOIN similarity_scores ss ON t.id = ss.tenant_id AND ss.is_valid = TRUE
GROUP BY t.id;

-- =====================================================
-- COMMENTS AND DOCUMENTATION
-- =====================================================

-- This schema supports three main AI features:
-- 1. Intelligent Tenant Matching (Cosine Similarity)
--    - Uses tenant_preferences and property_vectors tables
--    - Stores results in similarity_scores table
--
-- 2. Smart Recommendation System
--    - Uses browsing_history, user_interactions, user_similarity tables
--    - Caches results in recommendation_cache table
--
-- 3. Predictive Analytics for Landlords
--    - Uses rental_analytics table for historical data
--    - Stores predictions in property_demand_forecast table

-- =====================================================
-- END OF SCHEMA
-- =====================================================
