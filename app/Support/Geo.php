<?php

namespace App\Support;

/** Utilitas geometri GeoJSON sederhana (Polygon) untuk sisi server. */
class Geo
{
    private const RADIUS = 6378137.0; // WGS84, sama dengan Turf.js

    /**
     * Normalisasi input GeoJSON (string/array; Feature, FeatureCollection, Polygon, MultiPolygon)
     * menjadi geometry Polygon. Kembali null jika tidak valid.
     *
     * @return array{type:string, coordinates:array}|null
     */
    public static function normalizePolygon(mixed $input): ?array
    {
        if (is_string($input)) {
            $input = json_decode($input, true);
        }
        if (! is_array($input)) {
            return null;
        }

        $geom = match ($input['type'] ?? null) {
            'FeatureCollection' => collect($input['features'] ?? [])
                ->map(fn ($f) => $f['geometry'] ?? null)
                ->first(fn ($g) => in_array($g['type'] ?? null, ['Polygon', 'MultiPolygon'], true)),
            'Feature' => $input['geometry'] ?? null,
            default => $input,
        };

        if (! is_array($geom)) {
            return null;
        }
        if (($geom['type'] ?? null) === 'MultiPolygon') {
            $geom = ['type' => 'Polygon', 'coordinates' => $geom['coordinates'][0] ?? []];
        }
        if (($geom['type'] ?? null) !== 'Polygon' || empty($geom['coordinates']) || ! is_array($geom['coordinates'])) {
            return null;
        }

        $rings = [];
        foreach ($geom['coordinates'] as $ring) {
            if (! is_array($ring)) {
                return null;
            }
            $pts = [];
            foreach ($ring as $p) {
                if (! is_array($p) || count($p) < 2 || ! is_numeric($p[0]) || ! is_numeric($p[1])) {
                    return null;
                }
                [$lng, $lat] = [(float) $p[0], (float) $p[1]];
                if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
                    return null;
                }
                $pts[] = [$lng, $lat];
            }
            if (count($pts) >= 3 && $pts[0] !== end($pts)) {
                $pts[] = $pts[0]; // tutup ring
            }
            if (count($pts) < 4) {
                return null;
            }
            $rings[] = $pts;
        }

        return ['type' => 'Polygon', 'coordinates' => $rings];
    }

    /** Luas geodesik (m²), rumus yang sama dengan @turf/area. Cincin pertama = luar, sisanya lubang. */
    public static function area(array $polygon): float
    {
        $rings = $polygon['coordinates'] ?? [];
        if (! $rings) {
            return 0.0;
        }

        $total = abs(self::ringArea($rings[0]));
        foreach (array_slice($rings, 1) as $hole) {
            $total -= abs(self::ringArea($hole));
        }

        return max(0.0, $total);
    }

    /** @return array{0:float,1:float,2:float,3:float} [minLng, minLat, maxLng, maxLat] */
    public static function bbox(array $polygon): array
    {
        $lngs = array_column($polygon['coordinates'][0], 0);
        $lats = array_column($polygon['coordinates'][0], 1);

        return [min($lngs), min($lats), max($lngs), max($lats)];
    }

    private static function ringArea(array $coords): float
    {
        $n = count($coords);
        if ($n <= 2) {
            return 0.0;
        }

        $total = 0.0;
        for ($i = 0; $i < $n; $i++) {
            if ($i === $n - 2) {
                [$l, $m, $u] = [$n - 2, $n - 1, 0];
            } elseif ($i === $n - 1) {
                [$l, $m, $u] = [$n - 1, 0, 1];
            } else {
                [$l, $m, $u] = [$i, $i + 1, $i + 2];
            }
            $total += (deg2rad($coords[$u][0]) - deg2rad($coords[$l][0])) * sin(deg2rad($coords[$m][1]));
        }

        return $total * self::RADIUS * self::RADIUS / 2;
    }
}
