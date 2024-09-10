<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Model;

use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use PHPUnit\Framework\Attributes\DataProvider;

class PackageTest extends SapphireTest
{
    public static function providePackageNamesAndTitles()
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
     *
     * Ensure the vendor and 'silverstripe-' is stripped from module names.
     */
    #[DataProvider('providePackageNamesAndTitles')]
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

        // setBadges to test
        $setBadges = [
            'A good Badge' => 'good',
            'A typeless badge' => null
        ];
        $testPackage->setBadges($setBadges);

        // test addBadge appends badge to the stored list
        $addedBadgeTitle = 'Integer badge';
        $addedBadgeValue = 3;
        $testPackage->addBadge($addedBadgeTitle, $addedBadgeValue);

        // tests adding badges via getBadges optional parameter
        $extraBadge = ['Extra' => 'warning'];

        // combine the input data to test outputs against
        $badgeControlSample = array_merge($setBadges, [$addedBadgeTitle => $addedBadgeValue], $extraBadge);

        $badgeViewData = $testPackage->getBadges($extraBadge);

        // Test expected data structure is correct
        $this->assertInstanceOf(ArrayList::class, $badgeViewData);
        $this->assertContainsOnlyInstancesOf(ArrayData::class, $badgeViewData->toArray());

        // Test that the output format is correct
        // and that all our input is output
        reset($badgeControlSample);
        foreach ($badgeViewData as $badgeData) {
            $title = key($badgeControlSample ?? []);
            $type = current($badgeControlSample ?? []);
            $this->assertSame(
                [
                    'Title' => $title,
                    'Type' => $type,
                ],
                $badgeData->toMap()
            );
            // badgeControlSample is a keyed array, so shift the pointer manually
            // (because we can't lookup by index)
            next($badgeControlSample);
        }
    }
}
