"use strict";

// ------------------------------------------------------------
// Pomocné funkce
// ------------------------------------------------------------

TreeApp.escapeHtml = function (value) {

    if (
        value === null ||
        value === undefined
    ) {
        return "";
    }

    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

};


TreeApp.escapeAttribute = function (value) {
    return TreeApp.escapeHtml(value);
};


// ------------------------------------------------------------
// Vytvoření panelu
// ------------------------------------------------------------

TreeApp.initEditorPanel = function () {

    /*
     * Zabráníme vytvoření panelu vícekrát.
     */
    if (
        document.getElementById(
            "tree-editor-panel"
        )
    ) {
        return;
    }

    const panel =
        document.createElement("aside");

    panel.id = "tree-editor-panel";
    panel.className = "tree-editor-panel";
    panel.setAttribute("aria-hidden", "true");

    panel.innerHTML = `
        <div class="tree-editor-panel__header">

            <div>
                <div class="tree-editor-panel__eyebrow">
                    Editor stromu
                </div>

                <h2
                    id="tree-editor-panel-title"
                    class="tree-editor-panel__title"
                >
                    Strom
                </h2>
            </div>

            <button
                type="button"
                class="tree-editor-panel__close"
                aria-label="Zavřít editor"
                title="Zavřít editor"
            >
                ×
            </button>

        </div>

        <div
            id="tree-editor-panel-content"
            class="tree-editor-panel__content"
        ></div>
    `;

    document.body.appendChild(panel);

    const closeButton =
        panel.querySelector(
            ".tree-editor-panel__close"
        );

    closeButton.addEventListener(
        "click",
        function () {
            TreeApp.closeEditor();
        }
    );

};


// ------------------------------------------------------------
// Formulář editoru
// ------------------------------------------------------------

