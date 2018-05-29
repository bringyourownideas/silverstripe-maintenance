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
     *
     * Ensure the vendor and 'silverstripe-' is stripped from module names.
     */
    public function testTitleFormatsNameCorrectly($name, $title)
    {
        $testPackage = new Package([
            'Name' => $name
        ]);
        $this->assertEquals($title, $testPackage->getTitle());
    }

    /**
     * Ensure that the definition key is always the output title
     * and that the value is set as the Type.
     */
    public function testBadges()
    {
        $testPackage = new Package();
        $testBadges = [
            'A good Badge' => 'good',
            'A typeless badge' => null
        ];
        $testPackage->setBadges($testBadges);
        $badgeViewData = $testPackage->getBadges();

        // Test expected data structure is correct
        $this->assertInstanceOf('ArrayList', $badgeViewData);
        $this->assertContainsOnlyInstancesOf('ArrayData', $badgeViewData->toArray());

        // Test that the output format is correct
        reset($testBadges);
        foreach ($badgeViewData as $badgeData) {
            $title = key($testBadges);
            $type = current($testBadges);
            $this->assertSame(
                [
                    'Title' => $title,
                    'Type' => $type,
                ],
                $badgeData->toMap()
            );
            // testBadges is a keyed array, so shift the pointer manually
            // (because we can't lookup by index)
            next($testBadges);
        }
    }
}
