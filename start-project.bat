@echo off
echo 🚀 Starting eLearning Platform...

echo.
echo 1. Starting Node.js API on port 3000...
cd nodejsapp
start "Node.js API" node server.js

echo.
echo 2. Starting PHP server on port 80 (main site)...
cd ..\phpapp
start "PHP Server" php -S localhost:80

echo.
echo ✅ All services started!
echo 🌐 PHP Frontend: http://localhost:80
echo 🔗 Node.js API: http://localhost:3000
pause