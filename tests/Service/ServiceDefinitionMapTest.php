<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Boot\Service;

use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Boot\Service\ServiceDefinitionMap;
use PHPUnit\Framework\TestCase;

final class ServiceDefinitionMapTest extends TestCase
{
    public function testConstructWithSelf(): void
    {
        $serviceDefinitionMock = $this->createMock(ServiceDefinitionInterface::class);
        $serviceDefinitionMap = new ServiceDefinitionMap(['mock' => $serviceDefinitionMock]);
        $newMap = new ServiceDefinitionMap($serviceDefinitionMap);
        $this->assertCount(1, $newMap);
        $this->assertNotSame($serviceDefinitionMap, $newMap);
        $this->assertEquals($serviceDefinitionMap, $newMap);
    }

    public function testPush(): void
    {
        $emptyMap = new ServiceDefinitionMap;
        $serviceDefinitionMock = $this->createMock(ServiceDefinitionInterface::class);
        $serviceDefinitionMap = $emptyMap->with('mock', $serviceDefinitionMock);
        $this->assertCount(1, $serviceDefinitionMap);
        $this->assertEquals($serviceDefinitionMock, $serviceDefinitionMap->get('mock'));
        $this->assertTrue($emptyMap->isEmpty());
    }
}
