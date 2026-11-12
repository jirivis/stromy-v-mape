"use strict";

/*
 * Aktuální režim podbarvení markerů.
 */
TreeApp.markerColorMode = "none";
// Výchozí velikost zachovává dosavadní chování podle obvodu kmene.
TreeApp.markerSizeMode = "girth";
TreeApp.showTreeLabels = true;
TreeApp.treeLabelMinZoom = 17;

TreeApp.getMarkerDimension = function (tree, mode) {
    const minimum = 20;
    const firstPositive = function (fields) {
        for (const field of fields) {
            const n = Number(tree[field]);
            if (Number.isFinite(n) && n > 0) return n;
        }
        return null;
    };
    const girth = firstPositive(["girth1_cm", "girth2_cm", "girth3_cm", "girth4_cm", "girth_cm"]);
    const dbh = firstPositive(["dbh1_cm", "dbh2_cm", "dbh3_cm", "dbh4_cm"]);
    if (mode === "none") return minimum;
    if (mode === "girth") return Math.max(minimum, (girth || (dbh ? dbh * Math.PI : 0)) * 0.3);
    if (mode === "dbh") return Math.max(minimum, (dbh || (girth ? girth / Math.PI : 0)) * Math.PI * 0.3);
    if (mode === "spread") return Math.max(minimum, Math.min(110, Number(tree.spread_m) > 0 ? Number(tree.spread_m) * 4 : minimum));
    if (mode === "height") return Math.max(minimum, Math.min(110, Number(tree.height) > 0 ? Number(tree.height) * 2 : minimum));
    return minimum;
};

// Styl popisku je vložen z JS, aby nebylo nutné upravovat další CSS soubor.
TreeApp.installTreeLabelStyles = function () {
    if (document.getElementById("tree-usr-label-styles")) return;
    const style = document.createElement("style");
    style.id = "tree-usr-label-styles";
    style.textContent = `
        .leaflet-tooltip.tree-usr-label {
            background: transparent; border: 0; box-shadow: none;
            padding: 0; margin: 0; color: #fff;
            font: 700 13px/1.2 Arial, sans-serif;
            white-space: nowrap; pointer-events: none;
            -webkit-text-stroke: 2.5px #202020;
            paint-order: stroke fill;
            text-shadow: 1px 1px 1px #202020, -1px -1px 1px #202020,
                         1px -1px 1px #202020, -1px 1px 1px #202020;
        }
        .leaflet-tooltip.tree-usr-label::before { display: none; }
    `;
    document.head.appendChild(style);
};

TreeApp.refreshTreeLabels = function () {
    if (!TreeApp.map) return;
    const enabled = TreeApp.showTreeLabels && TreeApp.map.getZoom() >= TreeApp.treeLabelMinZoom;
    Object.entries(TreeApp.markersById || {}).forEach(function ([treeId, marker]) {
        const tree = (TreeApp.treesById || {})[String(treeId)];
        const value = tree && tree.usr_id != null ? String(tree.usr_id).trim() : "";
        if (!enabled || !value || value === "0") {
            if (marker.getTooltip()) marker.unbindTooltip();
            return;
        }
        const size = marker._treeMarkerSize || 20;
        const offset = L.point(0, size / 2 - 1);
        if (marker.getTooltip()) marker.unbindTooltip();
        marker.bindTooltip(value, {
            permanent: true, direction: "bottom", offset: offset,
            className: "tree-usr-label", opacity: 1, interactive: false
        });
    });
};

TreeApp.refreshTreeMarkerSizes = function () {
    Object.entries(TreeApp.markersById || {}).forEach(function ([treeId, marker]) {
        const tree = (TreeApp.treesById || {})[String(treeId)];
        if (!tree || !marker) return;
        const size = TreeApp.getMarkerDimension(tree, TreeApp.markerSizeMode);
        marker._treeMarkerSize = size;
        if (tree.status === "removed") {
            marker.setIcon(TreeApp.createRemovedTreeMarkerIcon(size));
        } else {
            marker.setIcon(TreeApp.createTreeMarkerIcon(tree, size));
        }
    });
    TreeApp.refreshTreeLabels();
};


/*
 * Vrátí nejvyšší naléhavost zásahu.
 *
 * U0 je nejurgentnější.
 */
TreeApp.getTreeUrgency = function (tree) {

    const urgencyFields = [
        {
            value: tree.treatment_u0,
            urgency: "u0"
        },
        {
            value: tree.treatment_u1,
            urgency: "u1"
        },
        {
            value: tree.treatment_u2,
            urgency: "u2"
        },
        {
            value: tree.treatment_u3,
            urgency: "u3"
        }
    ];

    const found =
        urgencyFields.find(function (item) {

            return (
                item.value !== null &&
                item.value !== undefined &&
                String(item.value).trim() !== ""
            );

        });

    return found
        ? found.urgency
        : null;
};


/*
 * Barva podle data příští kontroly.
 */
