// ===== SPM Track: JavaScript kecil (tema, jam, tombol Esc) =====

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

// 3. Tombol Esc menutup sidebar yang dibuka lewat tombol menu
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('sidebar-toggle').checked = false;
    }
});