<?php
namespace BookIt\Codeception\TestRail\Action;


use BookIt\Codeception\TestRail\Model\Project;

class GetProject implements ActionInterface
{
    use BasicActionTrait;

    /**
     * @return Project
     */
    public function __invoke()
    {
        list($projectId) = func_get_args();
        if (empty($projectId)) {
            throw new \InvalidArgumentException('Missing project id in call to get_project');
        }

        $res = $this->getConnection()->execute('get_project/' . $projectId);
        
        $project = new Project();
        $project->setId($res->id);
        $project->setName($res->name);
        $project->setSuites($this->getConnection()->getSuites($project));

        return $project;
    }

}
