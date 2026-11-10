"use strict";

TreeApp.enableColumnResize = function () {

    const wrapper =
        document.getElementById("tabulka_wrapper");

    if (!wrapper) {
        console.warn(
            "Nelze aktivovat změnu šířky – chybí #tabulka_wrapper."
        );
        return;
    }

    const headTable =
        wrapper.querySelector(
            ".dataTables_scrollHead table"
        );

    const bodyTable =
        wrapper.querySelector(
            ".dataTables_scrollBody table"
        );

    const headers =
        wrapper.querySelectorAll(
            ".dataTables_scrollHead thead th"
        );

    if (
        !headTable ||
        !bodyTable ||
        headers.length === 0
    ) {
        console.warn(
            "Viditelná hlavička DataTables nebyla nalezena."
        );
        return;
    }

    headers.forEach(function (header, columnIndex) {

        /*
         * Při opakovaném vykreslení nevkládat úchyt znovu.
         */
        if (
            header.querySelector(
                ".column-resizer"
            )
        ) {
            return;
        }

        header.style.position = "relative";

        const resizer =
            document.createElement("span");

        resizer.className = "column-resizer";
        resizer.title = "Tažením změnit šířku sloupce";

        header.appendChild(resizer);

        let resizing = false;
        let startX = 0;
        let startWidth = 0;

        const applyWidth = function (width) {

            const safeWidth =
                Math.max(55, Math.round(width));

            /*
             * Viditelná hlavička.
             */
            const headHeader =
                headTable.querySelectorAll(
                    "thead th"
                )[columnIndex];

            if (headHeader) {
                headHeader.style.width =
                    safeWidth + "px";

                headHeader.style.minWidth =
                    safeWidth + "px";

                headHeader.style.maxWidth =
                    safeWidth + "px";
            }

            /*
             * Původní/skrytá hlavička v těle DataTables.
             */
            const bodyHeader =
                bodyTable.querySelectorAll(
                    "thead th"
                )[columnIndex];

            if (bodyHeader) {
                bodyHeader.style.width =
                    safeWidth + "px";

                bodyHeader.style.minWidth =
                    safeWidth + "px";

                bodyHeader.style.maxWidth =
                    safeWidth + "px";
            }

            /*
             * Datové buňky daného sloupce.
             */
            bodyTable
                .querySelectorAll("tbody tr")
                .forEach(function (row) {

                    const cell =
                        row.children[columnIndex];

                    if (!cell) {
                        return;
                    }

                    cell.style.width =
                        safeWidth + "px";

                    cell.style.minWidth =
                        safeWidth + "px";

                    cell.style.maxWidth =
                        safeWidth + "px";
                });

            /*
             * Zvětšíme šířku tabulek podle součtu sloupců,
             * aby fungoval vodorovný posuvník.
             */
            let totalWidth = 0;

            headTable
                .querySelectorAll("thead th")
                .forEach(function (th) {
                    totalWidth +=
                        th.getBoundingClientRect().width;
                });

            headTable.style.width =
                totalWidth + "px";

            bodyTable.style.width =
                totalWidth + "px";
        };


        const onPointerMove = function (event) {

            if (!resizing) {
                return;
            }

            const difference =
                event.clientX - startX;

            applyWidth(
                startWidth + difference
            );

            event.preventDefault();
        };


        const stopResizing = function () {

            if (!resizing) {
                return;
            }

            resizing = false;

            document.body.classList.remove(
                "is-resizing-column"
            );

            document.removeEventListener(
                "pointermove",
                onPointerMove
            );

            document.removeEventListener(
                "pointerup",
                stopResizing
            );

            document.removeEventListener(
                "pointercancel",
                stopResizing
            );
        };


        resizer.addEventListener(
            "pointerdown",
            function (event) {

                if (
                    event.pointerType === "mouse" &&
                    event.button !== 0
                ) {
                    return;
                }

                resizing = true;
                startX = event.clientX;

                startWidth =
                    header.getBoundingClientRect().width;

                document.body.classList.add(
                    "is-resizing-column"
                );

                document.addEventListener(
                    "pointermove",
                    onPointerMove
                );

                document.addEventListener(
                    "pointerup",
                    stopResizing
                );

                document.addEventListener(
                    "pointercancel",
                    stopResizing
                );

                event.preventDefault();
                event.stopPropagation();
            }
        );

        /*
         * Zabrání řazení sloupce při kliknutí na úchyt.
         */
        resizer.addEventListener(
            "click",
            function (event) {
                event.preventDefault();
                event.stopPropagation();
            }
        );
    });

    console.log(
        "Ruční změna šířky sloupců aktivována:",
        headers.length
    );
};

