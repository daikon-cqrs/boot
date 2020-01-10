<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

use Daikon\Flysystem\Connector\FlysystemConnector;
use League\Flysystem\MountManager;

final class FlysystemFixtureLoader implements FixtureLoaderInterface
{
    private FlysystemConnector $connector;

    private array $settings;

    public function __construct(FlysystemConnector $connector, array $settings = [])
    {
        $this->connector = $connector;
        $this->settings = $settings;
    }

    public function load(): FixtureList
    {
        /** @var MountManager $filesystem */
        $filesystem = $this->connector->getConnection();
        $contents = $filesystem->listContents($this->settings['location'], true);
        $fixtureFiles = array_filter($contents, function (array $fileinfo): bool {
            return isset($fileinfo['extension']) && $fileinfo['extension'] === 'php';
        });

        // @todo better way to include fixture classes
        $fixtures = [];
        foreach ($fixtureFiles as $fixtureFile) {
            $declaredClasses = get_declared_classes();
            require_once $this->getBaseDir().'/'.$fixtureFile['path'];
            $fixtureClass = current(array_diff(get_declared_classes(), $declaredClasses));
            $fixtures[] = new $fixtureClass;
        }

        return new FixtureList($fixtures);
    }

    private function getBaseDir(): string
    {
        $connectorSettings = $this->connector->getSettings();
        return $connectorSettings['mounts']['fixture']['location'];
    }
}
