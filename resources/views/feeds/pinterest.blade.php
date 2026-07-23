<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>{{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }} – Regionen</title>
        <link>{{ url('/regionen') }}</link>
        <description>Die neuesten Regionen und Reiseziele auf {{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }}.</description>
        <language>de-DE</language>
        @foreach ($regions as $region)
        <item>
            <title>{{ $region['title'] }}</title>
            <link>{{ $region['link'] }}</link>
            <guid isPermaLink="true">{{ $region['link'] }}</guid>
            <pubDate>{{ $region['pubDate'] }}</pubDate>
            <description><![CDATA[{{ $region['description'] }}]]></description>
            @if ($region['image'])
                <enclosure url="{{ $region['image']['url'] }}" type="{{ $region['image']['type'] }}" length="{{ $region['image']['length'] }}" />
            @endif
        </item>
        @endforeach
    </channel>
</rss>
