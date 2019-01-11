<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class MakeSubsysCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:subsys')
            ->addArgument('name', InputArgument::REQUIRED)
            // the short description shown while running "php bin/console list"
            ->setDescription('创建一个子系统')
            ->setHelp('如: php xworker make:subsys audit|www|admin|api...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $io = new SymfonyStyle($input, $output);
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $baseDir = ".";
        $appDir = $baseDir . "/app";
        if (false === $fs->exists($appDir)) {
            $io->error("$appDir not exists");
            return;
        }

        $wwwrootDir = $appDir . '/wwwroot';
        //新建www子系统
        $wwwDirs = [
            'app_dir' => $appDir . "/$name",
            'wwwroot_dir' => $wwwrootDir . "/$name",
            'action_dir' => $appDir . "/$name/action",
            'tpl_dir' => $appDir . "/$name/tpl",
        ];

        $fs->mkdir($wwwDirs, 0755);
        //创建默认文件
        $this->createDefaultFile('actionmap.stub', $wwwDirs['app_dir'] . '/ActionMap.properties.php');
        $this->createDefaultFile('subbaseaction.stub', $wwwDirs['app_dir'] . '/' . ucfirst($name). "BaseAction.php", ['{{foo}}' => $name]);
        $this->createDefaultFile('index.stub', $wwwDirs['wwwroot_dir'] . '/index.php', ['{{dummy}}' => $name]);
        $this->createDefaultFileByCommand('make:action', ['app' => $name, 'name' =>'demo'], $input, $output);
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
}
