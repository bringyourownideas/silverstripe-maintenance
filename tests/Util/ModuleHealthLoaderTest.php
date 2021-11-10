<?php

use BringYourOwnIdeas\Maintenance\Util\ModuleHealthLoader;
use SilverStripe\Dev\SapphireTest;

class ModuleHealthLoaderTest extends SapphireTest
{
    /**
     * @var ModuleHealthLoader
     */
    protected $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->getMockBuilder(ModuleHealthLoader::class)
            ->setMethods(['doRequest'])
            ->getMock();
    }

    public function testModuleNamesAreInTheRequestUrl()
    {
        $this->loader->setModuleNames(['foo/bar', 'bar/baz']);

        $this->loader->expects($this->once())
            ->method('doRequest')
            ->with('addons.silverstripe.org/api/ratings?addons=foo/bar,bar/baz');

        $this->loader->getModuleHealthInfo();
    }
}
