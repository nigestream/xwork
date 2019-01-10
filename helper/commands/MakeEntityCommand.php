<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Created by PhpStorm.
 * User: nings
 * Date: 2018/8/9
 * Time: 10:52
 */

class MakeEntityCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:entity')
            ->addArgument('app', InputArgument::REQUIRED)
            ->addArgument('name', InputArgument::REQUIRED)
            // the short description shown while running "php bin/console list"
            ->setDescription('创建entity类, 如make:entity domain Dummy')
            ->setHelp('如: php helper make:entity domain Dummy')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $dir = SYS_ROOT . DIRECTORY_SEPARATOR . $input->getArgument('app') . DIRECTORY_SEPARATOR . 'entity';
        if (!$fs->exists($dir)) {
            $io = new SymfonyStyle($input, $output);
            $io->error("路径 ".$dir." 不存在");
            return;
        }
        //拷贝模板，替换变量，生成文件
        $finder->files()->name('entity.stub')->in(__DIR__.'/stubs');
        foreach ($finder as $file) {
            $content = replaceVar($file->getContents(), ['dummy' => $input->getArgument('name')]);

            $target = $dir . DIRECTORY_SEPARATOR . ucfirst($input->getArgument('name')) . '.class.php';
            file_put_contents($target, $content);
        }
    }
}