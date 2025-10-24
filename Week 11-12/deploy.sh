#!/bin/bash

# HomeHub Deployment Script for Railway.app
echo "🚀 Starting HomeHub deployment setup..."

# Create necessary directories
echo "📁 Creating required directories..."
mkdir -p uploads/properties
mkdir -p ai/cache
mkdir -p ai/logs
mkdir -p ai/models

# Set proper permissions
echo "🔒 Setting directory permissions..."
chmod 755 uploads
chmod 755 uploads/properties
chmod 755 ai/cache
chmod 755 ai/logs
chmod 755 ai/models

# Copy Railway database config if DATABASE_URL is set
if [ ! -z "$DATABASE_URL" ]; then
    echo "🗄️ Configuring Railway database connection..."
    cp config/db_connect_railway.php config/db_connect.php
    echo "✅ Database configuration updated for Railway"
else
    echo "⚠️ Using local database configuration"
fi

# Install Python dependencies if AI is enabled
if [ "$ENABLE_AI_FEATURES" = "true" ]; then
    echo "🤖 Setting up AI service..."
    cd ai
    pip install -r requirements.txt
    cd ..
    echo "✅ AI dependencies installed"
fi

# Check if database needs initialization
if [ "$INIT_DATABASE" = "true" ]; then
    echo "🗄️ Initializing database..."
    php -f simple_setup.php
    echo "✅ Database initialized"
fi

echo "🎉 HomeHub deployment setup complete!"
echo "📍 Application will be available at: $APP_URL"