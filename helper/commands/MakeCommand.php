<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by PhpStorm.
 * User: nings
 * Date: 2018/8/9
 * Time: 13:20
 */

class MakeCommand extends Command
{
    private $finder;
    private $fs;
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->finder = new \Symfony\Component\Finder\Finder();
        $this->fs = new Symfony\Component\Filesystem\Filesystem();
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:command')
            ->addArgument('name', InputArgument::REQUIRED)

            // the short description shown while running "php bin/console list"
            ->setDescription('创建命令类')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //拷贝命令模板，替换变量，生成命令类
        $this->finder->files()->name('command.stub')->in(__DIR__.'/stubs');
        foreach ($this->finder as $file) {
            $content = replaceVar($file->getContents(), ['dummy' => $input->getArgument('name')]);
            $target = __DIR__ . DIRECTORY_SEPARATOR . ucfirst($input->getArgument('name')) . 'Command.php';
            file_put_contents($target, $content);
        }
        require_once ROOT_TOP_PATH . DIRECTORY_SEPARATOR . 'generatorAssembly.console.php';
    }

}