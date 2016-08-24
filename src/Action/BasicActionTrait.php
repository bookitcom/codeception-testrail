<?php


namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Connection;

trait BasicActionTrait
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @param Connection $conn
     */
    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
    
}
