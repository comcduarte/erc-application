<?php
namespace Application\Form\Factory;

use Application\Form\VisionPropertyForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class VisionPropertyFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $form = new VisionPropertyForm();
        $adapter = $container->get('vision-adapter');
        
        $form->setDbAdapter($adapter);
        return $form;
    }
}