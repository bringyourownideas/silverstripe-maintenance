<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Reports;

use BringYourOwnIdeas\Maintenance\Model\Package;
use BringYourOwnIdeas\Maintenance\Reports\SiteSummary;
use BringYourOwnIdeas\Maintenance\Tests\Reports\Stubs\SiteSummaryExtensionStub;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\LiteralField;
use Symbiote\QueuedJobs\Services\QueuedJobService;

class SiteSummaryTest extends SapphireTest
{
    protected static $fixture_file = 'Package.yml';

    protected static $required_extensions = [
        SiteSummary::class => [
            SiteSummaryExtensionStub::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(QueuedJobService::class, 'use_shutdown_function', false);
    }

    public function testSourceRecords()
    {
        $summaryReport = new SiteSummary;
        $records = $summaryReport->sourceRecords();
        $firstRecord = $records->first();
        $this->assertInstanceOf(Package::class, $firstRecord);
        $this->assertStringStartsWith('pretend/', $firstRecord->Name);
    }

    public function testOnlySilverStripeModulesAreShown()
    {
        $summaryReport = new SiteSummary;
        $records = $summaryReport->sourceRecords();
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
        $this->assertStringContainsString('Sound the alarm!', $alertSummary->getContent());
    }
}