TreeApp.buildEditorPanelForm = function (p) {

    const id =
        TreeApp.escapeAttribute(p.id);

    return `
        <form
            id="tree-editor-form"
            class="tree-editor-form"
            data-tree-id="${id}"
        >

            <section class="tree-editor-section">

                <h3 class="tree-editor-section__title">
                    Základní údaje
                </h3>

                <label class="tree-editor-field">

                    <span>Vědecké jméno</span>

                    <input
                        type="text"
                        name="species"
                        value="${TreeApp.escapeAttribute(
                            p.species
                        )}"
                    >

                </label>

                <label class="tree-editor-field">

                    <span>České jméno</span>

                    <input
                        type="text"
                        name="czech"
                        value="${TreeApp.escapeAttribute(
                            p.czech
                        )}"
                    >

                </label>

                <div class="tree-editor-grid">

                    <label class="tree-editor-field">

                        <span>Číslo stromu</span>

                        <input
                            type="text"
                            name="usr_id"
                            value="${TreeApp.escapeAttribute(
                                p.usr_id
                            )}"
                        >

                    </label>

                    <label class="tree-editor-field">

                        <span>Rok výsadby</span>

                        <input
                            type="number"
                            name="planted"
                            value="${TreeApp.escapeAttribute(
                                p.planted
                            )}"
                        >

                    </label>

                </div>

            </section>


<section class="tree-editor-section">

    <h3 class="tree-editor-section__title">
        Rozměry
    </h3>

    <div class="tree-editor-grid">

        <label class="tree-editor-field">

            <span>Výška (m)</span>

            <input
                type="number"
                name="height"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.height
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Průměr koruny (m)</span>

            <input
                type="number"
                name="spread"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.spread
                )}"
            >

        </label>

    </div>


    <h4 class="tree-editor-subtitle">
        Průměry kmene
    </h4>

    <div class="tree-editor-grid">

        <label class="tree-editor-field">

            <span>Průměr kmene 1 (cm)</span>

            <input
                type="number"
                name="dbh1_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.dbh1_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Průměr kmene 2 (cm)</span>

            <input
                type="number"
                name="dbh2_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.dbh2_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Průměr kmene 3 (cm)</span>

            <input
                type="number"
                name="dbh3_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.dbh3_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Průměr kmene 4 (cm)</span>

            <input
                type="number"
                name="dbh4_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.dbh4_cm
                )}"
            >

        </label>

    </div>


    <h4 class="tree-editor-subtitle">
        Obvody kmene
    </h4>

    <div class="tree-editor-grid">

        <label class="tree-editor-field">

            <span>Obvod kmene 1 (cm)</span>

            <input
                type="number"
                name="girth1_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.girth1_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Obvod kmene 2 (cm)</span>

            <input
                type="number"
                name="girth2_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.girth2_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Obvod kmene 3 (cm)</span>

            <input
                type="number"
                name="girth3_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.girth3_cm
                )}"
            >

        </label>

        <label class="tree-editor-field">

            <span>Obvod kmene 4 (cm)</span>

            <input
                type="number"
                name="girth4_cm"
                min="0"
                step="0.1"
                value="${TreeApp.escapeAttribute(
                    p.girth4_cm
                )}"
            >

        </label>

    </div>

</section>
			
			<section class="tree-editor-section">

    <div class="tree-editor-section__heading">

        <h3 class="tree-editor-section__title">
            Historie měření
        </h3>

        <button
            type="button"
            class="tree-editor-small-button"
            id="tree-measurements-reload"
        >
            ↻ Obnovit
        </button>

    </div>

    <div
        id="tree-measurements-list"
        class="tree-measurements-list"
    >
        <p class="tree-editor-message">
            Načítám historii měření…
        </p>
    </div>

<div class="tree-measurement-add">

    <button
        type="button"
        id="tree-measurement-add-toggle"
        class="tree-editor-button tree-editor-button--secondary"
    >
        ➕ Nové měření
    </button>

    <div
        id="tree-measurement-add-form-wrapper"
        class="tree-measurement-add__form-wrapper"
        hidden
    >

<div
    id="tree-measurement-add-form"
    class="tree-measurement-add__form"
>

            <label class="tree-editor-field">

                <span>Typ měření</span>

                <select
                    name="measurement_type"
                    required
                >
                    <option value="girth">
                        Obvod kmene
                    </option>

                    <option value="dbh">
                        Průměr kmene
                    </option>
                </select>

            </label>

            <label class="tree-editor-field">

                <span>Hodnota (cm)</span>

                <input
                    type="number"
                    name="value_cm"
                    min="0.01"
                    step="0.01"
                    required
                >

            </label>

            <div class="tree-editor-grid">

                <label class="tree-editor-field">

                    <span>Datum měření</span>

                    <input
                        type="date"
                        name="measurement_date"
                        required
                    >

                </label>

                <label class="tree-editor-field">

                    <span>Čas měření</span>

                    <input
                        type="time"
                        name="measurement_time"
                        required
                    >

                </label>

            </div>

            <label class="tree-editor-field">

                <span>Poznámka</span>

                <textarea
                    name="note"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </label>

            <div class="tree-measurement-add__actions">

				<button
					type="button"
					id="tree-measurement-add-save"
					class="tree-editor-button tree-editor-button--save"
				>
					💾 Uložit měření
				</button>

                <button
                    type="button"
                    id="tree-measurement-add-cancel"
                    class="tree-editor-button tree-editor-button--cancel"
                >
                    Zrušit
                </button>

            </div>

            <p
                id="tree-measurement-add-message"
                class="tree-editor-message"
            ></p>

        </div>

    </div>

</div>

</section>

            <section class="tree-editor-section">

                <h3 class="tree-editor-section__title">
                    Hodnocení stromu
                </h3>

                <div class="tree-editor-grid">

                    <label class="tree-editor-field">

                        <span>Fyziologické stáří</span>

                        <input
                            type="text"
                            name="phy_age"
                            value="${TreeApp.escapeAttribute(
                                p.phy_age
                            )}"
                        >

                    </label>

                    <label class="tree-editor-field">

                        <span>Vitalita</span>

                        <input
                            type="text"
                            name="vitality"
                            value="${TreeApp.escapeAttribute(
                                p.vitality
                            )}"
                        >

                    </label>

                </div>

                <label class="tree-editor-field">

                    <span>Zdravotní stav</span>

                    <input
                        type="text"
                        name="tree_health"
                        value="${TreeApp.escapeAttribute(
                            p.tree_health
                        )}"
                    >

                </label>

                <div class="tree-editor-grid">

                    <label class="tree-editor-field">

                        <span>Stabilita</span>

                        <input
                            type="text"
                            name="stability"
                            value="${TreeApp.escapeAttribute(
                                p.stability
                            )}"
                        >

                    </label>

                    <label class="tree-editor-field">

                        <span>Perspektiva</span>

                        <input
                            type="text"
                            name="longevity"
                            value="${TreeApp.escapeAttribute(
                                p.longevity
                            )}"
                        >

                    </label>

                </div>

                <label class="tree-editor-field">

                    <span>Biologická hodnota</span>

                    <input
                        type="text"
                        name="ecological_value"
                        value="${TreeApp.escapeAttribute(
                            p.ecological_value
                        )}"
                    >

                </label>

            </section>


            <section class="tree-editor-section">

                <h3 class="tree-editor-section__title">
                    Poznámka
                </h3>

                <label class="tree-editor-field">

                    <span>Veřejná poznámka</span>

					<label for="note_public">Popis stromu</label>

					<textarea
						name="note_public"
						id="note_public"
						rows="6"
					><?= htmlspecialchars(
						(string) ($tree['note_public'] ?? ''),
						ENT_QUOTES,
						'UTF-8'
					) ?></textarea>

                </label>

            </section>


            <div class="tree-editor-panel__footer">

                <button
                    type="submit"
                    class="tree-editor-button tree-editor-button--save"
                >
                    💾 Zkontrolovat
                </button>

                <button
                    type="button"
                    class="tree-editor-button tree-editor-button--cancel"
                >
                    Zavřít
                </button>

            </div>

            <p
                id="tree-editor-message"
                class="tree-editor-message"
            >
                Změny se zatím neukládají do databáze.
            </p>

        </form>
    `;

};

