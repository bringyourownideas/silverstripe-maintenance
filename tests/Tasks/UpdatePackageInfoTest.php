<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\Maintenance\Util\ModuleHealthLoader;
use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class UpdatePackageInfoTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function mockSupportedAddonsLoader()
    {
        $supportedAddonsLoader = $this->getMockBuilder(SupportedAddonsLoader::class)
            ->setMethods(['getAddonNames'])
            ->getMock();
        return $supportedAddonsLoader;
    }
    protected function mockModuleHealthLoader()
    {

        $moduleHealthLoader = $this->getMockBuilder(ModuleHealthLoader::class)
            ->setMethods(['getModuleHealthInfo'])
            ->getMock();

        return $moduleHealthLoader;
    }


    public function testGetPackageInfo()
    {
        $lockOutput = [(object) [
            "name" => "fake/package",
            "description" => "A faux package from a mocked composer.lock for testing purposes",
            "version" => "1.0.0",
        ]];

        /** @var UpdatePackageInfoTask $processor */
        $processor = UpdatePackageInfoTask::create();
        $output = $processor->getPackageInfo($lockOutput);
        $this->assertIsArray($output);
        $this->assertCount(1, $output);
        $this->assertContains([
            "Name" => "fake/package",
            "Description" => "A faux package from a mocked composer.lock for testing purposes",
            "Version" => "1.0.0"
        ], $output);
    }

    public function testGetSupportedPackagesEchosErrors()
    {
        $supportedAddonsLoader = $this->mockSupportedAddonsLoader();
        $moduleHealthLoader = $this->mockModuleHealthLoader();

        $supportedAddonsLoader->expects($this->once())
            ->method('getAddonNames')
            ->will($this->throwException(new RuntimeException('A test message')));

        /** @var UpdatePackageInfoTask $task */
        $task = UpdatePackageInfoTask::create();
        $task->setSupportedAddonsLoader($supportedAddonsLoader);
        $task->setModuleHealthLoader($moduleHealthLoader);

        ob_start();
        $task->getSupportedPackages();
        $output = ob_get_clean();

        $this->assertStringContainsString('A test message', $output);
    }

    public function testPackagesAreAddedCorrectly()
    {

        $task = UpdatePackageInfoTask::create();

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

        $supportedAddonsLoader = $this->mockSupportedAddonsLoader();
        $moduleHealthLoader = $this->mockModuleHealthLoader();

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
