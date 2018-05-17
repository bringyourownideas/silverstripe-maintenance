<?php

/**
 * A button that contains a link to an URL.
 *
 * @package forms
 * @subpackage fields-gridfield
 */

class GridFieldLinkButton implements GridField_HTMLProvider
{
    /**
     * Fragment to write the button to.
     * @var string
     */
    protected $targetFragment;

    /**
     * URL link the button links out to.
     * @var string
     */
    protected $link;

    /**
     * @param string $link The URL link the button links out to.
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($link, $targetFragment)
    {
        $this->link = $link;
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the link button in a <p> tag above the field
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $fragment = ArrayData::create([
            'Link' => $this->link,
            'Caption' => _t('GridFieldLinkButton.LINK_TO_ADDONS', 'Explore Addons')
        ])->renderWith(__CLASS__);

        return [$this->targetFragment => $fragment];
    }
}
