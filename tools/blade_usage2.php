<?php
$root = getcwd();
$viewDir = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewDir, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/\.blade\.php$/', $file->getFilename())) {
        $rel = str_replace('\\', '/', substr($file->getRealPath(), strlen($viewDir) + 1));
        $bladeFiles[$rel] = $file->getRealPath();
    }
}

$sourceFiles = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iter as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getRealPath();
    if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    if (strpos($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views') !== false) {
        continue;
    }
    if (preg_match('/\.(php|blade\.php)$/', $path)) {
        $sourceFiles[] = $path;
    }
}

$references = [];
foreach ($sourceFiles as $path) {
    $text = file_get_contents($path);
    if ($text === false) {
        continue;
    }
    // general view names
    if (preg_match_all('/(?:view|@include|@extends|@component|@includeIf|@includeWhen|@includeFirst|\$__env->make)\(\s*["\']([a-zA-Z0-9_.\/\-]+)["\']/', $text, $m)) {
        foreach ($m[1] as $ref) {
            $references[$ref] = true;
        }
    }
    if (preg_match_all('/<x-([a-zA-Z0-9_.\-]+)(?:[\s>\/])/', $text, $m)) {
        foreach ($m[1] as $ref) {
            $references[str_replace('-', '.', $ref)] = true;
            $references[$ref] = true;
        }
    }
}

$unused = [];
foreach ($bladeFiles as $rel => $fullPath) {
    $viewDot = preg_replace('#\.blade\.php$#', '', $rel);
    $viewDot = str_replace('/', '.', $viewDot);
    $canonical = $viewDot;
    $slash = str_replace('.', '/', $canonical);
    $searchKeys = [$canonical, $slash];
    if (strpos($rel, 'components/') === 0) {
        $leaf = basename($rel, '.blade.php');
        $searchKeys[] = '<x-' . str_replace('.', '-', $canonical);
        $searchKeys[] = '<x-' . $leaf;
    }
    $found = false;
    foreach ($searchKeys as $key) {
        if (isset($references[$key])) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $unused[] = $canonical;
    }
}

echo "Total views: " . count($bladeFiles) . "\n";
echo "Source files scanned: " . count($sourceFiles) . "\n";
echo "Unused candidates: " . count($unused) . "\n";
foreach ($unused as $view) {
    echo $view . "\n";
}

echo "\nDuplicate groups:\n";
$hashGroups = [];
foreach ($bladeFiles as $rel => $fullPath) {
    $hash = md5_file($fullPath);
    $hashGroups[$hash][] = $rel;
}
foreach ($hashGroups as $hash => $group) {
    if (count($group) > 1) {
        echo "---\n";
        foreach ($group as $rel) {
            echo $rel . "\n";
        }
    }
}
