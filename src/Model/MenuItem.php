<?php

namespace TheWebmen\Menustructure\Model;

use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class MenuItem extends DataObject {

    private static $link_types = [
        'page' => 'Page',
        'url' => 'URL',
        'no-link' => 'Not linked'
    ];

    private static $table_name = 'Menustructure_MenuItem';

    private static $db = [
        'Title' => 'Varchar',
        'LinkType' => 'Varchar',
        'Url' => 'Varchar(255)',
        'OpenInNewWindow' => 'Boolean',
        'Sort' => 'Int'
    ];

    private static $has_one = [
        'Image' => Image::class,
        'Menu' => Menu::class,
        'ParentItem' => MenuItem::class,
        'LinkedPage' => SiteTree::class
    ];

    private static $has_many = [
        'Items' => MenuItem::class
    ];

    private static $summary_fields = [
        'Title',
        'LinkType',
        'OpenInNewWindow'
    ];

    private static $default_sort = 'Sort';

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');
        $fields->removeByName('ParentItemID');
        $fields->removeByName('MenuID');

        $fields->replaceField('LinkType', DropdownField::create('LinkType', $this->fieldLabel('LinkType'), self::$link_types));
        $fields->replaceField('LinkedPageID', $linkedPageWrapper = Wrapper::create(TreeDropdownField::create('LinkedPageID', $this->fieldLabel('LinkedPage'), SiteTree::class)));

        $linkedPageWrapper->displayIf('LinkType')->isEqualTo('page');
        $fields->dataFieldByName('Url')->displayIf('LinkType')->isEqualTo('url');
        $fields->dataFieldByName('OpenInNewWindow')->hideIf('LinkType')->isEqualTo('no-link');

        $fields->addFieldToTab('Root.Main', $fields->dataFieldByName('OpenInNewWindow'));
        $fields->addFieldToTab('Root.Main', $fields->dataFieldByName('Image')->setDescription('Optional image, can be used in some templates.'));

        $fields->removeByName('Items');
        if($this->exists()){
            $gridConfig = new GridFieldConfig_RecordEditor();
            $gridConfig->addComponent(GridFieldOrderableRows::create());
            $fields->addFieldToTab('Root.Main', GridField::create('Items', 'Items', $this->Items(), $gridConfig));
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if(!$this->MenuID){
            $parent = $this->ParentItem();
            if($parent){
                $this->MenuID = $parent->MenuID;
            }
        }
    }

    /**
     * @return bool|mixed
     */
    public function getLink(){
        switch ($this->LinkType) {
            case 'url':
                return $this->URL;
                break;
            case 'page':
                return $this->LinkedPage()->Link();
                break;
        }
        return false;
    }

}