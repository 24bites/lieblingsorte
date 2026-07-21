@props(['position'])

@php
    $code = \App\Models\Setting::get("ad_slot_{$position}", '');
@endphp

@if (trim((string) $code) !== '')
    <div class="ad-slot ad-slot-{{ $position }} my-6" data-ad-position="{{ $position }}">
        {!! $code !!}
    </div>
@endif
