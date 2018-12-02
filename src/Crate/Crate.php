<?php

declare(strict_types=1);

namespace Oroshi\Core\Crate;

final class Crate implements CrateInterface
{
    /** @var array */
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getLocation(): string
    {
        return dirname($this->settings['config_dir']);
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
