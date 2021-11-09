<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Forms;

use BringYourOwnIdeas\Maintenance\Forms\GridFieldRefreshButton;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;

class GridFieldRefreshButtonTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldRefreshButtonTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(QueuedJobService::class, 'use_shutdown_function', false);
    }

    public function testHasRunningJobReturnsTrueWhenJobIsRunning()
    {
        $button = $this->getButton();
        $this->assertTrue($button->hasPendingJob());
    }

    public function testHasRunningJobReturnsTrueForPendingJobsOnImmediateQueue()
    {
        $runningJob = $this->objFromFixture(QueuedJobDescriptor::class, 'runningjob');
        $runningJob->JobStatus = 'Complete';
        $runningJob->write();
        $this->assertTrue($this->getButton()->hasPendingJob());
    }

    public function testDoesNotHaveCancelledCompletedOrBrokenJob()
    {
        $this->completeRunningJob();

        $button = $this->getButton();
        $this->assertFalse($button->hasPendingJob());
    }

    public function testHandleRefreshDoesNotCreateJobWhenJobIsRunning()
    {
        $count = QueuedJobDescriptor::get()->count();

        $button = $this->getButton();
        $button->handleRefresh();

        $this->assertSame($count, QueuedJobDescriptor::get()->count());
    }

    public function testHandleRefreshCreatesJobWhenNoJobIsRunning()
    {
        $this->completeRunningJob();

        $count = QueuedJobDescriptor::get()->count();

        $button = $this->getButton();
        $button->handleRefresh();

        $this->assertSame($count + 1, QueuedJobDescriptor::get()->count());
    }

    public function testHandleCheckReturnsValidJson()
    {
        $button = $this->getButton();
        $this->assertStringContainsString('true', $button->handleCheck());
    }

    public function testButtonIsDisabledWhenJobIsRunning()
    {
        $button = $this->getButton();

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertStringContainsString('disabled', $output['test']);
    }

    public function testButtonIsEnabledWhenNoJobIsRunning()
    {
        $this->completeRunningJob();

        $button = $this->getButton();

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertStringNotContainsString('disabled', $output['test']);
    }

    /**
     * Turns the running job in the fixture file into a completed job
     */
    protected function completeRunningJob()
    {
        $runningJob = $this->objFromFixture(QueuedJobDescriptor::class, 'runningjob');
        $runningJob->JobStatus = 'Complete';
        $runningJob->write();

        $runningJob = $this->objFromFixture(QueuedJobDescriptor::class, 'immediatependingjob');
        $runningJob->JobStatus = 'Complete';
        $runningJob->write();
    }

    /**
     * Mocks and returns a gridfield with name 'TestGridField' and 'Link' method, which returns a url
     * @return mixed
     */
    protected function getGridFieldMock()
    {
        $gridFieldMock = $this
            ->getMockBuilder(GridField::class)
            ->setConstructorArgs(['TestGridField'])
            ->setMethods(['Link'])
            ->getMock();

        $gridFieldMock
            ->expects($this->any())
            ->method('Link')
            ->will($this->returnValue('http://example.com'));

        return $gridFieldMock;
    }

    /**
     * @return GridFieldRefreshButton
     */
    protected function getButton()
    {
        return Injector::inst()->create(GridFieldRefreshButton::class, 'test');
    }
}
