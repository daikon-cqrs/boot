<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command;

use Daikon\AsyncJob\Worker\WorkerMap;
use Daikon\RabbitMq3\Job\RabbitMq3Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RunWorker extends Command
{
    use DialogTrait;

    /** @var WorkerMap */
    protected $workerMap;

    public function __construct(WorkerMap $workerMap)
    {
        parent::__construct();
        $this->workerMap = $workerMap;
    }

    protected function configure(): void
    {
        $this
            ->setName('worker:run')
            ->setDescription('Run an asynchronous job worker.')
            ->addArgument(
                'queue',
                InputArgument::REQUIRED,
                'Name of the message queue from which to execute jobs.'
            )
            ->addArgument(
                'worker',
                InputArgument::OPTIONAL,
                'Name of the worker from which to execute jobs.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if (!$workerName = $input->getArgument('worker')) {
            $workerName = $this->listWorkers($input, $output);
        }
        $worker = $this->workerMap->get($workerName);
        $worker->run(['queue' => $input->getArgument('queue')]);
    }

    protected function listWorkers(InputInterface $input, OutputInterface $output): string
    {
        if (!count($this->workerMap)) {
            $output->writeln('<error>There are no workers available.</error>');
            $output->writeln('');
            exit;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a worker: ',
            array_keys($this->workerMap->toNative())
        );

        return $helper->ask($input, $output, $question);
    }
}
