<?php
spl_autoload_register(static function ($class) {
    $prefix = 'App\\Models\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $modelFile = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($modelFile)) {
        require_once $modelFile;
    }
});