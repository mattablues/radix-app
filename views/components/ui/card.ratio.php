{% props([
    'title',
    'shadow' => 'shadow-sm',
    'padding' => 'p-6'
]) %}

<div class="bg-white border border-gray-200 rounded-2xl {{ $shadow }} {{ $padding }}">
    <h4 class="text-lg font-bold mb-2">{{ $title }}</h4>
    {{ $slot }}
</div>