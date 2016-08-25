<?php
namespace BookIt\Codeception\TestRail;


use BookIt\Codeception\TestRail\Model\Plan;
use BookIt\Codeception\TestRail\Model\PlanEntry;
use BookIt\Codeception\TestRail\Model\Project;
use BookIt\Codeception\TestRail\Model\Run;
use Codeception\Exception\ModuleException;
use Codeception\Module as CodeceptionModule;
use Codeception\Step;
use Codeception\Test\Cest;
use Codeception\Test\Test;
use Codeception\TestInterface;

class Module extends CodeceptionModule
{
    protected $requiredFields = ['user', 'apikey', 'project', 'suite'];

    protected $config = [ ];

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Plan
     */
    protected $plan;

    /**
     * @var PlanEntry
     */
    protected $entry;

    /**
     * @var Run
     */
    protected $run;

    /**
     * @var int
     */
    protected $testCase;

    /**
     * @var int[][]
     */
    protected $usedCases = [];

    // HOOK: used after configuration is loaded
    public function _initialize()
    {
        $conn = new Connection();
        $conn->setUser($this->config['user']);
        $conn->setApiKey($this->config['apikey']);
        $conn->connect('https://bookit.testrail.com');

        $project = $conn->getProject($this->config['project']);
        $plan = $conn->createTestPlan($project, date('Y-m-d H:i:s'));
        // TODO: procedural generation of test plan names (template?  provider class?)

        $this->conn = $conn;
        $this->project = $project;
        $this->plan = $plan;
    }

    // HOOK: before each suite
    public function _beforeSuite($settings = array())
    {
        // TODO: Reuse suite runs if the suite already has an entry
        $suite = $this->project->getSuite($this->config['suite']);
        $entry = $this->conn->createTestPlanEntry($this->plan, $suite);
        $entry->setSuite($suite);
        $this->plan->addEntry($entry);
        $this->entry = $entry;
        $this->run = $entry->getRuns()[0];
    }

    public function _afterSuite()
    {
        foreach ($this->usedCases as $run=>$cases) {
            $this->conn->updatePlanEntry($this->plan->getId(), $this->entry->getId(),[
                'include_all' => false,
                'case_ids' => $cases,
            ]);
        }
    }

    // HOOK: before the test
    public function _before(TestInterface $test)
    {
        $this->testCase = null;
    }

    // HOOK: after the test
    public function _after(TestInterface $test)
    {
        if ($test instanceof Test && $this->testCase) {
            $this->_processResult($test->getTestResultObject());
            $this->usedCases[$this->run->getId()][] = $this->testCase;
        }
    }

    public function _processResult(\PHPUnit_Framework_TestResult $result)
    {
        if ($result->wasSuccessful()) {
            if ($result->noneSkipped() && $result->allCompletelyImplemented()) {
                $this->conn->addResult($this->run, $this->testCase, 1);
            } else {
                if (!$result->noneSkipped()) {
                    $this->conn->addResult($this->run, $this->testCase, 11);
                } elseif (!$result->allCompletelyImplemented()) {
                    $this->conn->addResult($this->run, $this->testCase, 12);
                }
            }
        } else {
            if ($result->errorCount() > 0) {
                if ($result->failureCount() > 0) {
                    $this->conn->addResult($this->run, $this->testCase, 5);
                } else {
                    $this->conn->addResult($this->run, $this->testCase, 5);
                }
            }
        }
    }

    /**
     * Tell the module which test case you're recording a result for
     *
     * @param int $caseId
     */
    public function setTestCase($caseId)
    {
        $this->testCase = $caseId;
    }

}
