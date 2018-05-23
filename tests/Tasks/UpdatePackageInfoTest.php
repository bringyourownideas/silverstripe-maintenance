<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use SapphireTest;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfo;

class UpdatePackageInfoTest extends SapphireTest
{
    public function testGetPackageInfo()
    {
        $loader = $this->getMockBuilder(ComposerLoader::class)->setMethods(['getLock'])->getMock();
        $loader->expects($this->any())->method('getLock')->will($this->returnValue(json_decode(<<<LOCK
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
        )));
        $processor = new UpdatePackageInfo;
        $output = $processor->getPackageInfo($loader->getLock()->packages);
        $this->assertInternalType('array', $output);
        $this->assertCount(1, $output);
        $this->assertSame([
            "Name" => "fake/package",
            "Description" => "A faux package from a mocked composer.lock for testing purposes",
            "Version" => "1.0.0"
        ], $output[0]);
    }
}
