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
        $button = new GridFieldLinkButton('https://packagist.org', 'Browse Modules', 'test');
        $output = $button->getHTMLFragments(null);

        $this->assertStringContainsString('https://packagist.org', $output['test']);
    }
}
