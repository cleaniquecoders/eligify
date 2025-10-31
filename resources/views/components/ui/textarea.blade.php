@props(['rows' => 3])

<textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => \CleaniqueCoders\Eligify\Support\Theme::classes('textarea')]) }}>{{ $slot }}</textarea>
