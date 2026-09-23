[CmdletBinding()]
param([switch]$Demo, [switch]$SkipAdmin)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
function Invoke-Checked {
    param([string]$Command, [string[]]$Arguments)
    & $Command @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Command failed (exit $LASTEXITCODE): $Command $($Arguments -join ' ')" }
}
Push-Location $root
try {
    Get-Command php -ErrorAction Stop | Out-Null
    Get-Command composer -ErrorAction Stop | Out-Null
    Invoke-Checked -Command "php" -Arguments @("scripts/prepare.php")
    Invoke-Checked -Command "composer" -Arguments @("install", "--prefer-dist", "--no-interaction")
    Invoke-Checked -Command "php" -Arguments @("artisan", "config:clear")
    $arguments = @("artisan", "quizora:install")
    if ($Demo) { $arguments += "--demo" }
    if ($SkipAdmin) { $arguments += "--skip-admin" }
    Invoke-Checked -Command "php" -Arguments $arguments
}
finally { Pop-Location }
