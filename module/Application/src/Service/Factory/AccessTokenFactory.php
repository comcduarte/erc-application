<?php
namespace Application\Service\Factory;

use Laminas\Box\API\AccessToken;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AccessTokenFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $access_token = new AccessToken($container->get('access-token-config'));
        return $access_token;
    }
}