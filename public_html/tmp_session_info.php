<?php
header('Content-Type: text/plain; charset=UTF-8');
echo 'session_name=' . session_name() . PHP_EOL;
echo 'session_save_path=' . session_save_path() . PHP_EOL;
echo 'tmp=' . sys_get_temp_dir() . PHP_EOL;
