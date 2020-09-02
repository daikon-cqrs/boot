<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Serializer;

use Daikon\Boot\Middleware\Action\SerializerInterface;
use JMS\Serializer\SerializerInterface as JMSSerializerInterface;

final class JMSSerializerAdapter implements SerializerInterface
{
    private JMSSerializerInterface $serializer;

    public function __construct(JMSSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize($data, $format, $context = null, $type = null)
    {
        return $this->serializer->serialize($data, $format, $context, $type);
    }

    public function deserialize($data, $type, $format, $context = null)
    {
        return $this->serializer->deserialize($data, $type, $format, $context);
    }
}
