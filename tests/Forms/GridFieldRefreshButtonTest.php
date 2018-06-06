<?php

use BringYourOwnIdeas\Maintenance\Forms\GridFieldRefreshButton;

class GridFieldRefreshButtonTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldRefreshButtonTest.yml';

    public function testHasRunningJobReturnsTrueWhenJobIsRunning()
    {
        $button = $this->getButton();
        $this->assertTrue($button->hasActiveJob());
    }

    public function testHasRunningJobReturnsTrueForPendingJobsOnImmediateQueue()
    {
        $runningJob = $this->objFromFixture('QueuedJobDescriptor', 'runningjob');
        $runningJob->JobType = QueuedJob::IMMEDIATE;
        $runningJob->write();
    }

    public function testDoesNotHaveCancelledCompletedOrBrokenJob()
    {
        $this->completeRunningJob();

        $button = $this->getButton();
        $this->assertFalse($button->hasActiveJob());
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
        $this->assertSame('true', $button->handleCheck());
    }

    public function testButtonIsDisabledWhenJobIsRunning()
    {
        $button = $this->getButton();

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertContains('disabled', $output['test']);
    }

    public function testButtonIsEnabledWhenNoJobIsRunning()
    {
        $this->completeRunningJob();

        $button = $this->getButton();

        $gridFieldMock = $this->getGridFieldMock();

        $output = $button->getHTMLFragments($gridFieldMock);

        $this->assertNotContains('disabled', $output['test']);
    }

    /**
     * Turns the running job in the fixture file into a completed job
     */
    protected function completeRunningJob()
    {
        $runningJob = $this->objFromFixture('QueuedJobDescriptor', 'runningjob');
        $runningJob->JobStatus = 'Complete';
        $runningJob->write();

        $runningJob = $this->objFromFixture('QueuedJobDescriptor', 'immediatependingjob');
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
