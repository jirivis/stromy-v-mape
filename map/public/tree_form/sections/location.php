<!-- 🟪 SEKCE 6 — LOKALIZACE -->
<h2>Lokalizace</h2>

<div class="location-actions">

    <button type="button" id="useDeviceLocation">
        📍 Použít polohu
    </button>

    <button type="button" id="startLocationAveraging">
        🎯 Průměrovat polohu
    </button>

    <button type="button" id="editLocationManually">
        ✏️ Ruční oprava
    </button>

</div>

<div
    id="locationStatus"
    class="location-status"
    hidden
></div>

<div
    id="manualLocationPanel"
    class="manual-location-panel"
    hidden
>
    <div class="manual-location-info">
        Klikni do mapy nebo přetáhni značku na požadované místo.
    </div>
	
	<button
    type="button"
    id="zoomToDeviceLocation"
    disabled
>
    🎯 Na moji polohu
</button>

    <div
        id="manualLocationMap"
        style="height: 400px; width: 100%; margin-top: 10px;"
    ></div>

    <div style="margin-top: 10px;">
        <button
            type="button"
            id="confirmManualLocation"
        >
            ✓ Použít tuto polohu
        </button>

        <button
            type="button"
            id="cancelManualLocation"
        >
            Zrušit
        </button>
    </div>
</div>

<div
    id="locationAveragingPanel"
    class="location-averaging-panel"
    hidden
>
    <div>
        <strong>🎯 Probíhá průměrování polohy</strong>
    </div>

    <div>
        Čas:
        <strong>
            <span id="locationAverageTime">0</span> s
        </strong>
    </div>

    <div>
        Získané vzorky:
        <strong>
            <span id="locationAverageCount">0</span>
        </strong>
    </div>

    <div>
        Aktuální přesnost:
        <strong>
            <span id="locationAverageAccuracy">—</span>
        </strong>
    </div>

    <div>
        Nejlepší přesnost:
        <strong>
            <span id="locationAverageBestAccuracy">—</span>
        </strong>
    </div>

    <div>
        Použitelné vzorky:
        <strong>
            <span id="locationAverageUsedCount">0</span>
        </strong>
    </div>

    <button
        type="button"
        id="stopLocationAveraging"
    >
        ✓ Ukončit a použít polohu
    </button>

    <button
        type="button"
        id="cancelLocationAveraging"
    >
        Zrušit
    </button>
</div>

<label for="lat_wgs84">
    Latitude (WGS84)
</label>

<input
    type="text"
    name="lat_wgs84"
    id="lat_wgs84"
    value="<?= htmlspecialchars(
        (string) $lat,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="lng_wgs84">
    Longitude (WGS84)
</label>

<input
    type="text"
    name="lng_wgs84"
    id="lng_wgs84"
    value="<?= htmlspecialchars(
        (string) $lng,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="y_sjtsk">
    Y (S-JTSK) (zadej s -)
</label>

<input
    type="text"
    name="y_sjtsk"
    id="y_sjtsk"
    value="<?= htmlspecialchars(
        (string) ($tree['y_sjtsk'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="x_sjtsk">
    X (S-JTSK) (zadej s -)
</label>

<input
    type="text"
    name="x_sjtsk"
    id="x_sjtsk"
    value="<?= htmlspecialchars(
        (string) ($tree['x_sjtsk'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>