"use strict";

TreeApp.initMap = function () {

    // --------------------------------------------------------
    // 1. Základní mapa
    // --------------------------------------------------------

    TreeApp.map = L.map("map", {
        center: [49.8, 15.5],
        zoom: 8,
        maxZoom: 22
    });

    // --------------------------------------------------------
    // 2. Podkladové vrstvy
    // --------------------------------------------------------

    const osm = L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 22,
            maxNativeZoom: 19,
            attribution: "&copy; OpenStreetMap"
        }
    ).addTo(TreeApp.map);

    const ortofoto = L.tileLayer.wms(
        "https://ags.cuzk.gov.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer",
        {
            layers: "0",
            format: "image/jpeg",
            transparent: false,
            version: "1.3.0",
            maxZoom: 22,
            attribution: "&copy; ČÚZK"
        }
    );
	
	const katastralniMapa = L.tileLayer.wms(
    "https://services.cuzk.gov.cz/wms/local-KM-wms.asp",
    {
        layers: "KN",
        format: "image/png",
        transparent: true,
        version: "1.3.0",
        maxZoom: 22,
        attribution: "&copy; ČÚZK"
    }
);

const whiteBackground = L.gridLayer({
    attribution: ""
});

whiteBackground.createTile = function () {

    const tile = document.createElement("div");

    tile.style.width = "256px";
    tile.style.height = "256px";
    tile.style.background = "#ffffff";

    return tile;
};

TreeApp.layersControl = L.control.layers(
    {
        "OpenStreetMap": osm,
        "Ortofoto ČÚZK": ortofoto,
        "Bílý podklad": whiteBackground
    },
    {
        "Katastrální mapa ČÚZK": katastralniMapa
    }
).addTo(TreeApp.map);

    // --------------------------------------------------------
    // 3. Tlačítko „+ strom“
    // --------------------------------------------------------

    L.Control.AddTree = L.Control.extend({

        onAdd: function () {

            const button = L.DomUtil.create(
                "button",
                "leaflet-bar add-tree-btn"
            );

            button.type = "button";
            button.innerHTML = "+ strom";
            button.title = "Přidat nový strom";

            L.DomEvent.disableClickPropagation(button);

            L.DomEvent.on(button, "click", function (event) {

                L.DomEvent.stopPropagation(event);
                L.DomEvent.preventDefault(event);

                window.open(
                    "tree_new.php",
                    "_blank"
                );

            });

            return button;
        }

    });

    L.control.addTree = function (options) {
        return new L.Control.AddTree(options);
    };

    L.control.addTree({
        position: "topleft"
    }).addTo(TreeApp.map);
	
	// --------------------------------------------------------
// 3a. Tlačítko „Celý projekt“
// --------------------------------------------------------

const zoomAllControl = L.control({
    position: "topleft"
});

zoomAllControl.onAdd = function () {

    const container = L.DomUtil.create(
        "div",
        "leaflet-bar leaflet-control"
    );

    const button = L.DomUtil.create(
        "a",
        "",
        container
    );

    button.innerHTML = "⛶";
    button.href = "#";
    button.title = "Zobrazit celý projekt";
    button.setAttribute(
        "aria-label",
        "Zobrazit celý projekt"
    );

    L.DomEvent.disableClickPropagation(container);

    L.DomEvent.on(
        button,
        "click",
        function (event) {

            L.DomEvent.stopPropagation(event);
            L.DomEvent.preventDefault(event);

            if (
                typeof TreeApp.fitMapToFilteredTrees
                === "function"
            ) {
                TreeApp.fitMapToFilteredTrees();
            }

        }
    );

    return container;
};

zoomAllControl.addTo(TreeApp.map);

    // --------------------------------------------------------
    // 4. Pravý klik do mapy
    // --------------------------------------------------------

    TreeApp.map.on("contextmenu", function (event) {

        const lat = event.latlng.lat;
        const lng = event.latlng.lng;

        window.open(
            "tree_new.php?lat=" +
            encodeURIComponent(lat) +
            "&lng=" +
            encodeURIComponent(lng),
            "_blank"
        );

    });

// --------------------------------------------------------
// 5. Průběžná poloha zařízení
// --------------------------------------------------------

