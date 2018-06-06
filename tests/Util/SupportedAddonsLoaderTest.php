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

    protected function setUp()
    {
        parent::setUp();

        $this->loader = $this->getMockBuilder(SupportedAddonsLoader::class)
            ->setMethods(['doRequest'])
            ->getMock();
    }

    public function testCallsSupportedAddonsEndpoint()
    {
        $this->loader->expects($this->once())
            ->method('doRequest')
            ->with('addons.silverstripe.org/api/supported-addons', function () {
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
            'foo' => 'bar',
            'addons' => 'baz',
        ];

        $this->assertSame('baz', $result($mockResponse));
    }

    public function testValueOfDoRequestIsReturned()
    {
        $this->loader->expects($this->once())
            ->method('doRequest')
            ->willReturn('hello world');

        $this->assertSame('hello world', $this->loader->getAddonNames());
    }
}
