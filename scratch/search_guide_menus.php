<?php
$content = file_get_contents('d:/Server/WebApp/siCare/guide-for-ide.md');
$lines = explode("\n", $content);
$keywords = ['menu', 'fitur', 'halaman', 'sidebar', 'modul'];
foreach ($lines as $index => $line) {
    foreach ($keywords as $kw) {
        if (stripos($line, $kw) !== false) {
            echo "Line " . ($index + 1) . " [$kw]: " . trim($line) . "\n";
            break;
        }
    }
}
