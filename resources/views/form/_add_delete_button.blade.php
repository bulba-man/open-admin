@php
    $buttonOptions = $options ?? [];
    $buttonType = $type ?? 'add';
    $textKey = match ($buttonType) {
        'delete' => 'deleteButtonText',
        'edit' => 'editButtonText',
        default => 'addButtonText',
    };
    $iconKey = match ($buttonType) {
        'delete' => 'deleteButtonIcon',
        'edit' => 'editButtonIcon',
        default => 'addButtonIcon',
    };
    $showTextKey = match ($buttonType) {
        'delete' => 'deleteButShowText',
        'edit' => 'editButShowText',
        default => 'addButShowText',
    };
    $showIconKey = match ($buttonType) {
        'delete' => 'deleteButShowIcon',
        'edit' => 'editButShowIcon',
        default => 'addButShowIcon',
    };
    $text = $buttonOptions[$textKey] ?? ($defaultText ?? null);
    $icon = $buttonOptions[$iconKey] ?? ($defaultIcon ?? null);
    $showText = $buttonOptions[$showTextKey] ?? true;
    $showIcon = $buttonOptions[$showIconKey] ?? true;
    $hasText = $text !== null && $text !== '';
    $hasIcon = $icon !== null && $icon !== '';
@endphp
@if($showIcon && $hasIcon)
    @if(isset($iconStyle) && $iconStyle !== '')
        <i class="{{ $icon }}" style="{{ $iconStyle }}"></i>
    @else
        <i class="{{ $icon }}"></i>
    @endif
@endif
@if($showIcon && $hasIcon && $showText && $hasText)
    &nbsp;
@endif
@if($showText && $hasText)
    {{ $text }}
@endif
