<?php
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $rel  = str_replace('\\', '/', $class) . '.php';   // App/Foo/Bar.php
        $file = base_path('app/' . substr($rel, 4));       // app/Foo/Bar.php
        if (is_file($file)) require_once $file;
    }
});
