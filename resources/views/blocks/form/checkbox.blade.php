{{-- Bootstrap 3 form group with a single checkbox. Variables: name, label. --}}
<div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
    <div>
        <div class="checkbox">
            <label>
                <input name="{{ $name }}" type="checkbox" value="1" @checked(old($name))> {{ $label }}
            </label>
        </div>
        @if ($errors->has($name))
            <span class="help-block">{{ $errors->first($name) }}</span>
        @endif
    </div>
</div>
