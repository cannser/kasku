{{-- Panel kiri halaman masuk/daftar. Baris contoh hanya hiasan (aria-hidden), bukan data pengguna. --}}
<aside class="auth-aside">
    <a class="brand" href="{{ route('home') }}">
        <span class="brand-mark"><x-icon name="k" :size="20" /></span>
        <span class="brand-name">KasKu</span>
    </a>
    <div>
        <h2 class="auth-headline">Tahu ke mana uangmu pergi.</h2>
        <p class="auth-lead">Catat pemasukan dan pengeluaran harianmu, lalu lihat rekap bulanannya dalam satu layar.</p>
    </div>
    <div class="mock" aria-hidden="true">
        <div class="mock-row"><span>Uang saku</span><span class="in">+ Rp 1.500.000</span></div>
        <div class="mock-row"><span>Makan siang</span><span class="out">- Rp 18.000</span></div>
        <div class="mock-row"><span>Kopi sore</span><span class="out">- Rp 24.000</span></div>
    </div>
    <p class="auth-note">Aplikasi untuk tugas Cyber Security. Gunakan data dummy.</p>
</aside>
