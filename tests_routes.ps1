# Route Testing Script for Contrats & Discussions Module
# Tests all routes and reports HTTP status codes

Write-Host "" -ForegroundColor Cyan
Write-Host "=== TESTING SYMFONY ROUTES ===" -ForegroundColor Cyan
Write-Host "Base URL: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host "" -ForegroundColor Yellow

$baseUrl = "http://127.0.0.1:8000"
$results = @()

# Test routes (GET requests that don't require parameters)
$testRoutes = @(
    @{Path="/"; Name="Home"},
    @{Path="/auth/login"; Name="Login"},
    @{Path="/discussion"; Name="Discussion Index"},
    @{Path="/discussion/new"; Name="New Discussion"},
    @{Path="/contrat"; Name="Contrat Index"},
    @{Path="/contrat/new"; Name="New Contrat"}
)

Write-Host "Testing Routes..." -ForegroundColor Green
Write-Host ("=" * 80)

foreach ($route in $testRoutes) {
    try {
        $response = Invoke-WebRequest -Uri "$baseUrl$($route.Path)" -Method GET -UseBasicParsing -ErrorAction Stop
        $status = $response.StatusCode
        $statusColor = "Green"
        $statusText = "OK"
    }
    catch {
        $status = $_.Exception.Response.StatusCode.value__
        if ($status -ge 400 -and $status -lt 500) {
            $statusColor = "Yellow"
            $statusText = "CLIENT ERROR"
        }
        elseif ($status -ge 500) {
            $statusColor = "Red"
            $statusText = "SERVER ERROR"
        }
        else {
            $statusColor = "Gray"
            $statusText = "REDIRECT/OTHER"
        }
    }
    
    $result = [PSCustomObject]@{
        Route = $route.Name
        Path = $route.Path
        Status = $status
        Category = $statusText
    }
    
    $results += $result
    
    Write-Host ("[{0}] {1,-30} {2}" -f $status, $route.Name, $route.Path) -ForegroundColor $statusColor
}

Write-Host ""
Write-Host ("=" * 80)
Write-Host ""
Write-Host "Summary:" -ForegroundColor Cyan

$success = ($results | Where-Object { $_.Status -eq 200 }).Count
$clientErrors = ($results | Where-Object { $_.Status -ge 400 -and $_.Status -lt 500 }).Count
$serverErrors = ($results | Where-Object { $_.Status -ge 500 }).Count
$redirects = ($results | Where-Object { $_.Status -ge 300 -and $_.Status -lt 400 }).Count

Write-Host "  Total Routes Tested: $($results.Count)"
Write-Host "  Success (200): $success" -ForegroundColor Green
Write-Host "  Redirects (3xx): $redirects" -ForegroundColor Gray
Write-Host "  Client Errors (4xx): $clientErrors" -ForegroundColor Yellow
Write-Host "  Server Errors (5xx): $serverErrors" -ForegroundColor $(if ($serverErrors -gt 0) { "Red" } else { "Gray" })

if ($serverErrors -gt 0) {
    Write-Host ""
    Write-Host "WARNING: SERVER ERRORS DETECTED!" -ForegroundColor Red
    Write-Host "Check the following routes:" -ForegroundColor Red
    $results | Where-Object { $_.Status -ge 500 } | ForEach-Object {
        Write-Host "  - $($_.Path) [$($_.Status)]" -ForegroundColor Red
    }
}
elseif ($clientErrors -gt 0) {
    Write-Host ""
    Write-Host "INFO: Some routes require authentication or parameters" -ForegroundColor Yellow
}
else {
    Write-Host ""
    Write-Host "SUCCESS: All routes responding successfully!" -ForegroundColor Green
}

Write-Host ""
