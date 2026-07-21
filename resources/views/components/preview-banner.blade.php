@props(['isPublished'])

<div class="bg-amber-100 border-b border-amber-300 text-amber-900 text-sm text-center py-2.5 px-4">
    <strong>Vorschau-Modus</strong> &ndash;
    @if ($isPublished)
        dieser Inhalt ist bereits veröffentlicht.
    @else
        dieser Inhalt ist noch <strong>nicht veröffentlicht</strong> und für Besucher nicht sichtbar.
    @endif
</div>
