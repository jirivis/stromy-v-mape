"use strict";

TreeApp.buildCarePlanCell = function (tree) {

    const levels = [
        {
            urgency: "U0",
            treatments: tree.treatment_u0,
            cost: tree.costs_tu0
        },
        {
            urgency: "U1",
            treatments: tree.treatment_u1,
            cost: tree.costs_tu1
        },
        {
            urgency: "U2",
            treatments: tree.treatment_u2,
            cost: tree.costs_tu2
        },
        {
            urgency: "U3",
            treatments: tree.treatment_u3,
            cost: tree.costs_tu3
        }
    ];

    const rows = levels
        .filter(function (item) {

            const hasTreatments =
                item.treatments !== null &&
                item.treatments !== undefined &&
                String(item.treatments).trim() !== "";

            const hasCost =
                item.cost !== null &&
                item.cost !== undefined &&
                String(item.cost).trim() !== "" &&
                Number(item.cost) !== 0;

            return hasTreatments || hasCost;
        })
        .map(function (item) {

            const treatments =
                item.treatments
                    ? TreeApp.popupEscapeHtml(
                        item.treatments
                    )
                    : "bez specifikace";

            const cost =
                item.cost !== null &&
                item.cost !== undefined &&
                String(item.cost).trim() !== "" &&
                Number(item.cost) !== 0
                    ? Number(item.cost)
                        .toLocaleString("cs-CZ") +
                      " Kč"
                    : "";

            return `
                <div
                    class="
                        tree-care-plan__row
                        tree-care-plan__row--${item.urgency.toLowerCase()}
                    "
                >
                    <span class="tree-care-plan__urgency">
                        ${item.urgency}
                    </span>

                    <span class="tree-care-plan__treatments">
                        ${treatments}
                    </span>

                    ${
                        cost
                            ? `
                                <span class="tree-care-plan__cost">
                                    ${cost}
                                </span>
                            `
                            : ""
                    }
                </div>
            `;
        });

const nextInspectionRaw =
    tree.next_insp_at
        ? String(tree.next_insp_at).trim()
        : "";

if (nextInspectionRaw) {

    /*
     * Datum ve formátu YYYY-MM-DD.
     * Přidání T00:00:00 zabrání posunu data
     * způsobenému časovým pásmem.
     */
    const inspectionDate =
        new Date(
            nextInspectionRaw + "T00:00:00"
        );

    const today = new Date();

    today.setHours(
        0,
        0,
        0,
        0
    );

    const millisecondsPerDay =
        24 * 60 * 60 * 1000;

    const daysUntilInspection =
        Math.ceil(
            (
                inspectionDate.getTime() -
                today.getTime()
            ) / millisecondsPerDay
        );

    let inspectionClass =
        "tree-care-plan__inspection--future";

    let inspectionStatus =
        "více než 90 dní";

    if (daysUntilInspection < 0) {

        inspectionClass =
            "tree-care-plan__inspection--overdue";

        inspectionStatus =
            "po termínu";

    } else if (daysUntilInspection <= 90) {

        inspectionClass =
            "tree-care-plan__inspection--soon";

        inspectionStatus =
            daysUntilInspection === 0
                ? "dnes"
                : `za ${daysUntilInspection} dní`;

    }

    const formattedInspectionDate =
        Number.isNaN(inspectionDate.getTime())
            ? nextInspectionRaw
            : inspectionDate.toLocaleDateString(
                "cs-CZ"
            );

    rows.push(`
        <div
            class="
                tree-care-plan__inspection
                ${inspectionClass}
            "
            title="${TreeApp.popupEscapeAttribute(
                inspectionStatus
            )}"
        >
            <span>
                Kontrola
            </span>

            <strong>
                ${TreeApp.popupEscapeHtml(
                    formattedInspectionDate
                )}
            </strong>
        </div>
    `);
}

    return rows.length > 0
        ? `
            <div class="tree-care-plan">
                ${rows.join("")}
            </div>
        `
        : "-";
};

/**
 * Načte stromy z API, vytvoří markery,
 * popupy a řádky tabulky.
 */
