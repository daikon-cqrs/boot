<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Boot\Middleware\Action\SerializerInterface;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;
use Daikon\ValueObject\ValueObjectInterface;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;

class JMSSerializerServiceProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $className = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();

        $injector
            ->alias(SerializerInterface::class, $className)
            ->share($className)
            ->delegate($className, $this->factory($className, $configProvider, $settings));
    }

    private function factory(
        string $className,
        ConfigProviderInterface $configProvider,
        array $settings
    ): callable {
        return function () use ($className, $configProvider, $settings): SerializerInterface {
            return new $className(SerializerBuilder::create()
                ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy)
                ->setAnnotationReader(new SimpleAnnotationReader)
                ->setMetadataDirs((array)($settings['metadata_dirs'] ?? []))
                ->configureHandlers(function (HandlerRegistry $registry): void {
                    $registry->registerHandler(
                        GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                        'ValueObject',
                        'json',
                        /**
                         * @param mixed $visitor
                         * @return mixed
                         */
                        fn($visitor, ValueObjectInterface $valueObject, array $type) => $valueObject->toNative()
                    );
                })
                ->setCacheDir($configProvider->get('app.cache_dir'))
                ->setDebug($configProvider->get('app.debug'))
                ->build());
        };
    }
}
