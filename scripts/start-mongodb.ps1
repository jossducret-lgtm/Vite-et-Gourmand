# MongoDB local sans Docker (ECF Vite & Gourmand)
# Usage : powershell -ExecutionPolicy Bypass -File scripts/start-mongodb.ps1

$ErrorActionPreference = "Stop"
$baseDir = Join-Path $env:LOCALAPPDATA "ViteEtGourmand\mongodb"
$dataDir = Join-Path $baseDir "data"
$logFile = Join-Path $baseDir "mongod.log"
$lockFile = Join-Path $baseDir ".download.lock"
$zipUrl = "https://fastdl.mongodb.org/windows/mongodb-windows-x86_64-7.0.21.zip"
$zipPath = Join-Path $baseDir "mongodb.zip"
$zipPartPath = Join-Path $baseDir "mongodb.zip.part"
$minZipSizeBytes = 500MB

function Test-MongoPort {
    return (Test-NetConnection -ComputerName 127.0.0.1 -Port 27017 -WarningAction SilentlyContinue).TcpTestSucceeded
}

function Get-MongodBinary {
    return Get-ChildItem -Path $baseDir -Recurse -Filter "mongod.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
}

function Test-ValidZip {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return $false }
    if ((Get-Item $Path).Length -lt $minZipSizeBytes) { return $false }
    try {
        Add-Type -AssemblyName System.IO.Compression.FileSystem -ErrorAction Stop
        [System.IO.Compression.ZipFile]::OpenRead($Path).Dispose()
        return $true
    } catch {
        return $false
    }
}

function Acquire-DownloadLock {
    if (Test-Path $lockFile) {
        $lockAge = (Get-Date) - (Get-Item $lockFile).LastWriteTime
        if ($lockAge.TotalMinutes -lt 30) {
            Write-Error "Un telechargement MongoDB est deja en cours (verrou actif). Attendez la fin ou supprimez $lockFile si bloque."
        }
        Remove-Item $lockFile -Force
    }
    New-Item -ItemType File -Path $lockFile -Force | Out-Null
}

function Release-DownloadLock {
    if (Test-Path $lockFile) {
        Remove-Item $lockFile -Force
    }
}

New-Item -ItemType Directory -Force -Path $baseDir, $dataDir | Out-Null

if (Test-MongoPort) {
    Write-Host "MongoDB deja actif sur 127.0.0.1:27017"
    Write-Host "Synchronisez les commandes : php bin/console app:mongo:sync-orders"
    exit 0
}

$mongod = Get-MongodBinary

if (-not $mongod) {
    if (Get-Process -Name mongod -ErrorAction SilentlyContinue) {
        Write-Error "mongod.exe est en cours d execution mais le binaire est introuvable. Arretez le processus mongod puis relancez le script."
    }

    if (-not (Test-ValidZip $zipPath)) {
        if (Test-Path $zipPath) {
            Write-Host "Archive incomplete ou corrompue, nouveau telechargement..."
            Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
        }
        if (Test-Path $zipPartPath) {
            Remove-Item $zipPartPath -Force -ErrorAction SilentlyContinue
        }

        Acquire-DownloadLock
        try {
            Write-Host "Telechargement MongoDB 7.0 (environ 600 Mo, une seule fois)..."
            Invoke-WebRequest -Uri $zipUrl -OutFile $zipPartPath -UseBasicParsing
            Move-Item -Path $zipPartPath -Destination $zipPath -Force
        } finally {
            Release-DownloadLock
        }

        if (-not (Test-ValidZip $zipPath)) {
            Write-Error "Telechargement MongoDB invalide. Relancez le script."
        }
    }

    Write-Host "Extraction..."
    Expand-Archive -Path $zipPath -DestinationPath $baseDir -Force
    Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
    $mongod = Get-MongodBinary
}

if (-not $mongod) {
    Write-Error "mongod.exe introuvable apres extraction."
}

Write-Host "Demarrage MongoDB : $($mongod.FullName)"
Start-Process -FilePath $mongod.FullName -ArgumentList @(
    "--dbpath", $dataDir,
    "--port", "27017",
    "--bind_ip", "127.0.0.1",
    "--logpath", $logFile,
    "--logappend"
) -WindowStyle Hidden

Start-Sleep -Seconds 3

if (Test-MongoPort) {
    Write-Host "MongoDB demarre. URL : mongodb://127.0.0.1:27017"
    Write-Host "Synchronisez les commandes : php bin/console app:mongo:sync-orders"
    exit 0
}

Write-Error "MongoDB n'a pas demarre. Consultez $logFile"
