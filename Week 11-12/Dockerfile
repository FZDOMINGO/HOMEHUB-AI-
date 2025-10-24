FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Make scripts executable
RUN chmod +x docker-start.sh || chmod +x start.sh || true

# Create required directories
RUN mkdir -p uploads/properties ai/cache ai/logs ai/models && \
    chmod -R 755 uploads ai/cache ai/logs ai/models || true

# Expose port (Railway uses PORT env variable)
EXPOSE $PORT

# Start the application
CMD php -S 0.0.0.0:${PORT:-8080} -t . -d display_errors=1 -d error_reporting=E_ALL