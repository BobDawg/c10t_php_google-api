@echo off
setlocal
call GetDate.cmd > nul
call GetTime.cmd > nul
set myTimeStamp=%_YY%-%_MM%-%_DD% %_time%

title Minecraft Google Map (Started: %myTimeStamp%)
endlocal

:SetVars
setlocal
set mapName=Latest_Overhead_Map
set absC10tDir=D:\MinecraftTools\c10t
set absLiveWorldDir=D:\MinecraftServer\world
set absTmpWorldDir=D:\MinecraftTools\TMP\world
set relTmpWorldDir=.\..\TMP\world\
set relTmpMapDir=.\..\TMP\Maps\%mapName%\
set relLiveMapDir=.\..\..\apache2triad\htdocs\minecraft\%mapName%\
set absFaviconDir=D:\MinecraftTools\favicons
set absTmpMapDir=D:\MinecraftTools\TMP\Maps\%mapName%
set absLiveMapDir=D:\apache2triad\htdocs\minecraft\%mapName%

:MakeTmpCopy
echo Now making a copy of world to be rendered:
robocopy %absLiveWorldDir% %absTmpWorldDir% /ZB /COPYALL /MIR /NDL /NFL /NS /NC /R:8 /W:15
echo.

:MakeMap
echo Now Generating Overhead Google Map:
pushd %absC10tDir%\
php google-api.php -w=%relTmpWorldDir% -o=%relTmpMapDir% -O="--striped-terrain --show-players --show-signs"
echo.

:CopyTileResizer
::echo Now Copying Just-in-Time Tile Resizer:
::copy /Y %absC10tDir%\rs.php %absTmpMapDir%

:AddFavicons
echo Now Adding Favicons to Folder:
copy /Y %absFaviconDir%\favicon.gif %absTmpMapDir%
copy /Y %absFaviconDir%\favicon.png %absTmpMapDir%
copy /Y %absFaviconDir%\apple-touch-icon.png %absTmpMapDir%
copy /Y %absFaviconDir%\apple-touch-icon-57x57.png %absTmpMapDir%
copy /Y %absFaviconDir%\apple-touch-icon-72x72.png %absTmpMapDir%
copy /Y %absFaviconDir%\apple-touch-icon-114x114.png %absTmpMapDir%
copy /Y %absFaviconDir%\favicon.ico %absTmpMapDir%
copy /Y %absFaviconDir%\favicon_large.ico %absTmpMapDir%
echo.

:AddEmptyPixel
echo Now Copying 'empty_pixel.png' to Folder:
copy /Y %absC10tDir%\empty_pixel.png %absTmpMapDir%\tiles
echo.

:AddJsonParser
::echo Now Adding 'json.js' to Folder:
::copy /Y %absC10tDir%\json-minified.js %absTmpMapDir%\json.js
::echo.

:WriteLastUpdateFile
::echo Writing file containing the current timestamp.
::for /f %%G in ('powershell -command "& {[int][double]::Parse((get-date -UFormat %%s))}"') do set unixTimeStamp=%%G
::echo {"lastUpdate": {"timestamp": %unixTimeStamp%}} > %absTmpMapDir%\lastUpdate.json
::echo.

:MakeNewMapLive
echo Now moving new Overhead Google Map to Live Folder:
robocopy %absTmpMapDir% %absLiveMapDir% /COPYALL /E /MOVE /PURGE /NDL /NFL /NS /NC /R:8 /W:15
echo.
endlocal
