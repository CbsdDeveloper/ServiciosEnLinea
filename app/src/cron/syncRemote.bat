@echo off

goto comment
	Author: Lalytto
	Email: apinango@lalytto.com
	
	Description: Este archivo es para copiar los archivos remotos utilizados por la aplicación
:comment

REM VARIABLES GLOBALES - DEV
REM SET IP_REMOTE=D:\Lalytto\Git\
SET IP_REMOTE=C:\Server\www\
REM SET IP_REMOTE="C:\Server\www\
SET SRC_ROUTE=autoInspeccion\app\src\

REM CONECTAR RED
net use L: \\192.168.1.93\Shared /user:PREVENCION 123456 /persistent:yes
REM dev
robocopy L:\gesdoc %IP_REMOTE%%SRC_ROUTE%gesdoc /s /e
robocopy L:\prevencion %IP_REMOTE%%SRC_ROUTE%prevencion /s /e
robocopy L:\tthh %IP_REMOTE%%SRC_ROUTE%tthh /s /e
REM ELIMINAR CONEXION
net use L: /delete

REM CONECTAR RED
net use L: \\192.168.1.61\Shared /user:PREVENCION 123456 /persistent:yes
REM dev
robocopy L:\gesdoc %IP_REMOTE%%SRC_ROUTE%gesdoc /s /e
robocopy L:\prevencion %IP_REMOTE%%SRC_ROUTE%prevencion /s /e
robocopy L:\tthh %IP_REMOTE%%SRC_ROUTE%tthh /s /e
REM ELIMINAR CONEXION
net use L: /delete