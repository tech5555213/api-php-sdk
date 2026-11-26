<?php
// Fallback autoloader when composer install has not been run.
spl_autoload_register(function ($class) {
    $prefix = 'Loamhill\\Api\\';
    if (strpos($class, $prefix) === 0) {
        $file = __DIR__ . '/../src/' . substr($class, strlen($prefix)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
