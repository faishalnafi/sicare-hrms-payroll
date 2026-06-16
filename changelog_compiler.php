<?php
/**
 * siCare Changelog Compiler & Publisher v2
 * ═══════════════════════════════════════════════════════
 * Versioning Tracks:
 * - Stable LTS: yy.05-LTS (May, subscription annual updates)
 * - Stable STS: yy.11-STS (November, one-time lifetime purchase)
 * - Beta: X.Y.Z (SemVer, no LTS/STS suffix)
 * - Pre-release: yy.mm.nnnnn (Enterprise only)
 * - Pre-production: [env]-yy.mm.nnnnn (local/tqa/stg/mtc, Enterprise only)
 *
 * Editions:
 * - Enterprise: Mono + Multi fork
 * - Community: Mono only
 */

// Define files
$trackerFile    = __DIR__ . '/tracker.md';
$archiveFile    = __DIR__ . '/tracker_archive.md';
$trackerTxtFile = __DIR__ . '/tracker.txt';
$jsonFile       = __DIR__ . '/changelog.json';
$mdFile         = __DIR__ . '/changelog.md';

// Print header
echo "\n=============================================\n";
echo "    siCare CHANGELOG COMPILER & PUBLISHER    \n";
echo "=============================================\n\n";

if (!file_exists($trackerFile)) {
    echo "Error: tracker.md not found.\n";
    exit(1);
}

// Parse command line arguments
$options     = getopt("", ["type:", "edition:", "migration:", "version:", "yes", "repo:", "alias:", "env:"]);
$type        = isset($options['type']) ? strtoupper($options['type']) : 'LTS';
$edition     = isset($options['edition']) ? ucfirst(strtolower($options['edition'])) : 'Enterprise';
$repo        = isset($options['repo']) ? strtolower($options['repo']) : 'mono';
$migration   = isset($options['migration']) ? $options['migration'] : 'stg -> production';
$aliasName   = isset($options['alias']) ? trim($options['alias']) : null;
$autoConfirm = isset($options['yes']);

// Normalize repo tag
if ($repo === 'monorepo') $repo = 'mono';
if ($repo === 'multirepo') $repo = 'multi';

// Validate repo type
if (!in_array($repo, ['mono', 'multi'])) {
    echo "Error: Invalid repo type '{$repo}'. Valid types are: mono, multi\n";
    exit(1);
}

// Validate edition
if (!in_array(strtolower($edition), ['enterprise', 'community'])) {
    echo "Error: Invalid edition '{$edition}'. Valid editions are: Enterprise, Community\n";
    exit(1);
}

// Validate: Community only supports Mono
if (strtolower($edition) === 'community' && $repo === 'multi') {
    echo "Error: Community Edition only supports Mono (monorepo). Multi (multirepo) is exclusive to Enterprise.\n";
    exit(1);
}

// Classify release types
$stableTypes     = ['LTS', 'STS'];
$betaType        = 'BETA';
$preReleaseType  = 'PRERELEASE';
$envTypes        = ['LOCAL', 'TQA', 'STG', 'MTC'];
$allValidTypes   = array_merge($stableTypes, [$betaType, $preReleaseType], $envTypes);

if (!in_array($type, $allValidTypes)) {
    echo "Error: Invalid release type '{$type}'.\n";
    echo "Valid types: " . implode(', ', $allValidTypes) . "\n";
    exit(1);
}

// Validate: Pre-release and env types are Enterprise only
$isPreRelease = ($type === $preReleaseType);
$isEnvType    = in_array($type, $envTypes);

if (($isPreRelease || $isEnvType) && strtolower($edition) === 'community') {
    echo "Error: Pre-release and environment versions are exclusive to Enterprise Edition.\n";
    exit(1);
}

// Validate: Stable versions require alias
$isStable = in_array($type, $stableTypes);
if ($isStable && empty($aliasName) && !$autoConfirm) {
    echo "Stable releases require an alias name (e.g., 'Ammonite').\n";
    echo "Enter alias name: ";
    $aliasName = trim(fgets(STDIN));
    if (empty($aliasName)) {
        echo "Error: Alias name is required for stable releases.\n";
        exit(1);
    }
} elseif ($isStable && empty($aliasName) && $autoConfirm) {
    echo "Error: Stable releases require --alias parameter.\n";
    exit(1);
}