TreeApp.loadTrees = function () {

    fetch("api/trees_geojson.php")

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    "API vrátilo HTTP " + response.status
                );
            }

            return response.json();

        })

        .then(function (data) {

            // ------------------------------------------------
            // 1. Kontrola a příprava tabulky
            // ------------------------------------------------

            const dataTablesAvailable =
    typeof window.jQuery !== "undefined" &&
    typeof window.jQuery.fn !== "undefined" &&
    typeof window.jQuery.fn.DataTable !== "undefined";

if (
    dataTablesAvailable &&
    window.jQuery.fn.DataTable.isDataTable("#tabulka")
) {
    window.jQuery("#tabulka")
        .DataTable()
        .destroy();
}

            const tbody =
                document.querySelector("#tabulka tbody");

            if (!tbody) {
                throw new Error(
                    "Nebyl nalezen tbody tabulky #tabulka."
                );
            }

            tbody.innerHTML = "";

            // Vyčištění starých vazeb
            TreeApp.markersById = {};
            TreeApp.rowsById = {};
			TreeApp.treesById = {};

            TreeApp.selectedTreeId = null;
            TreeApp.selectedMarker = null;

            // ------------------------------------------------
            // 2. Vytvoření GeoJSON vrstvy
            // ------------------------------------------------
			
			/*
 * Odstranění předchozí clusterové vrstvy
 * při opakovaném načtení stromů po editaci.
 */
if (TreeApp.markerClusterGroup) {

    TreeApp.markerClusterGroup.clearLayers();

    if (
        TreeApp.map.hasLayer(
            TreeApp.markerClusterGroup
        )
    ) {
        TreeApp.map.removeLayer(
            TreeApp.markerClusterGroup
        );
    }

}


/*
 * Skupina pro klastrování stromů.
 */
TreeApp.markerClusterGroup =
    L.markerClusterGroup({

        /*
         * Při najetí na cluster nezobrazovat
         * polygon jeho rozsahu.
         */
        showCoverageOnHover: false,

        /*
         * Kliknutí přiblíží mapu
         * na stromy uvnitř clusteru.
         */
        zoomToBoundsOnClick: true,

        /*
         * Stromy na totožném nebo velmi blízkém
         * místě se na maximálním zoomu rozvějíří.
         */
        spiderfyOnMaxZoom: true,

        /*
         * Od zoomu 19 už zobrazovat
         * jednotlivé stromy bez clusterů.
         */
        disableClusteringAtZoom: 19,

        /*
         * Menší hodnota vytváří více menších clusterů.
         */
        maxClusterRadius: 40,

        /*
         * Zrychlení při větším množství stromů.
         */
        removeOutsideVisibleBounds: true
    });
	
	/*
 * Při opakovaném načtení odstraníme
 * předchozí vrstvu stromů z mapy.
 */
if (
    TreeApp.treeLayer
    && TreeApp.map.hasLayer(
        TreeApp.treeLayer
    )
) {
    TreeApp.map.removeLayer(
        TreeApp.treeLayer
    );
}

TreeApp.treeLayer = null;

            TreeApp.treeLayer = L.geoJSON(data, {

                // --------------------------------------------
                // Vzhled markeru
                // --------------------------------------------

                pointToLayer: function (feature, latlng) {

                    const p = feature.properties;

                    const size = TreeApp.getMarkerDimension(
                        p, TreeApp.markerSizeMode
                    );

                    const icon = TreeApp.isTreeStump(p)
                        ? TreeApp.createRemovedTreeMarkerIcon(size)
                        : TreeApp.createTreeMarkerIcon(p, size);

const marker =
    L.marker(
        latlng,
        {
            icon: icon
        }
    );

marker._treeMarkerSize = size;

TreeApp.markersById[
    String(p.id)
] = marker;

return marker;

},

// --------------------------------------------
// Zpracování každého stromu
// --------------------------------------------

onEachFeature: function (
    feature,
    layer
) {

                    const p = feature.properties;
                    const treeId = String(p.id);
					TreeApp.treesById[treeId] = p;

                    // ----------------------------------------
                    // Průměry kmene
                    // ----------------------------------------

const hasTreeValue = function (value) {

    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return false;
    }

    const normalizedValue =
        String(value)
            .trim()
            .replace(",", ".");

    /*
     * U průměrů a obvodů považujeme nulu
     * za nezadanou hodnotu.
     */
    if (
        normalizedValue !== "" &&
        !Number.isNaN(Number(normalizedValue)) &&
        Number(normalizedValue) === 0
    ) {
        return false;
    }

    return true;
};

const uniqueTreeValues = function (values) {
    return [...new Set(
        values
            .filter(hasTreeValue)
            .map(function (value) {
                return String(value).trim();
            })
    )];
};


/*
 * Průměry kmene.
 * Podporujeme nové i starší názvy polí.
 */
const dbhValues = uniqueTreeValues([
    p.dbh1_cm,
    p.dbh2_cm,
    p.dbh3_cm,
    p.dbh4_cm,
    p.dbh5_cm,

    p.dbh_cm,
    p.dbh,

    p.dbh_1,
    p.dbh_2,
    p.dbh_3,
    p.dbh_4,
    p.dbh_5
]);


/*
 * Obvody kmene.
 */
const girthValues = uniqueTreeValues([
    p.girth_cm,

    p.girth1_cm,
    p.girth2_cm,
    p.girth3_cm,
    p.girth4_cm,
    p.girth5_cm,

    p.girth,

    p.girth_1,
    p.girth_2,
    p.girth_3,
    p.girth_4,
    p.girth_5
]);


const dbhLabel =
    dbhValues.length > 1
        ? "Průměry kmene"
        : "Průměr kmene";

const girthLabel =
    girthValues.length > 1
        ? "Obvody kmene"
        : "Obvod kmene";

const dbhString =
    dbhValues.join(", ");

const girthString =
    girthValues.join(", ");

                    // ----------------------------------------
                    // Popup
                    // ----------------------------------------

const popupContent =
    TreeApp.buildPopupContent(
        p,
        dbhValues,
        dbhLabel,
        dbhString,
        girthValues,
        girthLabel,
        girthString
    );

layer.bindPopup(
    popupContent,
    TreeApp.treePopupOptions
);

                    /*
                     * Kliknutí na marker:
                     * najde odpovídající řádek tabulky.
                     */
                    layer.on(
                        "click",
                        function () {

                            TreeApp.selectTree(
                                treeId,
                                {
                                    zoomToMarker: false,
                                    openPopup: false,
                                    scrollToRow: true
                                }
                            );

                        }
                    );

                    // ----------------------------------------
                    // Řádek tabulky
                    // ----------------------------------------

                    const row =
                        document.createElement("tr");

                    row.style.cursor = "pointer";
                    row.dataset.treeId = treeId;

                    TreeApp.rowsById[treeId] = row;

                    const photoCell =
                        TreeApp.buildPhotoCell(p);
						
					const carePlanCell =
						TreeApp.buildCarePlanCell(p);	

                    row.innerHTML = `
    <td>${p.usr_id || "-"}</td>

    <td>${p.species || "-"}</td>

    <td>${p.czech || "-"}</td>

    <td>${girthString || "-"}</td>

    <td>${p.height || "-"}</td>

    <td>${p.phy_age || "-"}</td>

    <td>
        ${
            TreeApp.popupHasDisplayValue(p.vitality)
                ? TreeApp.buildAssessmentBadge(
                    p.vitality,
                    "vitality"
                )
                : "-"
        }
    </td>

    <td>
        ${
            TreeApp.popupHasDisplayValue(p.tree_health)
                ? TreeApp.buildAssessmentBadge(
                    p.tree_health,
                    "tree_health"
                )
                : "-"
        }
    </td>

    <td>
        ${
            TreeApp.popupHasDisplayValue(p.stability)
                ? TreeApp.buildAssessmentBadge(
                    p.stability,
                    "stability"
                )
                : "-"
        }
    </td>
	
	<td>
    ${
        TreeApp.popupHasDisplayValue(p.longevity)
            ? TreeApp.buildAssessmentBadge(
                p.longevity,
                "longevity"
            )
            : "-"
    }
	</td>

<td>${p.ecological_value || "-"}</td>

<td>
    ${
        TreeApp.popupHasDisplayValue(p.landscape_value)
            ? TreeApp.buildAssessmentBadge(
                p.landscape_value,
                "landscape_value"
            )
            : "-"
    }
</td>

<td>${carePlanCell}</td>
<td>${photoCell}</td>
`;

                    /*
                     * Jednoduchý klik:
                     * zvýraznění a přiblížení stromu.
                     */
                    row.addEventListener(
                        "click",
                        function (event) {

                            if (
                                event.target.closest("a")
                            ) {
                                return;
                            }

                            TreeApp.selectTree(
                                treeId,
                                {
                                    zoomToMarker: true,
                                    openPopup: false,
                                    scrollToRow: false
                                }
                            );

                        }
                    );

                    /*
                     * Dvojklik:
                     * přiblížení a otevření popupu.
                     */
                    row.addEventListener(
                        "dblclick",
                        function (event) {

                            if (
                                event.target.closest("a")
                            ) {
                                return;
                            }

                            TreeApp.selectTree(
                                treeId,
                                {
                                    zoomToMarker: true,
                                    openPopup: true,
                                    scrollToRow: false
                                }
                            );

                        }
                    );

                    tbody.appendChild(row);

                }

});


/*
 * GeoJSON se nepřidává přímo do mapy,
 * ale nejprve do clusterové skupiny.
 */
TreeApp.markerClusterGroup.addLayer(
    TreeApp.treeLayer
);

TreeApp.markerClusterGroup.addTo(
    TreeApp.map
);

TreeApp.initMarkerColorControl();
TreeApp.refreshTreeLabels();

TreeApp.initTreeFilterControl();

            // ------------------------------------------------
            // 3. Aktivace DataTables
            // ------------------------------------------------

if (dataTablesAvailable) {

    TreeApp.installDataTableTreeFilter();

    TreeApp.initDataTable();

    TreeApp.enableSelectionAfterTableDraw();

            } else {

                console.warn(
                    "jQuery nebo DataTables nejsou dostupné. " +
                    "Stromy a běžná tabulka se načtou bez funkcí DataTables."
                );

            }

TreeApp.applyTreeFilters();

/*
 * Pokud jsme se na mapu vrátili po uložení
 * nebo úpravě stromu, zaměříme právě tento strom.
 */
const focusTreeId =
    new URLSearchParams(
        window.location.search
    ).get("focus_tree");

TreeApp.focusTreeId =
    focusTreeId || null;

TreeApp.focusTreeId =
    focusTreeId || null;

if (focusTreeId) {

    TreeApp.selectTree(
        focusTreeId,
        {
            zoomToMarker: true,
            openPopup: false,
            scrollToRow: true
        }
    );
	
	/*
 * focus_tree je pouze jednorázový pokyn.
 * Po zaměření stromu jej odstraníme z URL
 * bez nového načtení stránky.
 */
const cleanUrl =
    new URL(window.location.href);

cleanUrl.searchParams.delete(
    "focus_tree"
);

window.history.replaceState(
    {},
    "",
    cleanUrl.toString()
);

}

            console.log(
                "trees.js: stromy načteny",
                data.features
                    ? data.features.length
                    : 0
            );
			
        })

        .catch(function (error) {

            console.error(
                "Chyba při načítání stromů:",
                error
            );

            const tbody =
                document.querySelector("#tabulka tbody");

            if (tbody) {

                tbody.innerHTML = `
                    <tr>
                        <td colspan="13">
                            Stromy se nepodařilo načíst.
                        </td>
                    </tr>
                `;

            }

        });

};

/*
 * Změna stavu stromu z iframe editoru.
 *
 * tree_remove.php a tree_restore.php
 * po úspěšné změně pošlou zprávu
 * rodičovské mapě.
 */
window.addEventListener(
    "message",
    function (event) {

        /*
         * Přijímáme pouze zprávy
         * ze stejné domény.
         */
        if (
            event.origin !==
            window.location.origin
        ) {
            return;
        }

        if (
            !event.data
            || event.data.type !==
                "tree-status-changed"
        ) {
            return;
        }

        console.log(
            "Změněn stav stromu:",
            event.data.treeId
        );

        /*
         * Znovu načteme data,
         * markery a tabulku.
         */
        TreeApp.loadTrees();
    }
);

console.log("trees.js inicializován");