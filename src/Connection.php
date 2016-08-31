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
        $this->setClient(
            new Client(
                [
                'base_uri' => $baseUri,
                ]
            )
        );
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
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

        return json_decode($response->getBody()->getContents());
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
