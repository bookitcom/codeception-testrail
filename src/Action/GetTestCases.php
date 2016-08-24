<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Project;
use BookIt\Codeception\TestRail\Model\Suite;
use BookIt\Codeception\TestRail\Model\TestCase;

class GetTestCases implements ActionInterface
{
    use BasicActionTrait;

    public function __invoke()
    {
        list($project, $suite) = $this->processArgs(func_get_args());

        $uri = 'get_cases/'. $project;

        if (!empty($suite)) {
            $uri .= '&suite_id='. $suite;
        }

        $res = $this->getConnection()->execute($uri);

        $cases = [];
        foreach ($res as $raw) {
            $case = new TestCase();
            $case->setId($raw->id);
            $case->setTitle($raw->title);
            $cases[] = $case;
        }

        return $cases;
    }

    protected function processArgs(array $args)
    {
        list($project, $suite) = $args;

        if ($project instanceof Project) {
            $project = $project->getId();
        }

        if (!is_int($project)) {
            throw new \InvalidArgumentException('Project must be either an integer or a Project object');
        }

        if (isset($suite)) {
            if ($suite instanceof Suite) {
                $suite = $suite->getId();
            }

            if (!is_int($suite)) {
                throw new \InvalidArgumentException('Suite must be either an integer or a Suite object');
            }
        } else {
            $suite = null;
        }

        return [$project, $suite];
    }

}
