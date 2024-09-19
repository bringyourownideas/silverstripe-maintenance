<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use SilverStripe\Dev\SapphireTest;

class SupportedAddonsLoaderTest extends SapphireTest
{
    /**
     * @var SupportedAddonsLoader
     */
    protected $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->getMockBuilder(SupportedAddonsLoader::class)
            ->onlyMethods(['doRequest'])
            ->getMock();
    }

    public function testCallsSupportedAddonsEndpoint()
    {
        $endpoint = 'https://raw.githubusercontent.com/silverstripe/supported-modules/main/repositories.json';
        $this->loader->expects($this->once())
            ->method('doRequest')
            ->with($endpoint, function () {
                // no-op
            });

        $this->loader->getAddonNames();
    }

    public function testCallbackReturnsAddonsFromBody()
    {
        $this->loader->expects($this->once())
            ->method('doRequest')
            ->with($this->isType('string'), $this->isType('callable'))
            ->willReturnArgument(1);

        $result = $this->loader->getAddonNames();
        $mockResponse = [
            'supportedModules' => [
                [
                    'github' => 'some/repo1',
                    'packagist' => 'foo/bar',
                    'majorVersionMapping' => [
                        4 => [4],
                        5 => [5],
                    ],
                ],
                [
                    'github' => 'some/repo2',
                    'packagist' => 'bin/baz',
                    'majorVersionMapping' => [
                        5 => [5, 6],
                    ],
                ],
                [
                    'github' => 'some/repo3',
                    'packagist' => 'bin/baz2',
                    'majorVersionMapping' => [
                        4 => [4],
                    ],
                ],
            ],
            'workflow' => [
                [
                    'github' => 'some/repo4',
                    'packagist' => 'bin/baz1',
                    'majorVersionMapping' => [
                        4 => [4],
                        5 => [5],
                    ],
                ],
            ],
            'tooling' => [
                [
                    'github' => 'some/repo5',
                    'packagist' => 'bin/baz2',
                    'majorVersionMapping' => [
                        5 => [5, 6],
                    ],
                ],
            ],
            'misc' => [
                [
                    'github' => 'some/repo6',
                    'packagist' => 'bin/baz3',
                    'majorVersionMapping' => [
                        4 => [4],
                    ],
                ],
            ],
        ];

        $this->assertSame(['foo/bar', 'bin/baz'], $result($mockResponse));
    }

    public function testValueOfDoRequestIsReturned()
    {
        $this->loader->expects($this->once())
            ->method('doRequest')
            ->willReturn('hello world');

        $this->assertSame('hello world', $this->loader->getAddonNames());
    }
}
