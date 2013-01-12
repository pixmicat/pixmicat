@ECHO OFF
set PHPDIR=..\..\..\php
%PHPDIR%\php phpunit.phar --no-globals-backup --verbose %1 %2 %~dp0
pause