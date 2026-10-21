{{-- Bootstrap 3 form group with a label and an <input>. Variables: name, label, type (default text), value (default null). --}}
<div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
    <label for="{{ $name }}" class="control-label">{{ $label }}</label>
    <div>
        <input class="form-control" name="{{ $name }}" type="{{ $type ?? 'text' }}" id="{{ $name }}" value="{{ old($name, $value ?? null) }}">
        @if ($errors->has($name))
            <span class="help-block">{{ $errors->first($name) }}</span>
        @endif
    </div>
</div>
