'use strict';

window.TreeApp = window.TreeApp || {};

/**
 * Kontrola, zda je hodnota skutečně vyplněná.
 *
 * Hodnota 0 se považuje za platnou.
 */
TreeApp.popupHasValue = function (value) {
    return !(
        value === null ||
        value === undefined ||
        value === '' ||
        value === '—' ||
        value === '–' ||
        value === 'null' ||
        value === 'undefined'
    );
};

/**
 * Kontrola hodnot určených k zobrazení v popupu.
 *
 * Nula se zde považuje za nezadanou hodnotu.
 */
TreeApp.popupHasDisplayValue = function (value) {

    if (!TreeApp.popupHasValue(value)) {
        return false;
    }

    const normalizedValue =
        String(value)
            .trim()
            .replace(',', '.');

    /*
     * Skryje:
     * 0
     * "0"
     * "0.0"
     * "0,0"
     * "00"
     */
    if (
        normalizedValue !== '' &&
        !Number.isNaN(Number(normalizedValue)) &&
        Number(normalizedValue) === 0
    ) {
        return false;
    }

    return true;
};

/**
 * Vrátí první skutečně vyplněnou hodnotu.
 */
TreeApp.popupFirstValue = function (...values) {
    return values.find(TreeApp.popupHasValue) ?? null;
};


/**
 * Bezpečné vložení textu do HTML.
 */