// Read and parse tracker.md
echo "Reading tracker.md...\n";
$lines = file($trackerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$rawEntries = [];

foreach ($lines as $line) {
    if (preg_match('/^\|\s*(.*?)\s*\|$/', $line, $matches)) {
        $columns = explode('|', $matches[1]);
        $columns = array_map('trim', $columns);

        if (count($columns) < 6 || strtolower($columns[0]) === 'date' || strpos($columns[0], '---') !== false) {
            continue;
        }

        $rawEntries[] = [
            'date'        => $columns[0],
            'type'        => ucfirst(strtolower($columns[1])),
            'description' => $columns[2],
            'original'    => $columns[3],
            'stage'       => $columns[4],
            'developer'   => $columns[5]
        ];
    }
}

$entryCount = count($rawEntries);
echo "Found {$entryCount} development logs in tracker.md.\n";

if ($entryCount === 0) {
    echo "No entries to compile. Exiting.\n";
    exit(0);
}

// ── Determine version number ──────────────────────────────────
$version     = '';
$currentYear = date('y');
$currentMonth = date('m');

if (isset($options['version'])) {
    $version = $options['version'];
} else {
    if ($type === 'LTS') {
        $version = "{$currentYear}.05-LTS";
    } elseif ($type === 'STS') {
        $version = "{$currentYear}.11-STS";
    } elseif ($type === 'BETA') {
        // SemVer auto-increment from previous Beta
        $prevVersion = getPreviousBetaVersion($jsonFile);
        $newSem = incrementSemantic($prevVersion, $rawEntries);
        $version = $newSem;
    } elseif ($type === 'PRERELEASE') {
        // Pre-release: yy.mm.nnnnn
        $counter = getNextPreReleaseCounter($jsonFile, null, "{$currentYear}.{$currentMonth}");
        $version = "{$currentYear}.{$currentMonth}." . str_pad($counter, 5, '0', STR_PAD_LEFT);
    } else {
        // Environment: [env]-yy.mm.nnnnn
        $envPrefix = strtolower($type);
        $counter = getNextPreReleaseCounter($jsonFile, $envPrefix, "{$currentYear}.{$currentMonth}");
        $version = "{$envPrefix}-{$currentYear}.{$currentMonth}." . str_pad($counter, 5, '0', STR_PAD_LEFT);
    }
}

// Determine display label for type
$typeLabel = match($type) {
    'LTS'        => 'STABLE LTS (Long Term Support)',
    'STS'        => 'STABLE STS (Short Term Support)',
    'BETA'       => 'BETA (SemVer)',
    'PRERELEASE' => 'PRE-RELEASE',
    default      => strtoupper($type) . ' (Environment)'
};

echo "---------------------------------------------\n";
echo "Target Release:   {$typeLabel}\n";
echo "Target Version:   {$version}" . ($aliasName ? " / {$aliasName}" : "") . "\n";
echo "Edition:          {$edition}\n";
echo "Repo Architecture: " . ucfirst($repo) . "\n";
echo "Migration Level:  {$migration}\n";
echo "---------------------------------------------\n\n";

if (!$autoConfirm) {
    echo "Do you want to publish this version directly? (y/n): ";
    $input = trim(fgets(STDIN));
    if (strtolower($input) !== 'y') {
        echo "Compilation cancelled.\n";
        exit(0);
    }
}

// ── Group entries by type ─────────────────────────────────────
$summary = [
    'added'      => [],
    'improved'   => [],
    'fixed'      => [],
    'security'   => [],
    'deprecated' => [],
    'removed'    => [],
    'changed'    => [],
    'refactored' => []
];

foreach ($rawEntries as $entry) {
    $t = strtolower($entry['type']);
    if (!array_key_exists($t, $summary)) {
        $summary[$t] = [];
    }
    $summary[$t][] = $entry['description'];
}

$summary = array_filter($summary);

// ── 1. Compile to JSON ────────────────────────────────────────
$newRelease = [
    'version'         => $version,
    'edition'         => strtolower($edition),
    'repo_type'       => strtolower($repo),
    'compiled_date'   => date('Y-m-d'),
    'migration_level' => $migration,
    'alias_name'      => $aliasName,
    'summary'         => $summary
];

$existingData = [];
if (file_exists($jsonFile)) {
    $existingData = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($existingData)) {
        $existingData = [];
    }
}

array_unshift($existingData, $newRelease);
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "✓ Saved compiled JSON to: changelog.json\n";

// ── 2. Prepend to Markdown ────────────────────────────────────
$versionDisplay = $version;
if ($aliasName) {
    $versionDisplay .= " / {$aliasName}";
}

$newMdContent = "## Version {$versionDisplay} ({$edition}) - " . ucfirst($repo) . "\n";
$newMdContent .= "*Compiled Date: " . date('Y-m-d') . " | Migration: {$migration}*\n\n";

foreach ($summary as $key => $items) {
    if (count($items) > 0) {
        $newMdContent .= "### " . ucfirst($key) . "\n";
        foreach ($items as $item) {
            $newMdContent .= "- {$item}\n";
        }
        $newMdContent .= "\n";
    }
}
$newMdContent .= "---\n\n";

$existingMd = '';
if (file_exists($mdFile)) {
    $existingMd = file_get_contents($mdFile);
} else {
    $existingMd = "# siCare Release Changelog\n\nAll public releases of the siCare HRMS Payroll system are listed here.\n\n---\n";
}

$pos = strpos($existingMd, '---');
if ($pos !== false) {
    $header = substr($existingMd, 0, $pos + 4);
    $body = substr($existingMd, $pos + 4);
    $finalMd = $header . "\n" . $newMdContent . $body;
} else {
    $finalMd = $existingMd . "\n" . $newMdContent;
}

