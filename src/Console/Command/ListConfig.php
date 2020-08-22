<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Console\Command;

use Daikon\Config\ConfigProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class ListConfig extends Command
{
    private ConfigProviderInterface $configProvider;

    public function __construct(ConfigProviderInterface $configProvider)
    {
        parent::__construct();

        $this->configProvider = $configProvider;
    }

    protected function configure(): void
    {
        $this
            ->setName('config:ls')
            ->setDescription('List configuration settings.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Filter values for a given path.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_string($path = $input->getArgument('path'))) {
            //@todo improve root scope config listing
            $scopes = [
                'app',
                'connectors',
                'crates',
                'databases',
                'fixtures',
                'jobs',
                'migrations',
                'project',
                'secrets',
                'services',
            ];
            $path = $this->getHelper('question')->ask(
                $input,
                $output,
                new ChoiceQuestion('Available scopes:', $scopes)
            );
        }

        $configs = $this->configProvider->get($path, []);
        $this->renderValues($output, $configs);

        return 0;
    }

    private function renderValues(OutputInterface $output, array $settings, int $indent = 0): void
    {
        foreach ($settings as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                switch (true) {
                    case is_bool($value):
                        $value = $value === true ? 'true' : 'false';
                        break;
                    case is_null($value):
                        $value = 'null';
                        break;
                    case $value === '':
                        $value = '""';
                        break;
                }
                $output->writeln(str_repeat(' ', $indent*2)."<info>$key</info>: $value");
            } elseif (empty($value)) {
                $output->writeln(str_repeat(' ', $indent*2)."<info>$key</info>: []");
            } else {
                $output->writeln(str_repeat(' ', $indent*2)."<info>$key</info>: [");
                $this->renderValues($output, $value, ++$indent);
                $output->writeln(str_repeat(' ', --$indent*2)."]");
            }
        }
    }
}
