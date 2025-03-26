<?php

namespace OpenAdmin\Admin\Form\Field;

class Fieldset
{
    protected $name = '';

    public function __construct()
    {
        $this->name = uniqid('fieldset-');
    }

    /**
     * @param $title
     * @param $collapsed
     * @param $hideLink
     * @return string
     */
    public function start($title, $collapsed = true, $hideLink = false)
    {
        $hideLink = (!$title) ? true : $hideLink;

        $hiddenLinkStyle = ($hideLink) ? 'style="display:none;"' : '';
        $collapsedClass = ($collapsed && !$hideLink) ? 'collapsed' : '';
        $showClass = ($collapsed && !$hideLink) ? '' : 'show';


        return <<<HTML
<div>
    <div class="fieldset" {$hiddenLinkStyle}>
        <a data-bs-toggle="collapse" href="#{$this->name}" class="{$this->name}-title fieldset-link {$collapsedClass}">
        <span>{$title}</span> <i class="icon-angle-up"></i>
        </a>
    </div>
    <div class="collapse in {$showClass}" id="{$this->name}">
HTML;
    }

    public function end()
    {
        return '</div></div>';
    }
}
