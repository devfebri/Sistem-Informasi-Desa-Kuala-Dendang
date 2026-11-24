// Modul peta (Leaflet + Leaflet.draw + Turf). Hanya dimuat di halaman yang memakainya.
import L from 'leaflet';
window.L = L; // Leaflet.draw membutuhkan global L
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';
import area from '@turf/area';
import intersect from '@turf/intersect';
import { featureCollection } from '@turf/helpers';
import { kml as kmlKeGeoJson } from '@tmcw/togeojson';

export const WARNA_STATUS = {
    SHM: '#198754', HGB: '#0d6efd', SKT: '#fd7e14',
    Girik: '#6f42c1', 'Letter C': '#d63384', 'Belum bersertifikat': '#dc3545',
};
export const WARNA_PENGGUNAAN = {
    pemukiman: '#e03131', sawah: '#94d82d', kebun: '#2f9e44', ladang: '#e8a317', lainnya: '#868e96',
};

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/** Peta dasar: OpenStreetMap + Satelit (Esri World Imagery). */
export function buatPeta(el, { pusat, zoom }) {
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap',
    });
    const satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: 'Tiles &copy; Esri',
    });
    const map = L.map(el, { layers: [osm] }).setView(pusat, zoom);
    const kontrol = L.control.layers({ OpenStreetMap: osm, 'Satelit (Esri)': satelit }, {}).addTo(map);

    return { map, kontrol };
}

function bboxGeometri(geom) {
    const pts = geom.coordinates[0];
    const lngs = pts.map((p) => p[0]);
    const lats = pts.map((p) => p[1]);
    return [Math.min(...lngs), Math.min(...lats), Math.max(...lngs), Math.max(...lats)];
}

/** Ambil geometry Polygon pertama dari GeoJSON (Feature/FeatureCollection/Polygon/MultiPolygon). */
export function ambilPolygon(gj) {
    if (!gj) return null;
    if (gj.type === 'FeatureCollection') {
        for (const f of gj.features) { const g = ambilPolygon(f); if (g) return g; }
        return null;
    }
    if (gj.type === 'Feature') return ambilPolygon(gj.geometry);
    if (gj.type === 'Polygon') return gj;
    if (gj.type === 'MultiPolygon') return { type: 'Polygon', coordinates: gj.coordinates[0] };
    return null;
}

/** Peta statis (halaman detail). */
export function petaStatis({ el, pusat, zoom, geometry }) {
    const { map } = buatPeta(el, { pusat, zoom });
    if (geometry) {
        const layer = L.geoJSON(geometry, { style: { color: '#0d6efd', weight: 2, fillOpacity: 0.3 } }).addTo(map);
        map.fitBounds(layer.getBounds(), { maxZoom: 19, padding: [20, 20] });
    }
    return map;
}

/**
 * Peta form: gambar/edit polygon, luas otomatis (Turf), peringatan tumpang tindih, import GeoJSON/KML.
 */
