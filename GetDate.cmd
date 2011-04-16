@echo off&SETLOCAL

:: This will return date into environment vars
:: Works on any NT/2K/XP machine independent of regional date settings
:: 20 March 2002

FOR /f "tokens=1-4 delims=/-. " %%G IN ('date /t') DO (call :s_fixdate %%G %%H %%I %%J)
goto :s_print_the_date
   
:s_fixdate
if "%1:~0,1%" GTR "9" shift
FOR /f "skip=1 tokens=2-4 delims=(-)" %%G IN ('echo.^|date') DO (
  set %%G=%1&set %%H=%2&set %%I=%3)
goto :eof

:s_print_the_date
echo Month:[%mm%]  Day:[%dd%]  Year:[%yy%]
ENDLOCAL&SET _mm=%mm%&SET _dd=%dd%&SET _yy=%yy%