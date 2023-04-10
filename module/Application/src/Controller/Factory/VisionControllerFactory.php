<?php
namespace Application\Controller\Factory;

use Application\Controller\VisionController;
use Application\Model\VisionPropertyModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Form\VisionPropertyForm;

class VisionControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $controller = new VisionController();
        $adapter = $container->get('vision-adapter');
        $model = new VisionPropertyModel($adapter);
        $form = $container->get('FormElementManager')->get(VisionPropertyForm::class);
        
        $controller->model = $model;
        $controller->form = $form;
        $controller->logger = $container->get('syslogger');
        $controller->setDbAdapter($adapter);
        return $controller;
    }
}