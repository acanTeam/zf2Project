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
		echo $dbDist;exit();
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
		$dbConfigFile = $baseDir . '/config/autoload/local.php';
		$dbConfig = (file_exists($dbConfigFile)) ? require $dbConfigFile : array();

        $dbConfig['db']['username'] = $params['user'];
        $dbConfig['db']['password'] = $params['pass'];
        $dbConfig['db']['dsn'] = "mysql:dbname={$params['db_name']};host={$params['host']}";

        $adapter = $this->testDbConfig($config['db'], true);

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
}
