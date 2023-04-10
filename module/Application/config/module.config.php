<?php

declare(strict_types=1);

namespace Application;

use Application\Controller\Factory\BoxControllerFactory;
use Application\Controller\Factory\IndexControllerFactory;
use Application\Controller\Factory\VisionControllerFactory;
use Application\Form\SelectMetadataTemplateForm;
use Application\Form\VisionPropertyForm;
use Application\Form\Factory\SelectMetadataTemplateFormFactory;
use Application\Form\Factory\VisionPropertyFormFactory;
use Application\Service\Factory\AccessTokenFactory;
use Application\Service\Factory\ApplicationModelAdapterFactory;
use Application\Service\Factory\VisionModelAdapterFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'box' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/box[/:action]',
                    'defaults' => [
                        'controller' => Controller\BoxController::class,
                        'action'     => 'redirect',
                    ],
                ],
            ],
            'vision' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/vision[/:action]',
                    'defaults' => [
                        'controller' => Controller\VisionController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'acl' => [
        'EVERYONE' => [
            'home' => [],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => IndexControllerFactory::class,
            Controller\BoxController::class => BoxControllerFactory::class,
            Controller\VisionController::class => VisionControllerFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            SelectMetadataTemplateForm::class => SelectMetadataTemplateFormFactory::class,
            VisionPropertyForm::class => VisionPropertyFormFactory::class,
        ],
    ],
    'log' => [
        'syslogger' => [
            'writers' => [
                'syslog' => [
                    'name' => \Laminas\Log\Writer\Syslog::class,
                    'priority' => \Laminas\Log\Logger::INFO,
                    'options' => [
                        'application' => 'ERC',
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'default' => [
            'home' => [
                'label' => 'Home',
                'route' => 'home',
                'order' => 0,
            ],
        ],
    ],
    'service_manager' => [
        'aliases' => [
        ],
        'factories' => [
            'model-adapter' => ApplicationModelAdapterFactory::class,
            'vision-adapter' => VisionModelAdapterFactory::class,
            'access-token' => AccessTokenFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'navigation'              => __DIR__ . '/../../Components/view/components/partials/navigation.phtml',
            'flashmessenger'          => __DIR__ . '/../../Components/view/components/partials/flashmessenger.phtml',
            'layout/layout'           => __DIR__ . '/../view/layout/custom-layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'title' => 'Electronic Records Clerk',
        'version' => 'alpha',
    ],
];