// ------------------------------------------------------------
// Formátování data a času
// ------------------------------------------------------------

TreeApp.formatMeasurementDate = function (value) {

    if (!value) {
        return "Datum neznámé";
    }

    /*
     * MySQL vrací tvar YYYY-MM-DD HH:MM:SS.
     * Převod mezery na T pomáhá prohlížeči
     * správně vytvořit Date.
     */
    const normalized =
        String(value).replace(" ", "T");

    const date = new Date(normalized);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(
        "cs-CZ",
        {
            dateStyle: "medium",
            timeStyle: "short"
        }
    ).format(date);

};


// ------------------------------------------------------------
// Načtení historie měření
// ------------------------------------------------------------

TreeApp.loadTreeMeasurements = function (treeId) {

    const id = String(treeId);

    const container =
        document.getElementById(
            "tree-measurements-list"
        );

    if (!container) {
        return;
    }

    container.innerHTML = `
        <p class="tree-editor-message">
            Načítám historii měření…
        </p>
    `;

    fetch(
        "api/tree_measurements.php?tree_id=" +
        encodeURIComponent(id)
    )

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    "API vrátilo HTTP " +
                    response.status
                );
            }

            return response.json();

        })

        .then(function (data) {

            if (!data.success) {
                throw new Error(
                    data.message ||
                    "Historii se nepodařilo načíst."
                );
            }

            TreeApp.renderTreeMeasurements(
                data.measurements || []
            );

        })

        .catch(function (error) {

            console.error(
                "Chyba historie měření:",
                error
            );

            container.innerHTML = `
                <p class="
                    tree-editor-message
                    tree-editor-message--error
                ">
                    Historii měření se nepodařilo načíst.
                </p>
            `;

        });

};


