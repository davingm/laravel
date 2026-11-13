@props(['label'])

<label {{ $attributes->class(['crud-field']) }}>
    <span>{{ $label }}</span>
    {{ $slot }}
</label>
