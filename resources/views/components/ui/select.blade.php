<select {{ $attributes->merge(['class' => \CleaniqueCoders\Eligify\Support\Theme::classes('select')]) }}>
    {{ $slot }}
</select>
