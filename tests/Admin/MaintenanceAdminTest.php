<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Admin;

use ComposerPackageVersion;
use MaintenanceAdmin;
use PHPUnit_Framework_TestCase;
use SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class MaintenanceAdminTest extends SapphireTest
{
    public function testGetManangedModels()
    {
        if (!class_exists(ComposerPackageVersion::class)) {
            $this->markTestSkipped('Required dependency is not installed');
        }

        $result = (new MaintenanceAdmin)->getManagedModels();
        $this->assertArrayHasKey(ComposerPackageVersion::class, $result);
    }
}
