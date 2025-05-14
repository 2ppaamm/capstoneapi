@echo off
setlocal enabledelayedexpansion

:: Set the folder relative to the root of your Laravel project
set "folder=public\videos\fields\15"

:: Check if folder exists
if not exist "%folder%" (
    echo Folder not found: %folder%
    pause
    exit /b
)

:: Change directory
pushd "%folder%"

:: Rename each file to lowercase
for %%F in (*.*) do (
    set "name=%%~nxF"
    set "lower="
    for %%A in (!name!) do (
        call :tolower "%%~A"
        ren "%%F" "!lower!"
    )
)

popd
echo All files renamed to lowercase in %folder%
pause
exit /b

:tolower
set "lower=%~1"
for %%a in (A B C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    set "lower=!lower:%%a=%%a!"
)
exit /b
