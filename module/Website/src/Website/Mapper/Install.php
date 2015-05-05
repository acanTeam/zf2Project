<?php

namespace Website\Mapper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class Install implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $dbAdapter;
    protected $sql;

    public function dbConfig($params)
    {
        $dbDist = __DIR__ . 'config/autoload/database.local.php.dist';
        $config = include($dbDist);


        return $config;
    }

    public function testDbConfig($dbConfig, $returnAdapter = false)
    {
        $adapter = new Adapter($dbConfig);
        try {
            $adapter->getCurrentSchema();
        } catch (\Zend\Db\Adapter\Exception\RuntimeException $e) {
            $message = $e->getMessage();
        }

        if (isset($message)) {
            return $message;
        } else if ($returnAdapter) {
            return $adapter;
        } else {
            return true;
        }
    }

    public function createDbConfig($params)
    {
		$config = $this->getServiceLocator()->get('Config');
		$baseDir = $config['common']['baseDir'];
		$dbConfigFile = $baseDir . '/config/autoload/database.local.php';
		$dbConfig = (file_exists($dbConfigFile)) ? require $dbConfigFile : array();

        $dbConfig['db']['username'] = $params['user'];
        $dbConfig['db']['password'] = $params['pass'];
        $dbConfig['db']['dsn'] = "mysql:dbname={$params['db_name']};host={$params['host']}";
		$dbConfig['db']['driver'] = isset($dbConfig['db']['driver']) ? $dbConfig['db']['driver'] : 'pdo';
        $adapter = $this->testDbConfig($dbConfig['db'], true);

        if (!$adapter instanceOf Adapter) {
            throw new \Exception($adapter);
        }
        $this->setDbAdapter($adapter);

        $content = "<?php\nreturn " . var_export($dbConfig, 1) . ';';

        file_put_contents($dbConfigFile, $content);

        return true;
    }

    public function query($sqlString)
    {
        $result = $this->getDbAdapter()->query($sqlString)->execute();
        return $result;
    }

    /**
     * @return dbAdapter
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * @param $dbAdapter
     * @return self
     */
    public function setDbAdapter($dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    public function setupMultisite($e)
    {
        $multisite = $e->getParam('multi_site');

        $mm = $e->getTarget()->getServiceLocator()->get('modulemanager');

        $module = $mm->getModule('SpeckMultisite');
        if(null === $module) {
            return array(false,  'setup multisite - Missing module - SpeckMultisite - Did you run composer install?');
        }
        $reflection = new \ReflectionClass($module);
        $path = dirname($reflection->getFileName());
        try {
            $config = include($path .'/config/module.SpeckMultisite.dist.php');
            $content = "<?php\nreturn " . var_export($config, 1) . ';';
            file_put_contents('config/autoload/multisite.global.php', $content);
        } catch (\Exception $e) {
            return array(false, "Websiteer was unable to complete 'setupMultisite' for {$moduleName} - " . $e->getMessage());
        }

        return array(true, "stored SpeckMultisite config");
    }
}
