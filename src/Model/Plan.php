<?php
namespace BookIt\Codeception\TestRail\Model;


class Plan
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
     * The key of the top-level array is the suite id the run corresponds to
     *
     * @var PlanEntry[]
     */
    protected $entries;

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
        return $this;
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
        return $this;
    }

    /**
     * @param int $suiteId
     *
     * @return bool
     */
    public function hasEntryForSuite($suiteId)
    {
        return array_reduce($this->entries, function ($carry, $val) use ($suiteId) {
            /** @var PlanEntry $val */
            return $carry || $val->getSuite()->getId() == $suiteId;
        }, false);
    }

    public function addEntry(PlanEntry $entry)
    {
        if (isset($this->entries[$entry->getId()])) {
            // TODO: throw useful exception here
        }
        
        $this->entries[$entry->getId()] = $entry;
    }

}
