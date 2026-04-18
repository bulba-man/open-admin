@if($help)
    <span class="help-block mode-{{ \Illuminate\Support\Arr::get($help, 'mode') }}">
    @if(\Illuminate\Support\Arr::get($help, 'mode') == 'text')
        <i class="{{ \Illuminate\Support\Arr::get($help, 'icon') }}"></i>&nbsp;{!! \Illuminate\Support\Arr::get($help, 'text') !!}
    @elseif(\Illuminate\Support\Arr::get($help, 'mode') == 'tooltip')
        <i class="{{ \Illuminate\Support\Arr::get($help, 'icon') }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{!! \Illuminate\Support\Arr::get($help, 'text') !!}"></i>
    @elseif(\Illuminate\Support\Arr::get($help, 'mode') == 'popover')
        <i class="{{ \Illuminate\Support\Arr::get($help, 'icon') }}" tabindex="0" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="top" data-bs-content="{!! \Illuminate\Support\Arr::get($help, 'text') !!}"></i>
    @endif
</span>
@endif
