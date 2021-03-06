<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Console\Command;

use Daikon\Boot\Fixture\FixtureInterface;
use Daikon\Boot\Fixture\FixtureList;
use Daikon\Boot\Fixture\FixtureTargetInterface;
use Daikon\Boot\Fixture\FixtureTargetMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportFixture extends Command
{
    private FixtureTargetMap $fixtureTargetMap;

    public function __construct(FixtureTargetMap $fixtureTargetMap)
    {
        parent::__construct();
        $this->fixtureTargetMap = $fixtureTargetMap;
    }

    protected function configure(): void
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
                'The version to import from (if omitted all available fixtures will be imported).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption('target');
        $from = intval($input->getOption('from'));

        $availableFixtures = new FixtureList;
        $loadedTargets = new FixtureTargetMap;
        $enabledTargets = $this->fixtureTargetMap->getEnabledTargets();
        /** @var FixtureTargetInterface $fixtureTarget */
        foreach ($enabledTargets as $targetKey => $fixtureTarget) {
            if ($target && $target !== $targetKey) {
                continue;
            }
            $fixtureList = $fixtureTarget->getFixtureList();
            $output->writeln(sprintf(
                'Found <options=bold>%d</> fixtures for target <options=bold>%s</>',
                $fixtureList->count(),
                $targetKey
            ));
            $loadedTargets = $loadedTargets->with($targetKey, $fixtureTarget);
            $availableFixtures = $availableFixtures->append($fixtureList);
        }

        $availableFixtures = $availableFixtures->sortByVersion();
        $importedFixtures = new FixtureList;

        /** @var FixtureInterface $fixture */
        foreach ($availableFixtures as $fixture) {
            if ($fixture->getVersion() < $from) {
                continue;
            }
            /** @var FixtureTargetInterface $fixtureTarget */
            foreach ($loadedTargets as $fixtureTarget) {
                if ($fixtureTarget->import($fixture)) {
                    $importedFixtures = $importedFixtures->push($fixture);
                    $output->writeln(sprintf(
                        '  <info>Imported fixture version %d (%s)</info>',
                        $fixture->getVersion(),
                        $fixture->getName()
                    ));
                }
            }
        }

        $output->writeln(sprintf(
            'Successfully imported <options=bold>%d</> fixtures.',
            count($importedFixtures)
        ));

        return 0;
    }
}
