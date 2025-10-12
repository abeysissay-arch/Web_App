#!/bin/bash
echo "🛑 Stopping all services..."

pkill -f "node server.js"
pkill -f "php -S localhost:8000"

# Windows nginx commands instead of sudo
cd /c/Users/metmt/Desktop/ELearning/nginx
nginx.exe -s stop 2>/dev/null

echo "✅ Done!"