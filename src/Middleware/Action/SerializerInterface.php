<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

interface SerializerInterface
{
    /**
     * @param mixed $data
     * @param string $format
     * @param mixed $context
     * @param null|string $type
     * @return mixed
     */
    public function serialize($data, $format, $context = null, $type = null);

    /**
     * @param string $data
     * @param string $type
     * @param string $format
     * @param mixed $context
     * @return mixed
     */
    public function deserialize($data, $type, $format, $context = null);
}
