<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command;

use Daikon\Config\ConfigProviderInterface;
use Daikon\ReadModel\Projector\EventProjectorMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListProjectors extends Command
{
    /** @var EventProjectorMap */
    private $eventProjectorMap;

    public function __construct(EventProjectorMap $eventProjectorMap)
    {
        $this->eventProjectorMap = $eventProjectorMap;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('projector:ls')
            ->setDescription('Lists currently configured projectors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->eventProjectorMap as $projectorKey => $eventProjector) {
            $projectorFqcn = get_class($eventProjector->getProjector());
            $output->writeln("Projector <options=bold>$projectorKey</> implemented by $projectorFqcn");
            $output->writeln('  Responds to: '.implode(" | ", $eventProjector->getEventExpressions()));
        }
    }
}
