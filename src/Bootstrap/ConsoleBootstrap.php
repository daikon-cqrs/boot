<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Bootstrap;

use Auryn\Injector;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Console\Command\ImportFixture;
use Oroshi\Core\Console\Command\ListConfig;
use Oroshi\Core\Console\Command\ListCrates;
use Oroshi\Core\Console\Command\ListProjectors;
use Oroshi\Core\Console\Command\ListRoutes;
use Oroshi\Core\Console\Command\Migrate\CreateMigration;
use Oroshi\Core\Console\Command\Migrate\ListTargets;
use Oroshi\Core\Console\Command\Migrate\MigrateDown;
use Oroshi\Core\Console\Command\Migrate\MigrateUp;
use Oroshi\Core\Console\Command\RunWorker;
use Oroshi\Core\Service\ServiceProvisioner;
use Psr\Container\ContainerInterface;

final class ConsoleBootstrap implements BootstrapInterface
{
    use BootstrapTrait;

    public function __invoke(Injector $injector, array $bootParams): ContainerInterface
    {
        $configProvider = $this->loadConfiguration($bootParams);
        $injector
            ->share($configProvider)
            ->alias(ConfigProviderInterface::class, ConfigProvider::class);
        $container = (new ServiceProvisioner)->provision($injector, $configProvider);
        $injector
            ->share($container)
            ->alias(ContainerInterface::class, get_class($container))
            ->defineParam(
                'consoleCommands',
                array_map([$container, 'get'], [
                    CreateMigration::class,
                    ImportFixture::class,
                    ListConfig::class,
                    ListCrates::class,
                    ListProjectors::class,
                    ListRoutes::class,
                    ListTargets::class,
                    MigrateUp::class,
                    MigrateDown::class,
                    RunWorker::class
                ])
            );
        return $container;
    }
}
