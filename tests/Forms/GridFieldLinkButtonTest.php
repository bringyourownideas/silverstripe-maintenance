<?php

class GridFieldLinkButtonTest extends SapphireTest
{
    /**
     * Not very strong test, as it relies on template output.
     */
    public function testCorrectLinkIsContained()
    {
        $button = new GridFieldLinkButton('https://addons.silverstripe.org', 'test');
        $output = $button->getHTMLFragments(null);

        $this->assertContains('https://addons.silverstripe.org', $output['test']);
    }
}
