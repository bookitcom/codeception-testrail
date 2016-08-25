<?php
namespace BookIt\Codeception\TestRail;


use Codeception\Event\FailEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Exception\ExtensionException;
use Codeception\Extension as CodeceptionExtension;
use Codeception\Test\Cest;
use Codeception\Test\Test;
use Codeception\TestInterface;
use Codeception\Util\Annotation;

class Extension extends CodeceptionExtension
{
    const ANNOTATION_SUITE = 'tr-suite';
    const ANNOTATION_CASE  = 'tr-case';

    public static $events = [
        Events::SUITE_AFTER     => 'afterSuite',

        Events::TEST_SUCCESS    => 'success',
        Events::TEST_SKIPPED    => 'skipped',
        Events::TEST_INCOMPLETE => 'incomplete',
        Events::TEST_FAIL       => 'failed',
        Events::TEST_ERROR      => 'errored',
    ];

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
     * @var array
     */
    protected $results = [];

    public function _initialize()
    {
        $conn = new Connection();
        $conn->setUser($this->config['user']);
        $conn->setApiKey($this->config['apikey']);
        $conn->connect('https://bookit.testrail.com');

        $project = $conn->execute('get_project/'. $this->config['project']);

        if ($project->is_completed) {
            throw new ExtensionException(
                $this,
                'TestRail project id passed in the config has been completed and cannot be modified'
            );
        }

        // TODO: procedural generation of test plan names (template?  provider class?)
        $plan = $conn->execute('add_plan/'. $project->id, 'POST', [
            'name' => date('Y-m-d H:i:s'),
        ]);

        $this->conn = $conn;
        $this->project = $project->id;
        $this->plan = $plan->id;
    }

    public function afterSuite(SuiteEvent $event)
    {
        foreach ($this->results as $suiteId=>$results) {
            $caseIds = array_reduce($results, function ($carry, $val) {
                $carry[] = $val['case_id'];
                return $carry;
            },[]);

            $suiteDetails = $this->conn->execute('/get_suite/'. $suiteId);

            $entry = $this->conn->execute('/add_plan_entry/'. $this->plan, 'POST', [
                'suite_id' => $suiteId,
                'name' => $event->getSuite()->getName(). ' : '. $suiteDetails->name,
                'case_ids' => $caseIds,
                'include_all' => false,
            ]);

            $run = $entry->runs[0];
            $this->conn->execute('/add_results_for_cases/'. $run->id, 'POST', [
                'results' => $results,
            ]);
        }
    }

    public function success(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Test) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult($suite, $case, 1);
    }

    public function skipped(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Test) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult($suite, $case, 11);
    }

    public function incomplete(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Test) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult($suite, $case, 12);
    }

    public function failed(FailEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Test) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult($suite, $case, 5);
    }

    public function errored(FailEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Test) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult($suite, $case, 5);
    }

    /**
     * @param int $suite TestRail Suite ID
     * @param int $case TestRail Case ID
     * @param int $status TestRail Status ID
     * @param array $other Array of other elements to add to the result (comments, elapsed, etc)
     */
    public function handleResult($suite, $case, $status)
    {
        codecept_debug($suite);
        codecept_debug($case);
        codecept_debug($status);

        if ($suite && $case) {
            $this->results[$suite][] = [
                'case_id' => $case,
                'status_id' => $status,
            ];
        }
    }

    /**
     * @param TestInterface $test
     *
     * @return int|null
     */
    public function getSuiteForTest(TestInterface $test)
    {
        if (!$test instanceof Cest) {
            return null;
        }

        $suite = Annotation::forMethod($test->getTestClass(), $test->getTestMethod())->fetch($this::ANNOTATION_SUITE);
        if (!$suite) {
            $suite = Annotation::forClass($test->getTestClass())->fetch($this::ANNOTATION_SUITE);
            if (!$suite) {
                return null;
            }
        }

        return $suite;
    }

    /**
     * @param TestInterface $test
     *
     * @return int|null
     */
    public function getCaseForTest(TestInterface $test)
    {
        if (!$test instanceof Cest) {
            return null;
        }

        $case = Annotation::forMethod($test->getTestClass(), $test->getTestMethod())->fetch($this::ANNOTATION_CASE);
        if (!$case) {
            $case = Annotation::forClass($test->getTestClass())->fetch($this::ANNOTATION_CASE);
            if (!$case) {
                return null;
            }
        }

        return $case;
    }
}
