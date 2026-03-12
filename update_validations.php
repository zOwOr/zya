<?php
$dir = new RecursiveDirectoryIterator('c:\xampp\htdocs\zya\app\Http\Controllers');
$iter = new RecursiveIteratorIterator($dir);
foreach ($iter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = preg_replace("/'image\|file\|max:(\d+)'/", "'file|mimes:jpeg,png,jpg,gif,pdf,webp|max:$1'", $content);
        $newContent = preg_replace("/'nullable\|image\|max:(\d+)'/", "'nullable|file|mimes:jpeg,png,jpg,gif,pdf,webp|max:$1'", $newContent);
        $newContent = preg_replace("/'image\|file'/", "'file|mimes:jpeg,png,jpg,gif,pdf,webp'", $newContent);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
?>
