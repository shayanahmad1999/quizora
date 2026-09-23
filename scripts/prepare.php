<?php
declare(strict_types=1);
$root=dirname(__DIR__);
if (PHP_VERSION_ID < 80300) { fwrite(STDERR,"Laravel 13 requires PHP 8.3 or newer. Active PHP: ".PHP_VERSION."\n"); exit(1); }
$required=['ctype','dom','fileinfo','mbstring','openssl','pdo','tokenizer','xml','xmlwriter'];
$missing=array_values(array_filter($required,fn($e)=>!extension_loaded($e)));
if ($missing) {
    fwrite(STDERR,"Missing PHP extensions: ".implode(', ',$missing)."\nLoaded php.ini: ".(php_ini_loaded_file() ?: '(none)')."\nEnable these extensions for the active CLI PHP and retry.\n"); exit(1);
}
if (!is_file($root.'/.env')) {
    if (!copy($root.'/.env.example',$root.'/.env')) { throw new RuntimeException('Could not create .env. Check folder permissions.'); }
}
$env=file_get_contents($root.'/.env');
$driver=preg_match('/^DB_CONNECTION\s*=\s*([^\r\n]+)/m',$env,$m) ? trim($m[1]," \"'") : 'sqlite';
$extensions=['sqlite'=>'pdo_sqlite','mysql'=>'pdo_mysql','pgsql'=>'pdo_pgsql'];
if (!isset($extensions[$driver]) || !extension_loaded($extensions[$driver])) {
    fwrite(STDERR,"Database driver {$driver} is not enabled. Enable ".($extensions[$driver] ?? 'the correct PDO driver')." in ".(php_ini_loaded_file() ?: 'php.ini').".\n"); exit(1);
}
foreach (['bootstrap/cache','storage/app/private','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','public/assets/vendor'] as $directory) {
    $path=$root.'/'.$directory;
    if (!is_dir($path) && !mkdir($path,0775,true) && !is_dir($path)) { throw new RuntimeException('Cannot create '.$path); }
    if (!is_writable($path)) { throw new RuntimeException('Directory is not writable: '.$path); }
}
if ($driver==='sqlite' && !is_file($root.'/database/database.sqlite') && !touch($root.'/database/database.sqlite')) {
    throw new RuntimeException('Cannot create the SQLite database file.');
}
echo "Quizora preflight passed\nPHP: ".PHP_VERSION." (".PHP_BINARY.")\nINI: ".(php_ini_loaded_file() ?: '(none)')."\nDatabase: {$driver}\n";
