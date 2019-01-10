<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class MakeTestCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:test')
            ->addArgument('name', InputArgument::REQUIRED)

            // the short description shown while running "php bin/console list"
            ->setDescription('创建单元测试')
            ->setHelp('eg: php helper make:test service/user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $target_dir = ROOT . DIRECTORY_SEPARATOR . 'tests';
        if (!$fs->exists($target_dir)) {
            $io = new SymfonyStyle($input, $output);
            $io->error("单元测试文件夹 tests 不存在");
            return;
        }
        $name = $input->getArgument('name');
        $nameArr = preg_split('/[\/]/', $name);
        if (count($nameArr) > 1) {
            for ($i = 0; $i < count($nameArr)-1; $i++) {
                $target_dir = $target_dir . DIRECTORY_SEPARATOR . $nameArr[$i];
            }
            $fs->mkdir($target_dir);
            $name = array_slice($nameArr, -1, 1)[0];
        }

        //拷贝模板，替换变量，生成文件
        $finder->files()->name('test.stub')->in(__DIR__.DIRECTORY_SEPARATOR.'stubs');
        foreach ($finder as $file) {
            $content = replaceVar($file->getContents(), ['dummy' => $input->getArgument('name')]);

            $target = $target_dir . DIRECTORY_SEPARATOR . ucfirst($name) . 'Test.php';

            if ($fs->exists($target)) {
                echo '文件已存在';
                return;
            }
            file_put_contents($target, $content);
        }
        echo '文件路径：' . $target;
    }
}