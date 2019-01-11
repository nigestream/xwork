<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class MakeNewCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('make:new')
            ->addArgument('name', InputArgument::REQUIRED)
            // the short description shown while running "php bin/console list"
            ->setDescription('根据表新建相关组件类: entity dao service...')
            ->setHelp('如: php xworker make:new Dummy')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $this->createDefaultFileByCommand('make:entity', ['name' => $name], $input, $output);
        $this->createDefaultFileByCommand('make:dao', ['name' => $name], $input, $output);
        $this->createDefaultFileByCommand('make:service', ['name' => $name], $input, $output);
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
}
