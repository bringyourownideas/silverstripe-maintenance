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
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        // Mock the version provider to return a known version because VersionProvider
        // will normally read the projects composer.lock file to get the version of framework
        // which is often a forked version of silverstripe/framework
        // This does need to match a supported major version in silverstripe/supported-modules
        // repositories.json
        $mockVersionProvider = new class extends VersionProvider {
            public function getModuleVersion(string $module): string
            {
                return '6.0.0';
            }
        };
        Injector::inst()->registerService(new $mockVersionProvider(), VersionProvider::class);
        $composerLoader = $this->getMockBuilder(ComposerLoader::class)
            ->onlyMethods(['getLock'])->getMock();
        $composerLoader->expects($this->any())->method('getLock')->willReturn(json_decode(<<<LOCK
{
    "packages": [
        {
            "name": "silverstripe/framework",
            "description": "A faux package from a mocked composer.lock for testing purposes",
            "version": "6.0.0"
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
        ));

        $task = UpdatePackageInfoTask::create();
        $task->setComposerLoader($composerLoader);
        $output = PolyOutput::create(PolyOutput::FORMAT_ANSI);
        $output->setWrappedOutput(new BufferedOutput());
        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $task->run($input, $output);

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
