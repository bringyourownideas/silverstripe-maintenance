<?php

namespace BringYourOwnIdeas\Maintenance\Tests\Forms;

use BringYourOwnIdeas\Maintenance\Forms\GridFieldLinkButton;
use SilverStripe\Dev\SapphireTest;

class GridFieldLinkButtonTest extends SapphireTest
{
    /**
     * Not very strong test, as it relies on template output.
     */
    public function testCorrectLinkIsContained()
    {
        $button = new GridFieldLinkButton('https://addons.silverstripe.org', 'Explore Addons', 'test');
        $output = $button->getHTMLFragments(null);

        $this->assertContains('https://addons.silverstripe.org', $output['test']);
    }
}
