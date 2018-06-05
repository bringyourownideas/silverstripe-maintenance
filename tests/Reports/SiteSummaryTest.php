<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Reports;

use BringYourOwnIdeas\Maintenance\Reports\SiteSummary;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\SapphireTest;
use BringYourOwnIdeas\Maintenance\Tests\Reports\Stubs\SiteSummaryExtensionStub;
use SilverStripe\Forms\LiteralField;

class SiteSummaryTest extends SapphireTest
{
    protected static $fixture_file = 'Package.yml';

    protected static $required_extensions = [
        SiteSummary::class => [SiteSummaryExtensionStub::class]
    ];

    public function testSourceRecords()
    {
        $summaryReport = new SiteSummary;
        $records = $summaryReport->sourceRecords(null);
        $firstRecord = $records->first();
        $this->assertInstanceOf(Package::class, $firstRecord);
        $this->assertStringStartsWith('pretend/', $firstRecord->Name);
    }

    public function testOnlySilverStripeModulesAreShown()
    {
        $summaryReport = new SiteSummary;
        $records = $summaryReport->sourceRecords(null);
        $this->assertCount(3, $records);
        foreach ($records as $record) {
            $this->assertEquals('silverstripe-module', $record->Type);
        }
    }

    public function testAlertsRenderAtopTheReportField()
    {
        $summaryReport = new SiteSummary;
        $fields = $summaryReport->getCMSFields();
        $alertSummary = $fields->fieldByName('AlertSummary');
        $this->assertInstanceOf(LiteralField::class, $alertSummary);
        $this->assertContains('Sound the alarm!', $alertSummary->getContent());
    }
}
