$p = Start-Process -FilePath "C:\xampp\php\php.exe" -ArgumentList "-S 127.0.0.1:8096 -t c:\xampp\htdocs\uline\public" -PassThru
Start-Sleep -Seconds 2
& "C:\xampp\php\php.exe" tests\result_http_e2e_test.php 8096
Stop-Process -Id $p.Id -Force
