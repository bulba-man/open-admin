@php
    $buttonOptions = $options ?? [];
    $buttonType = $type ?? 'add';
    $textKey = $buttonType === 'delete' ? 'deleteButtonText' : 'addButtonText';
    $iconKey = $buttonType === 'delete' ? 'deleteButtonIcon' : 'addButtonIcon';
    $showTextKey = $buttonType === 'delete' ? 'deleteButShowText' : 'addButShowText';
    $showIconKey = $buttonType === 'delete' ? 'deleteButShowIcon' : 'addButShowIcon';
    $text = $buttonOptions[$textKey] ?? ($defaultText ?? null);
    $icon = $buttonOptions[$iconKey] ?? ($defaultIcon ?? null);
    $showText = $buttonOptions[$showTextKey] ?? true;
    $showIcon = $buttonOptions[$showIconKey] ?? true;
    $hasText = $text !== null && $text !== '';
    $hasIcon = $icon !== null && $icon !== '';
@endphp
@if($showIcon && $hasIcon)<i class="{{ $icon }}" @isset($iconStyle) style="{{ $iconStyle }}" @endisset></i>@endif@if($showIcon && $hasIcon && $showText && $hasText)&nbsp;@endif@if($showText && $hasText){{ $text }}@endif
