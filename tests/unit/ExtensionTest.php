<?php
namespace BookIt\Codeception\TestRail\Tests\Unit;

use BookIt\Codeception\TestRail\Connection;
use BookIt\Codeception\TestRail\Extension;
use Codeception\Event\FailEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Suite;
use Codeception\Test\Cept;
use Codeception\Test\Cest;
use Exception;

class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function getExtension($methods=null)
    {
        return $this
            ->getMockBuilder(Extension::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param $expected
     * @param $input
     *
     * @dataProvider providerFormatTime
     */
    public function testFormatTime($expected, $input)
    {
        /** @var Extension $target */
        $target = $this->getExtension();

        $this->assertEquals($expected, $target->formatTime($input));
    }

    public function testGetConnection()
    {
        /** @var Extension $target */
        $target = $this->getExtension();

        $target->injectConfig([
            'user' => 'a.user@example.com',
            'apikey' => 'randomstring',
        ]);

        $this->assertAttributeEmpty('conn', $target);
        $conn = $target->getConnection();
        $this->assertAttributeSame($conn, 'conn', $target);
    }

    public function testHandleResult()
    {
        $target = $this->getExtension(['formatTime']);

        $suiteId = 314;
        $caseId = 424242;
        $statusId = 1;
        $optional = [
            'comment' => 'some comment here',
            'elapsed' => 1.24,
        ];

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with(
                $this->equalTo($optional['elapsed'])
            )
            ->willReturn('formatted time');
        /** @var Extension $target */

        $target->handleResult($suiteId, $caseId, $statusId, $optional);

        $this->assertAttributeCount(1, 'results', $target);
        $this->assertAttributeEquals(
            [
                $suiteId =>
                [
                    [
                    'case_id' => $caseId,
                    'status_id' => $statusId,
                    'comment' => $optional['comment'],
                    'elapsed' => 'formatted time',
                    ]
                ]
            ],
            'results',
            $target
        );
    }

    public function testHandleResultNoSuite()
    {
        /** @var Extension $target */
        $target = $this->getExtension();

        $suiteId = null;
        $caseId = 424242;
        $statusId = 1;

        $target->handleResult($suiteId, $caseId, $statusId);

        $this->assertAttributeCount(0, 'results', $target);
    }

    public function testHandleResultNoCase()
    {
        /** @var Extension $target */
        $target = $this->getExtension();

        $suiteId = 314;
        $caseId = null;
        $statusId = 1;

        $target->handleResult($suiteId, $caseId, $statusId);

        $this->assertAttributeCount(0, 'results', $target);
    }

    public function testSuccess()
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);
        $test = $this->getMockBuilder(Cest::class)->disableOriginalConstructor()->getMock(); /** @var Cest $test */
        $event = new TestEvent($test, 2.71);

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with($this->equalTo(2.71))
            ->willReturn('3s');
        $target
            ->expects($this->once())
            ->method('getCaseForTest')
            ->with($this->identicalTo($test))
            ->willReturn(424242);
        $target
            ->expects($this->once())
            ->method('getSuiteForTest')
            ->with($this->identicalTo($test))
            ->willReturn(314);
        /** @var Extension $target */

        $target->success($event);

        $this->assertAttributeCount(1, 'results', $target);
        $results = $target->getResults();

        $this->assertArrayHasKey(314, $results);
        $this->assertCount(1, $results[314]);
        $this->assertArrayHasKey('case_id', $results[314][0]);
        $this->assertArrayHasKey('status_id', $results[314][0]);
        $this->assertArrayHasKey('elapsed', $results[314][0]);

        $this->assertEquals(424242, $results[314][0]['case_id']);
        $this->assertEquals($target::TESTRAIL_STATUS_SUCCESS, $results[314][0]['status_id']);
        $this->assertEquals('3s', $results[314][0]['elapsed']);
    }

    public function testFailed()
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);
        $test = $this->getMockBuilder(Cest::class)->disableOriginalConstructor()->getMock(); /** @var Cest $test */
        $event = new FailEvent($test, 2.71, new Exception());

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with($this->equalTo(2.71))
            ->willReturn('3s');
        $target
            ->expects($this->once())
            ->method('getCaseForTest')
            ->with($this->identicalTo($test))
            ->willReturn(424242);
        $target
            ->expects($this->once())
            ->method('getSuiteForTest')
            ->with($this->identicalTo($test))
            ->willReturn(314);
        /** @var Extension $target */

        $target->failed($event);

        $this->assertAttributeCount(1, 'results', $target);
        $results = $target->getResults();

        $this->assertArrayHasKey(314, $results);
        $this->assertCount(1, $results[314]);
        $this->assertArrayHasKey('case_id', $results[314][0]);
        $this->assertArrayHasKey('status_id', $results[314][0]);
        $this->assertArrayHasKey('elapsed', $results[314][0]);

        $this->assertEquals(424242, $results[314][0]['case_id']);
        $this->assertEquals($target::TESTRAIL_STATUS_FAILED, $results[314][0]['status_id']);
        $this->assertEquals('3s', $results[314][0]['elapsed']);
    }

    public function testErrored()
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);
        $test = $this->getMockBuilder(Cest::class)->disableOriginalConstructor()->getMock(); /** @var Cest $test */
        $event = new FailEvent($test, 2.71, new Exception());

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with($this->equalTo(2.71))
            ->willReturn('3s');
        $target
            ->expects($this->once())
            ->method('getCaseForTest')
            ->with($this->identicalTo($test))
            ->willReturn(424242);
        $target
            ->expects($this->once())
            ->method('getSuiteForTest')
            ->with($this->identicalTo($test))
            ->willReturn(314);
        /** @var Extension $target */

        $target->errored($event);

        $this->assertAttributeCount(1, 'results', $target);
        $results = $target->getResults();

        $this->assertArrayHasKey(314, $results);
        $this->assertCount(1, $results[314]);
        $this->assertArrayHasKey('case_id', $results[314][0]);
        $this->assertArrayHasKey('status_id', $results[314][0]);
        $this->assertArrayHasKey('elapsed', $results[314][0]);

        $this->assertEquals(424242, $results[314][0]['case_id']);
        $this->assertEquals($target::TESTRAIL_STATUS_FAILED, $results[314][0]['status_id']);
        $this->assertEquals('3s', $results[314][0]['elapsed']);
    }

    public function testSkipped()
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);
        $test = $this->getMockBuilder(Cest::class)->disableOriginalConstructor()->getMock(); /** @var Cest $test */
        $event = new TestEvent($test, 2.71);

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with($this->equalTo(2.71))
            ->willReturn('3s');
        $target
            ->expects($this->once())
            ->method('getCaseForTest')
            ->with($this->identicalTo($test))
            ->willReturn(424242);
        $target
            ->expects($this->once())
            ->method('getSuiteForTest')
            ->with($this->identicalTo($test))
            ->willReturn(314);
        /** @var Extension $target */

        $target->skipped($event);

        $this->assertAttributeCount(1, 'results', $target);
        $results = $target->getResults();

        $this->assertArrayHasKey(314, $results);
        $this->assertCount(1, $results[314]);
        $this->assertArrayHasKey('case_id', $results[314][0]);
        $this->assertArrayHasKey('status_id', $results[314][0]);
        $this->assertArrayHasKey('elapsed', $results[314][0]);

        $this->assertEquals(424242, $results[314][0]['case_id']);
        $this->assertEquals($target::TESTRAIL_STATUS_UNTESTED, $results[314][0]['status_id']);
        $this->assertEquals('3s', $results[314][0]['elapsed']);
    }

    public function testIncomplete()
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);
        $test = $this->getMockBuilder(Cest::class)->disableOriginalConstructor()->getMock(); /** @var Cest $test */
        $event = new TestEvent($test, 2.71);

        $target
            ->expects($this->once())
            ->method('formatTime')
            ->with($this->equalTo(2.71))
            ->willReturn('3s');
        $target
            ->expects($this->once())
            ->method('getCaseForTest')
            ->with($this->identicalTo($test))
            ->willReturn(424242);
        $target
            ->expects($this->once())
            ->method('getSuiteForTest')
            ->with($this->identicalTo($test))
            ->willReturn(314);
        /** @var Extension $target */

        $target->incomplete($event);

        $this->assertAttributeCount(1, 'results', $target);
        $results = $target->getResults();

        $this->assertArrayHasKey(314, $results);
        $this->assertCount(1, $results[314]);
        $this->assertArrayHasKey('case_id', $results[314][0]);
        $this->assertArrayHasKey('status_id', $results[314][0]);
        $this->assertArrayHasKey('elapsed', $results[314][0]);

        $this->assertEquals(424242, $results[314][0]['case_id']);
        $this->assertEquals($target::TESTRAIL_STATUS_SUCCESS, $results[314][0]['status_id']);
        $this->assertEquals('3s', $results[314][0]['elapsed']);
    }

    /**
     * @dataProvider providerActionHandlerInvalidTestType
     */
    public function testActionInvalidTestType($action, $event)
    {
        $target = $this->getExtension(['formatTime','getCaseForTest','getSuiteForTest']);

        $target
            ->expects($this->never())
            ->method('formatTime');
        $target
            ->expects($this->never())
            ->method('getCaseForTest');
        $target
            ->expects($this->never())
            ->method('getSuiteForTest');
        /** @var Extension $target */

        $target->{$action}($event);
    }

    public function testAfterSuite()
    {
        $target = $this->getExtension(['getResults','getConnection']);
        $suite = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->setMethods(['getName'])->getMock();
        $connection = $this->getMockBuilder(Connection::class)->setMethods(['execute'])->getMock();

        $expectedEntry = [
            'suite_id' => 314,
            'name' => 'Codeception : TestRail',
            'case_ids' => [424242, 424243, 424244],
            'include_all' => false,
        ];

        $connection
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->equalTo('/get_suite/314')
                ],
                [
                    $this->equalTo('/add_plan_entry/'),
                    $this->equalTo('POST'),
                    $this->equalTo($expectedEntry)
                ],
                [
                    $this->equalTo('/add_results_for_cases/'. 123456),
                    $this->equalTo('POST'),
                    $this->equalTo([
                        'results' => [
                            0 => [
                                'case_id' => 424242,
                                'status_id' => Extension::TESTRAIL_STATUS_SUCCESS,
                            ],
                            2 => [
                                'case_id' => 424244,
                                'status_id' => Extension::TESTRAIL_STATUS_FAILED,
                            ],
                        ],
                    ])
                ]
            )
            ->willReturnOnConsecutiveCalls(
                (object)['name' => 'TestRail'],
                (object)['runs' => [ (object)['id' => 123456]]],
                null
            );
        /** @var Connection $connection */

        $target
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        $target
            ->expects($this->once())
            ->method('getResults')
            ->willReturn([
                314 => [
                    [
                        'case_id' => 424242,
                        'status_id' => Extension::TESTRAIL_STATUS_SUCCESS,
                    ],
                    [
                        'case_id' => 424243,
                        'status_id' => Extension::TESTRAIL_STATUS_UNTESTED,
                    ],
                    [
                        'case_id' => 424244,
                        'status_id' => Extension::TESTRAIL_STATUS_FAILED,
                    ],
                ]
            ]);
        /** @var Extension $target */

        $suite
            ->expects($this->once())
            ->method('getName')
            ->willReturn('Codeception');
        /** @var Suite $suite */

        $event = new SuiteEvent($suite);
        $target->afterSuite($event);

    }

    public function testAfterSuiteDisabled()
    {
        $target = $this->getExtension(); /** @var Extension $target */
        $suite = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->setMethods(['getName'])->getMock();

        $suite
            ->expects($this->never())
            ->method('getName')
            ->willReturn('codeception.suite.name');
        /** @var Suite $suite */

        $event = new SuiteEvent($suite);

        $target->injectConfig([
            'enabled' => false,
        ]);

        $target->afterSuite($event);

    }

    public function testAfterSuiteEmptyResults()
    {
        $target = $this->getExtension(); /** @var Extension $target */
        $suite = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->setMethods(['getName'])->getMock();

        $suite
            ->expects($this->never())
            ->method('getName')
            ->willReturn('codeception.suite.name');
        /** @var Suite $suite */

        $event = new SuiteEvent($suite);

        $target->injectConfig([
            'enabled' => true,
        ]);

        $target->afterSuite($event);
    }

    public function testInitialize()
    {
        $target = $this->getExtension(['getConnection']);
        $connection = $this->getMockBuilder(Connection::class)->setMethods(['execute'])->getMock();

        $connection
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->equalTo('get_project/451'),
                ],
                [
                    $this->equalTo('add_plan/451'),
                    $this->equalTo('POST'),
                    $this->equalTo([
                        'name' => date('Y-m-d H:i:s'),
                    ]),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                (object)['id' => 451, 'is_completed' => false],
                (object)['id' => 271]
            );
        /** @var Connection $connection */

        $target
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        /** @var Extension $target */

        $statuses = [
            $target::STATUS_SUCCESS    => 100,
            $target::STATUS_SKIPPED    => 101,
            $target::STATUS_INCOMPLETE => 102,
            $target::STATUS_ERROR      => 103,
            $target::STATUS_FAILED     => 104,
        ];

        $target->injectConfig([
            'project' => 451,
            'status'  => $statuses,
        ]);

        $target->_initialize();

        $this->assertAttributeEquals($statuses, 'statuses', $target);
    }

    public function testInitializeDisabled()
    {
        $target = $this->getExtension(['getConnection']);

        $target
            ->expects($this->never())
            ->method('getConnection');
        /** @var Extension $target */

        $target->injectConfig([
            'enabled' => false,
        ]);

        $target->_initialize();
    }

    /**
     * @expectedException \Codeception\Exception\ExtensionException
     */
    public function testInitializeCompletedProject()
    {
        $target = $this->getExtension(['getConnection']);
        $connection = $this->getMockBuilder(Connection::class)->setMethods(['execute'])->getMock();

        $connection
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('get_project/451')
            )
            ->willReturn(
                (object)['id' => 451, 'is_completed' => true]
            );
        /** @var Connection $connection */

        $target
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        /** @var Extension $target */

        $target->injectConfig([
            'project' => 451,
        ]);

        $target->_initialize();
    }

    public function providerFormatTime()
    {
        return [
            [
                '0s',
                0.00001,
            ],
            [
                '1s',
                1.4,
            ],
            [
                '2s',
                1.5,
            ],
            [
                '1m 10s',
                70
            ],
            [
                '1h 1m 10s',
                3600 + 60 + 10,
            ],
            [
                '1h 10s',
                3610,
            ]
        ];
    }

    public function providerActionHandlerInvalidTestType()
    {
        $test = $this->getMockBuilder(Cept::class)->disableOriginalConstructor()->getMock(); /** @var Cept $test */
        return [
            [
                'success',
                new TestEvent($test),
            ],
            [
                'skipped',
                new TestEvent($test),
            ],
            [
                'incomplete',
                new TestEvent($test),
            ],
            [
                'failed',
                new FailEvent($test, 0.0, new Exception()),
            ],
            [
                'errored',
                new FailEvent($test, 0.0, new Exception()),
            ],
        ];
    }

}
