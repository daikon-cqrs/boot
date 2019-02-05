<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\Flysystem\Connector\FlysystemConnector;
use League\Flysystem\MountManager;

final class FlysystemFixtureLoader implements FixtureLoaderInterface
{
    /** @var FlysystemConnector */
    private $connector;

    /** @var array */
    private $settings;

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

        $fixtures = [];
        foreach ($fixtureFiles as $fixtureFile) {
            // @todo better way to include fixture classes
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