TreeApp.initDataTable = function () {

    const table =
        document.getElementById("tabulka");

    if (!table) {
        console.warn(
            "Tabulka #tabulka nebyla nalezena."
        );

        return;
    }

    if (
        typeof window.jQuery === "undefined" ||
        typeof window.jQuery.fn === "undefined" ||
        typeof window.jQuery.fn.DataTable === "undefined"
    ) {
        console.warn(
            "jQuery nebo DataTables nejsou dostupné."
        );

        return;
    }

    const expectedColumnCount =
        table.querySelectorAll(
            "thead tr:first-child th"
        ).length;

    const bodyRows =
        table.querySelectorAll(
            "tbody tr"
        );

    let invalidRowsFound = false;

    bodyRows.forEach(function (
        row,
        rowIndex
    ) {

        const cellCount =
            row.querySelectorAll(
                ":scope > td"
            ).length;

        if (
            cellCount !== expectedColumnCount
        ) {
            invalidRowsFound = true;

            console.error(
                "Vadný řádek tabulky:",
                {
                    rowIndex,
                    expectedColumnCount,
                    cellCount,
                    html: row.innerHTML
                }
            );
        }

    });

    if (invalidRowsFound) {
        console.error(
            "DataTables nebyla spuštěna, " +
            "protože některé řádky mají jiný počet buněk než hlavička."
        );

        return;
    }

    if (
        window.jQuery.fn.DataTable
            .isDataTable("#tabulka")
    ) {
        window.jQuery("#tabulka")
            .DataTable()
            .destroy();
    }

    TreeApp.dataTable =
        window.jQuery("#tabulka")
            .DataTable({

                language: {
                    search: "Hledat:",
                    lengthMenu: "Zobrazit _MENU_ záznamů",
                    emptyTable: "Žádné záznamy",
                    zeroRecords: "Nenalezeny žádné odpovídající záznamy",
                    loadingRecords: "Načítání…",
                    processing: "Zpracování…",

                    info:
                        "Zobrazeno _START_–_END_ z _TOTAL_",

                    infoEmpty:
                        "Zobrazeno 0 z 0",

                    infoFiltered:
                        " (filtrováno z _MAX_)",

                    paginate: {
                        previous: "‹",
                        next: "›"
                    }
                },

                pageLength: 10,

                lengthMenu: [
                    [5, 10, 25, 50],
                    [5, 10, 25, 50]
                ],

info: true,
responsive: false,

scrollY: "calc(27vh - 115px)",
scrollCollapse: true,
scrollX: true,

columnDefs: [

    { targets: 0, width: "70px" },
    { targets: 1, width: "250px" },
    { targets: 2, width: "220px" },
    { targets: 3, width: "90px" },
    { targets: 4, width: "80px" },
    { targets: 5, width: "70px" },
    { targets: 6, width: "70px" },
    { targets: 7, width: "70px" },
    { targets: 8, width: "70px" },   // Stabilita
	{ targets: 9, width: "70px" },   // Perspektiva
	{ targets: 10, width: "70px" },  // BioH
	{ targets: 11, width: "340px" }, // Plán péče
	{ targets: 12, width: "90px" }   // Fotky

],

paging: true,
autoWidth: false
            });

    const initializedTable = TreeApp.dataTable;
    window.setTimeout(
        function () {

            if (
                TreeApp.dataTable === initializedTable &&
                table.isConnected &&
                window.jQuery.fn.DataTable
                    .isDataTable("#tabulka")
            ) {
                TreeApp.dataTable
                    .columns
                    .adjust();
            }

        },
        500
    );

    console.log(
        "DataTables inicializována"
    );

    window.setTimeout(function () {

        if (TreeApp.dataTable === initializedTable && table.isConnected) TreeApp.enableColumnResize();

    }, 300);

};