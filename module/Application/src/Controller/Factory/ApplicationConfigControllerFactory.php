<?php
namespace Application\Controller\Factory;

use Application\Controller\ApplicationConfigController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ApplicationConfigControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $controller = new ApplicationConfigController();
        $adapter = $container->get('model-adapter');
        $controller->setDbAdapter($adapter);
        
        $access_token = $container->get('access-token');
        $controller->setAccessToken($access_token);
        return $controller;
    }
}