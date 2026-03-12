<?php
$dir = new RecursiveDirectoryIterator('c:\xampp\htdocs\zya\resources\views');
$iter = new RecursiveIteratorIterator($dir);
foreach ($iter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace('accept="image/*"', 'accept=".pdf, image/*"', $content);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
?>