// ------------------------------------------------------------
// Vykreslení historie měření
// ------------------------------------------------------------

TreeApp.renderTreeMeasurements = function (
    measurements
) {

    const container =
        document.getElementById(
            "tree-measurements-list"
        );

    if (!container) {
        return;
    }

    if (
        !Array.isArray(measurements) ||
        measurements.length === 0
    ) {

        container.innerHTML = `
            <p class="tree-editor-message">
                Strom zatím nemá uloženou historii měření.
            </p>
        `;

        return;
    }

    const groups = {
        girth: [],
        dbh: []
    };

    measurements.forEach(function (item) {

        if (groups[item.measurement_type]) {
            groups[item.measurement_type].push(item);
        }

    });

    let html = "";

    html += TreeApp.buildMeasurementGroup(
        "Obvody kmene",
        "girth",
        groups.girth
    );

    html += TreeApp.buildMeasurementGroup(
        "Průměry kmene",
        "dbh",
        groups.dbh
    );

    container.innerHTML = html;

};


// ------------------------------------------------------------
// Jedna skupina měření
// ------------------------------------------------------------

TreeApp.buildMeasurementGroup = function (
    title,
    type,
    measurements
) {

    let rows = "";

    if (measurements.length === 0) {

        rows = `
            <p class="tree-measurements-empty">
                Zatím bez měření.
            </p>
        `;

    } else {

        measurements.forEach(function (item) {

            const note = item.note
                ? `
                    <div class="tree-measurement__note">
                        ${TreeApp.escapeHtml(item.note)}
                    </div>
                `
                : "";

            rows += `
                <article class="tree-measurement">

                    <div class="tree-measurement__value">
                        ${TreeApp.escapeHtml(item.value_cm)}
                        <span>cm</span>
                    </div>

                    <div class="tree-measurement__meta">

                        <time>
                            ${TreeApp.escapeHtml(
                                TreeApp.formatMeasurementDate(
                                    item.measured_at
                                )
                            )}
                        </time>

                        ${note}

                    </div>

                </article>
            `;

        });

    }

    return `
        <div
            class="tree-measurements-group"
            data-measurement-type="${type}"
        >

            <h4 class="tree-measurements-group__title">
                ${title}
            </h4>

            ${rows}

        </div>
    `;

};

// ------------------------------------------------------------
// Otevření editoru
// ------------------------------------------------------------

TreeApp.openEditor = function(treeId) {
 window.location.href='/map/public/tree_new.php?id='+encodeURIComponent(String(treeId));
};

TreeApp.fillCurrentMeasurementDateTime = function () {

    const measurementBox =
        document.getElementById(
            "tree-measurement-add-form"
        );

    if (!measurementBox) {
        return;
    }

    const dateInput =
        measurementBox.querySelector(
            '[name="measurement_date"]'
        );

    const timeInput =
        measurementBox.querySelector(
            '[name="measurement_time"]'
        );

    if (!dateInput || !timeInput) {
        return;
    }

    const now = new Date();

    const year = now.getFullYear();

    const month = String(
        now.getMonth() + 1
    ).padStart(2, "0");

    const day = String(
        now.getDate()
    ).padStart(2, "0");

    const hours = String(
        now.getHours()
    ).padStart(2, "0");

    const minutes = String(
        now.getMinutes()
    ).padStart(2, "0");

    dateInput.value =
        `${year}-${month}-${day}`;

    timeInput.value =
        `${hours}:${minutes}`;

};

// ------------------------------------------------------------
// Události formuláře
// ------------------------------------------------------------

