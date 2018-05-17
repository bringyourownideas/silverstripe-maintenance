<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use SapphireTest;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfo;

class UpdatePackageInfoTest extends SapphireTest
{
    public function testGetPackageInfo()
    {
        $loader = new ComposerLoader(__DIR__);
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
