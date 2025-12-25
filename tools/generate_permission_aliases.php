<?php
// Script: tools/generate_permission_aliases.php
// Usage: php tools/generate_permission_aliases.php

declare(strict_types=1);

function findFiles(array $dirs, array $exts = ['php', 'blade.php']) {
    $files = [];
    foreach ($dirs as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $name = $file->getFilename();
            foreach ($exts as $ext) {
                if (str_ends_with($name, $ext)) {
                    $files[] = $file->getPathname();
                    break;
                }
            }
        }
    }
    return $files;
}

// Load canonical permission slugs from config
$configFile = __DIR__ . '/..//config/permissions.php';
$canonical = [];
if (file_exists($configFile)) {
    $cfg = require $configFile;
    $can = $cfg['canonical'] ?? [];
    foreach ($can as $group => $list) {
        foreach ($list as $s) $canonical[] = $s;
    }
}

// Scan repository for hasPermission('...') occurrences
$roots = [__DIR__ . '/..'];
$files = findFiles($roots, ['php', 'blade.php']);

$pattern = '/hasPermission\(\s*["\']([^"\']+)["\']\s*\)/';
$found = [];
foreach ($files as $f) {
    $text = @file_get_contents($f);
    if ($text === false) continue;
    if (preg_match_all($pattern, $text, $m)) {
        foreach ($m[1] as $slug) $found[] = $slug;
    }
}

$found = array_values(array_unique($found));
sort($found);

$canonical = array_values(array_unique($canonical));
sort($canonical);

$suggestions = [];

// helper: try direct equivalents (.- swap)
function swapDotsDashes(string $s): string {
    if (str_contains($s, '.')) return str_replace('.', '-', $s);
    if (str_contains($s, '-')) return str_replace('-', '.', $s);
    return $s;
}

foreach ($found as $slug) {
    if (in_array($slug, $canonical, true)) continue;

    // try dot/dash swap
    $swap = swapDotsDashes($slug);
    if (in_array($swap, $canonical, true)) {
        $suggestions[$slug] = $swap;
        continue;
    }

    // levenshtein best match
    $best = null;
    $bestScore = PHP_INT_MAX;
    foreach ($canonical as $c) {
        $d = levenshtein($slug, $c);
        if ($d < $bestScore) {
            $bestScore = $d;
            $best = $c;
        }
    }

    // apply threshold heuristics
    if ($best !== null) {
        $len = max(1, strlen($best));
        $ratio = $bestScore / $len;
        if ($bestScore <= 3 || $ratio <= 0.33) {
            $suggestions[$slug] = $best;
            continue;
        }
    }

    // substring match
    foreach ($canonical as $c) {
        if (str_contains($slug, $c) || str_contains($c, $slug)) {
            $suggestions[$slug] = $c;
            break 1;
        }
    }
}

$out = [
    'found' => $found,
    'canonical' => $canonical,
    'suggestions' => $suggestions,
];

$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/..//permissions_aliases_suggested.json', $json);

echo "Wrote permissions_aliases_suggested.json with " . count($suggestions) . " suggested aliases\n";
