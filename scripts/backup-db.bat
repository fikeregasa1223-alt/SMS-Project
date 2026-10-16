@echo off
REM ============================================================
REM SMS - Database Backup Script (Windows)
REM ============================================================

SETLOCAL

REM Configuration
SET DB_NAME=sms-db
SET DB_USER=root
SET DB_PASS=
SET DB_HOST=127.0.0.1
SET MYSQL_BIN=C:\xampp\mysql\bin\mysqldump.exe

REM Backup folder
SET BACKUP_DIR=C:\xampp\htdocs\SMS\backups
IF NOT EXIST "%BACKUP_DIR%" MKDIR "%BACKUP_DIR%"

REM Timestamp
FOR /F "tokens=2 delims==" %%I IN ('wmic os get localdatetime /value') DO SET DT=%%I
SET STAMP=%DT:~0,4%-%DT:~4,2%-%DT:~6,2%_%DT:~8,2%-%DT:~10,2%-%DT:~12,2%

SET OUTFILE=%BACKUP_DIR%\%DB_NAME%_%STAMP%.sql

echo Backing up %DB_NAME% to %OUTFILE% ...

IF "%DB_PASS%"=="" (
    "%MYSQL_BIN%" -h %DB_HOST% -u %DB_USER% --routines --triggers --single-transaction --default-character-set=utf8mb4 %DB_NAME% > "%OUTFILE%"
) ELSE (
    "%MYSQL_BIN%" -h %DB_HOST% -u %DB_USER% -p%DB_PASS% --routines --triggers --single-transaction --default-character-set=utf8mb4 %DB_NAME% > "%OUTFILE%"
)

IF %ERRORLEVEL% EQU 0 (
    echo Backup complete: %OUTFILE%
) ELSE (
    echo Backup FAILED with code %ERRORLEVEL%
)

REM Delete backups older than 30 days
FORFILES /P "%BACKUP_DIR%" /M *.sql /D -30 /C "cmd /c del @path" 2>NUL

ENDLOCAL