<?php
namespace Application\Form\Factory;

use Application\Form\SelectMetadataTemplateForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SelectMetadataTemplateFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $form = new SelectMetadataTemplateForm();
        $adapter = $container->get('model-adapter');
        
        $form->setDbAdapter($adapter);
        return $form;
    }
}