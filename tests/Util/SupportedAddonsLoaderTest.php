<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Simple\NullCache;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class SupportedAddonsLoaderTest extends SapphireTest
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about supported addons. Error code 404
     */
    public function testNon200ErrorCodesAreHandled()
    {
        $loader = $this->getSupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(404)));

        $loader->getAddonNames();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about supported addons. Response is not JSON
     */
    public function testNonJsonResponsesAreHandled()
    {
        $loader = $this->getSupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        )));

        $loader->getAddonNames();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not obtain information about supported addons. Response returned unsuccessfully
     */
    public function testUnsuccessfulResponsesAreHandled()
    {
        $loader = $this->getSupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => 'false'])
        )));

        $loader->getAddonNames();
    }


    public function testAddonsAreParsedAndReturnedCorrectly()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $loader = $this->getSupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['success' => true, 'addons' => $fakeAddons])
        )));

        $addons = $loader->getAddonNames();

        $this->assertSame($fakeAddons, $addons);
    }

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

        $loader = $this->getSupportedAddonsLoader($cacheMock);
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json', 'Cache-Control' => 'max-age=5000'],
            json_encode(['success' => true, 'addons' => $fakeAddons])
        )));

        $loader->getAddonNames();
    }

    public function testCachedAddonsAreUsedWhenAvailable()
    {
        $fakeAddons = ['foo/bar', 'bin/baz'];

        $cacheMock = $this->getMockBuilder(NullCache::class)
            ->setMethods(['get', 'set'])
            ->getMock();

        $cacheMock->expects($this->once())->method('get')->will($this->returnValue(json_encode($fakeAddons)));
        $loader = $this->getSupportedAddonsLoader($cacheMock);

        $mockClient = $this->getMockBuilder(Client::class)->setMethods(['send'])->getMock();
        $mockClient->expects($this->never())->method('send');
        $loader->setGuzzleClient($mockClient);

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

    protected function getSupportedAddonsLoader($cacheMock = false)
    {
        if (!$cacheMock) {
            $cacheMock = $this->getMockBuilder(NullCache::class)
                ->setMethods(['get', 'set'])
                ->getMock();
            $cacheMock->expects($this->any())->method('get')->will($this->returnValue(false));
            $cacheMock->expects($this->any())->method('set')->will($this->returnValue(true));
        }

        $loader = new SupportedAddonsLoader;
        $loader->setCache($cacheMock);

        return $loader;
    }
}
