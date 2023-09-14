@echo off

goto comment
	Author: Lalytto
	Email: apinango@lalytto.com
	
	Description: Este archivo es para sacar respaldos de la base de datos usando la herramienta "pg_dump".
	File Format flags : 	
		c = Custom
		t = Tar
		p = Plain SQL
	Follow links below to apply more flags to "pg_dump" :
		http://www.postgresql.org/docs/8.4/static/app-pgdump.html
		https://www.commandprompt.com/ppbook/x17860
		http://www.brownfort.com/2014/10/backup-restore-postgresql/
	Table Excluding Flags:
		-T table_name
:comment

REM "Set following backup parameters to take backup"
SET PGPASSWORD=Cbsd2019
SET db_name=db_cbsd
SET file_format=c
SET host_name=192.168.1.3
SET port=5432
SET user_name=postgres
REM SET pg_dump_path="C:\Program Files\PostgreSQL\9.5\bin\pg_dump.exe"  
SET pg_dump_path="D:\Program Files\PostgreSQL\10\bin\pg_dump.exe"  
SET target_backup_path=D:\Lalytto\Git\autoInspeccion\app\src\backup\
SET other_pg_dump_flags=--blobs --verbose -c 

REM Fetch Current System Date and set month,day and year variables
for /f "tokens=1-4 delims=/ " %%a in ("%DATE%") do (
	set day=%%a
	set month=%%b
	set year=%%c
)
for /f "tokens=1-2 delims=: " %%i in ("%time%") do (
	set hour=%%i
	set min=%%j
)

REM Creating string for backup file name
for /f "delims=" %%i in ('dir "%target_backup_path%" /b/a-d ^| find /v /c "::"') do set count=%%i
set /a count=%count%+1 
set datestr=backup_%year%A%month%M%day%D%hour%H%min%M

REM Backup File name
set BACKUP_FILE=%db_name%_%datestr%.backup

REM :> Executing command to backup database
%pg_dump_path% --host=%host_name% -p %port% -U %user_name% --format=%file_format%  %other_pg_dump_flags% -f %target_backup_path%%BACKUP_FILE%  %db_name%