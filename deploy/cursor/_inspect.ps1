# Windows PowerShell 5.1 helper.
# Do not use bash &&, heredocs, or python -c snippets that contain -bor.
# Parks BrandDad Chrome off-screen, then classifies leftover URLs with PHP+curl.exe.
param(
	[int]$Hwnd = 197924,
	[string[]]$Url
)

$ErrorActionPreference = 'Continue'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path

python (Join-Path $here '_park_chrome.py') $Hwnd
if ($LASTEXITCODE -ne 0) {
	exit $LASTEXITCODE
}

$probe = Join-Path $here '_probe_status.php'
if ($Url -and $Url.Count -gt 0) {
	& php $probe @Url
} else {
	& php $probe
}
exit $LASTEXITCODE
