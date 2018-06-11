<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use BringYourOwnIdeas\Maintenance\Util\ApiLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Simple\NullCache;

class ApiLoaderTest extends SapphireTest
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about module. Error code 404
     */
    public function testNon200ErrorCodesAreHandled()
    {
        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(404)));

        $loader->doRequest('foo', function () {
            // noop
        });
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about module. Response is not JSON
     */
    public function testNonJsonResponsesAreHandled()
    {
        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        )));

        $loader->doRequest('foo', function () {
            // noop
        });
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about module. Response returned unsuccessfully
     */
    public function testUnsuccessfulResponsesAreHandled()
    {
        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => false])
        )));

        $loader->doRequest('foo', function () {
            // noop
        });
    }

    /**
     * Note: contains some logic from SupportedAddonsLoader for context
     *
     * @group integration
     */
    public function testAddonsAreParsedAndReturnedCorrectly()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => true, 'addons' => $fakeAddons])
        )));

        $addons = $loader->doRequest('foo', function ($responseBody) {
            return $responseBody['addons'];
        });

        $this->assertSame($fakeAddons, $addons);
    }

    /**
     * Note: contains some logic from SupportedAddonsLoader for context
     *
     * @group integration
     */
    public function testCacheControlSettingsAreRespected()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $cacheMock = $this->getMockBuilder(NullCache::class)
            ->setMethods(['get', 'set'])
            ->getMock();

        $cacheMock->expects($this->once())->method('get')->will($this->returnValue(false));
        $cacheMock->expects($this->once())
            ->method('set')
            ->with($this->anything(), json_encode($fakeAddons), 5000)
            ->will($this->returnValue(true));

        $loader = $this->getLoader($cacheMock);
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json', 'Cache-Control' => 'max-age=5000'],
            json_encode(['success' => true, 'addons' => $fakeAddons])
        )));

        $loader->doRequest('foo', function ($responseBody) {
            return $responseBody['addons'];
        });
    }

    public function testCachedAddonsAreUsedWhenAvailable()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $cacheMock = $this->getMockBuilder(NullCache::class)
            ->setMethods(['get', 'set'])
            ->getMock();

        $cacheMock->expects($this->once())->method('get')->will($this->returnValue(json_encode($fakeAddons)));
        $loader = $this->getLoader($cacheMock);

        $mockClient = $this->getMockBuilder(Client::class)->setMethods(['send'])->getMock();
        $mockClient->expects($this->never())->method('send');
        $loader->setGuzzleClient($mockClient);

        $addons = $loader->doRequest('foo', function () {
            // noop
        });

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

    /**
     * @param bool $cacheMock
     * @return ApiLoader
     */
    protected function getLoader($cacheMock = false)
    {
        if (!$cacheMock) {
            $cacheMock = $this->getMockBuilder(NullCache::class)
                ->setMethods(['get', 'set'])
                ->getMock();

            $cacheMock->expects($this->any())->method('get')->will($this->returnValue(false));
            $cacheMock->expects($this->any())->method('set')->will($this->returnValue(true));
        }

        $loader = $this->getMockBuilder(ApiLoader::class)
            ->getMockForAbstractClass();

        $loader->setCache($cacheMock);

        return $loader;
    }
}
