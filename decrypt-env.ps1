# decrypt-env.ps1
# Dechiffre .env.sops vers .env pour usage local (docker compose up)
# Usage : .\decrypt-env.ps1

$env:SOPS_AGE_KEY_FILE = Join-Path $PSScriptRoot "afrikraga-age-key.txt"

if (-not (Test-Path $env:SOPS_AGE_KEY_FILE)) {
    Write-Host "ERREUR : cle privee age introuvable a $env:SOPS_AGE_KEY_FILE" -ForegroundColor Red
    exit 1
}

Write-Host "Dechiffrement de .env.sops vers .env..." -ForegroundColor Cyan
sops -d --input-type dotenv --output-type dotenv .env.sops > .env

if ($LASTEXITCODE -eq 0) {
    Write-Host ".env genere avec succes." -ForegroundColor Green
} else {
    Write-Host "ECHEC du dechiffrement." -ForegroundColor Red
    exit 1
}
