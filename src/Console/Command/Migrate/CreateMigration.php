<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Console\Command\Migrate;

use Daikon\Boot\Console\Command\DialogTrait;
use Daikon\Boot\Crate\CrateInterface;
use Daikon\Boot\Crate\CrateMap;
use Daikon\Dbal\Migration\MigrationTargetMap;
use Daikon\Interop\Assertion;
use DateTimeImmutable;
use Stringy\Stringy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class CreateMigration extends Command
{
    use DialogTrait;

    private MigrationTargetMap $migrationTargetMap;

    private CrateMap $crateMap;

    public function __construct(MigrationTargetMap $migrationTargetMap, CrateMap $crateMap)
    {
        $this->migrationTargetMap = $migrationTargetMap;
        $this->crateMap = $crateMap;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate:create')
            ->setDescription('Create a new migration within a selected crate.')
            ->addArgument(
                'crate',
                InputArgument::OPTIONAL,
                'Name of the crate to create the migration in.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!count($this->crateMap) || !count($this->migrationTargetMap)) {
            $output->writeln('<error>There are no targets available to generate migrations for.</error>');
            $output->writeln('');
            exit;
        }

        if (!is_string($crateName = $input->getArgument('crate'))) {
            $crateName = $this->promptCrate($input, $output);
        }

        if (!$this->crateMap->has($crateName)) {
            $output->writeln("<error>Crate '$crateName' does not exist.</error>");
            $output->writeln('');
            exit;
        }

        /** @var CrateInterface $crate */
        $crate = $this->crateMap->get($crateName);
        $crateSettings = $crate->getSettings();
        $targetDir = $this->promptDir($crateSettings['migration_dir'], $input, $output);

        $timestamp = (new DateTimeImmutable)->format('Ymdhis');
        $name = $this->promptName($input, $output);
        $migrationTpl = $this->migrationTemplate();
        $className = $name.$timestamp;
        $migration = str_replace("[CLASSNAME]", $className, $migrationTpl);
        $migrationDir = implode('', [
            $crateSettings['migration_dir'],
            "/$targetDir/$timestamp-$name",
        ]);

        if (!is_dir($migrationDir)) {
            mkdir($migrationDir);
        }

        $migrationFile = "$migrationDir/$className.php";
        if (file_put_contents($migrationFile, $migration)) {
            $output->writeln("Created migration file at: <options=bold>$migrationFile</>");
        }

        return 0;
    }

    private function promptCrate(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Please select a crate: ', $this->crateMap->keys());
        return $helper->ask($input, $output, $question);
    }

    private function promptDir(string $parent, InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a migration-target dir: ',
            array_map(function (SplFileInfo $fileInfo): string {
                return $fileInfo->getBasename();
            }, array_values(iterator_to_array(
                (new Finder)->depth(0)->directories()->in($parent)
            )))
        );
        return $helper->ask($input, $output, $question);
    }

    private function promptName(InputInterface $input, OutputInterface $output): string
    {
        $name = $this->getHelper('question')->ask($input, $output, new Question(
            'Please provide a short migration description: '
        ));
        return (string)Stringy::create($name)->upperCamelize();
    }

    private function migrationTemplate(): string
    {
        return <<<MIGRATION
<?php

namespace Change\Me\Migration\MyTarget;

use Daikon\Dbal\Migration\MigrationInterface;

final class [CLASSNAME] implements MigrationInterface
{

}

MIGRATION;
    }
}
