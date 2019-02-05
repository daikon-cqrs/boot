<?php

declare(strict_types=1);

namespace Oroshi\Core\Config;

use Aura\Router\RouterContainer;
use Daikon\Config\ConfigLoaderInterface;
use Daikon\Config\ConfigProviderInterface;

final class RoutingConfigLoader implements ConfigLoaderInterface
{
    /** @var RouterContainer */
    private $router;

    /** @var ConfigProviderInterface */
    private $configProvider;

    public function __construct(RouterContainer $router, ConfigProviderInterface $configProvider)
    {
        $this->router = $router;
        $this->configProvider = $configProvider;
    }

    public function load(array $locations, array $sources): array
    {
        $router = $this->router;
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
                    /** @psalm-suppress UnresolvableInclude */
                    require_once $filepath;
                    $loadedConfigs[] = $filepath;
                }
            }
        }
        return $loadedConfigs;
    }
}
