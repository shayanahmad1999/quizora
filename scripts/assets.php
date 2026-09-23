<?php
declare(strict_types=1);
$root=dirname(__DIR__); $source=$root.'/vendor/twbs/bootstrap'; $target=$root.'/public/assets/vendor';
if (!is_dir($target) && !mkdir($target,0775,true) && !is_dir($target)) { throw new RuntimeException('Cannot create the local asset directory.'); }
foreach (['dist/css/bootstrap.min.css'=>'bootstrap.min.css','LICENSE'=>'BOOTSTRAP-LICENSE.txt'] as $from=>$to) {
    if (!is_file($source.'/'.$from) || !copy($source.'/'.$from,$target.'/'.$to)) { throw new RuntimeException('Could not publish Bootstrap asset: '.$from.'. Run composer install and retry.'); }
}
if (is_file($source.'/dist/css/bootstrap.min.css.map')) { copy($source.'/dist/css/bootstrap.min.css.map',$target.'/bootstrap.min.css.map'); }
echo "Bootstrap CSS published locally. No CDN or frontend build is required.\n";
