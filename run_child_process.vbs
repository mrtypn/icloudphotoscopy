'We need this file for windows 
Dim WShell
Set WShell = CreateObject("WScript.Shell")
WShell.Run "php7\php7.exe icloudphotos.php child",0
Set WShell = Nothing