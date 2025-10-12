#!/bin/bash
echo "🚀 Starting eLearning Platform..."

echo "1. Starting Node.js API on port 3000..."
cd nodejsapp
node server.js &

echo "2. Starting PHP server on port 8000..."
cd ../phpapp
php -S localhost:8000 &

echo "3. Starting Nginx..."
cd ../nginx
nginx.exe -s stop 2>/dev/null
nginx.exe -p ./

echo "✅ All services started!"
echo "🌐 PHP Frontend: http://localhost:8000"
echo "🔗 Node.js API: http://localhost:3000"
echo "🌍 Nginx Proxy: http://localhost:80"