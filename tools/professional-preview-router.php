<?php

if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$static = realpath($root.'/public'.$path);
if ($path !== '/' && $static && str_starts_with($static, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($static)) return false;
foreach (['APP_ENV'=>'local','APP_URL'=>'http://127.0.0.1:8017','DB_CONNECTION'=>'sqlite',
    'DB_DATABASE'=>$root.'/storage/app/professional-preview/preview.sqlite','DB_URL'=>'',
    'SESSION_DRIVER'=>'file','SESSION_COOKIE'=>'adfc_professional_preview','CACHE_STORE'=>'array','MAIL_MAILER'=>'log'] as $key=>$value) {
    putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value;
}
require $root.'/public/index.php';