TreeApp.popupEscapeHtml = function (value) {
    if (!TreeApp.popupHasValue(value)) {
        return '';
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};


/**
 * Bezpečné vložení hodnoty do atributu HTML.
 */
TreeApp.popupEscapeAttribute = function (value) {
    return TreeApp.popupEscapeHtml(value);
};


/**
 * Vytvoří jeden řádek atributů.
 *
 * Nevyplněné hodnoty se vůbec nevykreslí.
 */
TreeApp.popupAttributeRow = function (
    label,
    value,
    suffix = ''
) {
    if (!TreeApp.popupHasDisplayValue(value)) {
        return '';
    }

    const safeLabel =
        TreeApp.popupEscapeHtml(label);

    const safeValue =
        TreeApp.popupEscapeHtml(value);

    const safeSuffix =
        TreeApp.popupEscapeHtml(suffix);

    return `
        <div class="tree-popup__row">
            <span class="tree-popup__label">
                ${safeLabel}
            </span>

            <span class="tree-popup__value">
                ${safeValue}${safeSuffix}
            </span>
        </div>
    `;
};


/**
 * Sestavení URL obrázku s verzí proti cache.
 */
function protectedTreePhotoUrl(value) {
    try {
        const url = new URL(value, location.href);
        const match = url.pathname.match(/^\/map\/public\/(uploads|photos)\/(.+)$/);
        if (url.origin !== location.origin || !match) return value;
        const target = new URL('photo.php', new URL('/map/public/', location.origin));
        target.search = url.search;
        target.searchParams.set('folder', match[1]);
        target.searchParams.set('file', decodeURIComponent(match[2]));
        return target.href;
    } catch { return value; }
}

TreeApp.popupVersionedImageUrl = function (
    url,
    photo = null
) {
    if (!TreeApp.popupHasValue(url)) {
        return '';
    }

    url = protectedTreePhotoUrl(url);
    const separator =
        String(url).includes('?')
            ? '&'
            : '?';

    let version = Date.now();

    if (photo) {
        version = [
            photo.id ?? 0,
            photo.sort_order ?? 0
        ].join('-');
    }

    return (
        String(url) +
        separator +
        'photo_version=' +
        encodeURIComponent(version) + '&thumb=1'
    );
};


/**
 * Připraví a seřadí galerii.
 */
TreeApp.preparePopupGallery = function (tree) {
    const gallery =
        Array.isArray(tree.gallery)
            ? [...tree.gallery]
            : [];

    gallery.sort(function (a, b) {
        const sortA =
            Number(a.sort_order ?? 0);

        const sortB =
            Number(b.sort_order ?? 0);

        if (sortA !== sortB) {
            return sortA - sortB;
        }

        return (
            Number(a.id ?? 0) -
            Number(b.id ?? 0)
        );
    });

    const primaryPhotoUrl =
        TreeApp.popupFirstValue(
            tree.photo_url,
            gallery[0]?.url
        );

    /*
     * Hlavní fotografii přesuneme na první místo,
     * aby jí začínala také galerie.
     */
    if (
        TreeApp.popupHasValue(primaryPhotoUrl) &&
        gallery.length > 0
    ) {
        gallery.sort(function (a, b) {
            const aIsPrimary =
                a.url === primaryPhotoUrl;

            const bIsPrimary =
                b.url === primaryPhotoUrl;

            if (aIsPrimary && !bIsPrimary) {
                return -1;
            }

            if (!aIsPrimary && bIsPrimary) {
                return 1;
            }

            const sortA =
                Number(a.sort_order ?? 0);

            const sortB =
                Number(b.sort_order ?? 0);

            if (sortA !== sortB) {
                return sortA - sortB;
            }

            return (
                Number(a.id ?? 0) -
                Number(b.id ?? 0)
            );
        });
    }

    return {
        gallery,
        primaryPhotoUrl
    };
};


/**
 * Vytvoří tabulku hodnocení stromu.
 *
 * Sloupce bez hodnoty se vůbec nevytvoří.
 */
 
 TreeApp.getAssessmentColor = function (
    value,
    fieldName
) {
    const normalized =
        String(value)
            .trim()
            .toLowerCase();

    /*
     * Perspektiva má vlastní stupnici a–c.
     */
    if (fieldName === "longevity") {

        const longevityColors = {
            a: "#2e7d32",
            b: "#ef8f00",
            c: "#c62828"
        };

        return (
            longevityColors[normalized] ||
            "#9e9e9e"
        );
    }

    /*
     * Obecná hodnoticí stupnice 1–5.
     */
    const numericColors = {
        1: "#2e7d32",
        2: "#1976d2",
        3: "#e0b400",
        4: "#ef7d00",
        5: "#c62828"
    };

    return (
        numericColors[Number(value)] ||
        "#9e9e9e"
    );
};


TreeApp.buildAssessmentBadge = function (
    value,
    fieldName
) {
    if (
        !TreeApp.popupHasDisplayValue(value)
    ) {
        return "";
    }

    const color =
        TreeApp.getAssessmentColor(
            value,
            fieldName
        );

    return `
        <span
            class="tree-assessment-badge"
            style="--assessment-color:${color};"
        >
            ${TreeApp.popupEscapeHtml(value)}
        </span>
    `;
};
 
/**
 * Vytvoří fotografii a Lightbox galerii.
 */
TreeApp.buildPopupGallery = function (
    tree,
    gallery,
    primaryPhotoUrl
) {
    if (!TreeApp.popupHasValue(primaryPhotoUrl)) {
        return '';
    }

    const treeNumber =
        TreeApp.popupFirstValue(
            tree.usr_id,
            tree.id
        );

    const primaryItem =
        gallery.find(function (photo) {
            return photo.url === primaryPhotoUrl;
        }) ?? null;

    let primaryCaption =
        `Strom č. ${treeNumber}`;

    const primaryNote =
        TreeApp.popupFirstValue(
            primaryItem?.note_photo,
            primaryItem?.note,
            primaryItem?.caption
        );

    if (TreeApp.popupHasValue(primaryNote)) {
        primaryCaption +=
            ` – ${primaryNote}`;
    }

    const primaryHref =
        TreeApp.popupEscapeAttribute(
            primaryPhotoUrl
        );

    const primarySource =
        TreeApp.popupEscapeAttribute(
            TreeApp.popupVersionedImageUrl(
                primaryPhotoUrl,
                primaryItem
            )
        );

    const lightboxGroup =
        `tree-${TreeApp.popupEscapeAttribute(
            tree.id
        )}`;

    let hiddenGalleryLinks = '';

    gallery.forEach(function (photo) {
        if (
            !TreeApp.popupHasValue(photo.url) ||
            photo.url === primaryPhotoUrl
        ) {
            return;
        }

        let caption =
            `Strom č. ${treeNumber}`;

        const photoNote =
            TreeApp.popupFirstValue(
                photo.note_photo,
                photo.note,
                photo.caption
            );

        if (TreeApp.popupHasValue(photoNote)) {
            caption +=
                ` – ${photoNote}`;
        }

        hiddenGalleryLinks += `
            <a
                href="${TreeApp.popupEscapeAttribute(
                    photo.url
                )}"
                data-lightbox="${lightboxGroup}"
                data-title="${TreeApp.popupEscapeAttribute(
                    caption
                )}"
                class="tree-popup__hidden-gallery-link"
                aria-hidden="true"
                tabindex="-1"
            ></a>
        `;
    });

    const galleryCount =
        gallery.length > 0
            ? gallery.length
            : 1;

    const galleryInformation =
        galleryCount > 1
            ? `
                <div class="tree-popup__gallery-info">
                    Kliknutím na obrázek otevřete galerii
                    (${galleryCount})
                </div>
            `
            : '';

    return `
        <section class="tree-popup__gallery">

            <a
                href="${primaryHref}"
                data-lightbox="${lightboxGroup}"
                data-title="${TreeApp.popupEscapeAttribute(
                    primaryCaption
                )}"
                class="tree-popup__photo-link"
            >
                <img
                    src="${primarySource}"
                    alt="Fotografie stromu"
                    class="tree-popup__photo"
                    loading="lazy"
                >
            </a>

            ${hiddenGalleryLinks}

            ${galleryInformation}

        </section>
    `;
};


/**
 * Hlavní funkce pro vytvoření popupu.
 *
 * Signatura zůstává kompatibilní s původním kódem.
 */
 
 /**
 * Vytvoří obsah buňky Fotky v tabulce stromů.
 */
TreeApp.buildPhotoCell = function (p) {

    if (!p || typeof p !== "object") {
        return "-";
    }

    const gallery =
        Array.isArray(p.gallery)
            ? [...p.gallery]
            : [];

    gallery.sort(function (a, b) {

        const sortA =
            Number(a.sort_order ?? 0);

        const sortB =
            Number(b.sort_order ?? 0);

        if (sortA !== sortB) {
            return sortA - sortB;
        }

        return (
            Number(a.id ?? 0) -
            Number(b.id ?? 0)
        );

    });

    const primaryPhotoUrl =
        TreeApp.popupFirstValue(
            p.photo_url,
            gallery[0]?.url
        );

    if (!TreeApp.popupHasValue(primaryPhotoUrl)) {
        return "-";
    }

    const primaryPhoto =
        gallery.find(function (photo) {
            return photo.url === primaryPhotoUrl;
        }) ?? gallery[0] ?? null;

    const imageUrl =
        TreeApp.popupVersionedImageUrl(
            primaryPhotoUrl,
            primaryPhoto
        );

    const treeNumber =
        TreeApp.popupFirstValue(
            p.usr_id,
            p.tree_number,
            p.number,
            p.id
        );

    const lightboxGroup =
        "table-tree-" +
        TreeApp.popupEscapeAttribute(p.id);

    const hiddenLinks = gallery
        .filter(function (photo) {
            return (
                TreeApp.popupHasValue(photo.url) &&
                photo.url !== primaryPhotoUrl
            );
        })
        .map(function (photo) {

            const caption =
                TreeApp.popupFirstValue(
                    photo.note_photo,
                    photo.note,
                    photo.caption,
                    "Strom č. " + treeNumber
                );

            return `
                <a
                    href="${TreeApp.popupEscapeAttribute(photo.url)}"
                    data-lightbox="${lightboxGroup}"
                    data-title="${TreeApp.popupEscapeAttribute(caption)}"
                    style="display:none"
                    tabindex="-1"
                    aria-hidden="true"
                ></a>
            `;

        })
        .join("");

    return `
        <a
            href="${TreeApp.popupEscapeAttribute(primaryPhotoUrl)}"
            data-lightbox="${lightboxGroup}"
            data-title="Strom č. ${TreeApp.popupEscapeAttribute(treeNumber)}"
            class="tree-table-photo-link"
            onclick="event.stopPropagation();"
        >
            <img
                src="${TreeApp.popupEscapeAttribute(imageUrl)}"
                alt="Fotografie stromu"
                class="tree-table-photo"
                loading="lazy"
            >
        </a>

        ${hiddenLinks}
    `;
};

/**
 * Vrátí barvu pro hodnoticí odznak.
 */
TreeApp.getAssessmentColor = function (
    value,
    fieldName
) {
    const normalized =
        String(value)
            .trim()
            .toLowerCase();

    /*
     * Perspektiva má vlastní stupnici a–c.
     */
    if (fieldName === "longevity") {

        const longevityColors = {
            a: "#2e7d32",
            b: "#ef8f00",
            c: "#c62828"
        };

        return (
            longevityColors[normalized] ||
            "#9e9e9e"
        );
    }

    /*
     * Ostatní hodnoticí stupnice 1–5.
     */
    const numericColors = {
        1: "#2e7d32",
        2: "#1976d2",
        3: "#d6aa00",
        4: "#ef7d00",
        5: "#c62828"
    };

    return (
        numericColors[Number(value)] ||
        "#9e9e9e"
    );
};


/**
 * Vytvoří barevný odznak hodnoty.
 */
TreeApp.buildAssessmentBadge = function (
    value,
    fieldName
) {
    if (
        !TreeApp.popupHasDisplayValue(value)
    ) {
        return "";
    }

    const color =
        TreeApp.getAssessmentColor(
            value,
            fieldName
        );

    return `
        <span
            class="tree-assessment-badge"
            style="--assessment-color: ${color};"
        >
            ${TreeApp.popupEscapeHtml(value)}
        </span>
    `;
};


/**
 * Vytvoří tabulku hodnocení stromu.
 *
 * Nevyplněné a nulové hodnoty se nezobrazí.
 */
TreeApp.buildPopupAssessment = function (tree) {

    const firstValue =
        TreeApp.popupFirstValue;

    const assessmentItems = [
        {
            fieldName: "phy_age",
            abbreviation: "FyVěk",
            title: "Fyziologický věk",
            value: firstValue(
                tree.phy_age,
                tree.physiological_age,
                tree.physiologicalAge,
                tree.fyziologicky_vek
            )
        },
        {
            fieldName: "vitality",
            abbreviation: "Vit",
            title: "Vitalita",
            value: firstValue(
                tree.vitality,
                tree.vitalita
            )
        },
        {
            fieldName: "tree_health",
            abbreviation: "ZdrS",
            title: "Zdravotní stav",
            value: firstValue(
                tree.tree_health,
                tree.health,
                tree.zdravotni_stav
            )
        },
        {
            fieldName: "stability",
            abbreviation: "Stab",
            title: "Statická stabilita",
            value: firstValue(
                tree.stability,
                tree.static_stability,
                tree.staticka_stabilita
            )
        },
        {
            fieldName: "longevity",
            abbreviation: "Pers",
            title: "Perspektiva",
            value: firstValue(
                tree.perspective,
                tree.longevity,
                tree.perspektiva
            )
        },
        {
            fieldName: "ecological_value",
            abbreviation: "BioH",
            title: "Ekologická hodnota",
            value: firstValue(
                tree.ecological_value,
                tree.ecologicalValue,
                tree.ekologicka_hodnota
            )
        },
		
		{
    fieldName: "landscape_value",
    abbreviation: "SadH",
    title: "Sadovnická hodnota",
    value: firstValue(
        tree.landscape_value,
        tree.landscapeValue,
        tree.sadovnicka_hodnota
    )
}
		
    ].filter(function (item) {

        return TreeApp.popupHasDisplayValue(
            item.value
        );

    });

    /*
     * Když není vyplněné žádné hodnocení,
     * nevytvoří se ani nadpis tabulky.
     */
    if (assessmentItems.length === 0) {
        return "";
    }

    const headings =
        assessmentItems
            .map(function (item) {

                return `
                    <th
                        title="${TreeApp.popupEscapeAttribute(
                            item.title
                        )}"
                    >
                        ${TreeApp.popupEscapeHtml(
                            item.abbreviation
                        )}
                    </th>
                `;

            })
            .join("");

    const values =
        assessmentItems
            .map(function (item) {

                const shouldUseBadge = [
                    "vitality",
                    "tree_health",
                    "stability",
                    "longevity",
					"landscape_value"
                ].includes(item.fieldName);

                return `
                    <td
                        title="${TreeApp.popupEscapeAttribute(
                            item.title
                        )}"
                    >
                        ${
                            shouldUseBadge
                                ? TreeApp.buildAssessmentBadge(
                                    item.value,
                                    item.fieldName
                                )
                                : TreeApp.popupEscapeHtml(
                                    item.value
                                )
                        }
                    </td>
                `;

            })
            .join("");

    return `
        <section class="tree-popup__assessment">

            <div class="tree-popup__section-title">
                Hodnocení stromu
            </div>

            <div class="tree-popup__assessment-scroll">

                <table class="tree-popup__assessment-table">

                    <thead>
                        <tr>
                            ${headings}
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            ${values}
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>
    `;
};
 
TreeApp.buildPopupContent = function (
    p,
    dbhValues = [],
    dbhLabel = 'Průměr kmene',
    dbhString = '',
    girthValues = [],
    girthLabel = 'Obvod kmene',
    girthString = ''
) {
    if (!p || typeof p !== 'object') {
        return `
            <div class="tree-popup">
                Data stromu nejsou dostupná.
            </div>
        `;
    }

    const firstValue =
        TreeApp.popupFirstValue;

    const treeNumber =
        firstValue(
            p.usr_id,
            p.tree_number,
            p.number,
            p.id
        );

    const czechName =
        firstValue(
            p.czech,
            p.czech_name,
            p.name_cs
        );

    const scientificName =
        firstValue(
            p.species,
            p.scientific_name,
            p.latin_name
        );

    const {
        gallery,
        primaryPhotoUrl
    } = TreeApp.preparePopupGallery(p);

    /*
     * Jednotky přidáváme jen tehdy,
     * pokud už nejsou součástí hodnoty.
     */
    const dbhSuffix =
        TreeApp.popupHasValue(dbhString) &&
        !String(dbhString).toLowerCase().includes('cm')
            ? ' cm'
            : '';

    const girthSuffix =
        TreeApp.popupHasValue(girthString) &&
        !String(girthString).toLowerCase().includes('cm')
            ? ' cm'
            : '';

const crownWidth =
    firstValue(
        p.crown_width_m,
        p.crown_diameter_m,
        p.spread_m,

        p.spread,
        p.crown_width,
        p.crown_diameter,
        p.sirka_koruny
    );

    const origin =
        firstValue(
            p.origin,
            p.puvod
        );

    const planted =
        firstValue(
            p.planted,
            p.planting_year,
            p.year_planted,
            p.rok_vysadby
        );

    const author =
        firstValue(
            p.author,
            p.species_author,
            p.autor
        );

    const nextInspection =
        firstValue(
            p.next_insp_at,
            p.next_inspection,
            p.nextInspection
        );

    const publicNote =
        firstValue(
            p.note_public,
            p.public_note
        );

    /*
     * Hlavička popupu.
     */
    const headerHtml = `
        <header class="tree-popup__header">

            <div class="tree-popup__heading">

                ${
                    TreeApp.popupHasValue(treeNumber)
                        ? `
                            <div class="tree-popup__number">
                                Strom č. ${TreeApp.popupEscapeHtml(
                                    treeNumber
                                )}
                            </div>
                        `
                        : ''
                }

                ${
                    TreeApp.popupHasValue(czechName)
                        ? `
                            <div class="tree-popup__name">
                                ${TreeApp.popupEscapeHtml(
                                    czechName
                                )}
                            </div>
                        `
                        : ''
                }

            </div>

            <div class="tree-popup__actions">

${
    typeof TreeApp.openEditor === 'function'
    && typeof TreeApp.canEditTrees === 'function'
    && TreeApp.canEditTrees()
        ? `
            <button
                type="button"
                class="tree-popup__button"
                onclick="TreeApp.openEditor('${TreeApp.popupEscapeAttribute(
                    p.id
                )}')"
            >
                Upravit
            </button>
        `
        : ''
}

                <a
                    href="karta.php?id=${encodeURIComponent(
                        p.id
                    )}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tree-popup__button"
                >
                    Karta
                </a>

            </div>

        </header>
    `;

    /*
     * Původní seznam atributů.
     *
     * Každý řádek se zobrazí pouze,
     * když má příslušný údaj hodnotu.
     */
    const attributesHtml = `
        <section class="tree-popup__attributes">

            ${TreeApp.popupAttributeRow(
                'Vědecké jméno',
                scientificName
            )}

            ${TreeApp.popupAttributeRow(
                'Autor',
                author
            )}

            ${TreeApp.popupAttributeRow(
                dbhLabel || 'Průměr kmene',
                dbhString,
                dbhSuffix
            )}

            ${TreeApp.popupAttributeRow(
                girthLabel || 'Obvod kmene',
                girthString,
                girthSuffix
            )}

            ${TreeApp.popupAttributeRow(
    "Výška",
    firstValue(
        p.height_m,
        p.height,
        p.vyska
    ),
    TreeApp.popupHasValue(
        firstValue(
            p.height_m,
            p.height,
            p.vyska
        )
    )
        ? " m"
        : ""
)}

            ${TreeApp.popupAttributeRow(
                'Šířka koruny',
                crownWidth,
                TreeApp.popupHasValue(crownWidth)
                    ? ' m'
                    : ''
            )}
			
			${TreeApp.popupAttributeRow(
    "Výška nasazení koruny",
    firstValue(
        p.crown_base_height_m,
        p.crown_base_height,
        p.height_crown_base,
        p.vyska_nasazeni_koruny
    ),
    TreeApp.popupHasValue(
        firstValue(
            p.crown_base_height_m,
            p.crown_base_height,
            p.height_crown_base,
            p.vyska_nasazeni_koruny
        )
    )
        ? " m"
        : ""
)}

            ${TreeApp.popupAttributeRow(
                'Rok výsadby',
                planted
            )}

            ${TreeApp.popupAttributeRow(
                'Původ',
                origin
            )}

            ${TreeApp.popupAttributeRow(
                'Příští kontrola',
                nextInspection
            )}

        </section>
    `;

    const assessmentHtml =
        TreeApp.buildPopupAssessment(p);

    const noteHtml =
        TreeApp.popupHasValue(publicNote)
            ? `
                <section class="tree-popup__note">
                    ${TreeApp.popupEscapeHtml(
                        publicNote
                    )}
                </section>
            `
            : '';

    const galleryHtml =
        TreeApp.buildPopupGallery(
            p,
            gallery,
            primaryPhotoUrl
        );

    console.log(
        'Popup stromu:',
        p.id,
        {
            photo_url: p.photo_url,
            primaryPhotoUrl,
            gallery
        }
    );

    return `
        <article class="tree-popup">

            ${headerHtml}

            ${attributesHtml}

            ${assessmentHtml}

            ${noteHtml}

            ${galleryHtml}

        </article>
    `;
};


/**
 * Doporučené nastavení Leaflet popupu.
 *
 * Použití:
 *
 * marker.bindPopup(
 *     popupContent,
 *     TreeApp.treePopupOptions
 * );
 */
TreeApp.treePopupOptions = {
    autoPan: true,
    keepInView: true,

    autoPanPaddingTopLeft: [30, 30],
    autoPanPaddingBottomRight: [30, 30],

    minWidth: 280,
    maxWidth: 380,

    closeButton: true,
    autoClose: true,
    closeOnClick: true
};