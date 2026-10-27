<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa library tambahan.
 * Hanya butuh ekstensi PHP "zip" (cek dengan: php -m | findstr zip).
 *
 * Cara pakai:
 *   $x = new XlsxWriter('Rekap SPM');
 *   $x->lebarKolom([30, 40, 12]);
 *   $x->baris(['Judul'], XlsxWriter::GAYA_JUDUL);
 *   $x->baris(['Kolom A', 'Kolom B'], XlsxWriter::GAYA_HEADER);
 *   $x->baris(['isi 1', 'isi 2'], XlsxWriter::GAYA_SEL);
 *   $path = $x->simpanSementara(); // lokasi file .xlsx di folder temp
 */
class XlsxWriter
{
    // Nomor gaya sel (urutannya mengikuti cellXfs di styles.xml di bawah)
    public const GAYA_NORMAL = 0;
    public const GAYA_JUDUL = 1;   // tebal, besar
    public const GAYA_INFO = 2;    // miring, abu-abu
    public const GAYA_HEADER = 3;  // tebal, berlatar, bergaris, rata tengah
    public const GAYA_SEL = 4;     // bergaris, teks dibungkus, rata atas
    public const GAYA_TENGAH = 5;  // seperti GAYA_SEL tapi rata tengah

    private string $namaSheet;

    /** @var array<int, array{sel: array, gaya: int|array}> */
    private array $baris = [];

    /** @var array<int, float> */
    private array $lebar = [];

    private ?int $barisBeku = null;
    private ?int $barisFilter = null;

    public function __construct(string $namaSheet = 'Sheet1')
    {
        // Nama sheet Excel: maksimal 31 karakter dan tidak boleh memuat \ / ? * [ ] : '
        $bersih = preg_replace('/[\\\\\/\?\*\[\]:\']/', '', $namaSheet) ?? 'Sheet1';
        $this->namaSheet = mb_substr($bersih, 0, 31) ?: 'Sheet1';
    }

    // Lebar tiap kolom (urut dari kolom A), satuan karakter
    public function lebarKolom(array $lebar): void
    {
        $this->lebar = array_values($lebar);
    }

    // Tambah satu baris. $gaya boleh satu angka (semua sel) atau array angka (per sel)
    public function baris(array $sel, int|array $gaya = self::GAYA_NORMAL): void
    {
        $this->baris[] = ['sel' => array_values($sel), 'gaya' => $gaya];
    }

    // Bekukan semua baris sampai baris header (nomor baris dimulai dari 1)
    public function bekukan(int $nomorBarisHeader): void
    {
        $this->barisBeku = $nomorBarisHeader;
    }

    // Pasang filter otomatis di baris header sampai baris terakhir
    public function filterOtomatis(int $nomorBarisHeader): void
    {
        $this->barisFilter = $nomorBarisHeader;
    }

    // Tulis file .xlsx ke folder temp dan kembalikan lokasinya
    public function simpanSementara(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'spm');
        if ($path === false) {
            throw new RuntimeException('Tidak bisa membuat file sementara.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file Excel (cek ekstensi PHP zip).');
        }

