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

    const STATUS_SUCCESS    = 'success';
    const STATUS_SKIPPED    = 'skipped';
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_FAILED     = 'failed';
    const STATUS_ERROR      = 'error';

    const TESTRAIL_STATUS_SUCCESS = 1;
    const TESTRAIL_STATUS_FAILED = 5;
    const TESTRAIL_STATUS_UNTESTED = 3;
    const TESTRAIL_STATUS_RETEST = 4;
    const TESTRAIL_STATUS_BLOCKED = 2;

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

    /**
     * @var array
     */
    protected $config = [ 'enabled' => true ];

    /**
     * @var array
     */
    protected $statuses = [
        self::STATUS_SUCCESS    => self::TESTRAIL_STATUS_SUCCESS,
        self::STATUS_SKIPPED    => self::TESTRAIL_STATUS_UNTESTED,
        self::STATUS_INCOMPLETE => self::TESTRAIL_STATUS_SUCCESS,
        self::STATUS_FAILED     => self::TESTRAIL_STATUS_FAILED,
        self::STATUS_ERROR      => self::TESTRAIL_STATUS_FAILED,
    ];

    public function _initialize()
    {
        // we only care to do these things if the extension is enabled
        if ($this->config['enabled']) {
            $conn = $this->getConnection();

            $project = $conn->execute('get_project/'. $this->config['project']);
            if ($project->is_completed) {
                throw new ExtensionException(
                    $this,
                    'TestRail project id passed in the config has been completed and cannot be modified'
                );
            }

            // TODO: procedural generation of test plan names (template?  provider class?)
            $plan = $conn->execute(
                'add_plan/'. $project->id,
                'POST',
                [
                'name' => date('Y-m-d H:i:s'),
                ]
            );

            $this->project = $project->id;
            $this->plan = $plan->id;
        }

        // merge the statuses from the config over the default ones
        if (array_key_exists('status', $this->config)) {
            $this->statuses = array_merge($this->statuses, $this->config['status']);
        }
    }

    public function afterSuite(SuiteEvent $event)
    {
        $recorded = $this->getResults();
        // skip action if we don't have results or the Extension is disabled
        if (empty($recorded) || !$this->config['enabled']) {
            return;
        }

        $conn = $this->getConnection();

        foreach ($recorded as $suiteId => $results) {
            $caseIds = array_reduce(
                $results,
                function ($carry, $val) {
                    $carry[] = $val['case_id'];
                    return $carry;
                },
                []
            );

            $suiteDetails = $conn->execute('/get_suite/'. $suiteId);

            $entry = $conn->execute(
                '/add_plan_entry/'. $this->plan,
                'POST',
                [
                'suite_id' => $suiteId,
                'name' => $event->getSuite()->getName(). ' : '. $suiteDetails->name,
                'case_ids' => $caseIds,
                'include_all' => false,
                ]
            );

            $results = array_filter(
                $results,
                function ($val) {
                    return $val['status_id'] != $this::TESTRAIL_STATUS_UNTESTED;
                }
            );

            $run = $entry->runs[0];
            $conn->execute(
                '/add_results_for_cases/'. $run->id,
                'POST',
                [
                'results' => $results,
                ]
            );
        }
    }

    public function success(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Cest) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult(
            $suite,
            $case,
            $this->statuses[$this::STATUS_SUCCESS],
            [
            'elapsed' => $event->getTime(),
            ]
        );
    }

    public function skipped(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Cest) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult(
            $suite,
            $case,
            $this->statuses[$this::STATUS_SKIPPED],
            [
            'elapsed' => $event->getTime(),
            ]
        );
    }

    public function incomplete(TestEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Cest) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult(
            $suite,
            $case,
            $this->statuses[$this::STATUS_INCOMPLETE],
            [
            'elapsed' => $event->getTime(),
            ]
        );
    }

    public function failed(FailEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Cest) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult(
            $suite,
            $case,
            $this->statuses[$this::STATUS_FAILED],
            [
            'elapsed' => $event->getTime(),
            ]
        );
    }

    public function errored(FailEvent $event)
    {
        $test = $event->getTest();

        if (!$test instanceof Cest) {
            return;
        }

        $suite = $this->getSuiteForTest($test);
        $case = $this->getCaseForTest($test);
        $this->handleResult(
            $suite,
            $case,
            $this->statuses[$this::STATUS_ERROR],
            [
            'elapsed' => $event->getTime(),
            ]
        );
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        if (!$this->conn) {
            $conn = new Connection();
            $conn->setUser($this->config['user']);
            $conn->setApiKey($this->config['apikey']);
            $conn->connect($this->config['url']);
            $this->conn = $conn;
        }
        return $this->conn;
    }

    /**
     * Inject an overlay config.  mostly useful for unit testing
     *
     * @param array $config
     */
    public function injectConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param int   $suite  TestRail Suite ID
     * @param int   $case   TestRail Case ID
     * @param int   $status TestRail Status ID
     * @param array $other  Array of other elements to add to the result (comments, elapsed, etc)
     */
    public function handleResult($suite, $case, $status, $optional = [])
    {
        if ($suite && $case) {
            $result = [
                'case_id' => $case,
                'status_id' => $status,
            ];

            if (!empty($optional)) {
                if (isset($optional['comment'])) {
                    $result['comment'] = $optional['comment'];
                }

                if (isset($optional['elapsed'])) {
                    $result['elapsed'] = $this->formatTime($optional['elapsed']);
                }
            }

            $this->results[$suite][] = $result;
        }
    }

    /**
     * @param TestInterface $test
     *
     * @return int|null
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
     */
    public function getCaseForTest(TestInterface $test)
    {
        if (!$test instanceof Cest) {
            return null;
        }

        return Annotation::forMethod($test->getTestClass(), $test->getTestMethod())->fetch($this::ANNOTATION_CASE);
    }

    /**
     * Formats a float seconds to a format that TestRail recognizes.  Will parse to hours, minutes, and seconds.
     *
     * @param float $time
     *
     * @return string
     */
    public function formatTime($time)
    {
        // TestRail doesn't support subsecond times
        if ($time < 1.0) {
            return '0s';
        }

        $formatted = '';
        $intTime = round($time);
        $intervals = [
            'h' => 3600,
            'm' => 60,
            's' => 1,
        ];

        foreach ($intervals as $suffix => $divisor) {
            if ($divisor > $intTime) {
                continue;
            }

            $amount = floor($intTime / $divisor);
            $intTime -= $amount * $divisor;
            $formatted .= $amount.$suffix.' ';
        }

        return trim($formatted);
    }
}
