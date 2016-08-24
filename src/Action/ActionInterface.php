<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Connection;

interface ActionInterface
{

    /**
     * @param Connection $conn
     */
    public function setConnection(Connection $conn);

    /**
     * @return mixed
     */
    public function __invoke();

}
