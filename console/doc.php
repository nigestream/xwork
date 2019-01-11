<?php
// composer require sami/sami
// use：
//    php vendor/sami/sami/sami.php update doc.php

$coreDir = __DIR__ . '/../core';
$domainDir = __DIR__ . '/../renmai.com/domain';

$iterator = Symfony\Component\Finder\Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('build')
    ->exclude('tests')
    ->in([$coreDir, $domainDir]);

$options = [
    'theme'                => 'default',
    'title'                => 'Laravel API Documentation',
    'build_dir'            => __DIR__ . '/build',
    'cache_dir'            => __DIR__ . '/cache',
];

$sami = new Sami\Sami($iterator, $options);

return $sami;

