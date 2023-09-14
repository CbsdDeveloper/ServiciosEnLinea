@echo off

goto comment
	Author: Lalytto
	Email: apinango@lalytto.com
	
	Description: Sincronización de archivos locales con servidor principal
:comment

REM VARIABLES GLOBALES - DEV
REM SET IP_REMOTE=D:\Lalytto\Git\
SET IP_REMOTE=C:\Server\
SET SRC_ROUTE=autoInspeccion\app\src\

REM dev
robocopy %IP_REMOTE%%SRC_ROUTE%gesdoc \\localhost\Shared\gesdoc /s /e
robocopy %IP_REMOTE%%SRC_ROUTE%prevencion \\localhost\Shared\prevencion /s /e
robocopy %IP_REMOTE%%SRC_ROUTE%tthh \\localhost\Shared\tthh /s /e