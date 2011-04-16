@echo off
SETLOCAL
For /f "tokens=1-3 delims=1234567890 " %%a in ("%time%") Do set "delims=%%a%%b%%c"
For /f "tokens=1-4 delims=%delims%" %%G in ("%time%") Do (
  Set _hh=%%G
  Set _min=%%H
  Set _ss=%%I
  Set _ms=%%J
)
:: Strip any leading spaces
Set _hh=%_hh: =%

:: Ensure the hours have a leading zero
if 1%_hh% LSS 20 Set _hh=0%_hh%

Echo The time is:   %_hh%:%_min%:%_ss%
ENDLOCAL&Set _time=%_hh%:%_min%&Set _hh=%_hh%&Set _min=%_min%&Set _ss=%_ss%