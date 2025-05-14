@echo off
setlocal enabledelayedexpansion

:: Set base folder path
set "baseFolder=public\videos\fields"

:: Navigate to the folder
if not exist "%baseFolder%" (
    echo Folder not found: %baseFolder%
    pause
    exit /b
)

cd /d "%baseFolder%"

:: Loop through all folders that contain a space
for /d %%F in (* *) do (
    set "oldName=%%F"
    for /f "tokens=1 delims= " %%A in ("%%F") do set "newName=%%A"

    if not "!oldName!"=="!newName!" (
        echo Renaming "!oldName!" to "!newName!"
        ren "!oldName!" "!newName!"
    )
)

echo All folder names trimmed after first space.
pause
exit /b
