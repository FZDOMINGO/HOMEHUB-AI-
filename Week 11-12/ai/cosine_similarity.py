"""
Intelligent Tenant Matching using Cosine Similarity
This module implements AI-powered property matching based on tenant preferences
"""

import numpy as np
import json
import logging
from typing import List, Dict, Tuple, Optional
from datetime import datetime
from database import db_manager
from config import AI_CONFIG, FEATURE_CONFIG

logger = logging.getLogger(__name__)


class TenantMatcher:
    """Implements cosine similarity-based tenant-property matching"""
    
    def __init__(self):
        self.config = AI_CONFIG['similarity']
        self.feature_config = FEATURE_CONFIG
        self.feature_weights = self.config['feature_weights']
    
    def vectorize_tenant_preferences(self, preferences: Dict) -> np.ndarray:
        """
        Convert tenant preferences to a normalized feature vector
        
        Features include:
        - Budget (normalized)
        - Location preferences (encoded)
        - Property type preferences (one-hot)
        - Lifestyle scores
        - Amenity preferences
        - Size preferences
        """
        vector_components = []
        
        # 1. Budget features (2 dimensions)
        min_budget = preferences.get('min_budget', 0)
        max_budget = preferences.get('max_budget', 50000)
        budget_mid = (min_budget + max_budget) / 2
        budget_range = max_budget - min_budget
        
        norm_config = self.feature_config['normalization']
        budget_normalized = self._normalize(budget_mid, norm_config['rent_min'], norm_config['rent_max'])
        budget_flexibility = budget_range / max_budget if max_budget > 0 else 0
        
        vector_components.extend([budget_normalized, budget_flexibility])
        
        # 2. Location preference (encoded as 1 dimension - can be expanded)
        preferred_cities = preferences.get('preferred_cities', [])
        location_diversity = len(preferred_cities) / 10.0  # Normalize by max expected cities
        vector_components.append(location_diversity)
        
        # 3. Property type preferences (one-hot encoded - 4 types)
        property_types = ['Apartment', 'House', 'Condo', 'Studio']
        preferred_types = preferences.get('preferred_property_types', [])
        for ptype in property_types:
            vector_components.append(1.0 if ptype in preferred_types else 0.0)
        
        # 4. Lifestyle scores (3 dimensions, normalized to 0-1)
        lifestyle_quiet_active = preferences.get('lifestyle_quiet_active', 5) / 10.0
        lifestyle_family_single = preferences.get('lifestyle_family_single', 5) / 10.0
        lifestyle_work_home = preferences.get('lifestyle_work_home', 5) / 10.0
        vector_components.extend([lifestyle_quiet_active, lifestyle_family_single, lifestyle_work_home])
        
        # 5. Amenity preferences (10 common amenities)
        common_amenities = [
            'Wi-Fi', 'Parking', 'Security', 'Gym', 'Pool',
            'Laundry', 'Furnished', 'Pet-Friendly', 'Balcony', 'AC'
        ]
        amenities_pref = preferences.get('amenities_preferences', {})
        for amenity in common_amenities:
            weight = amenities_pref.get(amenity, 0.5)  # Default 0.5 if not specified
            vector_components.append(weight)
        
        # 6. Size preferences (2 dimensions)
        min_bedrooms = preferences.get('min_bedrooms', 1)
        max_bedrooms = preferences.get('max_bedrooms', 5)
        bedrooms_normalized = (min_bedrooms + max_bedrooms) / 10.0  # Normalize
        bathrooms_normalized = preferences.get('min_bathrooms', 1) / 5.0
        vector_components.extend([bedrooms_normalized, bathrooms_normalized])
        
        # 7. Transportation preferences (2 dimensions)
        near_transport = 1.0 if preferences.get('near_public_transport', False) else 0.0
        parking_req = 1.0 if preferences.get('parking_required', False) else 0.0
        vector_components.extend([near_transport, parking_req])
        
        # Convert to numpy array and normalize
        vector = np.array(vector_components)
        return self._l2_normalize(vector)
    
    def vectorize_property(self, property_data: Dict) -> np.ndarray:
        """
        Convert property attributes to a feature vector matching tenant preferences structure
        """
        vector_components = []
        
        # 1. Budget features (2 dimensions)
        rent = property_data.get('rent_amount', 0)
        norm_config = self.feature_config['normalization']
        rent_normalized = self._normalize(rent, norm_config['rent_min'], norm_config['rent_max'])
        price_flexibility = 0.1  # Properties have less price flexibility
        vector_components.extend([rent_normalized, price_flexibility])
        
        # 2. Location (1 dimension - can be expanded with geo-encoding)
        city = property_data.get('city', '')
        location_score = 0.5  # Placeholder - could use city popularity/demand score
        vector_components.append(location_score)
        
        # 3. Property type (one-hot encoded)
        property_types = ['Apartment', 'House', 'Condo', 'Studio']
        prop_type = property_data.get('property_type', '')
        for ptype in property_types:
            vector_components.append(1.0 if prop_type == ptype else 0.0)
        
        # 4. Lifestyle compatibility scores (inferred from property attributes)
        # These would ideally be pre-calculated based on neighborhood data
        lifestyle_scores = self._infer_lifestyle_scores(property_data)
        vector_components.extend(lifestyle_scores)
        
        # 5. Amenities (10 common amenities)
        common_amenities = [
            'Wi-Fi', 'Parking', 'Security', 'Gym', 'Pool',
            'Laundry', 'Furnished', 'Pet-Friendly', 'Balcony', 'AC'
        ]
        property_amenities = property_data.get('amenities', [])
        for amenity in common_amenities:
            vector_components.append(1.0 if amenity in property_amenities else 0.0)
        
        # 6. Size (2 dimensions)
        bedrooms = property_data.get('bedrooms', 1)
        bathrooms = property_data.get('bathrooms', 1)
        bedrooms_normalized = bedrooms / 5.0
        bathrooms_normalized = bathrooms / 5.0
        vector_components.extend([bedrooms_normalized, bathrooms_normalized])
        
        # 7. Transportation (2 dimensions)
        # These would be calculated based on property location data
        near_transport = 0.5  # Placeholder
        has_parking = 1.0 if 'Parking' in property_amenities else 0.0
        vector_components.extend([near_transport, has_parking])
        
        vector = np.array(vector_components)
        return self._l2_normalize(vector)
    
    def _infer_lifestyle_scores(self, property_data: Dict) -> List[float]:
        """Infer lifestyle compatibility scores from property attributes"""
        # Quiet vs Active (based on property type and location)
        prop_type = property_data.get('property_type', '')
        quiet_score = 0.7 if prop_type in ['House', 'Condo'] else 0.4
        
        # Family vs Single (based on bedrooms)
        bedrooms = property_data.get('bedrooms', 1)
        family_score = min(bedrooms / 4.0, 1.0)
        
        # Work from home suitability (based on size and amenities)
        sqft = property_data.get('square_feet', 0)
        amenities = property_data.get('amenities', [])
        wfh_score = 0.6
        if sqft > 800:
            wfh_score += 0.2
        if 'Wi-Fi' in amenities:
            wfh_score += 0.2
        wfh_score = min(wfh_score, 1.0)
        
        return [quiet_score, family_score, wfh_score]
    
    def calculate_cosine_similarity(self, vector1: np.ndarray, vector2: np.ndarray) -> float:
        """Calculate cosine similarity between two vectors"""
        # Ensure vectors are the same length
        if len(vector1) != len(vector2):
            logger.warning(f"Vector length mismatch: {len(vector1)} vs {len(vector2)}")
            return 0.0
        
        # Calculate cosine similarity
        dot_product = np.dot(vector1, vector2)
        norm1 = np.linalg.norm(vector1)
        norm2 = np.linalg.norm(vector2)
        
        if norm1 == 0 or norm2 == 0:
            return 0.0
        
        similarity = dot_product / (norm1 * norm2)
        return float(similarity)
    
    def calculate_weighted_similarity(self, tenant_vector: np.ndarray, 
                                     property_vector: np.ndarray,
                                     breakdown: bool = False) -> Tuple[float, Optional[Dict]]:
        """
        Calculate weighted similarity score using feature-specific weights
        Returns: (overall_score, feature_breakdown_dict)
        """
        # Define feature ranges in the vector
        feature_ranges = {
            'budget': (0, 2),
            'location': (2, 3),
            'property_type': (3, 7),
            'lifestyle': (7, 10),
            'amenities': (10, 20),
            'size': (20, 22),
            'transportation': (22, 24)
        }
        
        weighted_score = 0.0
        feature_breakdown = {}
        
        for feature_name, (start, end) in feature_ranges.items():
            # Extract feature sub-vectors
            tenant_sub = tenant_vector[start:end]
            property_sub = property_vector[start:end]
            
            # Calculate similarity for this feature
            feature_sim = self.calculate_cosine_similarity(tenant_sub, property_sub)
            
            # Apply weight
            weight = self.feature_weights.get(feature_name, 0.1)
            weighted_score += feature_sim * weight
            
            if breakdown:
                feature_breakdown[feature_name] = {
                    'similarity': float(feature_sim),
                    'weight': weight,
                    'weighted_score': float(feature_sim * weight)
                }
        
        return weighted_score, feature_breakdown if breakdown else None
    
    def find_matches_for_tenant(self, tenant_id: int, top_k: int = None) -> List[Dict]:
        """
        Find top property matches for a tenant using cosine similarity
        
        Returns list of matches with scores and breakdown
        """
        if top_k is None:
            top_k = self.config['top_k']
        
        # Get tenant preferences
        preferences = db_manager.get_tenant_preferences(tenant_id)
        if not preferences:
            logger.warning(f"No preferences found for tenant {tenant_id}")
            return []
        
        # Convert preferences to vector
        tenant_vector = self.vectorize_tenant_preferences(preferences)
        
        # Save the preference vector for caching
        db_manager.save_tenant_preferences(tenant_id, {
            **preferences,
            'preference_vector': tenant_vector.tolist()
        })
        
        # Get all available properties
        properties = db_manager.get_all_properties()
        
        matches = []
        for property_data in properties:
            # Get property amenities
            amenities = db_manager.get_property_amenities(property_data['id'])
            property_data['amenities'] = amenities
            
            # Convert property to vector
            property_vector = self.vectorize_property(property_data)
            
            # Calculate weighted similarity with breakdown
            match_score, feature_breakdown = self.calculate_weighted_similarity(
                tenant_vector, property_vector, breakdown=True
            )
            
            # Calculate simple cosine similarity for reference
            cosine_sim = self.calculate_cosine_similarity(tenant_vector, property_vector)
            
            # Only include matches above threshold
            if match_score >= self.config['min_score']:
                matches.append({
                    'property_id': property_data['id'],
                    'property_title': property_data['title'],
                    'cosine_similarity': cosine_sim,
                    'match_score': match_score,
                    'match_percentage': int(match_score * 100),
                    'feature_breakdown': feature_breakdown,
                    'rent_amount': property_data['rent_amount'],
                    'city': property_data['city'],
                    'bedrooms': property_data['bedrooms'],
                    'bathrooms': property_data['bathrooms']
                })
        
        # Sort by match score
        matches.sort(key=lambda x: x['match_score'], reverse=True)
        
        # Add ranking
        for rank, match in enumerate(matches[:top_k], 1):
            match['rank'] = rank
        
        # Save to database
        if matches:
            db_manager.save_similarity_scores(tenant_id, matches[:top_k])
        
        logger.info(f"Found {len(matches[:top_k])} matches for tenant {tenant_id}")
        return matches[:top_k]
    
    def update_all_property_vectors(self) -> int:
        """
        Pre-calculate and store vectors for all properties
        This should be run periodically or when properties are updated
        """
        properties = db_manager.get_all_properties()
        updated_count = 0
        
        for property_data in properties:
            try:
                amenities = db_manager.get_property_amenities(property_data['id'])
                property_data['amenities'] = amenities
                
                vector = self.vectorize_property(property_data)
                
                # Store in property_vectors table
                # (Implementation would go in database.py)
                updated_count += 1
            except Exception as e:
                logger.error(f"Error vectorizing property {property_data['id']}: {e}")
        
        logger.info(f"Updated vectors for {updated_count} properties")
        return updated_count
    
    @staticmethod
    def _normalize(value: float, min_val: float, max_val: float) -> float:
        """Normalize value to 0-1 range"""
        if max_val == min_val:
            return 0.5
        normalized = (value - min_val) / (max_val - min_val)
        return max(0.0, min(1.0, normalized))  # Clamp to [0, 1]
    
    @staticmethod
    def _l2_normalize(vector: np.ndarray) -> np.ndarray:
        """L2 normalization (unit vector)"""
        norm = np.linalg.norm(vector)
        if norm == 0:
            return vector
        return vector / norm


# Create singleton instance
tenant_matcher = TenantMatcher()
