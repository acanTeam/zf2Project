<?php

namespace Website;

use Zend\Mvc\MvcEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'invokables' => array(
                'website_install' => '\Website\Controller\InstallController',
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Website\Mapper\Install' => function ($sm) {
                    $mapper = new \Website\Mapper\Install;
                    $config = $sm->get('config');
                    if(isset($config['db'])) {
                        $mapper->setDbAdapter($sm->get('Zend\Db\Adapter\Adapter'));
                    }
                    return $mapper;
                },
            ),
        );
    }

    public function getConfig()
    {
        return array(
            'router' => array(
                'routes' => array(
                    'install' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/install',
                            'defaults' => array(
                                'controller' => 'website_install',
                                'action'     => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'schema' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/schema',
                                    'defaults' => array(
                                        'action' => 'schema',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'view_manager' => array(
                'template_path_stack' => array(
                    __DIR__ . '/view'
                ),
            ),
        );
    }

    public function onBootstrap($e)
    {
        if($e->getRequest() instanceof \Zend\Console\Request){
            return;
        }
        $app = $e->getParam('application');
        $em  = $app->getEventManager();
        $sm  = $em->getSharedManager();

        $em->attach(MvcEvent::EVENT_DISPATCH, array($this , 'install'), 100);
	}

    public function install($e)
    {
        if($e->getRouteMatch()->getParam('controller') === 'website_install') {
            return;
        }
        $response = $e->getResponse();
        $response->setStatusCode(307)->getHeaders()->addHeaderLine('Location', '/install');
    }
}
