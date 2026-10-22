param([string]$EnvironmentFile = (Join-Path $PSScriptRoot '..\..\.env'))

$ErrorActionPreference = 'Stop'
if (!(Test-Path -LiteralPath $EnvironmentFile)) { throw 'Laravel environment file not found.' }
$graderKeys = @('CODING_GRADER_TOKEN', 'CODING_GRADER_BIND', 'CODING_GRADER_PORT', 'CODING_GRADER_IMAGE', 'CODING_GRADER_RUNTIME')
foreach ($graderLine in Get-Content -LiteralPath $EnvironmentFile) {
    if ($graderLine -match '^\s*(CODING_GRADER_[A-Z_]+)\s*=(.*)$' -and $Matches[1] -in $graderKeys) {
        $graderName = $Matches[1]
        $graderValue = $Matches[2].Trim()
        if ($graderValue.Length -ge 2 -and (($graderValue.StartsWith('"') -and $graderValue.EndsWith('"')) -or ($graderValue.StartsWith("'") -and $graderValue.EndsWith("'")))) {
            $graderValue = $graderValue.Substring(1, $graderValue.Length - 2)
        }
        if (![Environment]::GetEnvironmentVariable($graderName, 'Process')) {
            [Environment]::SetEnvironmentVariable($graderName, $graderValue, 'Process')
        }
    }
}
if (!$env:CODING_GRADER_TOKEN -or $env:CODING_GRADER_TOKEN.Length -lt 32) { throw 'Set a random CODING_GRADER_TOKEN of at least 32 characters in the Laravel environment file.' }
if (!$env:CODING_GRADER_BIND) { $env:CODING_GRADER_BIND = '127.0.0.1' }
if (!$env:CODING_GRADER_PORT) { $env:CODING_GRADER_PORT = '8020' }
if ($env:CODING_GRADER_BIND -ne '127.0.0.1') { throw 'The development gateway must bind to 127.0.0.1.' }
$dockerOs = & docker info --format '{{.OSType}}' 2>$null
if ($LASTEXITCODE -ne 0 -or $dockerOs -ne 'linux') { throw 'Start Docker Desktop with Linux containers before starting the grader.' }
$graderImage = if ($env:CODING_GRADER_IMAGE) { $env:CODING_GRADER_IMAGE } else { 'oopy-coding-sandbox:1' }
& docker image inspect $graderImage --format '{{.Id}}' 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Build the grader image first: docker build -t oopy-coding-sandbox:1 services/coding-grader' }
Write-Output "Starting private grader on 127.0.0.1:$($env:CODING_GRADER_PORT)."
& python -u (Join-Path $PSScriptRoot 'server.py')
exit $LASTEXITCODE
