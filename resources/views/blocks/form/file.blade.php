{{-- Bootstrap 3 form group with a label and a file input. Variables: name, label. --}}
<div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
    <label for="{{ $name }}" class="control-label">{{ $label }}</label>
    <div>
        <input class="form-control filestyle" data-buttonBefore="true" name="{{ $name }}" type="file" id="{{ $name }}">
        @if ($errors->has($name))
            <span class="help-block">{{ $errors->first($name) }}</span>
        @endif
    </div>
</div>
