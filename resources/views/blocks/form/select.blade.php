{{-- Bootstrap 3 form group with a label and a <select>. Variables: name, label, options (value => label). --}}
<div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
    <label for="{{ $name }}" class="control-label">{{ $label }}</label>
    <div>
        <select class="form-control" id="{{ $name }}" name="{{ $name }}">
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected(old($name) !== null && (string) old($name) === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        @if ($errors->has($name))
            <span class="help-block">{{ $errors->first($name) }}</span>
        @endif
    </div>
</div>
