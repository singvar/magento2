<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Model\ResourceModel\Type\Db\Pdo;

use Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface;
use Magento\Framework\DB;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;

/**
 * @codingStandardsIgnoreFile
 */
class Mysql extends \Magento\Framework\Model\ResourceModel\Type\Db implements ConnectionAdapterInterface
{
    /**
     * @var array
     */
    protected $connectionConfig;

    /**
     * @var SelectFactory
     */
    protected $selectFactory;

    /**
     * @var MysqlFactory
     */
    private $mysqlFactory;

    /**
     * Constructor
     *
     * @param SelectFactory $selectFactory
     * @param array $config
     * @param MysqlFactory|null $serializer
     */
    public function __construct(
        SelectFactory $selectFactory,
        array $config,
        MysqlFactory $mysqlFactory = null

    ) {
        $this->selectFactory = $selectFactory;
        $this->connectionConfig = $this->getValidConfig($config);
        $this->mysqlFactory = $mysqlFactory ?: ObjectManager::getInstance()->get(MysqlFactory::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(DB\LoggerInterface $logger)
    {
        $connection = $this->getDbConnectionInstance($logger);

        $profiler = $connection->getProfiler();
        if ($profiler instanceof DB\Profiler) {
            $profiler->setType($this->connectionConfig['type']);
            $profiler->setHost($this->connectionConfig['host']);
        }

        return $connection;
    }

    /**
     * Create and return database connection object instance
     *
     * @param DB\LoggerInterface $logger
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getDbConnectionInstance(DB\LoggerInterface $logger)
    {
        return $this->mysqlFactory->create(
            $this->getDbConnectionClassName(),
            $logger,
            $this->selectFactory,
            $this->connectionConfig
        );
    }

    /**
     * Retrieve DB connection class name
     *
     * @return string
     */
    protected function getDbConnectionClassName()
    {
        return DB\Adapter\Pdo\Mysql::class;
    }

    /**
     * Validates the config and adds default options, if any is missing
     *
     * @param array $config
     * @return array
     */
    private function getValidConfig(array $config)
    {
        $default = ['initStatements' => 'SET NAMES utf8', 'type' => 'pdo_mysql', 'active' => false];
        foreach ($default as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        $required = ['host'];
        foreach ($required as $name) {
            if (!isset($config[$name])) {
                throw new \InvalidArgumentException("MySQL adapter: Missing required configuration option '$name'");
            }
        }

        if (isset($config['port'])) {
            throw new \InvalidArgumentException(
                "Port must be configured within host (like '$config[host]:$config[port]') parameter, not within port"
            );
        }

        $config['active'] = !(
            $config['active'] === 'false'
            || $config['active'] === false
            || $config['active'] === '0'
        );

        return $config;
    }
}
