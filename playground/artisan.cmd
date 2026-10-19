@ECHO OFF
SETLOCAL
REM artisan – AutoLaravel CLI (Windows)
REM Forwards to .auto/cli.js so `artisan <command>` works without `php`.

SET "CLI=%~dp0.auto\cli.js"
IF NOT EXIST "%CLI%" (
    ECHO artisan: .auto/cli.js not found. Run this from the project root. 1>&2
    EXIT /B 1
)

node "%CLI%" %*
EXIT /B %ERRORLEVEL%
