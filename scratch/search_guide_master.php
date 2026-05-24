<?php
$content = file_get_contents('d:/Server/WebApp/siCare/guide-for-ide.md');
$lines = explode("\n", $content);
$keywords = ['Master Data', 'employees', 'hrops/employees', 'manajemen', 'karyawan'];
foreach ($lines as $index => $line) {
    foreach ($keywords as $kw) {
        if (stripos($line, $kw) !== false) {
            echo "Line " . ($index + 1) . " [$kw]: " . trim($line) . "\n";
            break;
        }
    }
}
