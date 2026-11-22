@props(['variant' => 'primary', 'type' => 'button'])

<button {{ $attributes->class(['button', 'button-'.$variant]) }} type="{{ $type }}">{{ $slot }}</button>
