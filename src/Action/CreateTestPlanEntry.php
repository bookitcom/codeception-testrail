<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Plan;
use BookIt\Codeception\TestRail\Model\PlanEntry;
use BookIt\Codeception\TestRail\Model\Project;
use BookIt\Codeception\TestRail\Model\Run;
use BookIt\Codeception\TestRail\Model\Suite;

class CreateTestPlanEntry implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        $this->processArgs(func_get_args(), $plan, $suite);

        $raw = $this->getConnection()->execute('add_plan_entry/'. $plan, 'POST', [
            'suite_id' => $suite,
            'include_all' => true,
        ]);

        $entry = new PlanEntry();
        $entry->setId($raw->id);

        $runs = [];
        foreach ($raw->runs as $rawRun) {
            $run = new Run();
            $run->setId($rawRun->id);
            $runs[] = $run;
        }
        $entry->setRuns($runs);

        return $entry;
    }

    protected function processArgs(array $args, &$plan, &$suite)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException('CreateTestPlanEntry requires at least two arguments');
        }

        $plan = $args[0] instanceof Plan ? $args[0]->getId() : $args[0];
        $suite = $args[1] instanceof Suite ? $args[1]->getId() : $args[1];

        if (!is_int($plan)) {
            throw new \InvalidArgumentException('CreateTestPlanEntry requires a Plan object or a plan id');
        }

        if (!is_int($suite)) {
            throw new \InvalidArgumentException('CreateTestPlanEntry requires a Suite object or a suite id');
        }
    }

}
