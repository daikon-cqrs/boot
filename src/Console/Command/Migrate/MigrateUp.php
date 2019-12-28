<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Console\Command\Migrate;

use Daikon\Dbal\Migration\MigrationInterface;
use Daikon\Dbal\Migration\MigrationTargetMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateUp extends Command
{
    /** @var MigrationTargetMap */
    private $migrationTargetMap;

    public function __construct(MigrationTargetMap $migrationTargetMap)
    {
        $this->migrationTargetMap = $migrationTargetMap;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate:up')
            ->setDescription('Migrate up to a specified migration version.')
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the target to migrate (if omitted all enabled targets will be migrated).'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'The version to migrate towards (if omitted all pendings migrations will be executed).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption('target');
        $version = intval($input->getOption('to'));

        foreach ($this->migrationTargetMap->getEnabledTargets() as $targetName => $migrationTarget) {
            if ($target && $target !== $targetName) {
                continue;
            }
            $output->writeln(sprintf('Executing migrations for target <options=bold>%s</>', $targetName));
            $executedMigrations = $migrationTarget->migrate(MigrationInterface::MIGRATE_UP, $version);
            if ($executedMigrations->count() > 0) {
                foreach ($executedMigrations as $migration) {
                    $output->writeln(sprintf(
                        '  <info>Executed migration version %d (%s)</info>',
                        $migration->getVersion(),
                        $migration->getName()
                    ));
                }
            } else {
                $output->writeln('  <comment>No pending migrations found</comment>');
            }
        }

        return 0;
    }
}
