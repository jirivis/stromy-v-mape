"use strict";

TreeApp.activeTreeFilters = {
    urgency: "all",
    inspection: "all",
    showRemoved: true
};


/**
 * Určí stav příští kontroly.
 */
TreeApp.getTreeInspectionState = function (tree) {

    const rawDate =
        tree && tree.next_insp_at
            ? String(tree.next_insp_at).trim()
            : "";

    if (!rawDate) {
        return "missing";
    }

    const inspectionDate =
        new Date(rawDate + "T00:00:00");

    if (
        Number.isNaN(
            inspectionDate.getTime()
        )
    ) {
        return "missing";
    }

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

    if (daysUntilInspection < 0) {
        return "overdue";
    }

    if (daysUntilInspection <= 90) {
        return "soon";
    }

    return "future";
};


/**
 * Ověří, zda strom odpovídá aktivním filtrům.
 */
TreeApp.treeMatchesActiveFilters = function (tree) {

    if (!tree) {
        return false;
    }
	
	/*
 * Odstraněné stromy.
 */
if (
    tree.status === "removed"
    && !TreeApp.activeTreeFilters.showRemoved
) {
    return false;
}

    const urgencyFilter =
        TreeApp.activeTreeFilters.urgency;

    const inspectionFilter =
        TreeApp.activeTreeFilters.inspection;


    /*
     * Naléhavost zásahu.
     */
if (urgencyFilter !== "all") {

    const treeUrgency =
        TreeApp.getTreeUrgency(tree);

    if (urgencyFilter === "none") {

        if (treeUrgency !== null) {
            return false;
        }

    } else if (
        treeUrgency !== urgencyFilter
    ) {
        return false;
    }

}


    /*
     * Termín kontroly.
     */
    if (inspectionFilter !== "all") {

        const inspectionState =
            TreeApp.getTreeInspectionState(
                tree
            );

        if (
            inspectionState !==
            inspectionFilter
        ) {
            return false;
        }

    }

    return true;
};


/**
 * Vrátí počet stromů odpovídajících filtrům.
 */
TreeApp.getFilteredTreeCount = function () {

    return Object.values(
        TreeApp.treesById || {}
    ).filter(function (tree) {

        return TreeApp
            .treeMatchesActiveFilters(tree);

    }).length;
};


/**
 * Aktualizuje text s počtem vyhovujících stromů.
 */
TreeApp.updateTreeFilterCount = function () {

    const countElement =
        document.getElementById(
            "treeFilterCount"
        );

    if (!countElement) {
        return;
    }

    const filteredCount =
        TreeApp.getFilteredTreeCount();

    const totalCount =
        Object.keys(
            TreeApp.treesById || {}
        ).length;

    countElement.textContent =
        filteredCount +
        " z " +
        totalCount +
        " stromů";
};


/**
 * Aplikuje filtry na mapu i tabulku.
 */
TreeApp.applyTreeFilters = function () {

    /*
     * Mapa a clustery.
     */
    if (TreeApp.markerClusterGroup) {

        const matchingMarkers = [];

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
                TreeApp
                    .treeMatchesActiveFilters(
                        tree
                    )
            ) {
                matchingMarkers.push(
                    marker
                );
            }

        });

        TreeApp.markerClusterGroup
            .clearLayers();

        TreeApp.markerClusterGroup
            .addLayers(
                matchingMarkers
            );
    }


    /*
     * DataTables.
     */
    if (
        TreeApp.dataTable &&
        window.jQuery &&
        window.jQuery.fn.DataTable
            .isDataTable("#tabulka")
    ) {
        TreeApp.dataTable.draw();
    } else {

        /*
         * Záloha pro případ, že DataTables
         * není dostupná.
         */
        Object.entries(
            TreeApp.rowsById || {}
        ).forEach(function (
            [treeId, row]
        ) {

            const tree =
                TreeApp.treesById[
                    String(treeId)
                ];

            row.hidden =
                !TreeApp
                    .treeMatchesActiveFilters(
                        tree
                    );

        });

    }

    TreeApp.updateTreeFilterCount();
};


/**
 * Přidá vlastní filtr do DataTables.
 */
TreeApp.installDataTableTreeFilter =
    function () {

        if (
            TreeApp
                .dataTableTreeFilterInstalled
        ) {
            return;
        }

        if (
            !window.jQuery ||
            !window.jQuery.fn ||
            !window.jQuery.fn.dataTable
        ) {
            return;
        }

        window.jQuery.fn.dataTable
            .ext.search.push(
                function (
                    settings,
                    data,
                    dataIndex
                ) {

                    /*
                     * Ostatní tabulky na stránce
                     * tímto filtrem neovlivňujeme.
                     */
                    if (
                        !settings.nTable ||
                        settings.nTable.id !==
                            "tabulka"
                    ) {
                        return true;
                    }

                    const tableRow =
                        settings
                            .aoData[
                                dataIndex
                            ]
                            ?.nTr;

                    if (!tableRow) {
                        return true;
                    }

                    const treeId =
                        tableRow.dataset.treeId;

                    const tree =
                        TreeApp.treesById[
                            String(treeId)
                        ];

                    return TreeApp
                        .treeMatchesActiveFilters(
                            tree
                        );
                }
            );

        TreeApp.dataTableTreeFilterInstalled =
            true;
    };


/**
 * Vytvoří ovládací panel filtrů v mapě.
 */
