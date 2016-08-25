<?php
namespace BookIt\Codeception\TestRail\Action;


class UpdatePlanEntry implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        $args = func_get_args();
        $this->getConnection()->execute('update_plan_entry/'. $args[0]. '/'. $args[1], 'POST', $args[2]);
    }

}
