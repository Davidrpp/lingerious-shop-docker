# Verify links rendered in the public footer, not just links in the theme source.
$ErrorActionPreference = 'Stop'
$baseUrl = 'https://lingerious.shop'
$homepageHtml = (Invoke-WebRequest -UseBasicParsing -Uri $baseUrl -TimeoutSec 20).Content
$footer = [regex]::Match($homepageHtml, '(?is)<footer\b[^>]*class="[^"]*lg-footer[^"]*"[^>]*>.*?</footer>').Value
if (-not $footer) { throw 'Public footer was not rendered.' }
$paths = [regex]::Matches($footer, 'href="([^"]+)"') | ForEach-Object { $_.Groups[1].Value } | Sort-Object -Unique
if ($paths.Count -lt 12) { throw "Only $($paths.Count) footer links found." }
$failures = @()
foreach ($path in $paths) {
    $url = if ($path.StartsWith('/')) { $baseUrl + $path } else { $path }
    $result = curl.exe --max-time 18 -L -sS -o NUL -w '%{http_code} %{url_effective}' $url
    Write-Output "$path => $result"
    if ($result -notmatch '^200 https://lingerious\.shop/') { $failures += $path }
}
foreach ($path in @('/about/', '/returns/')) {
    $html = (Invoke-WebRequest -UseBasicParsing -Uri ($baseUrl + $path + '?footerqa=1') -TimeoutSec 20).Content
    $headings = [regex]::Matches($html, '(?is)<h1\b[^>]*>.*?</h1>')
    $canonical = [regex]::Match($html, '(?is)<link[^>]*rel="canonical"[^>]*href="([^"]+)"').Groups[1].Value
    Write-Output "SEO $path H1=$($headings.Count) canonical=$canonical"
    if ($headings.Count -ne 1 -or $canonical -ne ($baseUrl + $path)) { $failures += "SEO $path" }
}
Write-Output "FOOTER_LINKS=$($paths.Count) FAILURES=$($failures.Count)"
if ($failures.Count) { throw "Footer QA failed: $($failures -join ', ')" }
Write-Output 'FOOTER_QA_PASS'
