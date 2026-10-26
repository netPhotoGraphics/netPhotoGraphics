/* Nova – photo map. Loads only what is visible; the server groups photos into grid clusters. */
(function () {
    'use strict';
    var el = document.getElementById('nova-geomap');
    if (!el || !window.L) { return; }
    var cfg = JSON.parse(el.getAttribute('data-config'));
    var statusEl = document.getElementById('nova-geomap-status');
    var t = cfg.i18n;

    var map = L.map(el, {preferCanvas: true, worldCopyJump: false, minZoom: 2});
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: cfg.attribution
    }).addTo(map);

    var b = cfg.bounds;
    map.fitBounds([[b[0], b[1]], [b[2], b[3]]], {padding: [30, 30], maxZoom: 14});

    var pin = L.divIcon({
        className: 'nova-pin',
        html: '<svg width="30" height="42" viewBox="0 0 30 42" aria-hidden="true"><path d="M15 1C7.3 1 1 7.2 1 14.8 1 25 15 41 15 41s14-16 14-26.2C29 7.2 22.7 1 15 1z" fill="#1f78c8" stroke="#fff" stroke-width="2"/><circle cx="15" cy="14.5" r="5" fill="#fff"/></svg>',
        iconSize: [30, 42], iconAnchor: [15, 41], popupAnchor: [0, -38]
    });
    var layer = L.layerGroup().addTo(map);
    var ctrl = null, timer = null;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function r(n) { return Math.round(n * 1000) / 1000; }
    function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
    function url(params) {
        var u = cfg.endpoint + (cfg.endpoint.indexOf('?') > -1 ? '&' : '?');
        return u + Object.keys(params).map(function (k) { return k + '=' + encodeURIComponent(params[k]); }).join('&');
    }

    function load() {
        var bb = map.getBounds(); /* exactly the visible area, so the count matches what can be seen */
        var p = {nova_geo: 'view', s: r(bb.getSouth()), w: r(bb.getWest()), n: r(bb.getNorth()), e: r(bb.getEast()), z: map.getZoom()};
        if (ctrl) { ctrl.abort(); }
        ctrl = window.AbortController ? new AbortController() : null;
        statusEl.textContent = t.loading;
        fetch(url(p), {credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined})
            .then(function (res) { if (!res.ok) { throw new Error(res.status); } return res.json(); })
            .then(draw)
            .catch(function (err) { if (!err || err.name !== 'AbortError') { statusEl.textContent = t.error; } });
    }

    function draw(data) {
        layer.clearLayers();
        data.pts.forEach(function (p) {
            var c = p[2], ll = [p[0], p[1]], m;
            if (c === 1) {
                m = L.marker(ll, {icon: pin});
                m.on('click', function () { openItems(ll, ll, m); });
            } else {
                var size = c < 10 ? 34 : c < 100 ? 40 : c < 1000 ? 46 : 54;
                m = L.marker(ll, {icon: L.divIcon({
                    className: 'nova-cluster',
                    html: '<span>' + (c >= 10000 ? Math.round(c / 1000) + 'k' : fmt(c)) + '</span>',
                    iconSize: [size, size]
                })});
                m.on('click', function () {
                    var sw = [p[3], p[4]], ne = [p[5], p[6]];
                    var degenerate = Math.abs(ne[0] - sw[0]) < 0.00002 && Math.abs(ne[1] - sw[1]) < 0.00002;
                    if (degenerate || map.getZoom() >= map.getMaxZoom() - 1) {
                        openItems(sw, ne, m);
                    } else {
                        map.fitBounds([sw, ne], {padding: [40, 40], maxZoom: map.getMaxZoom()});
                    }
                });
            }
            layer.addLayer(m);
        });
        statusEl.textContent = t.shown + ' ' + fmt(data.total);
    }

    function openItems(sw, ne, anchor) {
        var pad = 0.00003;
        var p = {nova_geo: 'items', s: sw[0] - pad, w: sw[1] - pad, n: ne[0] + pad, e: ne[1] + pad};
        var popup = L.popup({maxWidth: 380, className: 'nova-popup', autoPanPadding: [20, 20]})
            .setLatLng(anchor.getLatLng()).setContent('<div class="nova-popup__msg">' + esc(t.loading) + '</div>').openOn(map);
        fetch(url(p), {credentials: 'same-origin'})
            .then(function (res) { return res.json(); })
            .then(function (d) {
                var n = d.items.length, cols = n === 1 ? 1 : n === 2 ? 2 : 3;
                var cell = n === 1 ? 220 : 104;
                var h = '<div class="nova-popup__grid" style="width:' + (cols * cell + (cols - 1) * 6) + 'px;grid-template-columns:repeat(' + cols + ',' + cell + 'px)">' +
                    d.items.map(function (i) {
                        return '<a href="' + esc(i.url) + '" title="' + esc(i.title) + '"><img width="' + cell + '" height="' + cell + '" src="' + esc(i.thumb) + '" alt="' + esc(i.title) + '"></a>';
                    }).join('') + '</div>';
                if (n === 1) {
                    h += '<div class="nova-popup__title"><a href="' + esc(d.items[0].url) + '">' + esc(d.items[0].title) + '</a></div>';
                }
                if (d.total > n) {
                    h += '<p class="nova-popup__more">' + esc(t.more.replace('%s', fmt(d.total - n))) + '</p>';
                }
                popup.setContent(h);
                popup.update();
            })
            .catch(function () { popup.setContent(esc(t.error)); });
    }

    map.on('moveend', function () {
        clearTimeout(timer);
        timer = setTimeout(load, 250);
    });
    load();
})();
