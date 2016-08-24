<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Project;
use BookIt\Codeception\TestRail\Model\Suite;

class GetSuites implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        list($project) = func_get_args();

        if ($project instanceof Project) {
            $project = $project->getId();
        }

        if (!is_int($project)) {
            throw new \InvalidArgumentException('Passed argument must be either a project id or a Project object');
        }

        $res = $this->getConnection()->execute('get_suites/'. $project);

        $suites = [];
        foreach ($res as $raw) {
            $suite = new Suite();
            $suite->setId($raw->id);
            $suite->setName($raw->name);
            $suite->setCases($this->getConnection()->getTestCases($project, $suite));
            $suites[] = $suite;
        }

        return $suites;
    }
}