TreeApp.bindEditorForm = function () {

    const form =
        document.getElementById(
            "tree-editor-form"
        );

    if (!form) {
        return;
    }

    // --------------------------------------------------------
    // Hlavní formulář editoru
    // --------------------------------------------------------

    form.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

            TreeApp.previewEditorValues();

        }
    );


    const cancelButton =
        form.querySelector(
            ".tree-editor-button--cancel"
        );

    if (cancelButton) {

        cancelButton.addEventListener(
            "click",
            function () {
                TreeApp.closeEditor();
            }
        );

    }


    // --------------------------------------------------------
    // Obnovení historie měření
    // --------------------------------------------------------

    const reloadButton =
        document.getElementById(
            "tree-measurements-reload"
        );

    if (reloadButton) {

        reloadButton.addEventListener(
            "click",
            function () {

                TreeApp.loadTreeMeasurements(
                    form.dataset.treeId
                );

            }
        );

    }


    // --------------------------------------------------------
    // Nové měření
    // --------------------------------------------------------

    const addToggle =
        document.getElementById(
            "tree-measurement-add-toggle"
        );

    const addWrapper =
        document.getElementById(
            "tree-measurement-add-form-wrapper"
        );

    const measurementBox =
        document.getElementById(
            "tree-measurement-add-form"
        );

    const addSave =
        document.getElementById(
            "tree-measurement-add-save"
        );

    const addCancel =
        document.getElementById(
            "tree-measurement-add-cancel"
        );


    if (
        addToggle &&
        addWrapper &&
        measurementBox
    ) {

        addToggle.addEventListener(
            "click",
            function () {

                addWrapper.hidden = false;
                addToggle.hidden = true;

                TreeApp.fillCurrentMeasurementDateTime();

                const valueInput =
                    measurementBox.querySelector(
                        '[name="value_cm"]'
                    );

                if (valueInput) {
                    valueInput.focus();
                }

            }
        );

    }


    if (addSave) {

        addSave.addEventListener(
            "click",
            function () {

                TreeApp.saveTreeMeasurement(
                    form.dataset.treeId
                );

            }
        );

    }


    if (
        addCancel &&
        addWrapper &&
        addToggle &&
        measurementBox
    ) {

        addCancel.addEventListener(
            "click",
            function () {

                measurementBox
                    .querySelectorAll(
                        "input, textarea, select"
                    )
                    .forEach(function (field) {

                        if (
                            field.tagName === "SELECT"
                        ) {

                            field.selectedIndex = 0;

                        } else {

                            field.value = "";

                        }

                    });

                addWrapper.hidden = true;
                addToggle.hidden = false;

                const message =
                    document.getElementById(
                        "tree-measurement-add-message"
                    );

                if (message) {

                    message.textContent = "";

                    message.classList.remove(
                        "tree-editor-message--error",
                        "tree-editor-message--success"
                    );

                }

            }
        );

    }

};

// ------------------------------------------------------------
// Kontrola hodnot formuláře
// ------------------------------------------------------------

TreeApp.previewEditorValues = function () {

    const form =
        document.getElementById(
            "tree-editor-form"
        );

    if (!form) {
        return;
    }

    const values =
        Object.fromEntries(
            new FormData(form).entries()
        );

    values.id =
        String(form.dataset.treeId);

    console.log(
        "Hodnoty připravené k uložení:",
        values
    );

    const message =
        document.getElementById(
            "tree-editor-message"
        );

    if (message) {

        message.textContent =
            "✓ Formulář je připravený. " +
            "Dalším krokem bude uložení do databáze.";

        message.classList.add(
            "tree-editor-message--success"
        );

    }

};


// ------------------------------------------------------------
// Zavření editoru
// ------------------------------------------------------------

