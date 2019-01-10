#!/usr/bin/env php

<?php

const ROOT_TOP_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'console';
const SYS_ROOT = __DIR__ . DIRECTORY_SEPARATOR .'renmai.com';
const ROOT = __DIR__;

require __DIR__.'/console/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

$application = new Application();

$finder = new Finder();
$finder->files()->name('*Command.php')->in(__DIR__.'/console/commands');
foreach ($finder as $file) {
    $clsName = rtrim($file->getFilename(), '.php');
    //加载命令
    $application->add(new $clsName);
}
try {
    $application->run();
} catch (Exception $e) {
}