TreeApp.getInspectionColor = function (dateValue) {

    if (
        dateValue === null ||
        dateValue === undefined ||
        String(dateValue).trim() === ""
    ) {
        return "#c8c8c8";
    }

    const dateString =
        String(dateValue).trim();

    const inspectionDate =
        new Date(
            dateString + "T00:00:00"
        );

    if (
        Number.isNaN(
            inspectionDate.getTime()
        )
    ) {
        return "#c8c8c8";
    }

    const today = new Date();

    today.setHours(
        0,
        0,
        0,
        0
    );

    const dayMilliseconds =
        24 * 60 * 60 * 1000;

    const daysUntilInspection =
        Math.ceil(
            (
                inspectionDate.getTime() -
                today.getTime()
            ) / dayMilliseconds
        );

    if (daysUntilInspection < 0) {
        return "#c62828";
    }

    if (daysUntilInspection <= 90) {
        return "#ef7d00";
    }

    return "#2e7d32";
};


/*
 * Vrátí barvu kolečka podle zvoleného režimu.
 */
TreeApp.getTreeMarkerColor = function (
    tree,
    mode
) {

    const numericColors = {
        1: "#2e7d32",
        2: "#1976d2",
        3: "#d6aa00",
        4: "#ef7d00",
        5: "#c62828"
    };

    if (mode === "vitality") {
        return (
            numericColors[
                Number(tree.vitality)
            ] || "#c8c8c8"
        );
    }

    if (mode === "tree_health") {
        return (
            numericColors[
                Number(tree.tree_health)
            ] || "#c8c8c8"
        );
    }

    if (mode === "stability") {
        return (
            numericColors[
                Number(tree.stability)
            ] || "#c8c8c8"
        );
    }
	
	if (mode === "landscape_value") {
    return (
        numericColors[
            Number(tree.landscape_value)
        ] || "#c8c8c8"
    );
}

    if (mode === "longevity") {

        const perspective =
            String(
                tree.longevity || ""
            )
                .trim()
                .toLowerCase();

        const perspectiveColors = {
            a: "#2e7d32",
            b: "#ef7d00",
            c: "#c62828"
        };

        return (
            perspectiveColors[perspective] ||
            "#c8c8c8"
        );
    }

    if (mode === "urgency") {

        const urgency =
            TreeApp.getTreeUrgency(tree);

        const urgencyColors = {
            u0: "#c62828",
            u1: "#ef7d00",
            u2: "#1976d2",
            u3: "#2e7d32"
        };

        return (
            urgencyColors[urgency] ||
            "#c8c8c8"
        );
    }

    if (mode === "next_inspection") {

        return TreeApp.getInspectionColor(
            tree.next_insp_at
        );

    }

    return "#ffffff";
};


/*
 * Vytvoří barevné kolečko s černým SVG.
 */
TreeApp.createTreeMarkerIcon = function (
    tree,
    size
) {

    let iconUrl =
        "/icons/angio.svg";

    if (
        tree.angio_gymno ===
        "gymnospermae"
    ) {
        iconUrl =
            "/icons/gymno.svg";
    }

    const backgroundColor =
        TreeApp.getTreeMarkerColor(
            tree,
            TreeApp.markerColorMode
        );

    const safeSize =
        Math.max(
            TreeApp.getMarkerDimension({}, "none"),
            Math.round(size)
        );

    return L.divIcon({

        className:
            "tree-map-div-icon",

        iconSize: [
            safeSize,
            safeSize
        ],

        iconAnchor: [
            safeSize / 2,
            safeSize / 2
        ],

        html: `
            <div
                class="tree-map-symbol"
                style="
                    width:${safeSize}px;
                    height:${safeSize}px;
                    --tree-marker-background:${backgroundColor};
                "
            >
                <img
                    src="${iconUrl}"
                    class="tree-map-symbol__svg"
                    alt=""
                    draggable="false"
                >
            </div>
        `
    });
};


