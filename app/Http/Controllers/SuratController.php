<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\DokumenPengajuan;
use App\Models\PengajuanSurat;
use App\Support\SketsaPersil;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** PDF surat dan berkas persyaratan (akses berotorisasi). */
class SuratController extends Controller
{
    public function pdf(Request $request, PengajuanSurat $pengajuan)
    {
        $this->otorisasi($request, $pengajuan);
        abort_unless($pengajuan->suratTerbit(), 404, 'Surat belum diterbitkan.');

        $pengajuan->load(['pemohon', 'jenisSurat', 'bidangTanah.wilayah', 'penyetuju:id,name']);
        $bidang = $pengajuan->bidangTanah;

        $riwayat = $bidang->peralihanHak()
            ->with(['pemilikLama:id,nik,nama', 'pemilikBaru:id,nik,nama'])
            ->orderBy('tanggal')->orderBy('id')->get();

        $sketsa = $bidang->geojson
            ? SketsaPersil::dataUri(SketsaPersil::svg($bidang->geojson, $this->tetangga($bidang), 'Persil '.$bidang->no_persil))
            : null;

        $qr = 'data:image/svg+xml;base64,'.base64_encode(
            QrCode::format('svg')->size(110)->margin(0)->generate(route('validasi', $pengajuan->kode_validasi))
        );

        $nama = 'Surat-'.str_replace('/', '-', $pengajuan->nomor_surat).'.pdf';

        return Pdf::loadView($pengajuan->jenisSurat->template_view, [
            'p' => $pengajuan, 'pemohon' => $pengajuan->pemohon, 'bidang' => $bidang,
            'riwayat' => $riwayat, 'sketsa' => $sketsa, 'qr' => $qr, 'desa' => config('desa'),
        ])->setPaper('a4')->stream($nama);
    }

    public function dokumen(Request $request, DokumenPengajuan $dokumen)
    {
        $dokumen->load('pengajuan:id,nik_pemohon');
        $this->otorisasi($request, $dokumen->pengajuan);
        abort_unless(Storage::exists($dokumen->path_file), 404, 'Berkas tidak ditemukan.');

        return Storage::response($dokumen->path_file);
    }

    private function otorisasi(Request $request, PengajuanSurat $p): void
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->isKades() || ($u->isWarga() && $u->nik === $p->nik_pemohon), 403);
    }

    /** Persil di sekitar (untuk konteks sketsa), dibatasi 40 buah. */
    private function tetangga(BidangTanah $b): array
    {
        if ($b->min_lng === null) {
            return [];
        }
        $dx = ($b->max_lng - $b->min_lng) * 1.5 + 0.0002;
        $dy = ($b->max_lat - $b->min_lat) * 1.5 + 0.0002;

        return BidangTanah::query()->select(['id', 'geojson'])
            ->whereKeyNot($b->id)->whereNotNull('geojson')
            ->where('max_lng', '>=', $b->min_lng - $dx)->where('min_lng', '<=', $b->max_lng + $dx)
            ->where('max_lat', '>=', $b->min_lat - $dy)->where('min_lat', '<=', $b->max_lat + $dy)
            ->limit(40)->get()->pluck('geojson')->all();
    }
}
