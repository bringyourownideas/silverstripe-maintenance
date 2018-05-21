<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Model;

use Package;
use SapphireTest;

class PackageTest extends SapphireTest
{
    public function providePackageNamesAndTitles()
    {
        return [
            ['pretendvendor/silverstripe-prefixedpackage', 'prefixedpackage'],
            ['pretend-vendor/silverstripe-hyphen-package', 'hyphen-package'],
            ['pretendvendor/somepackage', 'somepackage'],
            ['pretend-vendor/silverstripepackage', 'silverstripepackage'],
            ['pretendvendor/hyphenated-package', 'hyphenated-package'],
            ['silverstripe/module', 'module'],
            ['silverstripe/some-thing', 'some-thing'],
            ['silverstripe/silverstripe-silverstripe-thing', 'silverstripe-thing'],
            ['silverstripe-themes/silverstripe-theme', 'theme'],
            ['silverstripe-themes/silverstripe-hyphenated-theme', 'hyphenated-theme'],
        ];
    }

    /**
     * @dataProvider providePackageNamesAndTitles
     */
    public function testTitleFormatsNameCorrectly($name, $title)
    {
        $testPackage = Package::create([
            'Name' => $name
        ]);
        $this->assertEquals($title, $testPackage->getTitle());
    }
}
