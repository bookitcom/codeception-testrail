<?php
namespace BookIt\Codeception\TestRail;

use BookIt\Codeception\TestRail\Action\ActionInterface;
use BookIt\Codeception\TestRail\Exception\ActionNotFound;
use BookIt\Codeception\TestRail\Exception\CallException;
use GuzzleHttp\Client;

/**
 * Class Connection
 *
 * @package BookIt\Codeception\TestRail
 *
 * @method Model\Project getProject(int $projectId)
 *
 * @method Model\Suite[] getSuites(int|Model\Project $forProject)
 *
 * @method Model\TestCase[] getTestCases(int|Model\Project $forProject, int|Model\Suite $forSuite)
 *
 * @method Model\Plan createTestPlan(int|Model\Project $forProject, string $withName)
 * @method Model\Plan getTestPlan(int $planId)
 *
 * @method Model\PlanEntry createTestPlanEntry(int|Model\Plan $forPlan, int|Model\Suite $forSuite)
 * @method void updateTestPlanEntry(int|Model\Plan $forPlan, int|Model\PlanEntry $forEntry, array $toUpdate)
 *
 * @method void addResult(int|Model\Run $forRun, int|Model\Suite $forCase, int $status)
 */
class Connection
{
    const AUTH_USER   = 0;
    const AUTH_APIKEY = 1;

    /**
     * @var string[]
     */
    protected $auth = [];

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ActionInterface[]
     */
    protected $actions;

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->auth[$this::AUTH_USER] = (string)$user;
    }

    /**
     * @param string $apikey
     */
    public function setApiKey($apikey)
    {
        $this->auth[$this::AUTH_APIKEY] = (string)$apikey;
    }

    /**
     * @param string $baseUri
     */
    public function connect($baseUri)
    {
        $this->client = new Client(
            [
            'base_uri' => $baseUri,
            ]
        );
    }

    /**
     * @param string $uri
     * @param string $verb
     * @param array  $payload
     */
    public function execute($uri, $verb = 'GET', array $payload = [])
    {
        $opts = [
            'auth' => $this->auth,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if (!empty($payload) && $verb == 'POST') {
            $opts['json'] = $payload;
        }

        // strip the leading slash since we're adding it back when we append the base
        if (strpos($uri, '/') === 0) {
            $uri = substr($uri, 1);
        }

        $response = $this->client->request($verb, 'index.php?/api/v2/'.$uri, $opts);

        switch ($response->getStatusCode()) {
            case 200:
                $ret = json_decode($response->getBody()->getContents());
                break;
            default:
                $error = json_decode($response->getBody()->getContents());
                throw new CallException(
                    sprintf(
                        'Call to remote API failed with status %d message %s',
                        $response->getStatusCode(),
                        $error->error
                    )
                );
        }

        return $ret;
    }

    /**
     * Magic caller which calls the relevant Action class
     *
     * @param string $name
     * @param array  $args
     */
    public function __call($name, array $args)
    {
        if (!isset($this->actions[$name])) {
            $action = __NAMESPACE__. '\\Action\\'. ucfirst($name);
            if (class_exists($action)) {
                $this->actions[$name] = new $action();
                $this->actions[$name]->setConnection($this);
            } else {
                throw new ActionNotFound(
                    sprintf(
                        '
                    The TestRail Connection couldn\'t locate an action class for "%s".',
                        $name
                    )
                );
            }
        }
        return call_user_func_array($this->actions[$name], $args);
    }
}
