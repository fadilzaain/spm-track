// ===== SPM Track: JavaScript kecil (tema, jam, tombol Esc, lihat password, lipat kelompok laporan) =====

const akar = document.documentElement;

// 1. Ganti tema gelap/terang, pilihan disimpan di browser
document.querySelector('[data-ganti-tema]')?.addEventListener('click', () => {
    const baru = akar.dataset.tema === 'terang' ? 'gelap' : 'terang';
    akar.dataset.tema = baru;
    try { localStorage.setItem('tema', baru); } catch (e) { /* abaikan kalau penyimpanan diblokir */ }
});

// 2. Jam hidup (zona waktu WIB), format: "Rab, 28 Okt · 08.24.47"
const elemenJam = document.querySelector('[data-jam]');
if (elemenJam) {
    const format = new Intl.DateTimeFormat('id-ID', {
        weekday: 'short', day: 'numeric', month: 'short',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false, timeZone: 'Asia/Jakarta',
    });

    const tampilkanJam = () => {
        const b = Object.fromEntries(format.formatToParts(new Date()).map((p) => [p.type, p.value]));
        elemenJam.textContent = `${b.weekday}, ${b.day} ${b.month} · ${b.hour}.${b.minute}.${b.second}`;
    };

    tampilkanJam();
    setInterval(tampilkanJam, 1000);
}

// 3. Tombol Esc menutup sidebar yang dibuka lewat tombol menu (di halaman login tidak ada sidebar, jadi aman)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const kotak = document.getElementById('sidebar-toggle');
        if (kotak) kotak.checked = false;
    }
});

// 4. Tombol mata di kolom password: tampilkan/sembunyikan isinya
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-lihat-sandi]');
    if (!tombol) return;

    const kolom = tombol.parentElement.querySelector('input');
    const tampil = kolom.type === 'password';
    kolom.type = tampil ? 'text' : 'password';
    tombol.toggleAttribute('data-tampil', tampil);
});

// 5. Laporan: lipat/buka satu kelompok pelayanan, atau semuanya sekaligus
const aturKelompok = (kelompok, buka) => {
    kelompok.classList.toggle('tutup', !buka);
    kelompok.querySelector('[data-lipat-grup]')?.setAttribute('aria-expanded', String(buka));
};

document.addEventListener('click', (e) => {
    const kepala = e.target.closest('[data-lipat-grup]');
    if (kepala) {
        const kelompok = kepala.closest('.grup-laporan');
        aturKelompok(kelompok, kelompok.classList.contains('tutup'));
        return;
    }

    const semua = e.target.closest('[data-lipat-semua]');
    if (semua) {
        document.querySelectorAll('.grup-laporan').forEach((k) => aturKelompok(k, semua.dataset.lipatSemua === 'buka'));
    }
});