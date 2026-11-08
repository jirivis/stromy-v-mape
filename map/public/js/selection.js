"use strict";

// ------------------------------------------------------------
// Zrušení zvýraznění řádku
// ------------------------------------------------------------

TreeApp.clearSelectedRows = function () {

    document
        .querySelectorAll("#tabulka tbody tr.is-selected")
        .forEach(function (row) {
            row.classList.remove("is-selected");
        });

};


// ------------------------------------------------------------
// Zvýraznění řádku
// ------------------------------------------------------------

TreeApp.highlightRow = function (
    treeId,
    scrollToRow = true
) {

    TreeApp.clearSelectedRows();

    const row = TreeApp.rowsById[String(treeId)];

    if (!row) {
        return;
    }

    row.classList.add("is-selected");

    if (scrollToRow) {

        row.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

    }

};


// ------------------------------------------------------------
// Zrušení zvýraznění markeru
// ------------------------------------------------------------

TreeApp.clearSelectedMarker = function () {

    if (!TreeApp.selectedMarker) {
        return;
    }

    TreeApp.selectedMarker.setZIndexOffset(0);

    const markerElement =
        TreeApp.selectedMarker.getElement();

    if (markerElement) {

        markerElement.classList.remove(
            "tree-marker--selected"
        );

    }

    TreeApp.selectedMarker = null;

};

// ------------------------------------------------------------
// Úplné zrušení výběru stromu
// ------------------------------------------------------------

TreeApp.clearSelection = function () {

    TreeApp.clearSelectedRows();
    TreeApp.clearSelectedMarker();

    TreeApp.selectedTreeId = null;

};

// ------------------------------------------------------------
// Zvýraznění markeru
// ------------------------------------------------------------

TreeApp.highlightMarker = function (marker) {

    TreeApp.clearSelectedMarker();

    if (!marker) {
        return;
    }

    TreeApp.selectedMarker = marker;

    marker.setZIndexOffset(1000);

    const markerElement = marker.getElement();

    if (markerElement) {

        markerElement.classList.add(
            "tree-marker--selected"
        );

    }

};


// ------------------------------------------------------------
// Nalezení správné stránky DataTables
// ------------------------------------------------------------

TreeApp.showTreeRowInTable = function (
    treeId,
    scrollToRow = true
) {

    const id = String(treeId);
    const row = TreeApp.rowsById[id];

    if (!row) {
        return;
    }

    if (
        !TreeApp.dataTable ||
        !$.fn.DataTable.isDataTable("#tabulka")
    ) {

        TreeApp.highlightRow(
            id,
            scrollToRow
        );

        return;
    }

    const dataTable = TreeApp.dataTable;
    const rowIndex = dataTable.row(row).index();

    if (rowIndex === undefined) {

        TreeApp.highlightRow(
            id,
            scrollToRow
        );

        return;
    }

    /*
     * Získáme pořadí řádků po aplikovaném
     * řazení a filtrování.
     */
    const visibleIndexes = dataTable
        .rows({
            order: "applied",
            search: "applied"
        })
        .indexes()
        .toArray();

    const position = visibleIndexes.indexOf(rowIndex);

    /*
     * Pokud je strom odfiltrován vyhledáváním,
     * řádek nelze na aktuálním výsledku zobrazit.
     */
    if (position === -1) {

        console.warn(
            "Vybraný strom je skrytý aktuálním filtrem tabulky."
        );

        return;
    }

    const pageLength = dataTable.page.len();

    const targetPage = Math.floor(
        position / pageLength
    );

    const currentPage = dataTable.page();

    if (targetPage !== currentPage) {

        dataTable
            .page(targetPage)
            .draw("page");

    }

    window.requestAnimationFrame(function () {

        TreeApp.highlightRow(
            id,
            scrollToRow
        );

    });

};


// ------------------------------------------------------------
// Hlavní funkce pro výběr stromu
// ------------------------------------------------------------

TreeApp.selectTree = function (
    treeId,
    options = {}
) {

    const settings = {
        zoomToMarker: false,
        openPopup: false,
        scrollToRow: true,
        ...options
    };

    const id = String(treeId);

    const marker =
        TreeApp.markersById[id];

    TreeApp.selectedTreeId = id;
	
	/*
 * Pokud už je editor otevřený,
 * přepneme jej na právě vybraný strom.
 */
if (
    typeof TreeApp.switchOpenEditorToTree
    === "function"
) {

    TreeApp.switchOpenEditorToTree(id);

}

    TreeApp.showTreeRowInTable(
        id,
        settings.scrollToRow
    );

    TreeApp.highlightMarker(marker);

    if (!marker) {
        return;
    }

    if (settings.zoomToMarker) {

        TreeApp.map.setView(
            marker.getLatLng(),
            18
        );

    }

    if (settings.openPopup) {

        marker.openPopup();

    }

};

// ------------------------------------------------------------
// Synchronizace viditelných markerů s filtrem DataTables
// ------------------------------------------------------------

