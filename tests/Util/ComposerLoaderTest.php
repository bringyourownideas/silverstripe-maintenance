<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Util;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use PHPUnit_Framework_TestCase;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class ComposerLoaderTest extends SapphireTest
{
    public function testGetJson()
    {
        $loader = new ComposerLoader(__DIR__ . '/Fixtures');
        $this->assertNotEmpty(
            $loader->getJson()->name,
            'JSON file is loaded and parsed'
        );
    }

    public function testGetLock()
    {
        $loader = new ComposerLoader(__DIR__ . '/Fixtures');
        $this->assertNotEmpty(
            $loader->getLock()->packages,
            'Lock file is loaded and parsed'
        );
    }
}