TreeApp.saveTreeMeasurement = function (
    treeId
) {

    const measurementBox =
        document.getElementById(
            "tree-measurement-add-form"
        );

    const message =
        document.getElementById(
            "tree-measurement-add-message"
        );

    if (!measurementBox) {
        return;
    }

    const submitButton =
        document.getElementById(
            "tree-measurement-add-save"
        );

    const typeInput =
        measurementBox.querySelector(
            '[name="measurement_type"]'
        );

    const valueInput =
        measurementBox.querySelector(
            '[name="value_cm"]'
        );

    const dateInput =
        measurementBox.querySelector(
            '[name="measurement_date"]'
        );

    const timeInput =
        measurementBox.querySelector(
            '[name="measurement_time"]'
        );

    const noteInput =
        measurementBox.querySelector(
            '[name="note"]'
        );

    if (
        !typeInput ||
        !valueInput ||
        !dateInput ||
        !timeInput
    ) {

        console.error(
            "Některé pole nového měření nebylo nalezeno."
        );

        return;
    }

    const valueCm =
        String(valueInput.value)
            .trim()
            .replace(",", ".");

    if (
        valueCm === "" ||
        !Number.isFinite(Number(valueCm)) ||
        Number(valueCm) <= 0
    ) {

        if (message) {

            message.textContent =
                "Zadej platnou hodnotu měření.";

            message.classList.add(
                "tree-editor-message--error"
            );

        }

        valueInput.focus();
        return;
    }

    if (
        !dateInput.value ||
        !timeInput.value
    ) {

        if (message) {

            message.textContent =
                "Vyplň datum a čas měření.";

            message.classList.add(
                "tree-editor-message--error"
            );

        }

        return;
    }

    const payload = {
        tree_id: String(treeId),

        measurement_type:
            typeInput.value,

        value_cm:
            valueCm,

        measured_at:
            `${dateInput.value} ` +
            `${timeInput.value}:00`,

        note:
            noteInput
                ? noteInput.value.trim()
                : ""
    };

    if (submitButton) {

        submitButton.disabled = true;
        submitButton.textContent =
            "Ukládám…";

    }

    if (message) {

        message.textContent = "";

        message.classList.remove(
            "tree-editor-message--error",
            "tree-editor-message--success"
        );

    }

    fetch(
        "api/save_tree_measurement.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify(payload)
        }
    )

        .then(function (response) {

            return response
                .json()
                .catch(function () {

                    throw new Error(
                        "Server nevrátil platný JSON."
                    );

                })
                .then(function (data) {

                    if (!response.ok) {

                        throw new Error(
                            data.message ||
                            "Uložení měření selhalo."
                        );

                    }

                    return data;

                });

        })

        .then(function (data) {

            if (!data.success) {

                throw new Error(
                    data.message ||
                    "Uložení měření selhalo."
                );

            }

            if (message) {

                message.textContent =
                    "✓ Měření bylo uloženo.";

                message.classList.add(
                    "tree-editor-message--success"
                );

            }

            valueInput.value = "";

            if (noteInput) {
                noteInput.value = "";
            }

            TreeApp.fillCurrentMeasurementDateTime();

            TreeApp.loadTreeMeasurements(
                treeId
            );

            valueInput.focus();

        })

        .catch(function (error) {

            console.error(
                "Chyba při ukládání měření:",
                error
            );

            if (message) {

                message.textContent =
                    error.message ||
                    "Měření se nepodařilo uložit.";

                message.classList.add(
                    "tree-editor-message--error"
                );

            }

        })

        .finally(function () {

            if (submitButton) {

                submitButton.disabled = false;

                submitButton.textContent =
                    "💾 Uložit měření";

            }

        });

};

TreeApp.closeEditor = function () {

    const panel =
        document.getElementById(
            "tree-editor-panel"
        );

    if (!panel) {
        return;
    }

    panel.classList.remove(
        "tree-editor-panel--open"
    );

    panel.setAttribute(
        "aria-hidden",
        "true"
    );

    TreeApp.editingTreeId = null;

};


// ------------------------------------------------------------
// Přepnutí editoru při výběru jiného stromu
// ------------------------------------------------------------

TreeApp.switchOpenEditorToTree = function (
    treeId
) {

    if (TreeApp.editingTreeId === null) {
        return;
    }

    const id = String(treeId);

    if (
        id ===
        String(TreeApp.editingTreeId)
    ) {
        return;
    }

    TreeApp.openEditor(id);

};

console.log("editor.js inicializován");