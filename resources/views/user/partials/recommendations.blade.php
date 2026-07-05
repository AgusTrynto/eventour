@forelse ($recommendedEvents as $recommendation)
    @php($event = $recommendation['event'])

    <div class="rec-item">
        <div class="rec-icon"><x-icon name="ticket" :size="22" /></div>
        <div class="rec-info">
            <div class="rec-title-row">
                <span class="rec-title">{{ $event->title }}</span>
            </div>
            <span class="rec-meta">
                {{ $event->location_name }}
                <span aria-hidden="true">&middot;</span>
                {{ $event->start_date?->translatedFormat('d M Y') ?? '-' }}
            </span>
            <div class="rec-signals">
                <span class="rec-signal">{{ $recommendation['model_label'] }}</span>
                <span class="rec-signal">{{ $recommendation['category_label'] }}</span>
                <span class="rec-signal">{{ $recommendation['time_label'] }}</span>
                <span class="rec-signal">{{ $recommendation['price_label'] }}</span>
            </div>
        </div>
        <div class="rec-actions">
            <span class="rec-score">{{ $recommendation['score_label'] }}</span>
            <a href="{{ route('events.show', $event) }}" class="rec-btn">Lihat</a>
        </div>
    </div>
@empty
    <div class="empty-state">
        <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
        <p>Belum ada event tersedia saat ini.</p>
    </div>
@endforelse