export function formPolygon({ el, pusat, zoom, input, luasInput, luasTeks, peringatan, initial, excludeId, geojsonUrl, fileInput }) {
    const { map } = buatPeta(el, { pusat, zoom });
    const grup = new L.FeatureGroup().addTo(map);
    const tetangga = L.geoJSON(null, { style: { color: '#6c757d', weight: 1, dashArray: '4', fillOpacity: 0.08 }, interactive: false }).addTo(map);
    const gaya = { color: '#0d6efd', weight: 2, fillOpacity: 0.3 };

    map.addControl(new L.Control.Draw({
        draw: {
            polygon: { allowIntersection: false, showArea: false, shapeOptions: gaya }, // showArea=false: hindari bug leaflet-draw 1.0.4
            polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false,
        },
        edit: { featureGroup: grup },
    }));

    let xhr = null;

    function geometriSaatIni() {
        const layer = grup.getLayers()[0];
        return layer ? layer.toGeoJSON().geometry : null;
    }

    function tampilkanPeringatan(daftar) {
        const $p = $(peringatan);
        if (!daftar.length) { $p.addClass('d-none').empty(); return; }
        $p.removeClass('d-none').html(
            '<i class="bi bi-exclamation-triangle-fill me-1"></i>Polygon <strong>tumpang tindih</strong> dengan persil: '
            + daftar.map((d) => `<span class="badge text-bg-warning">${esc(d.no)} (${d.luas.toLocaleString('id-ID')} m²)</span>`).join(' '),
        );
    }

    function cekTumpangTindih(geom) {
        xhr?.abort();
        if (!geom) { tampilkanPeringatan([]); tetangga.clearLayers(); return; }
        const bb = bboxGeometri(geom);
        xhr = $.getJSON(geojsonUrl, { bbox: bb.join(','), exclude: excludeId || '' }).done((fc) => {
            tetangga.clearLayers().addData(fc);
            const a = { type: 'Feature', properties: {}, geometry: geom };
            const hasil = [];
            fc.features.forEach((f) => {
                try {
                    const irisan = intersect(featureCollection([a, { type: 'Feature', properties: {}, geometry: f.geometry }]));
                    const luas = irisan ? area(irisan) : 0;
                    if (luas > 1) hasil.push({ no: f.properties.no_persil, luas: Math.round(luas) }); // >1 m² (abaikan sisi yang bersentuhan)
                } catch (e) { /* geometri tetangga tidak valid: lewati */ }
            });
            tampilkanPeringatan(hasil);
        });
    }

    function perbarui() {
        const geom = geometriSaatIni();
        $(input).val(geom ? JSON.stringify(geom) : '');
        const luas = geom ? Math.round(area({ type: 'Feature', properties: {}, geometry: geom }) * 100) / 100 : 0;
        $(luasInput).val(luas || '');
        $(luasTeks).text(luas ? `${luas.toLocaleString('id-ID')} m²` : '—');
        cekTumpangTindih(geom);
    }

    function setGeometri(geom, zoomKe = true) {
        grup.clearLayers();
        if (!geom) { perbarui(); return; }
        const layer = L.geoJSON(geom, { style: gaya }).getLayers()[0];
        grup.addLayer(layer);
        if (zoomKe) map.fitBounds(layer.getBounds(), { maxZoom: 19, padding: [20, 20] });
        perbarui();
    }

    map.on(L.Draw.Event.CREATED, (e) => { grup.clearLayers(); grup.addLayer(e.layer); perbarui(); });
    map.on(L.Draw.Event.EDITED, perbarui);
    map.on(L.Draw.Event.DELETED, perbarui);

    // Import GeoJSON / KML
    $(fileInput).on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const teks = String(reader.result);
                const gj = /\.kml$/i.test(file.name)
                    ? kmlKeGeoJson(new DOMParser().parseFromString(teks, 'text/xml'))
                    : JSON.parse(teks);
                const geom = ambilPolygon(gj);
                if (!geom) throw new Error('Tidak ada Polygon pada berkas.');
                setGeometri(geom);
                window.toastr.success('Polygon berhasil diimpor.');
            } catch (err) {
                window.toastr.error('Berkas tidak dapat dibaca: ' + err.message);
            }
            $(fileInput).val('');
        };
        reader.readAsText(file);
    });

    if (initial) setGeometri(initial);

    return { map, setGeometri };
}

/**
 * Peta Desa: layer bidang tanah (dimuat per bounding box), batas dusun, legenda, popup AJAX, pencarian.
 */
