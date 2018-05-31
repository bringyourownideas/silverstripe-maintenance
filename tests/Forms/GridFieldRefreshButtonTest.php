<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Forms;

use BringYourOwnIdeas\Maintenance\Forms\GridFieldRefreshButton;
use SilverStripe\Core\Config\Config;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Dev\SapphireTest;
use Symbiote\QueuedJobs\Services\QueuedJobService;

class GridFieldRefreshButtonTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldRefreshButtonTest.yml';

    protected function setUp()
    {
        parent::setUp();

        Config::modify()->set(QueuedJobService::class, 'use_shutdown_function', false);
    }

    public function testHasRunningJob()
    {
        $button = new GridFieldRefreshButton('test');
        $this->assertTrue($button->hasActiveJob());
    }

    public function testDoesNotHaveCancelledCompletedOrBrokenJob()
    {
        $this->completeRunningJob();

        $button = new GridFieldRefreshButton('test');
        $this->assertFalse($button->hasActiveJob());
    }

    public function testHandleRefreshDoesNotCreateJobWhenJobIsRunning()
    {
        $count = QueuedJobDescriptor::get()->count();

        $button = new GridFieldRefreshButton('test');
        $button->handleRefresh();

        $this->assertSame($count, QueuedJobDescriptor::get()->count());
    }

    public function testHandleRefreshCreatesJobWhenNoJobIsRunning()
    {
        $this->completeRunningJob();

        $count = QueuedJobDescriptor::get()->count();

        $button = new GridFieldRefreshButton('test');
        $button->handleRefresh();

        $this->assertSame($count + 1, QueuedJobDescriptor::get()->count());
    }

    public function testHandleCheckReturnsValidJson()
    {
        $button = new GridFieldRefreshButton('test');
        $this->assertSame('true', $button->handleCheck());
    }

    public function testButtonIsDisabledWhenJobIsRunning()
    {
        $button = new GridFieldRefreshButton('test');

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertContains('disabled', $output['test']);
    }

    public function testButtonIsEnabledWhenNoJobIsRunning()
    {
        $this->completeRunningJob();

        $button = new GridFieldRefreshButton('test');

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertNotContains('disabled', $output['test']);
    }

    /**
     * Turns the running job in the fixture file into a completed job
     */
    protected function completeRunningJob()
    {
        $runningJob = $this->objFromFixture(QueuedJobDescriptor::class, 'runningjob');
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
}
