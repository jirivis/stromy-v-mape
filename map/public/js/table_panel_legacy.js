TreeApp.initTableControl = function () {

    if (TreeApp.tableControl) {
        return;
    }

    const TableControl =
        L.Control.extend({

            options: {
                position: "topright"
            },

            onAdd: function () {

                const container =
                    L.DomUtil.create(
                        "div",
                        "tree-table-control leaflet-bar"
                    );

                const button =
                    L.DomUtil.create(
                        "button",
                        "tree-table-control__button",
                        container
                    );

                button.type = "button";
                button.title = "Seznam stromů";
                button.setAttribute(
                    "aria-label",
                    "Zobrazit seznam stromů"
                );

                /*
                 * Ikona tabulky – řádky i sloupce,
                 * aby se nepletla s menu.
                 */
                button.innerHTML = `
                    <svg
                        viewBox="0 0 24 24"
                        width="20"
                        height="20"
                        aria-hidden="true"
                    >
                        <rect
                            x="3"
                            y="4"
                            width="18"
                            height="16"
                            rx="1"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                        <path
                            d="
                                M3 9h18
                                M3 14h18
                                M9 4v16
                                M15 4v16
                            "
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        />
                    </svg>
                `;

                L.DomEvent.disableClickPropagation(
                    container
                );

                L.DomEvent.disableScrollPropagation(
                    container
                );

                L.DomEvent.on(
                    button,
                    "click",
                    function (event) {

                        L.DomEvent.stop(event);

                        if (
                            typeof TreeApp.toggleTablePanel ===
                            "function"
                        ) {
                            TreeApp.toggleTablePanel();
                        }
                    }
                );

                TreeApp.tableControlButton =
                    button;

                return container;
            }

        });

    TreeApp.tableControl =
        new TableControl();

    TreeApp.tableControl.addTo(
        TreeApp.map
    );
};