        $zip->addFromString('[Content_Types].xml', $this->xmlTipeIsi());
        $zip->addFromString('_rels/.rels', $this->xmlRelasiUtama());
        $zip->addFromString('xl/workbook.xml', $this->xmlWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xmlRelasiWorkbook());
        $zip->addFromString('xl/styles.xml', $this->xmlGaya());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xmlSheet());

        if (! $zip->close()) {
            throw new RuntimeException('Gagal menulis file Excel.');
        }

        return $path;
    }

    // ---------- Bagian-bagian XML ----------

    private const XML_AWAL = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

    private function xmlTipeIsi(): string
    {
        return self::XML_AWAL
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function xmlRelasiUtama(): string
    {
        return self::XML_AWAL
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xmlWorkbook(): string
    {
        $nama = $this->esc($this->namaSheet);
        $xml = self::XML_AWAL
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$nama.'" sheetId="1" r:id="rId1"/></sheets>';

        // Nama tersembunyi yang biasa dipasang Excel untuk area filter
        if ($rentang = $this->rentangFilter()) {
            $xml .= '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">'
                .'\''.$nama.'\'!'.$this->rentangMutlak($rentang).'</definedName></definedNames>';
        }

        return $xml.'</workbook>';
    }

    private function xmlRelasiWorkbook(): string
    {
        return self::XML_AWAL
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xmlGaya(): string
    {
        return self::XML_AWAL
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // Font: 0 biasa, 1 tebal, 2 judul, 3 info
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><name val="Calibri"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            .'</fonts>'
            // Latar: 0 dan 1 wajib ada (none dan gray125), 2 hijau muda untuk header
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDCEBE7"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            // Garis: 0 tanpa garis, 1 garis tipis semua sisi
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFBBBBBB"/></left><right style="thin"><color rgb="FFBBBBBB"/></right>'
            .'<top style="thin"><color rgb="FFBBBBBB"/></top><bottom style="thin"><color rgb="FFBBBBBB"/></bottom>'
            .'<diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            // Gaya sel: urutannya = konstanta GAYA_* di atas
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="top" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function xmlSheet(): string
    {
        $xml = self::XML_AWAL
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';

        // Bekukan baris header supaya tetap terlihat saat digulir
        if ($this->barisBeku !== null) {
            $xml .= '<sheetViews><sheetView workbookViewId="0">'
                .'<pane ySplit="'.$this->barisBeku.'" topLeftCell="A'.($this->barisBeku + 1).'" activePane="bottomLeft" state="frozen"/>'
                .'<selection pane="bottomLeft"/>'
                .'</sheetView></sheetViews>';
        } else {
            $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        }

        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->lebar) {
            $xml .= '<cols>';
            foreach ($this->lebar as $i => $w) {
                $n = $i + 1;
                $xml .= '<col min="'.$n.'" max="'.$n.'" width="'.$w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($this->baris as $i => $b) {
            $no = $i + 1;
            if (! $b['sel']) {
                $xml .= '<row r="'.$no.'"/>';

                continue;
            }

            $xml .= '<row r="'.$no.'">';
            foreach ($b['sel'] as $k => $nilai) {
                $gaya = is_array($b['gaya']) ? ($b['gaya'][$k] ?? self::GAYA_NORMAL) : $b['gaya'];
                $xml .= $this->xmlSel($this->huruf($k).$no, $nilai, (int) $gaya);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if ($rentang = $this->rentangFilter()) {
            $xml .= '<autoFilter ref="'.$rentang.'"/>';
        }

        // Cetak: kertas A4 lanskap, lebar muat satu halaman
        $xml .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';

        return $xml;
    }

    private function xmlSel(string $ref, mixed $nilai, int $gaya): string
    {
        if ($nilai === null || $nilai === '') {
            return '<c r="'.$ref.'" s="'.$gaya.'"/>';
        }

        if (is_int($nilai) || is_float($nilai)) {
            return '<c r="'.$ref.'" s="'.$gaya.'"><v>'.$nilai.'</v></c>';
        }

        return '<c r="'.$ref.'" s="'.$gaya.'" t="inlineStr"><is><t xml:space="preserve">'
            .$this->esc((string) $nilai).'</t></is></c>';
    }

    // ---------- Fungsi bantu ----------

    // Rentang filter, contoh "A4:P104". Null kalau filter tidak dipasang atau belum ada isinya.
    private function rentangFilter(): ?string
    {
        if ($this->barisFilter === null || ! isset($this->baris[$this->barisFilter - 1])) {
            return null;
        }

        $jumlahKolom = count($this->baris[$this->barisFilter - 1]['sel']);
        if ($jumlahKolom === 0) {
            return null;
        }

        return 'A'.$this->barisFilter.':'.$this->huruf($jumlahKolom - 1).count($this->baris);
    }

    // "A4:P104" menjadi "$A$4:$P$104"
    private function rentangMutlak(string $rentang): string
    {
        return preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $rentang) ?? $rentang;
    }

    // Nomor kolom (mulai dari 0) menjadi huruf: 0 = A, 25 = Z, 26 = AA
    private function huruf(int $indeks): string
    {
        $hasil = '';
        $n = $indeks + 1;
        while ($n > 0) {
            $sisa = ($n - 1) % 26;
            $hasil = chr(65 + $sisa).$hasil;
            $n = intdiv($n - 1, 26);
        }

        return $hasil;
    }

    // Aman untuk XML: buang karakter kontrol terlarang lalu escape tanda khusus
    private function esc(string $teks): string
    {
        $teks = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $teks) ?? '';

        return htmlspecialchars($teks, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}