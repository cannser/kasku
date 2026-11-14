{{-- Pesan sekali tampil (session flash). {{ }} meng-escape isi pesan --}}
@php($flashTypes = ['success' => 'check', 'danger' => 'alert', 'warning' => 'alert', 'info' => 'alert'])
@if (collect(array_keys($flashTypes))->contains(fn ($t) => session($t)))
    <div class="toasts" aria-live="polite">
        @foreach ($flashTypes as $type => $icon)
            @if (session($type))
                <div class="toast-kk {{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">
                    <x-icon :name="$icon" class="toast-icon" />
                    <p>{{ session($type) }}</p>
                    <button type="button" class="toast-close" data-toast-close aria-label="Tutup pesan">
                        <x-icon name="x" :size="16" />
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@endif
