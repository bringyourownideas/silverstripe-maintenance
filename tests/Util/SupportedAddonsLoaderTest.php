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
use Zend_Cache_Core;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class SupportedAddonsLoaderTest extends SapphireTest
{
    public function testNon200ErrorCodesAreHandled()
    {
        $loader = $this->getSupportedAddonsLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(404)));

        $this->setExpectedException(
            RuntimeException::class,
            'Could not obtain information about supported addons. Error code 404'
        );
        $loader->getAddonNames();
    }

    public function testNonJsonResponsesAreHandled()
    {
        $loader = $this->getSupportedAddonsLoader();
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
        $loader = $this->getSupportedAddonsLoader();
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

        $cacheMock = $this->getMockBuilder(Zend_Cache_Core::class)
            ->setMethods(['load', 'save'])
            ->getMock();

        $cacheMock->expects($this->once())->method('load')->will($this->returnValue(false));
        $cacheMock->expects($this->once())
            ->method('save')
            ->with(json_encode($fakeAddons), $this->anything(), [], 5000, $this->anything())
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

        $cacheMock = $this->getMockBuilder(Zend_Cache_Core::class)
            ->setMethods(['load', 'save'])
            ->getMock();

        $cacheMock->expects($this->once())->method('load')->will($this->returnValue(json_encode($fakeAddons)));
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
            $cacheMock = $this->getMockBuilder(Zend_Cache_Core::class)
                ->setMethods(['load', 'save'])
                ->getMock();
            $cacheMock->expects($this->any())->method('load')->will($this->returnValue(false));
            $cacheMock->expects($this->any())->method('save')->will($this->returnValue(true));
        }

        $loader = new SupportedAddonsLoader;
        $loader->setCache($cacheMock);

        return $loader;
    }
}
