@ECHO OFF
set PHPDIR=D:\XAMPP\php
%PHPDIR%\php phpunit.phar -c phpunit.xml %1 %2
pause