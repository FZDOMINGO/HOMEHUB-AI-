#!/bin/bash
echo "Starting HomeHub..."
mkdir -p uploads/properties ai/cache ai/logs ai/models
chmod 755 uploads uploads/properties ai/cache ai/logs ai/models
echo "Starting PHP server on port $PORT with error display enabled..."
exec php -S 0.0.0.0:$PORT -t . -d display_errors=1 -d error_reporting=E_ALL
