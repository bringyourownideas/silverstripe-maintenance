<?php

namespace BringYourOwnIdeas\Maintenance\Forms;

use LogicException;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * GridFieldDropdownFilter provides a dropdown that can be used to filter a GridField arbitrarily
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldDropdownFilter implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator
{
    /**
     * Default string used in the value http attribute on the option for all results
     */
    const DEFAULT_OPTION_VALUE = '_all';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $targetFragment;

    /**
     * @var SS_List
     */
    protected $filterOptions;

    /**
     * @var string
     */
    protected $defaultOption;

    /**
     * @param string $name A name unique to this GridFieldDropdownFilter on this GridField
     * @param string $targetFragment Fragment to write the html fragment to.
     * @param string $defaultOption A string used as the label for the default "All results" option
     */
    public function __construct($name, $targetFragment, $defaultOption = null)
    {
        $this->name = $name;
        $this->targetFragment = $targetFragment;
        $this->defaultOption = $defaultOption ?: _t(__CLASS__ . '.AllResults', 'All results');
        $this->filterOptions = ArrayList::create();
    }

    /**
     * Add an option to the dropdown that provides a filter
     *
     * @param string $name
     * @param string $title
     * @param callable|array $filter Either a closure to filter a given SS_Filterable or a simple associative array will
     *                              be used for filtering
     * @return $this
     */
    public function addFilterOption($name, $title, $filter)
    {
        $this->filterOptions->push(compact('name', 'title', 'filter'));
        return $this;
    }

    /**
     * Remove a filter option with the given name
     *
     * @param string $name
     * @return $this
     */
    public function removeFilterOption($name)
    {
        $this->filterOptions->remove($this->filterOptions->find('name', $name));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($gridField)
    {
        return ['filter'];
    }

    /**
     * {@inheritdoc}
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== 'filter') {
            return;
        }

        if (!isset($data['filter-selection']) || $data['filter-selection'] === static::DEFAULT_OPTION_VALUE) {
            $gridField->State->{__CLASS__ . '_' . $this->name} = null;
        } else {
            $gridField->State->{__CLASS__ . '_' . $this->name} = $data['filter-selection'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$dataList instanceof Filterable) {
            throw new LogicException(__CLASS__ . ' is only compatible with SS_Filterable lists');
        }

        $filter = $gridField->State->{__CLASS__ . '_' . $this->name};

        if (!$filter || !($option = $this->filterOptions->find('name', $filter))) {
            return $dataList;
        }

        $filterSpec = $option['filter'];

        if (is_callable($filterSpec)) {
            return $filterSpec($dataList);
        }
        if (is_array($filterSpec)) {
            foreach ($filterSpec as $key => $value) {
                if ($dataList->canFilterBy($key)) {
                    $dataList = $dataList->filter($key, $value);
                }
            }
            return $dataList;
        }

        throw new LogicException('Invalid filter specification given');
    }

    /**
     * {@inheritdoc}
     */
    public function getHTMLFragments($gridField)
    {
        Requirements::javascript('bringyourownideas/silverstripe-maintenance: client/dist/js/bundle.js');

        $dropdownOptions = [static::DEFAULT_OPTION_VALUE => $this->defaultOption] +
            $this->filterOptions->map('name', 'title')->toArray();

        $dropdown = DropdownField::create(
            'filter-selection',
            null,
            $dropdownOptions,
            $gridField->State->{__CLASS__ . '_' . $this->name} ?: static::DEFAULT_OPTION_VALUE
        );
        $dropdown->addExtraClass('no-change-track');

        return [
            $this->targetFragment => ArrayData::create([
                'Filter' => $dropdown,
                'Action' => GridField_FormAction::create($gridField, 'filter', 'Go', 'filter', null),
            ])->renderWith(GridFieldDropdownFilter::class),
        ];
    }
}
