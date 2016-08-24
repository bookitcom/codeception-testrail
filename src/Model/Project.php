<?php
namespace BookIt\Codeception\TestRail\Model;


class Project
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Suite[]
     */
    protected $suites;

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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * @param Suite[] $suites
     */
    public function setSuites(array $suites)
    {
        $this->suites = $suites;
    }

    /**
     * @return Suite[]
     */
    public function getSuites()
    {
        return $this->suites;
    }

    /**
     * @param int|string $search
     *
     * @return Suite
     */
    public function getSuite($search)
    {
        if (!is_int($search) && !is_string($search)) {
            throw new \InvalidArgumentException('Suite must be either an integer or a string');
        }

        foreach ($this->suites as $suite) {
            if (is_int($search)) {
                if ($suite->getId() == $search) {
                    return $suite;
                }
            } else {
                if (strtolower($suite->getName()) == strtolower($search)) {
                    return $suite;
                }
            }
        }

        return null;
    }

}
