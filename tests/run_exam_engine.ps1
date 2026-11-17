$p = Start-Process -FilePath "C:\xampp\php\php.exe" -ArgumentList "-S 127.0.0.1:8098 -t c:\xampp\htdocs\uline\public" -PassThru
Start-Sleep -Seconds 2
& "C:\xampp\php\php.exe" tests\exam_engine_test.php 8098
Stop-Process -Id $p.Id -Force
