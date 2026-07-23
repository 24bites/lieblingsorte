<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }} – Neueste Beiträge</title>
        <link>{{ url('/') }}</link>
        <atom:link href="{{ url('/feed.xml') }}" rel="self" type="application/rss+xml" />
        <description>Die neuesten Regionen, Reiseziele und Reiseberichte auf {{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }}.</description>
        <language>de-DE</language>
        @foreach ($items as $item)
        <item>
            <title>{{ $item['title'] }}</title>
            <link>{{ $item['link'] }}</link>
            <guid isPermaLink="true">{{ $item['link'] }}</guid>
            <pubDate>{{ $item['pubDate'] }}</pubDate>
            <category>{{ $item['type'] }}</category>
            <description><![CDATA[{{ $item['description'] }}]]></description>
            @if ($item['image'])
                <enclosure url="{{ $item['image'] }}" type="image/jpeg" length="0" />
            @endif
        </item>
        @endforeach
    </channel>
</rss>
