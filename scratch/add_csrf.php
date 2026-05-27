<?php
$dir = __DIR__ . '/../resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Skip if already contains csrf_token input
        if (preg_match('/<form[^>]*>/i', $content) && !str_contains($content, 'name="csrf_token"')) {
            $replacement = "$1\n    <input type=\"hidden\" name=\"csrf_token\" value=\"<?= \App\Middleware\SecurityMiddleware::getCsrfToken() ?>\">";
            $newContent = preg_replace('/(<form[^>]*>)/i', $replacement, $content);
            file_put_contents($file->getPathname(), $newContent);
            echo "Fixed: " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
