<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Boot\Crate;

use Daikon\Boot\Crate\CrateInterface;
use Daikon\Boot\Crate\CrateMap;
use PHPUnit\Framework\TestCase;

final class CrateMapTest extends TestCase
{
    public function testConstructWithSelf(): void
    {
        $crateMock = $this->createMock(CrateInterface::class);
        $crateMap = new CrateMap(['mock' => $crateMock]);
        $newMap = new CrateMap($crateMap);
        $this->assertCount(1, $newMap);
        $this->assertNotSame($crateMap, $newMap);
        $this->assertEquals($crateMap, $newMap);
    }

    public function testPush(): void
    {
        $emptyMap = new CrateMap;
        $crateMock = $this->createMock(CrateInterface::class);
        $crateMap = $emptyMap->with('mock', $crateMock);
        $this->assertCount(1, $crateMap);
        $this->assertEquals($crateMock, $crateMap->get('mock'));
        $this->assertTrue($emptyMap->isEmpty());
    }
}
