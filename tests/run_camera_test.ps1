$p = Start-Process -FilePath "C:\xampp\php\php.exe" -ArgumentList "-S 127.0.0.1:8099 -t c:\xampp\htdocs\uline\public" -PassThru
Start-Sleep -Seconds 2
& "C:\xampp\php\php.exe" tests\camera_http_test.php 8099
Stop-Process -Id $p.Id -Force
