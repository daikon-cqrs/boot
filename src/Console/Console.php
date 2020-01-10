<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Console;

use Daikon\Config\ConfigProviderInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

final class Console extends Application
{
    private ConfigProviderInterface $configProvider;

    public static function getLogo(): string
    {
        return <<<ASCII
   ____  ____  ____  _____ __  ______
  / __ \/ __ \/ __ \/ ___// / / /  _/
 / / / / /_/ / / / /\__ \/ /_/ // /
/ /_/ / _, _/ /_/ /___/ / __  // /
\____/_/ |_|\____//____/_/ /_/___/

ASCII;
    }

    public function __construct(
        ConfigProviderInterface $configProvider,
        array $consoleCommands = []
    ) {
        $this->configProvider = $configProvider;

        parent::__construct(
            (string)$configProvider->get('project.name'),
            sprintf('%s@%s', $configProvider->get('project.version'), $configProvider->get('app.env'))
        );

        $this->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The environment name.', 'dev')
        );

        foreach ($consoleCommands as $command) {
            $this->add($command);
        }
    }

    public function getHelp(): string
    {
        return self::getLogo().parent::getHelp();
    }
}
