@props(['label'])

<span
    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-white shadow-sm"
    style="background-color: {{ $label->color }}"
>
    {{ $label->name }}
</span>
