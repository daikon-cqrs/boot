<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command\Migrate;

use Daikon\Dbal\Migration\MigrationTargetMap;
use DateTimeImmutable;
use Oroshi\Core\Console\Command\DialogTrait;
use Oroshi\Core\Crate\CrateMap;
use Stringy\Stringy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class CreateMigration extends Command
{
    use DialogTrait;

    /** @var MigrationTargetMap */
    private $migrationTargetMap;

    /** @var CrateMap */
    private $crateMap;

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

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if (!count($this->crateMap) || !count($this->migrationTargetMap)) {
            $output->writeln('<error>There are no crates|migration-targets available to generate migrations for.</error>');
            $output->writeln('');
            exit;
        }
        if (!$crate = $input->getArgument('crate')) {
            $crate = $this->promptCrate($input, $output);
        }
        if (!$this->crateMap->has($crate)) {
            $output->writeln("<error>Crate '$crate' does not exist.</error>");
            $output->writeln('');
            exit;
        }
        $crateSettings = $this->crateMap->get($crate)->getSettings();
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
    }

    private function promptCrate(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a crate: ',
            array_keys($this->crateMap->toNative())
        );
        return $helper->ask($input, $output, $question);
    }

    private function promptDir(string $parent, InputInterface $input, OutputInterface $output)
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

    private function promptName(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getHelper('question')->ask($input, $output, new Question(
            'Please provide a short migration description: '
        ));
        return Stringy::create($name)->upperCamelize();
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
