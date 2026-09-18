@echo off
title MPSC Web & Tunnel Auto-Start (Port 80)

echo ========================================
echo  MPSC Port 80 Web Tunnel - Auto Startup
echo ========================================

echo.
echo [1/3] Starting XAMPP MySQL...
start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
timeout /t 2 /nobreak >nul

echo [2/3] Starting XAMPP Apache (Port 80)...
start "" /B "C:\xampp\apache\bin\httpd.exe"
timeout /t 2 /nobreak >nul

echo [3/3] Starting Cloudflare Tunnel (Port 80 Forwarding)...
"C:\Users\Laptop\Desktop\PROJECT MPSC\sync\cloudflared.exe" tunnel --protocol http2 run --token eyJhIjoiNjJiNDM1Y2Q2ZTA4NjA1ZjJjN2MxYWFkZWRjNmE1OTEiLCJ0IjoiNmRlMDcxZTYtY2M1ZS00N2NiLTk3ZGQtNGZkYjc1NjUzYWYzIiwicyI6Ik9ETTFOV0k0WVdNdFpEZ3pZaTAwWlRKbExUZzRNelF0TXpoaVlqQmxNMk00Tm1RMCJ9
