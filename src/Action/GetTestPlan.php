<?php
namespace BookIt\Codeception\TestRail\Action;


class GetTestPlan implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        $this->processArgs(func_get_args(), $id);

        $res = $this->getConnection()->execute('get_plan/'. $id);

        // TODO: complete this action -- should result in loading a previously created test plan
    }

    protected function processArgs(array $args, &$id)
    {
        if (count($args) < 1) {
            throw new \InvalidArgumentException('This method requires at least one argument');
        }

        $id = (int)$args[0];
    }

}
