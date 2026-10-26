@echo off
title Stop Travel Companion Public Hosting
echo Stopping Cloudflare Tunnel...
taskkill /F /IM cloudflared.exe >nul 2>&1
echo.
echo ======================================================================
echo  Cloudflare Tunnel has been stopped!
echo  Your website is no longer accessible via the public internet link.
echo ======================================================================
echo.
pause
