<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Config;

use Daikon\Config\ConfigLoaderInterface;
use Daikon\Config\YamlConfigLoader;
use Stringy\Stringy;

final class CratesConfigLoader implements ConfigLoaderInterface
{
    private YamlConfigLoader $yamlLoader;

    private array $dirPrefixes;

    public function __construct(array $dirPrefixes, YamlConfigLoader $yamlLoader = null)
    {
        $this->yamlLoader = $yamlLoader ?? new YamlConfigLoader;
        $this->dirPrefixes = $dirPrefixes;
    }

    public function load(array $locations, array $sources): array
    {
        $config = [];
        foreach ($this->yamlLoader->load($locations, $sources) as $crateName => $crateConfig) {
            //@todo improve configuration loading for missing values
            $configDir = $crateConfig['config_dir'] ?? '';
            $migrationDir = $crateConfig['migration_dir'] ?? '';
            $fixtureDir = $crateConfig['fixture_dir'] ?? '';
            $crateConfig['config_dir'] = $this->expandPath($configDir);
            $crateConfig['migration_dir'] = $this->expandPath($migrationDir);
            $crateConfig['fixture_dir'] = $this->expandPath($fixtureDir);
            $config[$crateName] = $crateConfig;
        }

        return $config;
    }

    private function expandPath(string $path): string
    {
        if (Stringy::create($path)->startsWith('/')) {
            return $path;
        }

        $search = array_keys($this->dirPrefixes);
        $replace = array_map(
            fn(string $path): string => Stringy::create($path)->endsWith('/') ? $path : "$path/",
            array_values($this->dirPrefixes)
        );

        return str_replace($search, $replace, $path);
    }
}
