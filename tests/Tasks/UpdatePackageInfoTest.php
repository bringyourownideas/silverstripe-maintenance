<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use PHPUnit_Framework_TestCase;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SupportedModules\MetaData;
use BringYourOwnIdeas\UpdateChecker\Extensions\CheckComposerUpdatesExtension;
use SilverStripe\Core\Injector\Injector;

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

    protected function setUp(): void
    {
        parent::setUp();
        // Remove extension that will connect to github
        // Extension is from bringyourownideas/silverstripe-composer-update-checker
        if (UpdatePackageInfoTask::has_extension(CheckComposerUpdatesExtension::class)) {
            UpdatePackageInfoTask::remove_extension(CheckComposerUpdatesExtension::class);
        }
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
        // Get the latest supported framework version from the MetaData service
        $data = MetaData::getMetaDataForRepository('silverstripe/silverstripe-framework')['majorVersionMapping'];
        $keys = array_keys($data);
        $key = $keys[count($keys) - 1];
        $latestFrameworkMajor = $data[$key][0];

        // Mock the VersionProvider service to return the latest framework version
        Injector::inst()->registerService(new class($latestFrameworkMajor) extends VersionProvider {
            private $latestFrameworkMajor;
            public function __construct($latestFrameworkMajor)
            {
                $this->latestFrameworkMajor = $latestFrameworkMajor;
            }
            public function getModuleVersion(string $module): string
            {
                if ($module === 'silverstripe/framework') {
                    return $this->latestFrameworkMajor . '.0.0';
                }
                return parent::getVersion($module);
            }
        }, VersionProvider::class);

        // Create a task and mock the ComposerLoader to return a specific composer.lock
        $task = UpdatePackageInfoTask::create();
        $task->setComposerLoader(new class($latestFrameworkMajor) extends ComposerLoader {
            private $latestFrameworkMajor;
            public function __construct($latestFrameworkMajor)
            {
                $this->latestFrameworkMajor = $latestFrameworkMajor;
            }
            public function getLock()
            {
                return json_decode(
                    <<<LOCK
                    {
                        "packages": [
                        {
                            "name": "silverstripe/framework",
                            "description": "A faux package from a mocked composer.lock for testing purposes",
                            "version": "{$this->latestFrameworkMajor}.0.0"
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
                );
            }
        });

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
