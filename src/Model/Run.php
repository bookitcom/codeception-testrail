<?php
namespace BookIt\Codeception\TestRail\Model;


class Run
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var Suite
     */
    protected $suite;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

}
