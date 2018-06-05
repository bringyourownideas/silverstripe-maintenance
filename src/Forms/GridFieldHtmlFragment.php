<?php

namespace BringYourOwnIdeas\Maintenance\Forms;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

/**
 * Facilitates adding arbitrary HTML to grid fields
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldHtmlFragment implements GridField_HTMLProvider
{
    /**
     * Fragment to write the html fragment to.
     * @var string
     */
    protected $targetFragment;

    /**
     * An HTML fragment to render
     * @var string
     */
    protected $htmlFragment;

    /**
     * @param string $targetFragment Fragment to write the html fragment to.
     * @param string $htmlFragment An HTML fragment to render
     */
    public function __construct($targetFragment, $htmlFragment)
    {
        $this->targetFragment = $targetFragment;
        $this->htmlFragment = $htmlFragment;
    }

    /**
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        return [$this->targetFragment => $this->htmlFragment];
    }
}
