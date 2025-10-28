"""
HomeHub AI Backend API Server
Flask-based REST API for AI features
"""

import sys
import os
from flask import Flask, request, jsonify
from flask_cors import CORS
import traceback
from datetime import datetime

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from ai.cosine_similarity import TenantMatcher
from ai.database import DatabaseManager
from ai.config import AI_CONFIG

app = Flask(__name__)
CORS(app)  # Enable CORS for PHP frontend

# Initialize components
db_manager = DatabaseManager()
tenant_matcher = TenantMatcher()

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'HomeHub AI Backend',
        'timestamp': datetime.now().isoformat(),
        'version': '1.0.0'
    })

@app.route('/api/match-tenant', methods=['POST'])
def match_tenant():
    """
    Find property matches for a tenant based on preferences
    
    Request Body:
    {
        "tenant_id": 123,
        "limit": 10  # optional, default 10
    }
    
    Response:
    {
        "success": true,
        "tenant_id": 123,
        "matches": [
            {
                "property_id": 456,
                "match_score": 0.85,
                "feature_breakdown": {...},
                "property_details": {...}
            }
        ]
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'tenant_id' not in data:
            return jsonify({
                'success': False,
                'error': 'tenant_id is required'
            }), 400
        
        tenant_id = data['tenant_id']
        limit = data.get('limit', 10)
        
        # Find matches using cosine similarity
        matches = tenant_matcher.find_matches_for_tenant(tenant_id, limit=limit)
        
        return jsonify({
            'success': True,
            'tenant_id': tenant_id,
            'matches': matches,
            'count': len(matches),
            'timestamp': datetime.now().isoformat()
        })
        
    except ValueError as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 404
        
    except Exception as e:
        print(f"Error in match_tenant: {str(e)}")
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': 'Internal server error',
            'details': str(e)
        }), 500

@app.route('/api/save-preferences', methods=['POST'])
def save_preferences():
    """
    Save tenant preferences
    
    Request Body:
    {
        "tenant_id": 123,
        "preferences": {
            "budget_min": 1000,
            "budget_max": 2000,
            "preferred_cities": ["Manila", "Quezon City"],
            "property_types": ["apartment", "condo"],
            "lifestyle_preferences": {...},
            "amenity_priorities": {...},
            "size_requirements": {...},
            "transportation_preferences": {...}
        }
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'tenant_id' not in data or 'preferences' not in data:
            return jsonify({
                'success': False,
                'error': 'tenant_id and preferences are required'
            }), 400
        
        tenant_id = data['tenant_id']
        preferences = data['preferences']
        
        # Save preferences to database
        db_manager.save_tenant_preferences(tenant_id, preferences)
        
        return jsonify({
            'success': True,
            'message': 'Preferences saved successfully',
            'tenant_id': tenant_id
        })
        
    except Exception as e:
        print(f"Error in save_preferences: {str(e)}")
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': 'Internal server error',
            'details': str(e)
        }), 500

@app.route('/api/track-interaction', methods=['POST'])
def track_interaction():
    """
    Track user interaction with property
    
    Request Body:
    {
        "user_id": 123,
        "property_id": 456,
        "interaction_type": "view",  # view, save, share, inquiry, reservation
        "metadata": {}  # optional additional data
    }
    """
    try:
        data = request.get_json()
        
        if not data or 'user_id' not in data or 'property_id' not in data or 'interaction_type' not in data:
            return jsonify({
                'success': False,
                'error': 'user_id, property_id, and interaction_type are required'
            }), 400
        
        user_id = data['user_id']
        property_id = data['property_id']
        interaction_type = data['interaction_type']
        metadata = data.get('metadata', {})
        
        # Track interaction
        db_manager.track_interaction(user_id, property_id, interaction_type, metadata)
        
        return jsonify({
            'success': True,
            'message': 'Interaction tracked successfully'
        })
        
    except Exception as e:
        print(f"Error in track_interaction: {str(e)}")
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': 'Internal server error',
            'details': str(e)
        }), 500

@app.route('/api/property-vector/<int:property_id>', methods=['GET'])
def get_property_vector(property_id):
    """Get or compute property feature vector"""
    try:
        # Get property vector (will compute if not cached)
        vector = tenant_matcher.vectorize_property(property_id)
        
        return jsonify({
            'success': True,
            'property_id': property_id,
            'vector': vector.tolist(),
            'dimensions': len(vector)
        })
        
    except Exception as e:
        print(f"Error in get_property_vector: {str(e)}")
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/tenant-vector/<int:tenant_id>', methods=['GET'])
def get_tenant_vector(tenant_id):
    """Get tenant preference vector"""
    try:
        # Get tenant preferences
        preferences = db_manager.get_tenant_preferences(tenant_id)
        
        if not preferences:
            return jsonify({
                'success': False,
                'error': 'No preferences found for tenant'
            }), 404
        
        # Vectorize preferences
        vector = tenant_matcher.vectorize_tenant_preferences(preferences)
        
        return jsonify({
            'success': True,
            'tenant_id': tenant_id,
            'vector': vector.tolist(),
            'dimensions': len(vector)
        })
        
    except Exception as e:
        print(f"Error in get_tenant_vector: {str(e)}")
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/invalidate-cache/<int:tenant_id>', methods=['POST'])
def invalidate_cache(tenant_id):
    """Invalidate similarity cache for a tenant"""
    try:
        db_manager.invalidate_similarity_cache(tenant_id)
        
        return jsonify({
            'success': True,
            'message': 'Cache invalidated successfully',
            'tenant_id': tenant_id
        })
        
    except Exception as e:
        print(f"Error in invalidate_cache: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'error': 'Endpoint not found'
    }), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({
        'success': False,
        'error': 'Internal server error'
    }), 500

if __name__ == '__main__':
    print("=" * 50)
    print("HomeHub AI Backend Server")
    print("=" * 50)
    print(f"Starting Flask server on http://127.0.0.1:5000")
    print("Available endpoints:")
    print("  GET  /api/health")
    print("  POST /api/match-tenant")
    print("  POST /api/save-preferences")
    print("  POST /api/track-interaction")
    print("  GET  /api/property-vector/<id>")
    print("  GET  /api/tenant-vector/<id>")
    print("  POST /api/invalidate-cache/<id>")
    print("=" * 50)
    print("\nPress Ctrl+C to stop the server\n")
    
    # Run the server
    app.run(
        host='127.0.0.1',
        port=5000,
        debug=False
    )
