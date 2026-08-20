@echo off
rem ==============================================================================
rem Conn2Flow Core CLI Wrapper (Windows Command Prompt)
rem ==============================================================================

php "%~dp0cli\c2f.php" %*
exit /b %ERRORLEVEL%
