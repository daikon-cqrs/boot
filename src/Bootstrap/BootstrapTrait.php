<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Bootstrap;

use Daikon\Boot\Config\CratesConfigLoader;
use Daikon\Config\ArrayConfigLoader;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Config\ConfigProviderParams;
use Daikon\Config\YamlConfigLoader;

trait BootstrapTrait
{
    private function loadConfiguration(array $bootParams): ConfigProviderInterface
    {
        $bootParams['daikon'] = ['config_dir' => dirname(dirname(__DIR__)) . '/config'];
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
                            $bootParams['daikon']['config_dir'],
                            $bootParams['config_dir']
                        ],
                        ['loaders.yml']
                    )
                )
            )
        );
    }
}
