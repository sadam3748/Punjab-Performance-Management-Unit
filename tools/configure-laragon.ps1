$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$publicRoot = (Resolve-Path (Join-Path $projectRoot 'public')).Path.Replace('\', '/')
$hostsPath = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$vhostPath = 'D:\laragon\etc\apache2\sites-enabled\auto.ppmu.test.conf'
$apacheExe = 'D:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe'

$hosts = Get-Content -LiteralPath $hostsPath -Raw
if ($hosts -notmatch '(?im)^\s*127\.0\.0\.1\s+ppmu\.test(?:\s|$)') {
    Add-Content -LiteralPath $hostsPath -Value "`r`n127.0.0.1      ppmu.test #laragon magic!"
}

$vhost = @"
<VirtualHost *:80>
    DocumentRoot "$publicRoot"
    ServerName ppmu.test
    ServerAlias *.ppmu.test
    <Directory "$publicRoot">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@

Set-Content -LiteralPath $vhostPath -Value $vhost -Encoding ASCII

& $apacheExe -t
if ($LASTEXITCODE -ne 0) {
    throw 'Apache configuration validation failed.'
}

& $apacheExe -k restart
ipconfig /flushdns | Out-Null

Write-Output 'Laragon host configured: http://ppmu.test/'
