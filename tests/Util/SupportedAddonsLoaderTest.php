<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class SupportedAddonsLoaderTest extends SapphireTest
{
    public function testNon200ErrorCodesAreHandled()
    {
        $loader = new SupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(404)));

        $this->setExpectedException(
            RuntimeException::class,
            'Could not obtain information about supported addons. Error code 404'
        );
        $loader->getAddonNames();
    }

    public function testNonJsonResponsesAreHandled()
    {
        $loader = new SupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        )));

        $this->setExpectedException(
            RuntimeException::class,
            'Could not obtain information about supported addons. Response is not JSON'
        );
        $loader->getAddonNames();
    }

    public function testUnsuccessfulResponsesAreHandled()
    {
        $loader = new SupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => 'false'])
        )));

        $this->setExpectedException(
            RuntimeException::class,
            'Could not obtain information about supported addons. Response returned unsuccessfully'
        );
        $loader->getAddonNames();
    }


    public function testAddonsAreParsedAndReturnedCorrectly()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $loader = new SupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => true, 'addons' => $fakeAddons])
        )));

        $addons = $loader->getAddonNames();

        $this->assertSame($fakeAddons, $addons);
    }

    /**
     * @param Response $withResponse
     * @return Client
     */
    protected function getMockClient(Response $withResponse)
    {
        $mock = new MockHandler([
            $withResponse
        ]);

        $handler = HandlerStack::create($mock);
        return new Client(['handler' => $handler]);
    }
}
