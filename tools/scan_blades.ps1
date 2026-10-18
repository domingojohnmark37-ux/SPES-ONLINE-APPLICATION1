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
$patterns = @(
    "view('",
    "view(\"",
    "@include('",
    "@include(\"",
    "@extends('",
    "@extends(\"",
    "@component('",
    "@component(\"",
    "\$__env->make('",
    "\$__env->make(\""
)
foreach ($path in $sourceFiles) {
    $text = Get-Content -Path $path -Raw -ErrorAction SilentlyContinue
    if (-not $text) { continue }
    foreach ($view in $views.Keys) {
        $rel = $views[$view]
        $candidates = @(
            "view('$view'",
            "view(\"$view\"",
            "@include('$view'",
            "@include(\"$view\"",
            "@extends('$view'",
            "@extends(\"$view\"",
            "@component('$view'",
            "@component(\"$view\"",
            "\$__env->make('$view'",
            "\$__env->make(\"$view\"",
            $rel,
            $rel.Replace('/', '.')
        )
        if ($rel -like 'components/*') {
            $leaf = [System.IO.Path]::GetFileNameWithoutExtension($rel)
            $candidates += "<x-$leaf"
        }
        foreach ($cand in $candidates) {
            if ($text -like "*${cand}*") { $refs.Add($view) | Out-Null; break }
        }
    }
}
$unused = @()
foreach ($view in $views.Keys) {
    if (-not $refs.Contains($view)) { $unused += $view }
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
        $hashes[$k] | ForEach-Object { Write-Output $_ }
    }
}