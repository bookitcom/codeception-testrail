<?php
namespace BookIt\Codeception\TestRail;


use Codeception\Event\FailEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Extension;
use Codeception\Test\Cest;

class TestRail extends Extension
{
    static $events = [
        'suite.before'    => 'startSuite',
        'test.before'     => 'startTest',
        'test.fail'       => 'logTestFail',
        'test.error'      => 'logTestFail',
        'test.incomplete' => 'logTestFail',
        'test.skipped'    => 'logTestFail',
        'test.success'    => 'logTestSuccess',
        'suite.after'     => 'finishSuite',
    ];

    public function startSuite(SuiteEvent $event)
    {
        codecept_debug('suite.before');
    }

    public function startTest(TestEvent $event)
    {
        codecept_debug('test.before');
    }

    public function logTestFail(FailEvent $event, $type)
    {
        codecept_debug($type);
    }

    public function logTestSuccess(TestEvent $event)
    {
        $test = $event->getTest();

        switch (get_class($test)) {
            case Cest::class: {
                $metadata = $test->getMetadata();
                codecept_debug($metadata->());
            } break;
            case \PHPUnit_Framework_TestCase::class: {
                codecept_debug(get_class($test->getMetadata()));
            } break;
        }

        codecept_debug('test.success');
    }

    public function finishSuite(SuiteEvent $event)
    {
        codecept_debug('suite.after');
    }
}
