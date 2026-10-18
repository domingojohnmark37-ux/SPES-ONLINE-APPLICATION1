$root = Get-Location
$bladeFiles = Get-ChildItem -Path resources\views -Recurse -Filter *.blade.php -File
$views = @{}
foreach ($f in $bladeFiles) {
    $rel = $f.FullName.Substring($root.Path.Length + 1).Replace('\\', '/').Replace('resources/views/', '')
    $view = $rel.Substring(0, $rel.Length - '.blade.php'.Length).Replace('/', '.')
    $views[$view] = $rel
}
$sourceFiles = Get-ChildItem -Recurse -Include *.php,*.blade.php -File | Where-Object { $_.FullName -notmatch '\\vendor\\' }
$refs = [System.Collections.Generic.HashSet[string]]::new()
$regex = [regex]('view\(\s*''([^'']+)''|view\(\s*""([^"]+)""|@include\(\s*''([^'']+)''|@include\(\s*""([^"]+)""|@extends\(\s*''([^'']+)''|@extends\(\s*""([^"]+)""|@component\(\s*''([^'']+)''|@component\(\s*""([^"]+)""|\$__env->make\(\s*''([^'']+)''|\$__env->make\(\s*""([^"]+)""|<x-([a-zA-Z0-9_.-]+)')
foreach ($path in $sourceFiles) {
    $text = Get-Content -Path $path -Raw -ErrorAction SilentlyContinue
    if (-not $text) { continue }
    foreach ($m in $regex.Matches($text)) {
        for ($i = 1; $i -lt $m.Groups.Count; $i++) {
            $grp = $m.Groups[$i]
            if ($grp.Success) { $refs.Add($grp.Value) | Out-Null }
        }
    }
}
$unused = @()
foreach ($view in $views.Keys) {
    $rel = $views[$view]
    if (-not $refs.Contains($view) -and -not $refs.Contains($rel) -and -not $refs.Contains($rel.Replace('/', '.'))) {
        $unused += $view
    }
}
Write-Output "Total blade views: $($views.Count)"
Write-Output "Unused blade candidates: $($unused.Count)"
if ($unused.Count -gt 0) { $unused | Sort-Object | ForEach-Object { Write-Output $_ } }
Write-Output '--- Duplicate blade content groups ---'
$hashes = @{}
foreach ($f in $bladeFiles) {
    $hash = (Get-FileHash -Path $f.FullName -Algorithm MD5).Hash
    if (-not $hashes.ContainsKey($hash)) { $hashes[$hash] = @() }
    $hashes[$hash] += $f.FullName
}
foreach ($k in $hashes.Keys | Sort-Object) {
    if ($hashes[$k].Count -gt 1) {
        Write-Output '---'
        foreach ($file in $hashes[$k]) { Write-Output $file }
    }
}
