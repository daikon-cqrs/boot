<?php

declare(strict_types=1);

namespace Oroshi\Core\Bootstrap;

use Daikon\Config\ArrayConfigLoader;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Config\ConfigProviderParams;
use Daikon\Config\YamlConfigLoader;
use Oroshi\Core\Config\CratesConfigLoader;

trait BootstrapTrait
{
    private function loadConfiguration(array $bootParams): ConfigProviderInterface
    {
        $bootParams['oroshi'] = ['config_dir' => dirname(dirname(__DIR__)) . '/config'];
        return new ConfigProvider(
            new ConfigProviderParams(
                array_merge(
                    [
                        'app' => [
                            'loader' => ArrayConfigLoader::class,
                            'sources' => $bootParams
                        ],
                        'crates' => [
                            'loader' => new CratesConfigLoader([
                                'crates:' => $bootParams['crates_dir'],
                                'vendor:' => $bootParams['base_dir'].'/vendor'
                            ]),
                            'locations' => [$bootParams['config_dir']],
                            'sources' => [
                                'crates.yml',
                                'crates.${app.context}.yml',
                                'crates.${app.env}.yml',
                                'crates.${app.context}.${app.env}.yml'
                            ]
                        ]
                    ],
                    (new YamlConfigLoader)->load(
                        [
                            $bootParams['oroshi']['config_dir'],
                            $bootParams['config_dir']
                        ],
                        ['loaders.yml']
                    )
                )
            )
        );
    }
}
