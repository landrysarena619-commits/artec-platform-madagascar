@echo off
cd /d "%~dp0"
"%~dp0venv\Scripts\python.exe" predict.py >> "%~dp0logs\ml.log" 2>&1