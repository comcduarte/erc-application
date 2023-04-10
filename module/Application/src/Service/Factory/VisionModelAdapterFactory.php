<?php
namespace Application\Service\Factory;

use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class VisionModelAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = new Adapter($container->get('vision-adapter-config'));
        return $adapter;
    }
}