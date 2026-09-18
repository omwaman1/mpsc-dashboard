@echo off
title MPSC Telegram Past History Scanner
cd /d "%~dp0"
echo ===================================================
echo Scanning Past Group Messages for Testbook Queries...
echo ===================================================
python scan_past_messages.py
pause
