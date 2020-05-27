<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Console\Command;

use Daikon\AsyncJob\Worker\WorkerInterface;
use Daikon\AsyncJob\Worker\WorkerMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RunWorker extends Command
{
    use DialogTrait;

    protected WorkerMap $workerMap;

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

    //@todo support running multiple queues
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_string($workerName = $input->getArgument('worker'))) {
            $workerName = $this->listWorkers($input, $output);
        }
        /** @var WorkerInterface $worker */
        $worker = $this->workerMap->get($workerName);
        $worker->run(['queue' => $input->getArgument('queue')]);
        //@todo return int from worker
        return 0;
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
            $this->workerMap->keys() //@todo show workers with filtered by queue
        );

        return $helper->ask($input, $output, $question);
    }
}
