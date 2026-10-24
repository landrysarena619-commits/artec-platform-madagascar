@echo off
cd /d "%~dp0"
"%~dp0venv\Scripts\python.exe" train_models.py >> "%~dp0logs\ml.log" 2>&1