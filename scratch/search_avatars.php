<?php
function findPattern($dir, $pattern) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;
        $content = file_get_contents($path);
        if (strpos($content, $pattern) !== false) {
            echo "Found '$pattern' in $path\n";
        }
    }
}
findPattern('d:/Server/WebApp/siCare/app', 'ui-avatars');
findPattern('d:/Server/WebApp/siCare/resources', 'ui-avatars');
findPattern('d:/Server/WebApp/siCare/app', 'gravatar');
findPattern('d:/Server/WebApp/siCare/resources', 'gravatar');
