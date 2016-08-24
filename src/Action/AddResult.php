<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Run;
use BookIt\Codeception\TestRail\Model\TestCase;

class AddResult implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        $this->processArgs(func_get_args(), $run, $case, $status);

        $this->getConnection()->execute('add_result_for_case/'. $run. '/'. $case, 'POST', [
            'status_id' => $status,
        ]);

    }

    protected function processArgs(array $args, &$run, &$case, &$status)
    {
        if (count($args) < 3) {
            throw new \InvalidArgumentException('AddResult requires at least three arguments');
        }

        $run = $args[0] instanceof Run ? $args[0]->getId() : $args[0];
        $case = $args[1] instanceof TestCase ? $args[1]->getId() : $args[1];
        $status = $args[2];

        if (!is_int($run)) {
            throw new \InvalidArgumentException('AddResult requires a Run object or a run id');
        }

        if (!is_int($case)) {
            throw new \InvalidArgumentException('AddResult requires a TestCase object or a test case id');
        }

        if (!is_int($status)) {
            throw new \InvalidArgumentException('AddResult requires a test status');
        }
    }
}
