[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$cf = Join-Path $PSScriptRoot "cloudflared.exe"
if (-not (Test-Path $cf)) {
    Write-Host "Error: cloudflared.exe was not found in $PSScriptRoot" -ForegroundColor Red
    exit 1
}

# Automatically calculate the web path whether on WAMP (www) or XAMPP (htdocs)
$projectDir = (Get-Item (Join-Path $PSScriptRoot "..")).FullName
$webSubpath = ""

if ($projectDir -match '(?i)[\\/]htdocs[\\/](.+)$') {
    $webSubpath = $matches[1] -replace '\\', '/'
} elseif ($projectDir -match '(?i)[\\/]www[\\/](.+)$') {
    $webSubpath = $matches[1] -replace '\\', '/'
} else {
    $webSubpath = (Split-Path $projectDir -Leaf)
}

$webSubpath = $webSubpath.Trim('/')
if ($webSubpath -ne '') {
    $webSubpath = "$webSubpath/"
}

$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $cf
$psi.Arguments = "tunnel --url http://localhost:80"
$psi.RedirectStandardError = $true
$psi.RedirectStandardOutput = $true
$psi.UseShellExecute = $false
$psi.CreateNoWindow = $true

$proc = [System.Diagnostics.Process]::Start($psi)
$urlPrinted = $false

Write-Host "Establishing secure tunnel to Cloudflare..." -ForegroundColor Cyan

while (-not $proc.HasExited) {
    $line = $proc.StandardError.ReadLine()
    if ($line) {
        if (-not $urlPrinted -and $line -match 'https://[a-zA-Z0-9\-]+\.trycloudflare\.com') {
            $urlPrinted = $true
            $rawUrl = $matches[0]
            Write-Host ""
            Write-Host "==========================================================================" -ForegroundColor Green
            Write-Host "  🎉 YOUR PUBLIC TRAVEL COMPANION WEBSITE IS LIVE:" -ForegroundColor Yellow
            Write-Host "  $rawUrl/$webSubpath" -ForegroundColor Cyan
            Write-Host "==========================================================================" -ForegroundColor Green
            Write-Host "Share this link with anyone! Keep this window open while hosting." -ForegroundColor White
            Write-Host ""
        }
    }
}