let deviceLocationMarker = null;
let deviceAccuracyCircle = null;
let deviceWatchId = null;
let lastDeviceLatLng = null;
let lastDeviceAccuracy = null;


// --------------------------------------------------------
// Aktualizace markeru zařízení
// --------------------------------------------------------

function updateDeviceLocation(
    lat,
    lng,
    accuracy
) {

    const latLng = L.latLng(
        lat,
        lng
    );

    lastDeviceLatLng = latLng;
    lastDeviceAccuracy = accuracy;

    /*
     * Marker aktuální polohy.
     */
    if (!deviceLocationMarker) {

        deviceLocationMarker =
            L.circleMarker(
                latLng,
                {
                    radius: 8,
                    weight: 3,
                    fillOpacity: 0.9
                }
            )
            .addTo(TreeApp.map)
            .bindTooltip(
                "Moje poloha"
            );

    } else {

        deviceLocationMarker
            .setLatLng(latLng);

    }


    /*
     * Kruh přesnosti GNSS.
     */
    if (
        Number.isFinite(accuracy)
        && accuracy > 0
    ) {

        if (!deviceAccuracyCircle) {

            deviceAccuracyCircle =
                L.circle(
                    latLng,
                    {
                        radius: accuracy,
                        weight: 1,
                        fillOpacity: 0.08
                    }
                )
                .addTo(TreeApp.map);

        } else {

            deviceAccuracyCircle
                .setLatLng(latLng);

            deviceAccuracyCircle
                .setRadius(accuracy);

        }

    }

}


// --------------------------------------------------------
// Spuštění průběžného sledování polohy
// --------------------------------------------------------

function startDeviceLocationWatch() {

    if (deviceWatchId !== null) {
        return;
    }

    if (!navigator.geolocation) {
        console.warn(
            "Geolokace není v tomto prohlížeči dostupná."
        );
        return;
    }

    deviceWatchId =
        navigator.geolocation.watchPosition(

            function (position) {

                updateDeviceLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy
                );

            },

            function (error) {

                console.warn(
                    "Nepodařilo se získat polohu zařízení:",
                    error
                );

            },

            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 10000
            }

        );

}


// --------------------------------------------------------
// 6. Tlačítko „Moje poloha“
// --------------------------------------------------------

const locationControl = L.control({
    position: "topright"
});

locationControl.onAdd = function () {

    const container = L.DomUtil.create(
        "div",
        "leaflet-bar leaflet-control"
    );

    const button = L.DomUtil.create(
        "a",
        "",
        container
    );

    button.innerHTML = "🎯";
    button.href = "#";
    button.title = "Moje poloha";
    button.setAttribute(
        "aria-label",
        "Moje poloha"
    );

    L.DomEvent.disableClickPropagation(container);

    L.DomEvent.on(
        button,
        "click",
        function (event) {

            L.DomEvent.stopPropagation(event);
            L.DomEvent.preventDefault(event);

            /*
             * Pokud už polohu známe,
             * pouze na ni přesuneme mapu.
             */
            if (lastDeviceLatLng) {

                TreeApp.map.setView(
                    lastDeviceLatLng,
                    19
                );

                return;
            }

            /*
             * Pokud ještě fix nemáme,
             * spustíme sledování polohy.
             */
            startDeviceLocationWatch();

        }
    );

    return container;
};

locationControl.addTo(TreeApp.map);


/*
 * Sledování spustíme rovnou po načtení mapy.
 * Mapa se přitom sama na zařízení nepřesouvá.
 */
startDeviceLocationWatch();

    if (typeof ResizeObserver !== "undefined") {
        let resizeFrame;
        const mapResizeObserver = new ResizeObserver(() => {
            cancelAnimationFrame(resizeFrame);
            resizeFrame = requestAnimationFrame(() => {
                TreeApp.map.invalidateSize({ pan: false });
            });
        });
        mapResizeObserver.observe(TreeApp.map.getContainer());
        TreeApp.map.once("unload", () => {
            mapResizeObserver.disconnect();
            cancelAnimationFrame(resizeFrame);
        });
    }

    console.log("map.js inicializován");

};