<?php

namespace App\Support;

/** Sketsa lokasi persil (SVG) untuk lampiran surat: persil utama + persil tetangga + arah utara + skala. */
class SketsaPersil
{
    /**
     * @param  array  $geom  Polygon geometry persil utama
     * @param  array<int, array>  $tetangga  daftar Polygon geometry persil sekitar
     */
    public static function svg(array $geom, array $tetangga = [], string $label = '', int $w = 600, int $h = 440): string
    {
        $ring = $geom['coordinates'][0];
        [$minLng, $minLat, $maxLng, $maxLat] = Geo::bbox($geom);
        $lat0 = ($minLat + $maxLat) / 2;
        $kx = cos(deg2rad($lat0));

        // meter per derajat
        $mx = 111320 * $kx;
        $my = 110574;

        $spanX = max(($maxLng - $minLng) * $mx, 1);
        $spanY = max(($maxLat - $minLat) * $my, 1);
        $pad = 50;
        $skala = min(($w - 2 * $pad) / ($spanX * 2.2), ($h - 2 * $pad) / ($spanY * 2.2)); // px per meter (zoom out agar tetangga terlihat)
        $cx = ($minLng + $maxLng) / 2;
        $cy = ($minLat + $maxLat) / 2;

        $px = fn (array $p) => round($w / 2 + ($p[0] - $cx) * $mx * $skala, 1);
        $py = fn (array $p) => round($h / 2 - ($p[1] - $cy) * $my * $skala, 1);
        $poly = fn (array $r) => implode(' ', array_map(fn ($p) => $px($p).','.$py($p), $r));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">';
        $svg .= '<rect x="0" y="0" width="'.$w.'" height="'.$h.'" fill="#ffffff" stroke="#999999" stroke-width="1"/>';

        foreach ($tetangga as $t) {
            if (! empty($t['coordinates'][0])) {
                $svg .= '<polygon points="'.$poly($t['coordinates'][0]).'" fill="#e9ecef" stroke="#868e96" stroke-width="1"/>';
            }
        }

        $svg .= '<polygon points="'.$poly($ring).'" fill="#cfe2ff" stroke="#0d6efd" stroke-width="2.5"/>';

        if ($label !== '') {
            $svg .= '<text x="'.($w / 2).'" y="'.($h / 2 + 4).'" font-size="13" font-family="sans-serif" text-anchor="middle" fill="#052c65">'
                .htmlspecialchars($label, ENT_XML1).'</text>';
        }

        // Arah utara
        $svg .= '<polygon points="'.($w - 40).',20 '.($w - 48).',48 '.($w - 32).',48" fill="#212529"/>';
        $svg .= '<text x="'.($w - 40).'" y="64" font-size="14" font-family="sans-serif" text-anchor="middle" fill="#212529">U</text>';

        // Skala (pilih panjang bulat yang muat ±120 px)
        $pilihan = [5, 10, 20, 50, 100, 200, 500, 1000];
        $meter = 5;
        foreach ($pilihan as $m) {
            if ($m * $skala <= 140) {
                $meter = $m;
            }
        }
        $len = round($meter * $skala, 1);
        $svg .= '<line x1="20" y1="'.($h - 24).'" x2="'.(20 + $len).'" y2="'.($h - 24).'" stroke="#212529" stroke-width="3"/>';
        $svg .= '<text x="20" y="'.($h - 32).'" font-size="12" font-family="sans-serif" fill="#212529">'.$meter.' m</text>';

        return $svg.'</svg>';
    }

    public static function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
