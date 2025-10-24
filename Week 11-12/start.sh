#!/bin/bash

# HomeHub Start Script for Railway.app
echo "🚀 Starting HomeHub Property Management System..."

# Create necessary directories
mkdir -p uploads/properties
mkdir -p ai/cache
mkdir -p ai/logs
mkdir -p ai/models

# Set proper permissions
chmod 755 uploads
chmod 755 uploads/properties
chmod 755 ai/cache
chmod 755 ai/logs
chmod 755 ai/models

# Check if DATABASE_URL is set (Railway environment)
if [ ! -z "$DATABASE_URL" ]; then
    echo "✅ Railway database detected"
    echo "🗄️ Database configured successfully"
else
    echo "⚠️ No DATABASE_URL found - using local configuration"
fi

echo "🎉 HomeHub startup complete!"
echo "🌐 Starting PHP server on port $PORT..."

# Start PHP built-in server
exec php -S 0.0.0.0:$PORT -t .
