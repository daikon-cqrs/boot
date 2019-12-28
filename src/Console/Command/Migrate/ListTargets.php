<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Console\Command\Migrate;

use Daikon\Dbal\Migration\MigrationTargetMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ListTargets extends Command
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
            ->setName('migrate:ls')
            ->setDescription('Lists available migration targets.')
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the target to list.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption('target');
        foreach ($this->migrationTargetMap as $targetName => $migrationTarget) {
            if ($target && $target !== $targetName) {
                continue;
            }
            $migrationList = $migrationTarget->getMigrationList();
            $executedMigrations = $migrationList->getExecutedMigrations();
            $pendingMigrations = $migrationList->getPendingMigrations();
            $output->writeln('Summary for migration target <options=bold>'.$targetName.'</>');
            $output->writeln('  Enabled: '.($migrationTarget->isEnabled() ? 'true' : 'false'));
            $output->writeln('  Executed Migrations: '.count($executedMigrations));
            $output->writeln('  Pending Migrations: '.count($pendingMigrations));
        }

        return 0;
    }
}