TreeApp.initTreeFilterControl = function () {

    if (TreeApp.treeFilterControl) {
        return;
    }

    const TreeFilterControl =
        L.Control.extend({

            options: {
                position: "topright"
            },

            onAdd: function () {

                const container =
                    L.DomUtil.create(
                        "div",
                        "tree-filter-control leaflet-bar"
                    );

container.innerHTML = `

    <button
        type="button"
        class="tree-filter-control__toggle"
        aria-expanded="true"
    >
<span class="tree-filter-control__toggle-label">
    <span
        class="tree-filter-control__funnel"
        aria-hidden="true"
    >
        <svg
            viewBox="0 0 24 24"
            width="19"
            height="19"
        >
            <path
                d="M4 5h16l-6.5 7.5V19l-3 1.5v-8L4 5z"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
            />
        </svg>
    </span>

    <span class="tree-filter-control__text">
        Filtry
    </span>
</span>

        <span
            class="tree-filter-control__toggle-icon"
        >
            ▲
        </span>
    </button>

    <div
        class="tree-filter-control__content"
    >

        <div
            class="tree-filter-control__title"
        >
            Filtrovat stromy
        </div>

        <label
            class="tree-filter-control__label"
            for="treeUrgencyFilter"
        >
            Naléhavost zásahu
        </label>

        <select
            id="treeUrgencyFilter"
            class="tree-filter-control__select"
        >
            <option value="all">
                Všechny
            </option>

            <option value="u0">
                U0 – ihned
            </option>

            <option value="u1">
                U1
            </option>

            <option value="u2">
                U2
            </option>

            <option value="u3">
                U3
            </option>

            <option value="none">
                Bez zásahu
            </option>
        </select>

        <label
            class="tree-filter-control__label"
            for="treeInspectionFilter"
        >
            Příští kontrola
        </label>

        <select
            id="treeInspectionFilter"
            class="tree-filter-control__select"
        >
            <option value="all">
                Všechny termíny
            </option>

            <option value="overdue">
                Po termínu
            </option>

            <option value="soon">
                Do 90 dnů
            </option>

            <option value="future">
                Za více než 90 dnů
            </option>

            <option value="missing">
                Bez data
            </option>
        </select>

        <label
            class="tree-filter-control__checkbox"
        >
            <input
                type="checkbox"
                id="treeShowRemovedFilter"
                checked
            >
            Zobrazit odstraněné stromy
        </label>

        <div
            id="treeFilterCount"
            class="tree-filter-control__count"
        >
        </div>

        <button
            type="button"
            id="resetTreeFilters"
            class="tree-filter-control__reset"
        >
            Zrušit filtry
        </button>

    </div>
`;

                L.DomEvent
                    .disableClickPropagation(
                        container
                    );

                L.DomEvent
                    .disableScrollPropagation(
                        container
                    );

                const urgencySelect =
                    container.querySelector(
                        "#treeUrgencyFilter"
                    );

                const inspectionSelect =
                    container.querySelector(
                        "#treeInspectionFilter"
                    );
					
				const showRemovedCheckbox =
					container.querySelector(
						"#treeShowRemovedFilter"
					);

                const resetButton =
                    container.querySelector(
                        "#resetTreeFilters"
                    );
					
					const toggleButton =
    container.querySelector(
        ".tree-filter-control__toggle"
    );

const filterContent =
    container.querySelector(
        ".tree-filter-control__content"
    );

const toggleIcon =
    container.querySelector(
        ".tree-filter-control__toggle-icon"
    );
	
function setFilterCollapsed(collapsed) {

    container.classList.toggle(
        "tree-filter-control--collapsed",
        collapsed
    );

    filterContent.hidden =
        collapsed;

    toggleButton.setAttribute(
        "aria-expanded",
        collapsed
            ? "false"
            : "true"
    );

    toggleIcon.textContent =
        collapsed
            ? "▼"
            : "▲";
}


/*
 * Filtry jsou po načtení
 * vždy sbalené.
 */
setFilterCollapsed(true);


toggleButton.addEventListener(
    "click",
    function () {

        const collapsed =
            !filterContent.hidden;

        setFilterCollapsed(
            collapsed
        );

    }
);


                urgencySelect.addEventListener(
                    "change",
                    function () {

                        TreeApp
                            .activeTreeFilters
                            .urgency =
                                urgencySelect.value;

                        TreeApp
                            .applyTreeFilters();
                    }
                );


                inspectionSelect
                    .addEventListener(
                        "change",
                        function () {

                            TreeApp
                                .activeTreeFilters
                                .inspection =
                                    inspectionSelect
                                        .value;

                            TreeApp
                                .applyTreeFilters();
                        }
                    );
					
					showRemovedCheckbox
						.addEventListener(
							"change",
							function () {

								TreeApp
									.activeTreeFilters
									.showRemoved =
										showRemovedCheckbox.checked;

								TreeApp
									.applyTreeFilters();
							}
						);


                resetButton.addEventListener(
                    "click",
                    function () {

                        urgencySelect.value =
                            "all";

                        inspectionSelect.value =
                            "all";

showRemovedCheckbox.checked =
    true;

TreeApp.activeTreeFilters =
    {
        urgency: "all",
        inspection: "all",
        showRemoved: true
    };

                        TreeApp
                            .applyTreeFilters();
                    }
                );

                return container;
            }

        });

    TreeApp.treeFilterControl =
        new TreeFilterControl();

    TreeApp.treeFilterControl.addTo(
        TreeApp.map
    );
};

console.log("filters.js inicializován");