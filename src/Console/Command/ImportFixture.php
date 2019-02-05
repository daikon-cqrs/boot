<?php

namespace Oroshi\Core\Console\Command;

use Oroshi\Core\Fixture\FixtureTargetMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportFixture extends Command
{
    /** @var FixtureTargetMap */
    private $fixtureTargetMap;

    public function __construct(FixtureTargetMap $fixtureTargetMap)
    {
        parent::__construct();

        $this->fixtureTargetMap = $fixtureTargetMap;
    }

    protected function configure()
    {
        $this
            ->setName('fixture:import')
            ->setDescription('Import fixtures from a target.')
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the target to import (if omitted all enabled targets will be imported).'
            )->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'The version to import from (if omitted all fixtures will be imported).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getOption('target');
        $version = intval($input->getOption('from'));

        foreach ($this->fixtureTargetMap->getEnabledTargets() as $targetName => $fixtureTarget) {
            if ($target && $target !== $targetName) {
                continue;
            }

            $output->writeln(sprintf('Importing fixtures for target <options=bold>%s</>', $targetName));
            $importedFixtures = $fixtureTarget->import($version);
            if ($importedFixtures->count() > 0) {
                foreach ($importedFixtures as $fixture) {
                    $output->writeln(sprintf(
                        '  <info>Imported fixture version %d (%s)</info>',
                        $fixture->getVersion(),
                        $fixture->getName()
                    ));
                }
            } else {
                $output->writeln('  <comment>No pending fixtures found</comment>');
            }
        }

        $output->writeln('Successfully imported fixtures.');
    }
}