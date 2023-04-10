<?php
namespace Application\Controller\Factory;

use Application\Controller\IndexController;
use Application\Form\SelectMetadataTemplateForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Model\VisionPropertyModel;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $controller = new IndexController();
        $form = $container->get('FormElementManager')->get(SelectMetadataTemplateForm::class);
        $adapter = $container->get('model-adapter');
        $access_token = $container->get('access-token');
        
        $vision_adapter = $container->get('vision-adapter');
        $controller->vision_adapter = $vision_adapter;
        $vision_model = new VisionPropertyModel($vision_adapter);
        $controller->vision_model = $vision_model;
        
        $controller->select_metadata_template_form = $form;
        $controller->logger = $container->get('syslogger');
        $controller->setDbAdapter($adapter);
        $controller->setAccessToken($access_token);
        return $controller;
    }
}