<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Bootstrap;

use Auryn\Injector;
use Daikon\Boot\Console\Command\ImportFixture;
use Daikon\Boot\Console\Command\ListConfig;
use Daikon\Boot\Console\Command\ListCrates;
use Daikon\Boot\Console\Command\ListProjectors;
use Daikon\Boot\Console\Command\ListRoutes;
use Daikon\Boot\Console\Command\Migrate\CreateMigration;
use Daikon\Boot\Console\Command\Migrate\ListTargets;
use Daikon\Boot\Console\Command\Migrate\MigrateDown;
use Daikon\Boot\Console\Command\Migrate\MigrateUp;
use Daikon\Boot\Console\Command\RunWorker;
use Daikon\Boot\Service\ServiceProvisioner;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Psr\Container\ContainerInterface;

final class ConsoleBootstrap implements BootstrapInterface
{
    use BootstrapTrait;

    public function __invoke(Injector $injector, array $bootParams): ContainerInterface
    {
        $configProvider = $this->loadConfiguration($bootParams);

        $injector
            ->share($injector)
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
