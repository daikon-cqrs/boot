<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command;

use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Crate\CrateMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListCrates extends Command
{
    /** @var CrateMap */
    private $crateMap;

    public function __construct(CrateMap $crateMap)
    {
        $this->crateMap = $crateMap;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('crate:ls')
            ->setDescription('Lists currently installed crates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->crateMap as $name => $crate) {
            $output->writeln(sprintf('Summary for crate <options=bold>%s</>', $name));
            $output->writeln('  Location: '.$crate->getLocation());
        }
    }
}
