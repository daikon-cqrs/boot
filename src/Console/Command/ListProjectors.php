<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Console\Command;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->eventProjectorMap as $projectorKey => $eventProjector) {
            $projectorFqcn = get_class($eventProjector->getProjector());
            $output->writeln("Projector <options=bold>$projectorKey</> implemented by $projectorFqcn");
            //@todo use closure binding instead of getter on EventProjector
            $output->writeln('  Responds to: '.implode(" | ", $eventProjector->getEventExpressions()));
        }

        return 0;
    }
}
