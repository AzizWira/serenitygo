# Serenity GO source integrity check. Read-only; does not alter your files or database.
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$manifest = Join-Path $projectRoot 'docs\source-files.txt'
if (-not (Test-Path -LiteralPath $manifest)) {
    Write-Host 'Manifest tidak ditemukan: docs/source-files.txt' -ForegroundColor Red
    exit 2
}
$expected = @(Get-Content -LiteralPath $manifest | Where-Object { $_.Trim().Length -gt 0 -and -not $_.StartsWith('#') })
$missing = @()
foreach ($file in $expected) {
    $target = Join-Path $projectRoot ($file -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path -LiteralPath $target -PathType Leaf)) { $missing += $file }
}
Write-Host "Diperiksa: $($expected.Count) file source Serenity GO." -ForegroundColor Cyan
if ($missing.Count -eq 0) {
    Write-Host 'OK: Semua file source yang diharapkan ada.' -ForegroundColor Green
    exit 0
}
Write-Host "TIDAK LENGKAP: $($missing.Count) file hilang:" -ForegroundColor Red
$missing | ForEach-Object { Write-Host "  - $_" }
Write-Host 'Pulihkan file yang hilang dari ZIP source lengkap v1.2.4. Jangan timpa .env, storage, atau vendor.' -ForegroundColor Yellow
exit 1
