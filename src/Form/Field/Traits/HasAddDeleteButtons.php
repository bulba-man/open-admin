<?php

namespace OpenAdmin\Admin\Form\Field\Traits;

trait HasAddDeleteButtons
{
    public function addButtonText(string $text): self
    {
        $this->options['addButtonText'] = $text;
        $this->options['addButShowText'] = true;

        return $this;
    }

    public function deleteButtonText(string $text): self
    {
        $this->options['deleteButtonText'] = $text;
        $this->options['deleteButShowText'] = true;

        return $this;
    }

    public function editButtonText(string $text): self
    {
        $this->options['editButtonText'] = $text;
        $this->options['editButShowText'] = true;

        return $this;
    }

    public function removeButtonText(string $text): self
    {
        return $this->deleteButtonText($text);
    }

    public function addButtonIcon(?string $icon): self
    {
        $this->options['addButtonIcon'] = $icon;
        $this->options['addButShowIcon'] = filled($icon);

        return $this;
    }

    public function deleteButtonIcon(?string $icon): self
    {
        $this->options['deleteButtonIcon'] = $icon;
        $this->options['deleteButShowIcon'] = filled($icon);

        return $this;
    }

    public function editButtonIcon(?string $icon): self
    {
        $this->options['editButtonIcon'] = $icon;
        $this->options['editButShowIcon'] = filled($icon);

        return $this;
    }

    public function removeButtonIcon(?string $icon): self
    {
        return $this->deleteButtonIcon($icon);
    }

    public function hideAddText(): self
    {
        $this->options['addButShowText'] = false;

        return $this;
    }

    public function hideDeleteText(): self
    {
        $this->options['deleteButShowText'] = false;

        return $this;
    }

    public function hideEditText(): self
    {
        $this->options['editButShowText'] = false;

        return $this;
    }

    public function hideRemoveText(): self
    {
        return $this->hideDeleteText();
    }

    public function hideAddIcon(): self
    {
        $this->options['addButShowIcon'] = false;

        return $this;
    }

    public function hideDeleteIcon(): self
    {
        $this->options['deleteButShowIcon'] = false;

        return $this;
    }

    public function hideEditIcon(): self
    {
        $this->options['editButShowIcon'] = false;

        return $this;
    }

    public function hideRemoveIcon(): self
    {
        return $this->hideDeleteIcon();
    }
}
