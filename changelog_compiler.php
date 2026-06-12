<?php
/**
 * siCare Changelog Compiler & Publisher
 * Versioning Tracks:
 * - Stable LTS: Released once a year (YY.05-LTS)
 * - Stable STS: Released once a year (YY.09-STS)
 * - Beta: Released every 3 months (Semantic X.Y.Z-Beta)
 * - Pre-releases/Dev: alpha, canary, gamma
 */

// Define files
$trackerFile = __DIR__ . '/tracker.md';
$archiveFile = __DIR__ . '/tracker_archive.md';
$jsonFile = __DIR__ . '/changelog.json';
$mdFile = __DIR__ . '/changelog.md';

// Print header
echo "\n=============================================\n";
echo "    siCare CHANGELOG COMPILER & PUBLISHER    \n";
echo "=============================================\n\n";

if (!file_exists($trackerFile)) {
    echo "Error: tracker.md not found. Creating a default one first...\n";
    exit(1);
}

// Parse command line arguments
$options = getopt("", ["type:", "edition:", "migration:", "version:", "yes", "repo:"]);
$type = isset($options['type']) ? strtoupper($options['type']) : 'LTS'; // Default LTS
$edition = isset($options['edition']) ? ucfirst($options['edition']) : 'Business'; // Default Business
$repo = isset($options['repo']) ? strtolower($options['repo']) : 'monorepo'; // Default monorepo
$migration = isset($options['migration']) ? $options['migration'] : 'stg -> production';
$autoConfirm = isset($options['yes']);

// Validate repo type
if (!in_array($repo, ['monorepo', 'multirepo'])) {
    echo "Error: Invalid repo type '{$repo}'. Valid types are: monorepo, multirepo\n";
    exit(1);
}

// Validate release types
$validTypes = ['LTS', 'STS', 'BETA', 'ALPHA', 'CANARY', 'GAMMA'];
if (!in_array($type, $validTypes)) {
    echo "Error: Invalid release type '{$type}'. Valid types are: " . implode(', ', $validTypes) . "\n";
    exit(1);
}

// Validate Edition vs Track Suffix
if (strtolower($edition) === 'business' && $type === 'STS') {
    echo "Error: Business Edition is exclusive to LTS. STS is not supported for Business Edition.\n";
    exit(1);
}

// Read and parse tracker.md
echo "Reading tracker.md...\n";
$lines = file($trackerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$rawEntries = [];

foreach ($lines as $line) {
    // Check if it's a table row (starts and ends with |)
    if (preg_match('/^\|\s*(.*?)\s*\|$/', $line, $matches)) {
        $columns = explode('|', $matches[1]);
        $columns = array_map('trim', $columns);
        
        // Skip header and divider rows
        if (count($columns) < 6 || strtolower($columns[0]) === 'date' || strpos($columns[0], '---') !== false) {
            continue;
        }
        
        $rawEntries[] = [
            'date' => $columns[0],
            'type' => ucfirst(strtolower($columns[1])),
            'description' => $columns[2],
            'original' => $columns[3],
            'stage' => $columns[4],
            'developer' => $columns[5]
        ];
    }
}

$entryCount = count($rawEntries);
echo "Found {$entryCount} development logs in tracker.md.\n";

if ($entryCount === 0) {
    echo "No entries to compile. Exiting.\n";
    exit(0);
}

// Determine version number
$version = '';
$currentYear = date('y'); // e.g. "26" for 2026

if (isset($options['version'])) {
    $version = $options['version'];
} else {
    if ($type === 'LTS') {
        $version = "{$currentYear}.05-LTS";
    } elseif ($type === 'STS') {
        $version = "{$currentYear}.09-STS";
    } elseif ($type === 'BETA') {
        // Read previous beta version from JSON to increment
        $prevVersion = getPreviousVersion($jsonFile, 'BETA');
        
        // Extract semantic part and suffix (LTS/STS)
        if (preg_match('/^(\d+\.\d+\.\d+)-(LTS|STS)$/', $prevVersion, $matches)) {
            $semPart = $matches[1];
            $suffix = $matches[2];
        } else {
            $semPart = '1.0.0';
            $suffix = 'LTS'; // Default
        }
        
        // If Business Edition, force LTS suffix
        if (strtolower($edition) === 'business') {
            $suffix = 'LTS';
        }
        
        $newSem = incrementSemantic($semPart, $rawEntries);
        $semParts = explode('.', $newSem);
        $paddedSem = sprintf('%03d.%03d.%03d', (int)$semParts[0], (int)$semParts[1], (int)$semParts[2]);
        $version = "{$paddedSem}-{$suffix}";
    } else {
        // Continuous Releases: alpha, canary, gamma
        $prefix = strtolower($type);
        $month = date('m');
        $counter = getNextPreReleaseCounter($jsonFile, $prefix, "{$currentYear}.{$month}");
        $version = "{$prefix}-{$currentYear}.{$month}-" . str_pad($counter, 5, '0', STR_PAD_LEFT);
    }
}

// Validate version suffix for Business Edition
if (strtolower($edition) === 'business' && strpos(strtoupper($version), '-STS') !== false) {
    echo "Error: Business Edition is exclusive to LTS. Version cannot have STS suffix.\n";
    exit(1);
}

echo "---------------------------------------------\n";
echo "Target Release:   " . ($type === 'LTS' ? 'STABLE LTS (Long Term Support)' : ($type === 'STS' ? 'STABLE STS (Short Term Support)' : $type)) . "\n";
echo "Target Version:   {$version}\n";
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

// Group entries by type
$summary = [
    'added' => [],
    'improved' => [],
    'fixed' => [],
    'security' => [],
    'deprecated' => [],
    'removed' => [],
    'changed' => [],
    'refactored' => []
];

foreach ($rawEntries as $entry) {
    $t = strtolower($entry['type']);
    if (!array_key_exists($t, $summary)) {
        $summary[$t] = [];
    }
    
    // Format descriptive string - focus strictly on development changes, omitting developer name and previous implementation details
    $desc = $entry['description'];
    
    $summary[$t][] = $desc;
}

// Clean empty arrays in summary
$summary = array_filter($summary);

// 1. Compile to JSON
$newRelease = [
    'version' => $version,
    'edition' => strtolower($edition),
    'repo_type' => strtolower($repo),
    'compiled_date' => date('Y-m-d'),
    'migration_level' => $migration,
    'summary' => $summary
];

$existingData = [];
if (file_exists($jsonFile)) {
    $existingData = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($existingData)) {
        $existingData = [];
    }
}

