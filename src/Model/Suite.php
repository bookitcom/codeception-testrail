<?php
namespace BookIt\Codeception\TestRail\Model;


class Suite
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
     * @var TestCase[]
     */
    protected $cases;

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
     * @return TestCase[]
     */
    public function getCases()
    {
        return $this->cases;
    }

    /**
     * @param TestCase[] $cases
     */
    public function setCases(array $cases)
    {
        $this->cases = $cases;
    }

}
