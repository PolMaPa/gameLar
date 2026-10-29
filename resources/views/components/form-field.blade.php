@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'placeholder' => null,
    'rows' => 4,
])

@php
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? "{$name}-help" : null, $hasError ? "{$name}-error" : null])
        ->filter()
        ->implode(' ');
@endphp

<div>
    <label for="{{ $name }}" class="field-label">
        {{ $label }}
        @if ($required)
            <span class="text-rose-400" aria-hidden="true">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $name }}"
                  name="{{ $name }}"
                  rows="{{ $rows }}"
                  @if ($required) required @endif
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                  @if ($hasError) aria-invalid="true" @endif
                  {{ $attributes->merge(['class' => 'field-input '.($hasError ? 'field-input-error' : '')]) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}"
               name="{{ $name }}"
               type="{{ $type }}"
               value="{{ old($name, $value) }}"
               @if ($required) required @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if ($hasError) aria-invalid="true" @endif
               {{ $attributes->merge(['class' => 'field-input '.($hasError ? 'field-input-error' : '')]) }}>
    @endif

    @if ($help)
        <p id="{{ $name }}-help" class="field-help">{{ $help }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $name }}-error" class="field-error" role="alert">
            <span aria-hidden="true">⚠</span> {{ $errors->first($name) }}
        </p>
    @endif
</div>
