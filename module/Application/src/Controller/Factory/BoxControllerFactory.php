<?php
namespace Application\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Controller\BoxController;

class BoxControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $controller = new BoxController();
        $adapter = $container->get('model-adapter');
        $access_token = $container->get('access-token');
        
        $controller->logger = $container->get('syslogger');
        $controller->setDbAdapter($adapter);
        $controller->setAccessToken($access_token);
        return $controller;
    }
}