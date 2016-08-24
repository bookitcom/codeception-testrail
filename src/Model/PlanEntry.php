<?php
namespace BookIt\Codeception\TestRail\Model;


class PlanEntry
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Suite
     */
    protected $suite;

    /**
     * @var Run[]
     */
    protected $runs;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = (string)$id;
    }

    /**
     * @return Suite
     */
    public function getSuite()
    {
        return $this->suite;
    }

    /**
     * @param Suite $suite
     */
    public function setSuite(Suite $suite)
    {
        $this->suite = $suite;
    }

    /**
     * @return Run[]
     */
    public function getRuns()
    {
        return $this->runs;
    }

    /**
     * @param Run[] $runs
     */
    public function setRuns(array $runs)
    {
        $this->runs = $runs;
    }
}
