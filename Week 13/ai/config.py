"""
HomeHub AI Configuration
Database and AI service configuration
"""

import os
from dotenv import load_dotenv

load_dotenv()

# Database Configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'homehub'),
    'charset': 'utf8mb4',
    'autocommit': True
}

# AI Model Configuration
AI_CONFIG = {
    # Cosine Similarity Settings
    'similarity': {
        'min_score': 0.3,  # Minimum similarity score to consider (0-1)
        'top_k': 20,  # Number of top matches to return
        'feature_weights': {
            'budget': 0.25,
            'location': 0.20,
            'property_type': 0.15,
            'lifestyle': 0.15,
            'amenities': 0.15,
            'size': 0.10
        }
    },
    
    # Recommendation System Settings
    'recommendation': {
        'collaborative_weight': 0.4,  # Weight for collaborative filtering
        'content_weight': 0.6,  # Weight for content-based filtering
        'min_interactions': 5,  # Minimum interactions needed for collaborative filtering
        'top_k_recommendations': 15,
        'user_similarity_threshold': 0.5,
        'cache_ttl': 3600  # Cache time-to-live in seconds (1 hour)
    },
    
    # Predictive Analytics Settings
    'analytics': {
        'forecast_periods': ['week', 'month', 'quarter'],
        'min_historical_data': 30,  # Minimum days of data needed
        'seasonal_factors': {
            'spring': 1.15,  # Peak rental season
            'summer': 1.10,
            'fall': 0.95,
            'winter': 0.90
        },
        'model_retrain_days': 7  # Retrain model every N days
    }
}

# Feature Engineering Configuration
FEATURE_CONFIG = {
    # Property features to vectorize
    'property_features': [
        'rent_amount',
        'bedrooms',
        'bathrooms',
        'square_feet',
        'property_type',
        'city',
        'amenities'
    ],
    
    # Tenant preference features
    'tenant_features': [
        'budget_range',
        'location_preference',
        'property_type_preference',
        'lifestyle_scores',
        'amenity_weights',
        'size_preference'
    ],
    
    # Normalization ranges
    'normalization': {
        'rent_min': 5000,
        'rent_max': 100000,
        'sqft_min': 200,
        'sqft_max': 5000
    }
}

# Logging Configuration
LOGGING_CONFIG = {
    'version': 1,
    'disable_existing_loggers': False,
    'formatters': {
        'standard': {
            'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        },
        'colored': {
            '()': 'colorlog.ColoredFormatter',
            'format': '%(log_color)s%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        }
    },
    'handlers': {
        'console': {
            'class': 'logging.StreamHandler',
            'level': 'INFO',
            'formatter': 'colored',
            'stream': 'ext://sys.stdout'
        },
        'file': {
            'class': 'logging.FileHandler',
            'level': 'DEBUG',
            'formatter': 'standard',
            'filename': 'logs/homehub_ai.log',
            'mode': 'a'
        }
    },
    'loggers': {
        '': {
            'handlers': ['console', 'file'],
            'level': 'INFO',
            'propagate': False
        }
    }
}

# Cache Configuration
CACHE_CONFIG = {
    'enabled': True,
    'backend': 'memory',  # 'memory' or 'redis'
    'redis_host': os.getenv('REDIS_HOST', 'localhost'),
    'redis_port': int(os.getenv('REDIS_PORT', 6379)),
    'redis_db': int(os.getenv('REDIS_DB', 0))
}

# API Configuration
API_CONFIG = {
    'host': '127.0.0.1',
    'port': 5000,
    'debug': os.getenv('FLASK_DEBUG', 'False') == 'True',
    'cors_origins': ['http://localhost', 'http://localhost:80']
}