// Prepend the new release to the JSON array
array_unshift($existingData, $newRelease);
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "✓ Saved compiled JSON to: changelog.json\n";

// 2. Prepend to Markdown
$newMdContent = "## Version {$version} ({$edition}) - " . ucfirst($repo) . "\n";
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

// Split the title/intro and prepend the new release block
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

// 3. Archive compiled entries
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

// 4. Clear tracker.md (except title and header table format)
$cleanTracker = "# siCare Development Tracker\n\n";
$cleanTracker .= "This file records local developer changes across `env`, `dev`, and `tqa` environments.\n";
$cleanTracker .= "The compiler script will parse these entries and compile them into a public release.\n\n";
$cleanTracker .= "| Date | Type | Description | Original Code/Behavior | Stage | Developer |\n";
$cleanTracker .= "| :--- | :--- | :--- | :--- | :--- | :--- |\n";

file_put_contents($trackerFile, $cleanTracker);
echo "✓ Cleaned tracker.md for next development cycle.\n\n";

echo "Publishing complete. Version {$version} is now LIVE!\n";

/**
 * Get previous version from JSON file
 */
function getPreviousVersion($jsonFile, $type) {
    if (!file_exists($jsonFile)) return '001.000.000-LTS';
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($data)) return '001.000.000-LTS';
    
    foreach ($data as $release) {
        $v = $release['version'];
        if ($type === 'BETA' && preg_match('/^\d+\.\d+\.\d+-(LTS|STS)$/', $v)) {
            return $v;
        }
    }
    return '001.000.000-LTS';
}

/**
 * Increment Semantic Version (X.Y.Z) based on content tags
 */
function incrementSemantic($prevVersion, $entries) {
    $parts = explode('.', $prevVersion);
    if (count($parts) < 3) {
        $parts = [1, 0, 0];
    }
    
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
        $x++;
        $y = 0;
        $z = 0;
    } elseif ($hasMinor) {
        $y++;
        $z = 0;
    } else {
        $z++;
    }
    
    return "{$x}.{$y}.{$z}";
}

/**
 * Get the next numeric counter for pre-releases (alpha, canary, gamma)
 */
function getNextPreReleaseCounter($jsonFile, $prefix, $yearMonth) {
    if (!file_exists($jsonFile)) return 1;
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($data)) return 1;
    
    $maxCounter = 0;
    foreach ($data as $release) {
        $v = $release['version'];
        // Format: prefix-YY.MM-NNNNN
        if (preg_match("/^{$prefix}-{$yearMonth}-(\d+)$/", $v, $matches)) {
            $val = (int)$matches[1];
            if ($val > $maxCounter) {
                $maxCounter = $val;
            }
        }
    }
    return $maxCounter + 1;
}
