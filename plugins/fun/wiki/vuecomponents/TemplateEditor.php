<?php namespace Fun\Wiki\VueComponents;

use System\Classes\VueComponentBase;

/**
 * TemplateEditor Vue Component
 *
 * @link https://docs.octobercms.com/4.x/extend/system/vue-components.html
 */
class TemplateEditor extends VueComponentBase
{
    /**
     * @var string componentName is the Vue component tag name (kebab-case).
     */
    protected $componentName = 'fun-wiki-templateeditor';

    /**
     * @var array require lists dependent Vue component classes.
     */
    protected $require = [];

    /**
     * prepareVars passes data from PHP to the template partial.
     */
    protected function prepareVars()
    {
    }

    /**
     * registerSubcomponents registers nested components sharing this context.
     * Each subcomponent needs its own JavaScript file and partial, eg:
     * assets/js/alert.js and partials/_alert.php for 'alert', rendered as
     * the tag fun-wiki-templateeditor-alert.
     */
    protected function registerSubcomponents()
    {
        // $this->registerSubcomponent('alert');
    }
}