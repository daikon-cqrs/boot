<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command;

use Daikon\Config\ConfigProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListConfig extends Command
{
    /** @var ConfigProviderInterface */
    private $configProvider;

    public function __construct(ConfigProviderInterface $configProvider)
    {
        parent::__construct();

        $this->configProvider = $configProvider;
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($path = $input->getArgument('path')) {
            $configs = $this->configProvider->get($path, []);
        } else {
            // @todo handle listing all configs
            $configs = $this->configProvider->get('*', []);
        }

        $this->renderValues($output, $configs);
    }

    private function renderValues(OutputInterface $output, $settings, $indent = 0)
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
