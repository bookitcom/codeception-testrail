<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Plan;
use BookIt\Codeception\TestRail\Model\Project;

class CreateTestPlan implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        $this->processArgs(func_get_args(), $project, $name);

        $raw = $this->getConnection()->execute('add_plan/'. $project, 'POST', [
            'name' => $name,
        ]);

        $plan = new Plan();
        $plan->setId($raw->id);
        $plan->setName($raw->name);

        return $plan;
    }

    protected function processArgs(array $args, &$project, &$name)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException('CreateTestPlan requires two arguments');
        }

        $project = $args[0] instanceof Project ? $args[0]->getId() : $args[0];
        $name = $args[1];

        if (!is_int($project)) {
            throw new \InvalidArgumentException('CreateTestPlan requires a Project object or a project id');
        }

        if (!is_string($name)) {
            throw new \InvalidArgumentException('CreateTestPlan requires a test plan name.');
        }
    }

}
