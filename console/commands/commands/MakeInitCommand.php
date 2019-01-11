<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;


class MakeInitCommand extends Command
{
    protected function configure() {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:init')
//            ->addArgument('appname', InputArgument::REQUIRED)
            // the short description shown while running "php bin/console list"
            ->setDescription('初始化一个app')
            ->setHelp('如: php xworker make:init');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $baseDir = ".";
        $appDir = $baseDir . "/app";
        if (false === $this->dirEmpty($appDir)) {
            $io->error("$appDir is not empty");
            return;
        }
        $testsDir = './tests';
        $domainDir = $appDir . '/domain';
        $dirs = [];
        if ($this->dirEmpty($testsDir)) {
            $dirs['tests_dir'] = './tests';
        }
        $dirs['wwwroot_dir'] = $appDir . '/wwwroot';
        $dirs['entity_dir'] = $domainDir . '/entity';
        $dirs['dao_dir'] = $domainDir . '/dao';
        $dirs['service_dir'] = $domainDir . '/service';
        $dirs['script'] = $appDir . '/script';
        $dirs['sys'] = $appDir . '/sys';
        $dirs['config'] = $dirs['sys'] . '/config';
        $fs->mkdir($dirs, 0755);
        //更新composer.json
        if (!$fs->exists($baseDir . '/composer.json')) {
            $io->error($baseDir . '/composer.json not found');
            return;
        }
        $this->updateComposerJson($baseDir . '/composer.json');
        //创建领域文件
        $this->createDefaultFile('pathdefine.stub', $dirs['sys'] . '/PathDefine.php');
        $this->createDefaultFile('config.stub', $dirs['config'] . '/config.sample.php');
        $this->createDefaultFile('baseaction.stub', $domainDir . '/BaseAction.php');
        $this->createDefaultFile('urlfor.stub', $domainDir . '/UrlFor.php');
        //创建测试文件, 调用另一个command
        $this->createDefaultFileByCommand('make:test', ['name' => 'Demo'], $input, $output);
        $this->createDefaultFileByCommand('make:entity', ['name' => 'User'], $input, $output);
        $this->createDefaultFileByCommand('make:dao', ['name' => 'User'], $input, $output);
        $this->createDefaultFileByCommand('make:service', ['name' => 'User'], $input, $output);
        //新建www子系统
        $this->createDefaultFileByCommand('make:subsys', ['name' => 'www'], $input, $output);
        $this->createDefaultFileByCommand('make:action', ['app' => 'www', 'name' => 'User'], $input, $output);
        $fs->symlink(__DIR__ . "/../../xworker", $baseDir . '/xworker');
        $io->success("success");
    }

    private function createDefaultFileByCommand(string $command, array $args, InputInterface $input, OutputInterface $output) {
        try {
            $this->getApplication()->find($command)->run(new ArrayInput($args), $output);
        } catch (Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->warning($e);
            return;
        }
    }

    private function createDefaultFile($tpl, $target, $replaces = []) {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->name($tpl)->in(__DIR__ . '/stubs');
        foreach ($finder as $file) {
            $content = xwork_console_replaceVar($file->getContents(), $replaces);
            file_put_contents($target, $content);
        }
    }

    private function updateComposerJson(string $filepath) {
        $content = file_get_contents($filepath);
        $arr = json_decode($content, true);
        if (!isset($arr['autoload'])) {
            $arr['autoload'] = [];
            if (!isset($arr['autoload']['classmap'])) {
                $arr['autoload']['classmap'] = [];
            }
            if (!isset($arr['autoload']['psr-4'])) {
                $arr['autoload']['psr-4'] = [];
            }
        }
        if (!in_array('app/domain/entity', $arr['autoload']['classmap'])) {
            $arr['autoload']['classmap'][] = 'app/domain/entity';
        }
        $arr['autoload']['psr-4']['App\\'] = "app/";
        $ret = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filepath, $ret);
    }

    private function dirEmpty($dir) {
        if (is_dir($dir)) {
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    closedir($handle);
                    return FALSE;
                }
            }
            closedir($handle);
        }

        return TRUE;
    }
}