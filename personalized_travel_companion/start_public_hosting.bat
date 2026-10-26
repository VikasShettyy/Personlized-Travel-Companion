@echo off
title Travel Companion - Free Public HTTPS Tunnel
echo ======================================================================
echo  Starting Free Public HTTPS Hosting for Personalized Travel Companion
echo  (Make sure WAMP is running and the tray icon is green)
echo ======================================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\run_tunnel.ps1"

pause
