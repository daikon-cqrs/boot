<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Config;

use Aura\Router\RouterContainer;
use Daikon\Config\ConfigLoaderInterface;
use Daikon\Config\ConfigProviderInterface;

final class RoutingConfigLoader implements ConfigLoaderInterface
{
    private RouterContainer $router;

    private ConfigProviderInterface $configProvider;

    public function __construct(RouterContainer $router, ConfigProviderInterface $configProvider)
    {
        $this->router = $router;
        $this->configProvider = $configProvider;
    }

    public function load(array $locations, array $sources): array
    {
        $router = $this->router;
        //these variables are in scope for included routing files
        $map = $router->getMap();
        $configProvider = $this->configProvider;

        $loadedConfigs = [];
        foreach ($locations as $location) {
            if (substr($location, -1) !== '/') {
                $location .= '/';
            }
            foreach ($sources as $source) {
                $filepath = $location.$source;
                if (is_file($filepath) && is_readable($filepath)) {
                    require_once $filepath;
                    $loadedConfigs[] = $filepath;
                }
            }
        }

        return $loadedConfigs;
    }
}
