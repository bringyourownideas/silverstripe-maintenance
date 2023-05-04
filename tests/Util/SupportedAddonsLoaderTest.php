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
            ->setMethods(['doRequest'])
            ->getMock();
    }

    public function testCallsSupportedAddonsEndpoint()
    {
        $endpoint = 'https://raw.githubusercontent.com/silverstripe/supported-modules/5/modules.json';
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
            ->will($this->returnArgument(1));

        $result = $this->loader->getAddonNames();
        $mockResponse = [
            [
                'composer' => 'foo/bar'
            ],
            [
                'composer' => 'bin/baz'
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
