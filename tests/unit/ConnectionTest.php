<?php
namespace Bookit\Codeception\TestRail\Tests\Unit;

use BookIt\Codeception\TestRail\Connection;
use BookIt\Codeception\TestRail\Exception\CallException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected $target;

    protected function setUp()
    {
        $this->target = new Connection();
    }

    protected function tearDown()
    {
        unset($this->target);
    }

    // tests
    public function testSetUser()
    {
        $target = $this->target;
        $username = 'mark.randles';

        $target->setUser($username);
        $this->assertAttributeEquals([$target::AUTH_USER => $username], 'auth', $target);
    }

    public function testSetApiKey()
    {
        $target = $this->target;
        $apikey = 'thequickbrownfoxjumpsoverthelazydog';

        $target->setApiKey($apikey);
        $this->assertAttributeEquals([$target::AUTH_APIKEY => $apikey], 'auth', $target);
    }

    public function testConnectSetClient()
    {
        $target = $this->target;

        $target->connect('https://example.com');
        $this->assertAttributeInstanceOf(Client::class, 'client', $target);
    }

    public function testExecuteGet()
    {
        $target   = $this->target;
        $client   = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->setMethods(['request'])->getMock();
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['getBody'])->getMock();
        $body     = $this->getMockBuilder(StreamInterface::class)->setMethods(['getContents'])->getMockForAbstractClass();

        $username = 'mark.randles';
        $apikey   = 'aquickbrownfoxjumpsoverthelazydog';
        $fragment = '/some_uri_fragment';
        $payload  = '{"some":"json","to":"decode"}';

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($payload);
        /** @var StreamInterface $body */

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var Response $response */

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('index.php?/api/v2'.$fragment),
                $this->equalTo([
                    'auth' => [
                        $target::AUTH_USER => $username,
                        $target::AUTH_APIKEY => $apikey,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ])
            )
            ->willReturn($response);
        /** @var Client $client */

        $target->setUser($username);
        $target->setApiKey($apikey);
        $target->setClient($client);

        $result = $target->execute($fragment);

        $this->assertInstanceOf('\stdClass', $result);
    }

    public function testExecutePost()
    {
        $target   = $this->target;
        $client   = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->setMethods(['request'])->getMock();
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['getBody'])->getMock();
        $body     = $this->getMockBuilder(StreamInterface::class)->setMethods(['getContents'])->getMockForAbstractClass();

        $username = 'mark.randles';
        $apikey   = 'aquickbrownfoxjumpsoverthelazydog';
        $fragment = '/some_uri_fragment';
        $payload  = '{"some":"json","to":"decode"}';
        $post     = ['some' => 'value'];

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($payload);
        /** @var StreamInterface $body */

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var Response $response */

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('index.php?/api/v2'.$fragment),
                $this->equalTo([
                    'auth' => [
                        $target::AUTH_USER => $username,
                        $target::AUTH_APIKEY => $apikey,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $post,
                ])
            )
            ->willReturn($response);
        /** @var Client $client */

        $target->setUser($username);
        $target->setApiKey($apikey);
        $target->setClient($client);

        $result = $target->execute($fragment, 'POST', $post);

        $this->assertInstanceOf('\stdClass', $result);
    }

    public function testExecuteGetWithPayload()
    {
        $target   = $this->target;
        $client   = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->setMethods(['request'])->getMock();
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['getBody'])->getMock();
        $body     = $this->getMockBuilder(StreamInterface::class)->setMethods(['getContents'])->getMockForAbstractClass();

        $username = 'mark.randles';
        $apikey   = 'aquickbrownfoxjumpsoverthelazydog';
        $fragment = '/some_uri_fragment';
        $payload  = '{"some":"json","to":"decode"}';
        $post     = ['some' => 'value'];

        $body
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($payload);
        /** @var StreamInterface $body */

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var Response $response */

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('index.php?/api/v2'.$fragment),
                $this->equalTo([
                    'auth' => [
                        $target::AUTH_USER => $username,
                        $target::AUTH_APIKEY => $apikey,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ])
            )
            ->willReturn($response);
        /** @var Client $client */

        $target->setUser($username);
        $target->setApiKey($apikey);
        $target->setClient($client);

        $result = $target->execute($fragment, 'GET', $post);

        $this->assertInstanceOf('\stdClass', $result);
    }

}
