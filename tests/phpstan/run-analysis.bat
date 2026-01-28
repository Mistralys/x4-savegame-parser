::
:: PHPStan / Analyze application and framework classes
::
:: @template-version 1.4
::

@echo off

set MemoryLimit=900M
set OutputFile=result.txt
set ConfigFile=config.neon
set BinFolder=..\..\vendor\bin

:: Load analysis level from level.txt
for /f "usebackq delims=" %%l in ("level.txt") do set Level=%%l

cls

echo -------------------------------------------------------
echo RUNNING PHPSTAN @ LEVEL %Level%
echo -------------------------------------------------------
echo.

call %BinFolder%\phpstan analyse -c %ConfigFile% --level=%Level% --memory-limit=%MemoryLimit% > %OutputFile%

start "" "%OutputFile%"
