<?php
namespace BookIt\Codeception\TestRail;


use Codeception\Exception\ModuleException;
use Codeception\Module as CodeceptionModule;
use Codeception\Step;
use Codeception\Test\Test;
use Codeception\TestInterface;

class Module extends CodeceptionModule
{
    protected $requiredFields = [ 'user', 'apikey', 'project' ];

    protected $config = [ 'suite' => null ];

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var int
     */
    protected $project;

    /**
     * @var int
     */
    protected $plan;

    /**
     * @var int
     */
    protected $case;

    /**
     * @var int
     */
    protected $suite;

    /**
     * Multi-dimensional array of results
     * @var array
     */
    protected $results = [];

    // HOOK: used after configuration is loaded
    public function _initialize()
    {
        $conn = new Connection();
        $conn->setUser($this->config['user']);
        $conn->setApiKey($this->config['apikey']);
        $conn->connect('https://bookit.testrail.com');

        $project = $conn->execute('get_project/'. $this->config['project']);

        if ($project->is_completed) {
            throw new ModuleException($this,'Project passed in the config has been completed and cannot be modified');
        }

        // TODO: procedural generation of test plan names (template?  provider class?)
        $plan = $conn->execute('add_plan/'. $project->id, 'POST', [
            'name' => date('Y-m-d H:i:s'),
        ]);

        $this->conn = $conn;
        $this->project = $project->id;
        $this->plan = $plan->id;
    }

    public function _afterSuite()
    {
        foreach ($this->results as $suite=>$results) {
            $caseIds = array_reduce($results, function ($carry, $val) {
                $carry[] = $val['case_id'];
                return $carry;
            },[]);

            $entry = $this->conn->execute('/add_plan_entry/'. $this->plan, 'POST', [
                'suite_id' => $suite,
                'case_ids' => $caseIds,
                'include_all' => false,
            ]);

            $run = $entry->runs[0];
            $this->conn->execute('/add_results_for_cases/'. $run->id, 'POST', [
                'results' => $results,
            ]);
        }
    }

    // HOOK: before the test
    public function _before(TestInterface $test)
    {
        $this->suite = null;
        $this->case = null;
    }

    // HOOK: after the test
    public function _after(TestInterface $test)
    {
        if ($test instanceof Test && $this->case) {
            /** @var \PHPUnit_Framework_TestResult $result */
            $result = $test->getTestResultObject();
            $status = $this->_determineStatus($result);
            $this->results[$this->suite][] = [
                'case_id' => $this->case,
                'status_id' => $status,
                'comment' => sprintf(
                    "%s \nAsserts: %d",
                    $test->getSignature(),
                    $test->getNumAssertions()
                ),
            ];
        }
    }

    public function _determineStatus(\PHPUnit_Framework_TestResult $result)
    {
        $status = null;
        if ($result->wasSuccessful()) {
            if ($result->noneSkipped() && $result->allCompletelyImplemented()) {
                $status = 1;
            } else {
                if (!$result->noneSkipped()) {
                    $status = 11;
                } elseif (!$result->allCompletelyImplemented()) {
                    $status = 12;
                }
            }
        } else {
            if ($result->errorCount() > 0) {
                if ($result->failureCount() > 0) {
                    $status = 5;
                } else {
                    $status = 5;
                }
            }
        }

        return $status;
    }

    /**
     * Tell the module which test case you're recording a result for
     *
     * @param int $case TestRail Test Case ID
     * @param int $suite TestRail Test Suite ID
     */
    public function setTestCase($case, $suite=null)
    {
        if (!$suite) {
            $suite = $this->config['suite'];

            if (!$suite) {
                throw new \RuntimeException(
                    'No suite passed with the test case or the suite id supplied by the config is null'
                );
            }
        }

        $this->suite = $suite;
        $this->case  = $case;
    }

}
