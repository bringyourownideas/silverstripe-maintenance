<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use Package;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use SapphireTest;
use UpdatePackageInfoTask;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class UpdatePackageInfoTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testGetPackageInfo()
    {
        $lockOutput = [(object) [
            "name" => "fake/package",
            "description" => "A faux package from a mocked composer.lock for testing purposes",
            "version" => "1.0.0",
        ]];

        $processor = new UpdatePackageInfoTask;
        $output = $processor->getPackageInfo($lockOutput);
        $this->assertInternalType('array', $output);
        $this->assertCount(1, $output);
        $this->assertContains([
            "Name" => "fake/package",
            "Description" => "A faux package from a mocked composer.lock for testing purposes",
            "Version" => "1.0.0"
        ], $output);
    }

    public function testGetSupportedPackagesEchosErrors()
    {
        $supportedAddonsLoader = $this->getMockBuilder(SupportedAddonsLoader::class)
            ->setMethods(['getAddonNames'])
            ->getMock();

        $supportedAddonsLoader->expects($this->once())
            ->method('getAddonNames')
            ->will($this->throwException(new RuntimeException('A test message')));

        $task = new UpdatePackageInfoTask;
        $task->setSupportedAddonsLoader($supportedAddonsLoader);

        ob_start();
        $task->getSupportedPackages();
        $output = ob_get_clean();

        $this->assertContains('A test message', $output);
    }

    public function testPackagesAreAddedCorrectly()
    {
        $task = new UpdatePackageInfoTask;

        $composerLoader = $this->getMockBuilder(ComposerLoader::class)
            ->setMethods(['getLock'])->getMock();
        $composerLoader->expects($this->any())->method('getLock')->will($this->returnValue(json_decode(<<<LOCK
{
    "packages": [
        {
            "name": "fake/supported-package",
            "description": "A faux package from a mocked composer.lock for testing purposes",
            "version": "1.0.0"
        },
        {
            "name": "fake/unsupported-package",
            "description": "A faux package from a mocked composer.lock for testing purposes",
            "version": "1.0.0"
        }
    ],
    "packages-dev": null
}
LOCK
        )));
        $task->setComposerLoader($composerLoader);

        $supportedAddonsLoader = $this->getMockBuilder(SupportedAddonsLoader::class)
            ->setMethods(['getAddonNames'])
            ->getMock();
        $supportedAddonsLoader->expects($this->once())
            ->method('getAddonNames')
            ->will($this->returnValue(['fake/supported-package']));
        $task->setSupportedAddonsLoader($supportedAddonsLoader);

        $task->run(null);

        $packages = Package::get();
        $this->assertCount(2, $packages);

        $package = $packages->find('Name', 'fake/supported-package');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals(1, $package->Supported);

        $package = $packages->find('Name', 'fake/unsupported-package');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals(0, $package->Supported);
    }
}
