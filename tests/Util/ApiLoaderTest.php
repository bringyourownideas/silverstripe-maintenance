<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use RuntimeException;
use BringYourOwnIdeas\Maintenance\Util\ApiLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SilverStripe\Dev\SapphireTest;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiLoaderTest extends SapphireTest
{
    public function testNon200ErrorCodesAreHandled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not obtain information about module. Error code 404');
        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(404)));

        $loader->doRequest('foo', function () {
            // noop
        });
    }

    private function getFakeJson(): array
    {
        return [
            [
                'composer' => 'foo/bar',
            ],
            [
                'composer' => 'bin/baz',
            ],
        ];
    }

    /**
     * Note: contains some logic from SupportedAddonsLoader for context
     */
    public function testAddonsAreParsedAndReturnedCorrectly()
    {
        $loader = $this->getLoader();
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($this->getFakeJson())
        )));

        $addons = $loader->doRequest('foo', function ($responseJson) {
            return array_map(fn(array $item) => $item['composer'], $responseJson);
        });

        $this->assertSame(['foo/bar', 'bin/baz'], $addons);
    }

    /**
     * Note: contains some logic from SupportedAddonsLoader for context
     */
    public function testCacheControlSettingsAreRespected()
    {
        $cacheMock = $this->getMockCacheInterface();

        $cacheMock->expects($this->once())->method('get')->willReturn(false);
        $cacheMock->expects($this->once())
            ->method('set')
            ->with($this->anything(), '["foo\/bar","bin\/baz"]', 5000)
            ->willReturn(true);

        $loader = $this->getLoader($cacheMock);
        $loader->setGuzzleClient($this->getMockClient(new Response(
            200,
            ['Content-Type' => 'application/json', 'Cache-Control' => 'max-age=5000'],
            json_encode($this->getFakeJson())
        )));

        $loader->doRequest('foo', function ($responseJson) {
            return array_map(fn(array $item) => $item['composer'], $responseJson);
        });
    }

    public function testCachedAddonsAreUsedWhenAvailable()
    {
        $cacheMock = $this->getMockCacheInterface();

        $cacheMock->expects($this->once())->method('get')->willReturn(json_encode($this->getFakeJson()));
        $loader = $this->getLoader($cacheMock);

        $mockClient = $this->getMockBuilder(Client::class)->onlyMethods(['send'])->getMock();
        $mockClient->expects($this->never())->method('send');
        $loader->setGuzzleClient($mockClient);

        $addons = $loader->doRequest('foo', function () {
            // noop
        });

        $this->assertSame($this->getFakeJson(), $addons);
    }

    #[DataProvider('provideParseResponseContentsEmpty')]
    public function testParseResponseContentsEmpty(string $contents)
    {
        // ApiLoader is an abstract class
        $inst = new class extends ApiLoader {
            protected function getCacheKey()
            {
                return 'abc';
            }
        };
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FAILURE_MESSAGE. Response was empty');
        $refMethod = new ReflectionMethod(ApiLoader::class, 'parseResponseContents');
        $refMethod->setAccessible(true);
        $failureMessage = 'FAILURE_MESSAGE. ';
        $refMethod->invoke($inst, $contents, $failureMessage);
        $inst->parseResponseContents($contents, $failureMessage);
    }

    public static function provideParseResponseContentsEmpty(): array
    {
        return [
            [
                '[]'
            ],
            [
                ''
            ]
        ];
    }

    public function testParseResponseContentsInvalid()
    {
        // ApiLoader is an abstract class
        $inst = new class extends ApiLoader {
            protected function getCacheKey()
            {
                return 'abc';
            }
        };
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FAILURE_MESSAGE. Syntax error');
        $refMethod = new ReflectionMethod(ApiLoader::class, 'parseResponseContents');
        $refMethod->setAccessible(true);
        $contents = '[ malformed }';
        $failureMessage = 'FAILURE_MESSAGE. ';
        $refMethod->invoke($inst, $contents, $failureMessage);
        $inst->parseResponseContents($contents, $failureMessage);
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
            $cacheMock = $this->getMockCacheInterface();

            $cacheMock->expects($this->any())->method('get')->willReturn(false);
            $cacheMock->expects($this->any())->method('set')->willReturn(true);
        }

        $loader = new class extends ApiLoader {
            protected function getCacheKey()
            {
                return 'cacheKey';
            }
        };

        $loader->setCache($cacheMock);

        return $loader;
    }

    protected function getMockCacheInterface()
    {
        $methods = ['get', 'set', 'has', 'delete', 'getMultiple', 'setMultiple', 'clear', 'deleteMultiple'];
        $mock = $this->getMockBuilder(CacheInterface::class)
            ->onlyMethods($methods)
            ->getMock();

        return $mock;
    }
}
