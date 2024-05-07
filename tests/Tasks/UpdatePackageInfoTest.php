<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SupportedModules\MetaData;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class UpdatePackageInfoTest extends SapphireTest
{
    protected $usesDatabase = true;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        MetaData::$isRunningUnitTests = true;
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

    public function testPackagesAreAddedCorrectly()
    {
        $task = UpdatePackageInfoTask::create();

        $frameworkVersion = VersionProvider::singleton()->getModuleVersion('silverstripe/framework');
        $composerLoader = $this->getMockBuilder(ComposerLoader::class)
            ->setMethods(['getLock'])->getMock();
        $composerLoader->expects($this->any())->method('getLock')->will($this->returnValue(json_decode(<<<LOCK
{
    "packages": [
        {
            "name": "silverstripe/framework",
            "description": "A faux package from a mocked composer.lock for testing purposes",
            "version": "$frameworkVersion"
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

        $task->run(null);

        $packages = Package::get();
        $this->assertCount(2, $packages);

        $package = $packages->find('Name', 'silverstripe/framework');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals(1, $package->Supported);

        $package = $packages->find('Name', 'fake/unsupported-package');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals(0, $package->Supported);
    }
}
