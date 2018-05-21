<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use SapphireTest;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfo;

class UpdatePackageInfoTest extends SapphireTest
{
    public function testGetPackageInfo()
    {
        $loader = $this->createMock(ComposerLoader::class);
        $loader->method('getLock')->willReturn(json_decode(<<<LOCK
{
    "packages": [
        {
            "name": "fake/package",
            "description": "A faux package from a mocked composer.lock for testing purposes",
            "version": "1.0.0"
        }
    ]
}
LOCK
        ));
        $processor = new UpdatePackageInfo;
        $output = $processor->getPackageInfo($loader->getLock()->packages);
        $this->assertTrue(is_array($output));
        $this->assertCount(1, $output);
        $this->assertArrayHasKey('Name', $output[0]);
        $this->assertArrayHasKey('Description', $output[0]);
        $this->assertArrayHasKey('Version', $output[0]);
        $this->assertEquals('fake/package', $output[0]['Name']);
    }
}