document.addEventListener("DOMContentLoaded", function () {

    const panel =
        document.getElementById("tablePanel");

    const handle =
        document.getElementById("toggleTablePanel");

    const arrow =
        document.getElementById("tablePanelArrow");

    if (!panel || !handle) {
        return;
    }

const minimumOpenHeight = 150;

function getCollapsedHeight() {
    return Math.ceil(
        handle.getBoundingClientRect().height
    );
}

	let panelVisible = true;

    let isDragging = false;
    let dragStarted = false;

    let startY = 0;
    let startHeight = 0;

    /*
     * Poslední otevřená výška.
     * Použije se po opětovném rozbalení.
     */
    let lastOpenHeight =
        Math.max(
            minimumOpenHeight,
            panel.getBoundingClientRect().height
        );


    function getMaximumHeight() {
        const workspace =
            document.querySelector(".app-workspace");

        if (!workspace) {
            return window.innerHeight * 0.75;
        }

        /*
         * Mapě necháme nejméně 180 px.
         */
        return Math.max(
            minimumOpenHeight,
            workspace.clientHeight - 180
        );
    }

function refreshPopupHeight() {

    const mapElement =
        document.getElementById("map");

    if (!mapElement) {
        return;
    }

    const mapHeight =
        mapElement.getBoundingClientRect().height;

    /*
     * Rezerva pro:
     * - špičku popupu,
     * - okraje mapy,
     * - zavírací tlačítko.
     */
    const popupReserve = 95;

    /*
     * Popup nebude menší než 150 px.
     */
    const popupMaxHeight =
        Math.max(
            150,
            Math.floor(
                mapHeight - popupReserve
            )
        );

    document.documentElement
        .style
        .setProperty(
            "--tree-popup-max-height",
            popupMaxHeight + "px"
        );

    /*
     * Aktualizuje právě otevřený popup,
     * aby reagoval už během tažení.
     */
    if (
        window.TreeApp &&
        TreeApp.map &&
        TreeApp.map._popup
    ) {
        TreeApp.map._popup.update();
    }
}

    function refreshMap() {
        window.requestAnimationFrame(function () {

            if (
                window.TreeApp &&
                TreeApp.map
            ) {
                TreeApp.map.invalidateSize({
                    animate: false
                });

            } else if (
                typeof window.map !== "undefined" &&
                window.map
            ) {
                window.map.invalidateSize({
                    animate: false
                });
            }

        });
    }


    function refreshDataTableHeight() {

        const panelContent =
            document.getElementById(
                "tablePanelContent"
            );

        const scrollBody =
            document.querySelector(
                "#tabulka_wrapper .dataTables_scrollBody"
            );

        if (!panelContent || !scrollBody) {
            return;
        }

        /*
         * Výška ovládání DataTables:
         * počet výsledků, hledání, hlavička a stránkování.
         */
        const controlsHeight = 90;

        const availableHeight =
            Math.max(
                70,
                panelContent.clientHeight -
                controlsHeight
            );

        scrollBody.style.height =
            availableHeight + "px";

        scrollBody.style.maxHeight =
            availableHeight + "px";

        if (
            window.TreeApp &&
            TreeApp.dataTable &&
            typeof TreeApp.dataTable.columns ===
                "function"
        ) {
            TreeApp.dataTable
                .columns
                .adjust();
        }
    }


function refreshLayout() {

    refreshDataTableHeight();

    refreshPopupHeight();

    refreshMap();
}


    function setPanelHeight(height) {

const collapsedHeight =
    getCollapsedHeight();

const limitedHeight =
    Math.max(
        collapsedHeight,
        Math.min(
            height,
            getMaximumHeight()
        )
    );

        panel.style.flexBasis =
            limitedHeight + "px";

        panel.style.height =
            limitedHeight + "px";

        const collapsed =
            limitedHeight <=
            collapsedHeight + 4;

        panel.classList.toggle(
            "is-collapsed",
            collapsed
        );

        handle.setAttribute(
            "aria-expanded",
            collapsed ? "false" : "true"
        );

        if (arrow) {
            arrow.textContent =
                collapsed ? "▲" : "▼";
        }

        if (!collapsed) {
            lastOpenHeight = limitedHeight;
        }

        refreshLayout();
    }
	
function setPanelVisible(visible) {

    panelVisible = visible;

    panel.classList.toggle(
        "is-hidden",
        !visible
    );

    if (TreeApp.tableControlButton) {

        TreeApp.tableControlButton.classList.toggle(
            "is-active",
            visible
        );

        TreeApp.tableControlButton.setAttribute(
            "aria-label",
            visible
                ? "Skrýt seznam stromů"
                : "Zobrazit seznam stromů"
        );

        TreeApp.tableControlButton.title =
            visible
                ? "Skrýt seznam stromů"
                : "Zobrazit seznam stromů";
    }

    if (visible) {

        /*
         * Při opětovném zobrazení vrátíme
         * poslední používanou výšku.
         */
        setPanelHeight(
            Math.max(
                minimumOpenHeight,
                lastOpenHeight
            )
        );

    } else {

        /*
         * Panel úplně odstraníme z layoutu.
         */
        panel.style.flexBasis = "0px";
        panel.style.height = "0px";
    }

    refreshLayout();
}


/*
 * Zpřístupní ovládání panelu
 * Leaflet tlačítku.
 */
TreeApp.toggleTablePanel = function () {

    setPanelVisible(
        !panelVisible
    );
};	

    handle.addEventListener(
        "pointerdown",
        function (event) {
			
			if (!panelVisible) {
    return;
}

            /*
             * Tažení funguje levým tlačítkem myši,
             * perem i dotykem.
             */
            if (
                event.pointerType === "mouse" &&
                event.button !== 0
            ) {
                return;
            }

            isDragging = true;
            dragStarted = false;

            startY = event.clientY;
            startHeight =
                panel.getBoundingClientRect().height;

            handle.setPointerCapture(
                event.pointerId
            );

            document.body.classList.add(
                "is-resizing-table"
            );

            /*
             * Zabrání označování textu a posouvání stránky.
             */
            event.preventDefault();
        }
    );


    handle.addEventListener(
        "pointermove",
        function (event) {

            if (!isDragging) {
                return;
            }

            const movement =
                startY - event.clientY;

            /*
             * Malý pohyb stále považujeme za klik.
             */
            if (Math.abs(movement) > 4) {
                dragStarted = true;
            }

            if (!dragStarted) {
                return;
            }

            /*
             * Pohyb nahoru zvětšuje tabulku,
             * pohyb dolů ji zmenšuje.
             */
            setPanelHeight(
                startHeight + movement
            );

            event.preventDefault();
        }
    );


    function stopDragging(event) {

        if (!isDragging) {
            return;
        }

        isDragging = false;

        document.body.classList.remove(
            "is-resizing-table"
        );

        if (
            handle.hasPointerCapture(
                event.pointerId
            )
        ) {
            handle.releasePointerCapture(
                event.pointerId
            );
        }

        refreshLayout();
    }


    handle.addEventListener(
        "pointerup",
        stopDragging
    );

    handle.addEventListener(
        "pointercancel",
        stopDragging
    );


    /*
     * Pouhý klik panel sbalí nebo rozbalí.
     * Po tažení se klik ignoruje.
     */
    handle.addEventListener(
        "click",
        function (event) {

            if (dragStarted) {
                dragStarted = false;
                event.preventDefault();
                return;
            }

            const collapsed =
                panel.classList.contains(
                    "is-collapsed"
                );

            if (collapsed) {
                setPanelHeight(
                    Math.max(
                        minimumOpenHeight,
                        lastOpenHeight
                    )
                );
            } else {
setPanelHeight(
    getCollapsedHeight()
);
            }
			window.setTimeout(function () {
    refreshLayout();
}, 280);
        }
    );
	
/*
 * Po dokončení animace panelu znovu změříme mapu
 * a upravíme maximální výšku popupu.
 */
panel.addEventListener("transitionend", function (event) {

    if (
        event.propertyName !== "height" &&
        event.propertyName !== "flex-basis"
    ) {
        return;
    }

    refreshLayout();

});

    window.addEventListener(
        "resize",
        function () {

            if (
                !panel.classList.contains(
                    "is-collapsed"
                )
            ) {
                const currentHeight =
                    panel.getBoundingClientRect()
                        .height;

                setPanelHeight(
                    Math.min(
                        currentHeight,
                        getMaximumHeight()
                    )
                );
            }

            refreshLayout();
        }
    );


/*
 * Po načtení začíná seznam stromů skrytý.
 */
setPanelVisible(false);

    /*
     * První přepočet po inicializaci DataTables.
     */
    window.setTimeout(
        refreshLayout,
        700
    );

});