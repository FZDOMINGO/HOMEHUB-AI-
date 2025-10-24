#!/bin/bash

# Setup script for Railway deployment
echo "Setting up HomeHub for Railway..."

# Create uploads directory if it doesn't exist
mkdir -p uploads/properties
chmod 755 uploads
chmod 755 uploads/properties

# Copy Railway database config if DATABASE_URL is set
if [ ! -z "$DATABASE_URL" ]; then
    echo "Using Railway database configuration..."
    cp config/db_connect_railway.php config/db_connect.php
fi

echo "Setup complete!"