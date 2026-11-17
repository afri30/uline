$port = 8099
$phpExe = "C:\xampp\php\php.exe"
$docRoot = "c:\xampp\htdocs\uline\public"

Write-Host "Starting temporary PHP built-in server on port $port..." -ForegroundColor Cyan
$serverProcess = Start-Process -FilePath $phpExe -ArgumentList "-S 127.0.0.1:$port -t $docRoot" -PassThru -NoNewWindow
Start-Sleep -Seconds 2

try {
    Write-Host "Running NVIDIA AI Chatbot & Guardrails E2E tests..." -ForegroundColor Yellow
    & $phpExe "tests/chatbot_http_e2e_test.php" $port
    $testExitCode = $LASTEXITCODE
} finally {
    Write-Host "Stopping PHP built-in server (PID: $($serverProcess.Id))..." -ForegroundColor Cyan
    Stop-Process -Id $serverProcess.Id -Force -ErrorAction SilentlyContinue
}

exit $testExitCode
