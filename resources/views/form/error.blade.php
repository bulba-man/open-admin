@if(is_array($errorKey))

    @foreach($errorKey as $key => $col)
        @if($errors->has($col.$key))
            <div class="invalid-feedback">
            @foreach($errors->get($col.$key) as $message)
                <div class="error-item">{{$message}}</div>
            @endforeach
            </div>
        @endif
    @endforeach

@else

    @if($errors->has($errorKey))
        <div class="invalid-feedback">
            @foreach($errors->get($errorKey) as $message)
                <div class="error-item">{{$message}}</div>
            @endforeach
        </div>
    @endif

@endif
