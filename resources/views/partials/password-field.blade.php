{{--
  Kolom password dengan tombol tampil/sembunyi (diatur di assets/js/app.js).
  Variabel: $id, $name, $label, $autocomplete, $hint (opsional), $rules (opsional, true = daftar syarat)
--}}
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <div class="pw">
        <input type="password" class="form-control" id="{{ $id }}" name="{{ $name }}" maxlength="72"
               autocomplete="{{ $autocomplete }}" required @if (! empty($autofocus)) autofocus @endif>
        <button type="button" class="pw-toggle" data-toggle-password="{{ $id }}" aria-label="Tampilkan password {{ strtolower($label) }}">
            <x-icon name="eye" class="i-on" :size="18" />
            <x-icon name="eye-off" class="i-off" :size="18" />
        </button>
    </div>
    @if (! empty($rules))
        <ul class="pw-rules" data-pw-rules="{{ $id }}" aria-live="polite">
            <li data-rule="len">Minimal 8 karakter</li>
            <li data-rule="letter">Mengandung huruf</li>
            <li data-rule="digit">Mengandung angka</li>
        </ul>
    @elseif (! empty($hint))
        <div class="form-text">{{ $hint }}</div>
    @endif
</div>
