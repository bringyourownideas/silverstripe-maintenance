<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Reports\Stubs;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class SiteSummaryExtensionStub extends Extension implements TestOnly
{
    protected function updateAlerts(&$alerts)
    {
        $alerts[] = '<p><strong>Alert! Alert!</strong> <br />Sound the alarm!</p>';
    }
}
