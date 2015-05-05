<?php

namespace Website\Controller;

use \Zend\Mvc\Controller\AbstractActionController;
use \Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    protected $responses;
    protected $mapper;
    protected $okToContinue = true;

    public function indexAction()
    {
    }

    public function schemaAction()
    {
        $params = $this->params()->fromPost();
        $mapper = $this->getMapper();

		try { 
			$response = $mapper->createDbConfig($params); 
		} catch (\Exception $e) { 
		}

        if (isset($response) && $response === true) {
            $this->addResponse(array(true, 'Store DB Config'));
        } else {
            $this->addResponse(array(false, 'Store DB Config - ' . $e->getMessage()));
            return $this->finish(false);
        }

        $mm = $this->getServiceLocator()->get('modulemanager');
        $module = $mm->getModule('Website');
        if(null === $module) {
            return array(false,  'Missing module - ' . $moduleName . ' - Did you run composer install?');
        }
        $reflection = new \ReflectionClass($module);
        $path = dirname($reflection->getFileName());

        $create = file_get_contents($path .'/data/schema.sql');
		if(!$create) { 
			throw new \Exception('cannot find schema file'); 
		}

        $response = $mapper->query($create);

		if (!$this->addResponse($response)) { 
			return $this->finish(false);
		}

        return $this->finish(true);
    }

    public function addResponse($response, $strings=array())
    {
        if($this->okToContinue === false) {
            return false;
        }
        if (is_string($response)) {
            $this->responses[] = array(true, $response);
        } elseif (count($response) == 2 && is_bool($response[0])) {
            $this->responses[] = $response;
            if($response[0] === false) {
                $this->okToContinue = false;
                return false;
            }
        } else {
            foreach ($response as $resp) {
                $this->addResponse($resp);
            }
        }
        return true;
    }

    public function finish($success)
    {
        $view = new ViewModel(array('responses' => $this->responses, 'success' => $success));
        return $view->setTemplate('/website/install/finish');
    }

    /**
     * @return mapper
     */
    public function getMapper()
    {
        if (null === $this->mapper) {
            $this->mapper = $this->getServiceLocator()->get('Website\Mapper\Install');
        }
        return $this->mapper;
    }

    /**
     * @param $mapper
     * @return self
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

}
