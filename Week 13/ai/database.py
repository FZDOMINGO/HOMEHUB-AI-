"""
Database utility module for HomeHub AI
Handles database connections and queries
"""

import pymysql
import logging
from contextlib import contextmanager
from typing import List, Dict, Any, Optional
from config import DB_CONFIG

logger = logging.getLogger(__name__)


class DatabaseManager:
    """Manages database connections and operations"""
    
    def __init__(self):
        self.config = DB_CONFIG
    
    @contextmanager
    def get_connection(self):
        """Context manager for database connections"""
        conn = None
        try:
            conn = pymysql.connect(**self.config)
            yield conn
        except pymysql.Error as e:
            logger.error(f"Database error: {e}")
            raise
        finally:
            if conn:
                conn.close()
    
    def execute_query(self, query: str, params: tuple = None, fetch_one: bool = False) -> Optional[Any]:
        """Execute a SELECT query and return results"""
        with self.get_connection() as conn:
            with conn.cursor(pymysql.cursors.DictCursor) as cursor:
                cursor.execute(query, params)
                if fetch_one:
                    return cursor.fetchone()
                return cursor.fetchall()
    
    def execute_update(self, query: str, params: tuple = None) -> int:
        """Execute an INSERT, UPDATE, or DELETE query"""
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                affected_rows = cursor.execute(query, params)
                conn.commit()
                return affected_rows
    
    def execute_many(self, query: str, params_list: List[tuple]) -> int:
        """Execute many queries in batch"""
        with self.get_connection() as conn:
            with conn.cursor() as cursor:
                affected_rows = cursor.executemany(query, params_list)
                conn.commit()
                return affected_rows
    
    # ==================== Property Queries ====================
    
    def get_all_properties(self, status: str = 'available') -> List[Dict]:
        """Get all properties with given status"""
        query = """
            SELECT p.*, pv.feature_vector, pv.amenities_vector
            FROM properties p
            LEFT JOIN property_vectors pv ON p.id = pv.property_id
            WHERE p.status = %s
            ORDER BY p.created_at DESC
        """
        return self.execute_query(query, (status,))
    
    def get_property_by_id(self, property_id: int) -> Optional[Dict]:
        """Get property by ID"""
        query = """
            SELECT p.*, pv.feature_vector, pv.amenities_vector
            FROM properties p
            LEFT JOIN property_vectors pv ON p.id = pv.property_id
            WHERE p.id = %s
        """
        return self.execute_query(query, (property_id,), fetch_one=True)
    
    def get_property_amenities(self, property_id: int) -> List[str]:
        """Get amenities for a property"""
        query = "SELECT amenity_name FROM property_amenities WHERE property_id = %s"
        results = self.execute_query(query, (property_id,))
        return [r['amenity_name'] for r in results]
    
    # ==================== Tenant Queries ====================
    
    def get_tenant_by_user_id(self, user_id: int) -> Optional[Dict]:
        """Get tenant information by user ID"""
        query = """
            SELECT t.*, u.first_name, u.last_name, u.email
            FROM tenants t
            JOIN users u ON t.user_id = u.id
            WHERE t.user_id = %s
        """
        return self.execute_query(query, (user_id,), fetch_one=True)
    
    def get_tenant_preferences(self, tenant_id: int) -> Optional[Dict]:
        """Get tenant preferences"""
        query = "SELECT * FROM tenant_preferences WHERE tenant_id = %s"
        return self.execute_query(query, (tenant_id,), fetch_one=True)
    
    def save_tenant_preferences(self, tenant_id: int, preferences: Dict) -> bool:
        """Save or update tenant preferences"""
        query = """
            INSERT INTO tenant_preferences 
            (tenant_id, min_budget, max_budget, preferred_cities, preferred_property_types,
             min_bedrooms, max_bedrooms, min_bathrooms, lifestyle_quiet_active, 
             lifestyle_family_single, lifestyle_work_home, pet_friendly_required,
             furnished_preference, amenities_preferences, near_public_transport, 
             parking_required, preference_vector)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                min_budget = VALUES(min_budget),
                max_budget = VALUES(max_budget),
                preferred_cities = VALUES(preferred_cities),
                preferred_property_types = VALUES(preferred_property_types),
                min_bedrooms = VALUES(min_bedrooms),
                max_bedrooms = VALUES(max_bedrooms),
                min_bathrooms = VALUES(min_bathrooms),
                lifestyle_quiet_active = VALUES(lifestyle_quiet_active),
                lifestyle_family_single = VALUES(lifestyle_family_single),
                lifestyle_work_home = VALUES(lifestyle_work_home),
                pet_friendly_required = VALUES(pet_friendly_required),
                furnished_preference = VALUES(furnished_preference),
                amenities_preferences = VALUES(amenities_preferences),
                near_public_transport = VALUES(near_public_transport),
                parking_required = VALUES(parking_required),
                preference_vector = VALUES(preference_vector),
                updated_at = CURRENT_TIMESTAMP
        """
        
        import json
        params = (
            tenant_id,
            preferences.get('min_budget', 0),
            preferences.get('max_budget', 50000),
            json.dumps(preferences.get('preferred_cities', [])),
            json.dumps(preferences.get('preferred_property_types', [])),
            preferences.get('min_bedrooms', 1),
            preferences.get('max_bedrooms', 5),
            preferences.get('min_bathrooms', 1),
            preferences.get('lifestyle_quiet_active', 5),
            preferences.get('lifestyle_family_single', 5),
            preferences.get('lifestyle_work_home', 5),
            preferences.get('pet_friendly_required', False),
            preferences.get('furnished_preference', 'either'),
            json.dumps(preferences.get('amenities_preferences', {})),
            preferences.get('near_public_transport', False),
            preferences.get('parking_required', False),
            json.dumps(preferences.get('preference_vector', []))
        )
        
        try:
            self.execute_update(query, params)
            return True
        except Exception as e:
            logger.error(f"Error saving tenant preferences: {e}")
            return False
    
    # ==================== Interaction Tracking ====================
    
    def track_interaction(self, user_id: int, property_id: int, 
                         interaction_type: str, weight: float = 1.0, 
                         data: Dict = None) -> bool:
        """Track user interaction"""
        import json
        query = """
            INSERT INTO user_interactions 
            (user_id, property_id, interaction_type, weight, interaction_data)
            VALUES (%s, %s, %s, %s, %s)
        """
        params = (user_id, property_id, interaction_type, weight, json.dumps(data) if data else None)
        
        try:
            self.execute_update(query, params)
            return True
        except Exception as e:
            logger.error(f"Error tracking interaction: {e}")
            return False
    
    def get_user_browsing_history(self, user_id: int, limit: int = 50) -> List[Dict]:
        """Get user browsing history"""
        query = """
            SELECT bh.*, p.title, p.rent_amount, p.city
            FROM browsing_history bh
            JOIN properties p ON bh.property_id = p.id
            WHERE bh.user_id = %s
            ORDER BY bh.viewed_at DESC
            LIMIT %s
        """
        return self.execute_query(query, (user_id, limit))
    
    def get_user_interactions(self, user_id: int, days: int = 90) -> List[Dict]:
        """Get user interactions for the last N days"""
        query = """
            SELECT * FROM user_interactions
            WHERE user_id = %s 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            ORDER BY created_at DESC
        """
        return self.execute_query(query, (user_id, days))
    
    # ==================== Similarity Scores ====================
    
    def save_similarity_scores(self, tenant_id: int, scores: List[Dict]) -> bool:
        """Batch save similarity scores"""
        query = """
            INSERT INTO similarity_scores 
            (tenant_id, property_id, cosine_similarity, feature_breakdown, 
             match_score, match_percentage, rank_for_tenant)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                cosine_similarity = VALUES(cosine_similarity),
                feature_breakdown = VALUES(feature_breakdown),
                match_score = VALUES(match_score),
                match_percentage = VALUES(match_percentage),
                rank_for_tenant = VALUES(rank_for_tenant),
                calculated_at = CURRENT_TIMESTAMP,
                is_valid = TRUE
        """
        
        import json
        params_list = [
            (
                tenant_id,
                score['property_id'],
                score['cosine_similarity'],
                json.dumps(score.get('feature_breakdown', {})),
                score['match_score'],
                score['match_percentage'],
                score.get('rank', 0)
            )
            for score in scores
        ]
        
        try:
            self.execute_many(query, params_list)
            return True
        except Exception as e:
            logger.error(f"Error saving similarity scores: {e}")
            return False
    
    def get_top_matches_for_tenant(self, tenant_id: int, limit: int = 20) -> List[Dict]:
        """Get top property matches for tenant"""
        query = """
            SELECT ss.*, p.title, p.rent_amount, p.city, p.bedrooms, p.bathrooms,
                   (SELECT image_url FROM property_images 
                    WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM similarity_scores ss
            JOIN properties p ON ss.property_id = p.id
            WHERE ss.tenant_id = %s 
            AND ss.is_valid = TRUE
            AND p.status = 'available'
            ORDER BY ss.match_score DESC
            LIMIT %s
        """
        return self.execute_query(query, (tenant_id, limit))
    
    # ==================== Recommendations ====================
    
    def get_similar_users(self, user_id: int, limit: int = 10) -> List[Dict]:
        """Get similar users for collaborative filtering"""
        query = """
            SELECT 
                CASE 
                    WHEN user_id_1 = %s THEN user_id_2
                    ELSE user_id_1
                END as similar_user_id,
                overall_similarity
            FROM user_similarity
            WHERE (user_id_1 = %s OR user_id_2 = %s)
            AND is_valid = TRUE
            ORDER BY overall_similarity DESC
            LIMIT %s
        """
        return self.execute_query(query, (user_id, user_id, user_id, limit))
    
    def save_recommendations(self, user_id: int, recommendations: List[Dict], 
                           algorithm_version: str, confidence: float) -> bool:
        """Cache recommendations"""
        import json
        from datetime import datetime, timedelta
        
        expires_at = datetime.now() + timedelta(hours=1)
        
        query = """
            INSERT INTO recommendation_cache 
            (user_id, recommended_properties, algorithm_version, 
             confidence_score, expires_at)
            VALUES (%s, %s, %s, %s, %s)
        """
        params = (
            user_id,
            json.dumps(recommendations),
            algorithm_version,
            confidence,
            expires_at
        )
        
        try:
            self.execute_update(query, params)
            return True
        except Exception as e:
            logger.error(f"Error saving recommendations: {e}")
            return False
    
    # ==================== Analytics ====================
    
    def get_property_analytics(self, property_id: int, days: int = 90) -> Dict:
        """Get property analytics data"""
        query = """
            SELECT 
                COUNT(DISTINCT ui.user_id) as total_viewers,
                COUNT(CASE WHEN ui.interaction_type = 'save' THEN 1 END) as save_count,
                COUNT(CASE WHEN ui.interaction_type = 'contact' THEN 1 END) as contact_count,
                COUNT(CASE WHEN ui.interaction_type = 'visit_request' THEN 1 END) as visit_requests,
                COUNT(CASE WHEN ui.interaction_type = 'reserve' THEN 1 END) as reservations,
                AVG(bh.view_duration) as avg_view_duration
            FROM properties p
            LEFT JOIN user_interactions ui ON p.id = ui.property_id 
                AND ui.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            LEFT JOIN browsing_history bh ON p.id = bh.property_id 
                AND bh.viewed_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            WHERE p.id = %s
            GROUP BY p.id
        """
        return self.execute_query(query, (days, days, property_id), fetch_one=True)
    
    def save_demand_forecast(self, property_id: int, forecast: Dict) -> bool:
        """Save demand forecast"""
        import json
        query = """
            INSERT INTO property_demand_forecast 
            (property_id, forecast_date, forecast_period, predicted_views, 
             predicted_inquiries, predicted_applications, suggested_rent_min,
             suggested_rent_optimal, suggested_rent_max, demand_score,
             competition_level, days_to_rent_estimate, model_version, confidence_level)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                predicted_views = VALUES(predicted_views),
                predicted_inquiries = VALUES(predicted_inquiries),
                predicted_applications = VALUES(predicted_applications),
                suggested_rent_min = VALUES(suggested_rent_min),
                suggested_rent_optimal = VALUES(suggested_rent_optimal),
                suggested_rent_max = VALUES(suggested_rent_max),
                demand_score = VALUES(demand_score),
                competition_level = VALUES(competition_level),
                days_to_rent_estimate = VALUES(days_to_rent_estimate),
                model_version = VALUES(model_version),
                confidence_level = VALUES(confidence_level)
        """
        
        params = (
            property_id,
            forecast['forecast_date'],
            forecast['forecast_period'],
            forecast['predicted_views'],
            forecast['predicted_inquiries'],
            forecast['predicted_applications'],
            forecast['suggested_rent_min'],
            forecast['suggested_rent_optimal'],
            forecast['suggested_rent_max'],
            forecast['demand_score'],
            forecast['competition_level'],
            forecast['days_to_rent_estimate'],
            forecast['model_version'],
            forecast['confidence_level']
        )
        
        try:
            self.execute_update(query, params)
            return True
        except Exception as e:
            logger.error(f"Error saving demand forecast: {e}")
            return False


# Create singleton instance
db_manager = DatabaseManager()