TreeApp.syncMapWithTableFilter = function () {

    if (
        !TreeApp.dataTable ||
        !TreeApp.treeLayer
    ) {
        return;
    }

    /*
     * Získáme všechny řádky odpovídající aktuálnímu
     * hledání. Nejen řádky na právě otevřené stránce.
     */
    const filteredRows = TreeApp.dataTable
        .rows({
            search: "applied"
        })
        .nodes()
        .toArray();

    const visibleTreeIds = new Set();

    filteredRows.forEach(function (row) {

        if (row.dataset.treeId) {
            visibleTreeIds.add(
                String(row.dataset.treeId)
            );
        }

    });

    /*
     * Projdeme všechny markery.
     * Odpovídající filtru zobrazíme,
     * ostatní z GeoJSON vrstvy dočasně odebereme.
     */
    Object.entries(
        TreeApp.markersById
    ).forEach(function ([treeId, marker]) {

        const shouldBeVisible =
            visibleTreeIds.has(String(treeId));

        const isVisible =
            TreeApp.treeLayer.hasLayer(marker);

        if (
            shouldBeVisible &&
            !isVisible
        ) {

            TreeApp.treeLayer.addLayer(marker);

        } else if (
            !shouldBeVisible &&
            isVisible
        ) {

            TreeApp.treeLayer.removeLayer(marker);

        }

    });
    /*
     * Pokud aktuální filtr skryl vybraný strom,
     * zrušíme také jeho zvýraznění.
     */
    if (
        TreeApp.selectedTreeId !== null &&
        !visibleTreeIds.has(
            String(TreeApp.selectedTreeId)
        )
    ) {

        TreeApp.clearSelection();

    }
};

// ------------------------------------------------------------
// Přiblížení mapy na stromy odpovídající filtru
// ------------------------------------------------------------

TreeApp.fitMapToFilteredTrees = function () {

    if (
        !TreeApp.dataTable ||
        !TreeApp.map
    ) {
        return;
    }

    const filteredRows = TreeApp.dataTable
        .rows({
            search: "applied"
        })
        .nodes()
        .toArray();

    const visibleMarkers = [];

    filteredRows.forEach(function (row) {

        const treeId = row.dataset.treeId;

        if (!treeId) {
            return;
        }

        const marker =
            TreeApp.markersById[String(treeId)];

        if (marker) {
            visibleMarkers.push(marker);
        }

    });

    // Žádný výsledek – mapou nehýbeme
    if (visibleMarkers.length === 0) {
        return;
    }

    // Jeden strom – přiblížíme přímo na něj
    if (visibleMarkers.length === 1) {

        TreeApp.map.setView(
            visibleMarkers[0].getLatLng(),
            18
        );

        return;
    }

    // Více stromů – zobrazíme jejich společný rozsah
    const markerGroup =
        L.featureGroup(visibleMarkers);

    const bounds = markerGroup.getBounds();

    if (!bounds.isValid()) {
        return;
    }

    TreeApp.map.fitBounds(
        bounds,
        {
            padding: [40, 40],
            maxZoom: 18
        }
    );

};

// ------------------------------------------------------------
// Zachování zvýraznění po překreslení DataTables
// ------------------------------------------------------------

TreeApp.enableSelectionAfterTableDraw = function () {

    if (!TreeApp.dataTable) {
        return;
    }

    /*
     * Odstraníme případné starší posluchače.
     * To je důležité, pokud se data někdy načtou znovu.
     */
    TreeApp.dataTable.off(
        ".treeSelection"
    );

    // --------------------------------------------------------
    // Překreslení tabulky
    // --------------------------------------------------------

    TreeApp.dataTable.on(
        "draw.treeSelection",
        function () {

            /*
             * Aktualizujeme viditelnost markerů.
             * Tato událost probíhá také při stránkování,
             * proto zde mapu nepřibližujeme.
             */
            TreeApp.syncMapWithTableFilter();

            /*
             * Obnovíme zvýraznění vybraného řádku,
             * pokud je na právě zobrazené stránce.
             */
            if (
                TreeApp.selectedTreeId !== null
            ) {

                TreeApp.highlightRow(
                    TreeApp.selectedTreeId,
                    false
                );

            }

        }
    );

    // --------------------------------------------------------
    // Změna vyhledávání
    // --------------------------------------------------------

    TreeApp.dataTable.on(
        "search.treeSelection",
        function () {

            /*
             * DataTables nejprve zpracuje hledání
             * a následně překreslí tabulku.
             * Krátké odložení zajistí, že pracujeme
             * už s aktuálním výsledkem.
             */
            window.setTimeout(
    function () {

        TreeApp.syncMapWithTableFilter();

        /*
         * Po návratu z uložení stromu
         * nesmí filtrování přepsat zaměření
         * na právě uložený strom.
         */
if (!TreeApp.focusTreeId) {
    TreeApp.fitMapToFilteredTrees();
}

    },
    0
);

        }
    );

    // První synchronizace po načtení
    TreeApp.syncMapWithTableFilter();

};

// ------------------------------------------------------------
// Aktivace zrušení výběru
// ------------------------------------------------------------

TreeApp.enableSelectionClearing = function () {

    if (!TreeApp.map) {
        return;
    }

    /*
     * Kliknutí do volné části mapy.
     *
     * Kliknutí na marker se také propaguje do mapy,
     * proto kontrolujeme původní HTML prvek události.
     */
    TreeApp.map.on(
        "click",
        function (event) {

            const originalEvent =
                event.originalEvent;

            if (!originalEvent) {
                return;
            }

            const target =
                originalEvent.target;

            if (
                target.closest &&
                target.closest(
                    ".leaflet-marker-icon, " +
                    ".leaflet-popup, " +
                    ".leaflet-control"
                )
            ) {
                return;
            }

            TreeApp.clearSelection();

        }
    );

    /*
     * Escape zruší výběr odkudkoliv na stránce.
     */
    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key !== "Escape") {
                return;
            }

            TreeApp.clearSelection();

            if (TreeApp.map) {
                TreeApp.map.closePopup();
            }

        }
    );

};

console.log("selection.js inicializován");