export function petaDesa({ el, pusat, zoom, urls, filter, mode, tandaiMilik = false }) {
    const { map, kontrol } = buatPeta(el, { pusat, zoom });
    let modeWarna = mode();
    let xhr = null;
    let fokusId = null;
    const layerById = {};

    const warnaFitur = (p) => (modeWarna === 'status' ? WARNA_STATUS[p.status_hak] : WARNA_PENGGUNAAN[p.penggunaan_lahan]) || '#6c757d';

    // Persil milik pengguna (peta warga) diberi garis tebal hitam.
    const gayaFitur = (f) => (tandaiMilik && f.properties.milik_saya
        ? { color: '#000000', weight: 3.5, fillColor: warnaFitur(f.properties), fillOpacity: 0.6 }
        : { color: warnaFitur(f.properties), weight: 1.5, fillColor: warnaFitur(f.properties), fillOpacity: 0.45 });

    const bidang = L.geoJSON(null, {
        style: (f) => gayaFitur(f),
        onEachFeature: (f, layer) => {
            layerById[f.properties.id] = layer;
            layer.on('click', (e) => {
                const pop = L.popup({ maxWidth: 320 }).setLatLng(e.latlng).setContent('Memuat...').openOn(map);
                $.ajax({ url: urls.popup.replace('__ID__', f.properties.id), dataType: 'html' })
                    .done((html) => pop.setContent(html))
                    .fail(() => pop.setContent('Gagal memuat detail.'));
            });
            layer.on('mouseover', () => layer.setStyle({ weight: 3 }));
            layer.on('mouseout', () => bidang.resetStyle(layer));
        },
    }).addTo(map);

    // Batas dusun
    const batas = L.geoJSON(null, {
        style: { color: '#212529', weight: 2, dashArray: '6 4', fill: false },
        onEachFeature: (f, layer) => layer.bindTooltip(esc(f.properties.nama), { sticky: true }),
        interactive: true,
    }).addTo(map);
    kontrol.addOverlay(bidang, 'Bidang tanah');
    kontrol.addOverlay(batas, 'Batas dusun');
    $.getJSON(urls.wilayah).done((fc) => batas.addData(fc));

    // Legenda
    const legenda = L.control({ position: 'bottomright' });
    legenda.onAdd = () => { legenda._div = L.DomUtil.create('div', 'peta-legenda'); return legenda._div; };
    function gambarLegenda() {
        const peta = modeWarna === 'status' ? WARNA_STATUS : WARNA_PENGGUNAAN;
        legenda._div.innerHTML = `<strong>${modeWarna === 'status' ? 'Status hak' : 'Penggunaan lahan'}</strong>`
            + Object.entries(peta).map(([k, w]) => `<div><i style="background:${w}"></i>${esc(k)}</div>`).join('')
            + (tandaiMilik ? '<div><i style="background:#fff;border:3px solid #000"></i>Milik Anda</div>' : '');
    }
    legenda.addTo(map);
    gambarLegenda();

    function muat() {
        xhr?.abort();
        const b = map.getBounds();
        const params = { ...filter(), bbox: [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].join(',') };
        xhr = $.getJSON(urls.geojson, params).done((fc) => {
            bidang.clearLayers();
            Object.keys(layerById).forEach((k) => delete layerById[k]);
            bidang.addData(fc);
            if (fc.truncated) window.toastr.info('Terlalu banyak persil di area ini. Perbesar peta (zoom in) untuk melihat semuanya.');
            if (fokusId && layerById[fokusId]) {
                layerById[fokusId].fire('click', { latlng: layerById[fokusId].getBounds().getCenter() });
                fokusId = null;
            }
        });
    }

    let timer = null;
    map.on('moveend', () => { clearTimeout(timer); timer = setTimeout(muat, 400); });
    muat();

    return {
        map,
        muat,
        ubahMode() { modeWarna = mode(); gambarLegenda(); bidang.setStyle((f) => gayaFitur(f)); },
        fokus(id, bbox) {
            fokusId = id;
            map.fitBounds([[bbox[1], bbox[0]], [bbox[3], bbox[2]]], { maxZoom: 19, padding: [40, 40] });
            if (layerById[id]) { layerById[id].fire('click', { latlng: layerById[id].getBounds().getCenter() }); fokusId = null; }
        },
    };
}

window.Peta = { buatPeta, petaStatis, formPolygon, petaDesa };