TreeApp.createRemovedTreeMarkerIcon = function (size) {
    return L.divIcon({
        className: "tree-map-div-icon tree-marker-removed",
        html: `<div class="tree-map-removed-symbol" style="width:${size}px;height:${size}px;">
                 <img src="/icons/stump.svg" alt="">
               </div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -(size / 2)]
    });
};

/*
 * Překreslí všechny existující markery.
 */
TreeApp.refreshTreeMarkerColors = function () {

    Object.entries(
        TreeApp.markersById || {}
    ).forEach(function (
        [treeId, marker]
    ) {

        const tree =
            TreeApp.treesById[
                String(treeId)
            ];

        if (
            !tree ||
            !marker ||
            !marker._treeMarkerSize
        ) {
            return;
        }

        marker.setIcon(
            tree.status === "removed"
                ? TreeApp.createRemovedTreeMarkerIcon(marker._treeMarkerSize)
                : TreeApp.createTreeMarkerIcon(tree, marker._treeMarkerSize)
        );

    });

};


/*
 * Přidá rozbalovací volbu do mapy.
 */
TreeApp.initMarkerColorControl = function () {

    if (
        TreeApp.markerColorControl
    ) {
        return;
    }

    const MarkerColorControl =
        L.Control.extend({

            options: {
                position: "topright"
            },

            onAdd: function () {

                const container =
                    L.DomUtil.create(
                        "div",
                        "tree-marker-color-control leaflet-bar"
                    );

container.innerHTML = `

    <button
        type="button"
        class="tree-marker-color-control__toggle"
        aria-expanded="true"
        title="Barvení značek"
    >
        <span class="tree-marker-color-control__toggle-label">

            <span
                class="tree-marker-color-control__color-wheel"
                aria-hidden="true"
            ></span>

            <span class="tree-marker-color-control__text">
                Barvení symbolů
            </span>

        </span>

        <span
            class="tree-marker-color-control__toggle-icon"
        >
            ▲
        </span>
    </button>

    <div
        class="tree-marker-color-control__content"
    >

        <label
            class="tree-marker-color-control__label"
            for="treeMarkerColorMode"
        >
            Podbarvit značky podle atributu:
        </label>

        <select
            id="treeMarkerColorMode"
            class="tree-marker-color-control__select"
        >
            <option value="none">
                Bez podbarvení
            </option>

            <option value="vitality">
                Vitalita
            </option>

            <option value="tree_health">
                Zdravotní stav
            </option>

            <option value="stability">
                Stabilita
            </option>

            <option value="landscape_value">
                Sadovnická hodnota
            </option>

            <option value="longevity">
                Perspektiva
            </option>

            <option value="urgency">
                Naléhavost zásahu
            </option>

            <option value="next_inspection">
                Příští kontrola
            </option>
        </select>

        <label class="tree-marker-color-control__label" for="treeMarkerSizeMode"
               style="display:block;margin-top:10px;">
            Velikost značek podle:
        </label>
        <select id="treeMarkerSizeMode" class="tree-marker-color-control__select">
            <option value="girth">Obvod kmene (dosavadní)</option>
            <option value="dbh">Průměr kmene</option>
            <option value="spread">Šířka koruny</option>
            <option value="height">Výška stromu</option>
            <option value="none">Bez pravidla (stejná velikost)</option>
        </select>
        <label style="display:flex;align-items:center;gap:6px;margin-top:10px;cursor:pointer;">
            <input type="checkbox" id="treeMarkerShowLabels" checked>
            Čísla stromů (usr_id)
        </label>
        <small style="display:block;margin-top:4px;">Popisky od přiblížení 17.</small>

    </div>
`;

                L.DomEvent.disableClickPropagation(
                    container
                );

                L.DomEvent.disableScrollPropagation(
                    container
                );

                const select =
                    container.querySelector(
                        "#treeMarkerColorMode"
                    );
					
const toggleButton =
    container.querySelector(
        ".tree-marker-color-control__toggle"
    );

const colorContent =
    container.querySelector(
        ".tree-marker-color-control__content"
    );

const toggleIcon =
    container.querySelector(
        ".tree-marker-color-control__toggle-icon"
    );

/*
 * Výchozí stav po načtení mapy:
 * panel barvení je sbalený.
 */
container.classList.add(
    "tree-marker-color-control--collapsed"
);

colorContent.hidden = true;

toggleButton.setAttribute(
    "aria-expanded",
    "false"
);

toggleIcon.textContent = "▼";

toggleButton.addEventListener(
    "click",
    function (event) {

        event.preventDefault();
        event.stopPropagation();

        const isCollapsed =
            container.classList.toggle(
                "tree-marker-color-control--collapsed"
            );

        colorContent.hidden =
            isCollapsed;

        toggleButton.setAttribute(
            "aria-expanded",
            isCollapsed
                ? "false"
                : "true"
        );

        toggleIcon.textContent =
            isCollapsed
                ? "▼"
                : "▲";
    }
);
					
                select.value =
                    TreeApp.markerColorMode;

                select.addEventListener(
                    "change",
                    function () {

                        TreeApp.markerColorMode =
                            select.value;

                        TreeApp.refreshTreeMarkerColors();
						
                    }
                );

                const sizeSelect = container.querySelector("#treeMarkerSizeMode");
                sizeSelect.value = TreeApp.markerSizeMode;
                sizeSelect.addEventListener("change", function () {
                    TreeApp.markerSizeMode = sizeSelect.value;
                    TreeApp.refreshTreeMarkerSizes();
                });

                const labelsCheckbox = container.querySelector("#treeMarkerShowLabels");
                labelsCheckbox.checked = TreeApp.showTreeLabels;
                labelsCheckbox.addEventListener("change", function () {
                    TreeApp.showTreeLabels = labelsCheckbox.checked;
                    TreeApp.refreshTreeLabels();
                });

                return container;
            }

        });

    TreeApp.markerColorControl =
        new MarkerColorControl();

    TreeApp.markerColorControl.addTo(
        TreeApp.map
    );
    TreeApp.installTreeLabelStyles();
    if (!TreeApp._treeLabelsZoomListenerInstalled) {
        TreeApp.map.on("zoomend", TreeApp.refreshTreeLabels);
        TreeApp._treeLabelsZoomListenerInstalled = true;
    }
};

console.log("markers.js inicializován");