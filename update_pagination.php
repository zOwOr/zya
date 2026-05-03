<?php

// 1. Update controllers
$controllersPath = 'app/Http/Controllers/Dashboard/';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, '$row > 100') !== false) {
            $content = str_replace(
                'if ($row < 1 || $row > 100) {',
                'if ($row < 1 || $row > 100000) {',
                $content
            );
            $content = str_replace(
                "abort(400, 'The per-page parameter must be an integer between 1 and 100.');",
                "abort(400, 'The per-page parameter must be an integer between 1 and 100000.');",
                $content
            );
            file_put_contents($file->getPathname(), $content);
            echo "Updated controller: " . $file->getPathname() . "\n";
        }
    }
}

// 2. Update blade files
$viewsPath = 'resources/views/';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Find select elements for rows
        if (strpos($content, 'name="row"') !== false) {
            $changed = false;
            
            // Add onchange
            if (strpos($content, '<select class="form-control" name="row"') !== false) {
                $content = str_replace(
                    '<select class="form-control" name="row">',
                    '<select class="form-control" name="row" onchange="this.form.submit()">',
                    $content
                );
                $changed = true;
            } elseif (strpos($content, '<select class="form-select" name="row"') !== false) {
                $content = str_replace(
                    '<select class="form-select" name="row">',
                    '<select class="form-select" name="row" onchange="this.form.submit()">',
                    $content
                );
                $changed = true;
            }

            // Add Todos option
            if ($changed && strpos($content, 'value="100000"') === false) {
                $todosOption = "\n                                    <option value=\"100000\" @if(request('row') == '100000') selected=\"selected\" @endif>Todos</option>";
                
                // Try replacing the 100 option line with the 100 option + Todos option
                $pattern1 = '<option value="100" @if (request(\'row\') == \'100\') selected="selected" @endif>100</option>';
                $pattern2 = '<option value="100" @if(request(\'row\') == \'100\') selected="selected" @endif>100</option>';
                
                if (strpos($content, $pattern1) !== false) {
                    $content = str_replace($pattern1, $pattern1 . $todosOption, $content);
                } elseif (strpos($content, $pattern2) !== false) {
                    $content = str_replace($pattern2, $pattern2 . $todosOption, $content);
                } else {
                    // Try regex replacement if exact match fails
                    $content = preg_replace('/(<option value="100"[^>]*>100\s*<\/option>)/', '$1' . $todosOption, $content);
                }
            }
            
            if ($changed) {
                file_put_contents($file->getPathname(), $content);
                echo "Updated view: " . $file->getPathname() . "\n";
            }
        }
    }
}
echo "Done.\n";
