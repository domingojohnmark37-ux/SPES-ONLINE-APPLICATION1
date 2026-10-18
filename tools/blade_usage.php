<?php
$root = getcwd();
$viewDir = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewDir));
foreach ($iterator as $file) {
    if ($file->isFile() && substr($file->getFilename(), -10) === '.blade.php') {
        $rel = str_replace('\\', '/', substr($file->getRealPath(), strlen($viewDir) + 1));
        $bladeFiles[$rel] = $file->getRealPath();
    }
}

$sourceFiles = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iter as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getRealPath();
    if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    if (preg_match('/\.(php|blade\.php)$/', $path)) {
        $sourceFiles[] = $path;
    }
}

$refs = [];
foreach ($sourceFiles as $path) {
    $text = file_get_contents($path);
    if ($text === false) {
        continue;
    }
    // view('auth.login'), @include('auth.login'), @extends('layouts.app'), $__env->make('...')
    preg_match_all('/\b(?:view|@include|@extends|@component|@includeIf|@includeWhen|@includeFirst|\$__env->make)\(\s*["\']([a-zA-Z0-9_\.\/\-]+)["\']/', $text, $m);
    foreach ($m[1] as $ref) {
        $refs[$ref] = true;
    }
    // component tags: <x-app-layout>, <x-dropdown-link>, <x-admin.users> etc.
    preg_match_all('/<x-([a-zA-Z0-9_.\-]+)([\s>\/])/', $text, $m);
    foreach ($m[1] as $ref) {
        // allow dot and dash representation
        $refs[str_replace('-', '.', $ref)] = true;
        $refs[$ref] = true;
    }
}

$unused = [];
foreach ($bladeFiles as $rel => $fullPath) {
    $view = preg_replace('#\.blade\.php$#', '', $rel); // path with slash
    $viewDot = str_replace('/', '.', $view);
    $viewSlash = str_replace('.', '/', $viewDot);
    if (!isset($refs[$viewDot]) && !isset($refs[$view]) && !isset($refs[$viewSlash])) {
        $unused[] = $viewDot;
    }
}

// Duplicates by content hash
$hashes = [];
foreach ($bladeFiles as $rel => $fullPath) {
    $hash = md5_file($fullPath);
    $hashes[$hash][] = $rel;
}

echo "Total Blade files: " . count($bladeFiles) . "\n";
echo "Total source files scanned: " . count($sourceFiles) . "\n\n";
echo "Unused / unreferenced Blade candidates: " . count($unused) . "\n";
foreach ($unused as $view) {
    echo $view . "\n";
}

echo "\nDuplicate Blade content groups:\n";
foreach ($hashes as $hash => $group) {
    if (count($group) > 1) {
        echo "---\n";
        foreach ($group as $rel) {
            echo $rel . "\n";
        }
    }
}
