<?php
// base_path() ya existe en tu bootstrap
spl_autoload_register(function (string $class) {
    // Solo clases de nuestro namespace
    if (str_starts_with($class, 'App\\')) {
        $rel = str_replace('\\', '/', $class) . '.php';   // App/Foo/Bar.php
        $file = base_path('app/' . substr($rel, 4));      // app/Foo/Bar.php
        if (is_file($file)) {
            require_once $file;
        }
    }
});