file_put_contents($mdFile, $finalMd);
echo "✓ Prepend compiled changelog to: changelog.md\n";

// ── 3. Append to tracker.txt (PERMANENT — never deleted) ─────
$trackerTxtContent = "";
foreach ($rawEntries as $entry) {
    $trackerTxtContent .= "[{$entry['date']}] [{$entry['type']}] {$entry['description']}\n";
}
file_put_contents($trackerTxtFile, $trackerTxtContent, FILE_APPEND);
echo "✓ Appended to permanent log: tracker.txt\n";

// ── 4. Archive compiled entries to tracker_archive.md ─────────
$archiveHeader = '';
if (!file_exists($archiveFile)) {
    $archiveHeader = "# siCare Development Tracker Archive\n\nThis file archives past compiled/published raw logs.\n\n";
    $archiveHeader .= "| Date | Type | Description | Original Code/Behavior | Stage | Developer |\n";
    $archiveHeader .= "| :--- | :--- | :--- | :--- | :--- | :--- |\n";
}

$archiveContent = "";
foreach ($rawEntries as $entry) {
    $archiveContent .= "| {$entry['date']} | {$entry['type']} | {$entry['description']} | {$entry['original']} | {$entry['stage']} | {$entry['developer']} |\n";
}

file_put_contents($archiveFile, $archiveHeader . $archiveContent, FILE_APPEND);
echo "✓ Archived compiled entries to: tracker_archive.md\n";

// ── 5. Clear tracker.md ───────────────────────────────────────
$cleanTracker = "# siCare Development Tracker\n\n";
$cleanTracker .= "This file records local developer changes across `local`, `tqa`, `stg`, and `mtc` environments.\n";
$cleanTracker .= "The compiler script will parse these entries and compile them into a public release.\n\n";
$cleanTracker .= "| Date | Type | Description | Original Code/Behavior | Stage | Developer |\n";
$cleanTracker .= "| :--- | :--- | :--- | :--- | :--- | :--- |\n";

file_put_contents($trackerFile, $cleanTracker);
echo "✓ Cleaned tracker.md for next development cycle.\n\n";

echo "Publishing complete. Version {$version} is now LIVE!\n";

// ═════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═════════════════════════════════════════════════════════════

/**
 * Get previous Beta version (X.Y.Z format) from JSON
 */
function getPreviousBetaVersion($jsonFile) {
    if (!file_exists($jsonFile)) return '1.0.0';
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($data)) return '1.0.0';

    foreach ($data as $release) {
        $v = $release['version'];
        // Match pure SemVer: X.Y.Z (no suffix, no prefix, no env)
        if (preg_match('/^\d+\.\d+\.\d+$/', $v)) {
            return $v;
        }
        // Also match legacy format XXX.YYY.ZZZ-LTS/STS (for backward compat)
        if (preg_match('/^(\d+\.\d+\.\d+)-(LTS|STS)$/', $v, $matches)) {
            return $matches[1];
        }
    }
    return '1.0.0';
}

/**
 * Increment Semantic Version (X.Y.Z) based on content tags
 */
function incrementSemantic($prevVersion, $entries) {
    $parts = explode('.', $prevVersion);
    if (count($parts) < 3) $parts = [1, 0, 0];

    $x = (int)$parts[0];
    $y = (int)$parts[1];
    $z = (int)$parts[2];

    $hasMajor = false;
    $hasMinor = false;

    foreach ($entries as $entry) {
        $type = strtolower($entry['type']);
        if ($type === 'removed' || $type === 'changed') {
            $hasMajor = true;
        } elseif ($type === 'added' || $type === 'deprecated') {
            $hasMinor = true;
        }
    }

    if ($hasMajor) {
        $x++; $y = 0; $z = 0;
    } elseif ($hasMinor) {
        $y++; $z = 0;
    } else {
        $z++;
    }

    return "{$x}.{$y}.{$z}";
}

/**
 * Get the next numeric counter for pre-releases and env versions
 * For pre-release (no prefix): matches yy.mm.nnnnn
 * For env (with prefix): matches [env]-yy.mm.nnnnn
 */
function getNextPreReleaseCounter($jsonFile, $prefix, $yearMonth) {
    if (!file_exists($jsonFile)) return 1;
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($data)) return 1;

    $maxCounter = 0;
    foreach ($data as $release) {
        $v = $release['version'];

        if ($prefix) {
            // Environment format: [env]-YY.MM.NNNNN
            if (preg_match("/^{$prefix}-{$yearMonth}\\.(\d+)$/", $v, $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxCounter) $maxCounter = $val;
            }
        } else {
            // Pre-release format: YY.MM.NNNNN (no prefix)
            if (preg_match("/^{$yearMonth}\\.(\d+)$/", $v, $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxCounter) $maxCounter = $val;
            }
            // Also match legacy canary-YY.MM-NNNNN format for backward compat
            if (preg_match("/^(?:canary|alpha|gamma)-{$yearMonth}-(\d+)$/", $v, $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxCounter) $maxCounter = $val;
            }
        }
    }
    return $maxCounter + 1;
}
