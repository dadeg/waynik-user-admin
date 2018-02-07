<?php

namespace Waynik\Models;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Doctrine\DBAL\Connection;
use Silex\Application;

abstract class AbstractManager
{
    /** @var Connection */
    protected $conn;

    /** @var \Silex\Application */
    protected $app;

    /** @var EventDispatcher */
    protected $dispatcher;
    
    /** @var string */
    protected $tableName;

    public function __construct(Connection $conn, Application $app)
    {
        $this->conn = $conn;
        $this->app = $app;
        $this->dispatcher = $app['dispatcher'];
    }
}