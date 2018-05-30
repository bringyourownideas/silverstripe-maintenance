<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Reports\Stubs;

use Extension;
use TestOnly;

class SiteSummaryExtensionStub extends Extension implements TestOnly
{
    public function updateAlerts(&$alerts)
    {
        $alerts[] = '<p><strong>Alert! Alert!</strong> <br />Sound the alarm!</p>';
    }
}
