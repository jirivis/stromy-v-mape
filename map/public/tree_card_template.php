<?php

declare(strict_types=1);

/*
 * Tento soubor se nespouští samostatně.
 */
if (
    !isset($tree)
    || !is_array($tree)
) {
    http_response_code(500);
    exit('Data stromu nejsou dostupná.');
}


/*
---------------------------------------------------
 Pomocné funkce
---------------------------------------------------
*/

function cardHasValue(
    mixed $value,
    bool $zeroIsEmpty = true
): bool {

    if (
        $value === null
        || $value === ''
    ) {
        return false;
    }

    if (
        $zeroIsEmpty
        && is_numeric($value)
        && (float) $value === 0.0
    ) {
        return false;
    }

    return true;
}


function cardEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function cardText(
    mixed $value
): string {

    return nl2br(
        cardEscape($value)
    );
}


function cardDate(
    mixed $value
): string {

    if (!cardHasValue($value, false)) {
        return '';
    }

    $timestamp =
        strtotime((string) $value);

    if ($timestamp === false) {
        return cardEscape($value);
    }

    return date(
        'j. n. Y',
        $timestamp
    );
}


function cardPrice(
    mixed $value
): string {

    if (!cardHasValue($value)) {
        return '';
    }

    return number_format(
        (float) $value,
        0,
        ',',
        ' '
    ) . ' Kč';
}


function cardAssessmentClass(
    string $field,
    mixed $value
): string {

    $normalized =
        strtolower(
            trim((string) $value)
        );

    if (
        in_array(
            $field,
            [
                'vitality',
                'tree_health',
                'stability',
				'landscape_value'
            ],
            true
        )
    ) {
        return match ($normalized) {
            '1' => 'rating--green',
            '2' => 'rating--blue',
            '3' => 'rating--yellow',
            '4' => 'rating--orange',
            '5' => 'rating--red',
            default => ''
        };
    }

    if ($field === 'longevity') {
        return match ($normalized) {
            'a' => 'rating--green',
            'b' => 'rating--orange',
            'c' => 'rating--red',
            default => ''
        };
    }

    return '';
}


function cardRow(
    string $label,
    mixed $value,
    string $suffix = '',
    bool $zeroIsEmpty = true
): string {

    if (
        !cardHasValue(
            $value,
            $zeroIsEmpty
        )
    ) {
        return '';
    }

    return '
        <tr>
            <th>'
                . cardEscape($label)
                . '</th>
            <td>'
                . cardEscape($value)
                . cardEscape($suffix)
                . '</td>
        </tr>
    ';
}


/*
---------------------------------------------------
 Příprava hodnot
---------------------------------------------------
*/

$treeNumber =
    $tree['usr_id']
    ?? $tree['id'];

$czechName =
    trim(
        (string) (
            $tree['czech']
            ?? ''
        )
    );

$species =
    trim(
        (string) (
            $tree['species']
            ?? ''
        )
    );

$author =
    trim(
        (string) (
            $tree['scientific_author']
            ?? ''
        )
    );

$cultivar =
    trim(
        (string) (
            $tree['cultivated_variety']
            ?? ''
        )
    );

$scientificNameParts = [];

if ($species !== '') {
    $scientificNameParts[] =
        '<em>'
        . cardEscape($species)
        . '</em>';
}

if ($author !== '') {
    $scientificNameParts[] =
        cardEscape($author);
}

if ($cultivar !== '') {
    $scientificNameParts[] =
        '\''
        . cardEscape($cultivar)
        . '\'';
}

$scientificName =
    implode(
        ' ',
        $scientificNameParts
    );


$dbhValues = [];

foreach (
    [
        'dbh1_cm',
        'dbh2_cm',
        'dbh3_cm',
        'dbh4_cm'
    ] as $field
) {
    if (cardHasValue($tree[$field] ?? null)) {
        $dbhValues[] =
            $tree[$field];
    }
}


$girthValues = [];

foreach (
    [
        'girth1_cm',
        'girth2_cm',
        'girth3_cm',
        'girth4_cm'
    ] as $field
) {
    if (cardHasValue($tree[$field] ?? null)) {
        $girthValues[] =
            $tree[$field];
    }
}


$assessmentItems = [
    [
        'field' => 'phy_age',
        'label' => 'Fyziologický věk',
        'short' => 'FyVěk'
    ],
    [
        'field' => 'vitality',
        'label' => 'Vitalita',
        'short' => 'Vit'
    ],
    [
        'field' => 'tree_health',
        'label' => 'Zdravotní stav',
        'short' => 'ZdrS'
    ],
    [
        'field' => 'stability',
        'label' => 'Stabilita',
        'short' => 'Stab'
    ],
    [
        'field' => 'longevity',
        'label' => 'Perspektiva',
        'short' => 'Pers'
    ],
[
    'field' => 'ecological_value',
    'label' => 'Ekologická hodnota',
    'short' => 'EkoH'
],
[
    'field' => 'landscape_value',
    'label' => 'Sadovnická hodnota',
    'short' => 'SadH'
]
];

$assessmentItems =
    array_values(
        array_filter(
            $assessmentItems,
            static function (
                array $item
            ) use ($tree): bool {

                return cardHasValue(
                    $tree[
                        $item['field']
                    ] ?? null
                );
            }
        )
    );


$careItems = [];

for ($urgency = 0; $urgency <= 3; $urgency++) {

    $treatment =
        $tree[
            'treatment_u'
            . $urgency
        ] ?? null;

    $cost =
        $tree[
            'costs_tu'
            . $urgency
        ] ?? null;

    if (
        cardHasValue(
            $treatment,
            false
        )
        || cardHasValue($cost)
    ) {
        $careItems[] = [
            'urgency' => $urgency,
            'treatment' => $treatment,
            'cost' => $cost
        ];
    }
}


$hasDendrometry =
    count($dbhValues) > 0
    || count($girthValues) > 0
    || cardHasValue($tree['height'] ?? null)
    || cardHasValue($tree['canopy_h_m'] ?? null)
    || cardHasValue($tree['spread_m'] ?? null);

$hasLocation =
    (
        cardHasValue(
            $tree['lat_wgs84'] ?? null
        )
        && cardHasValue(
            $tree['lng_wgs84'] ?? null
        )
    )
    || (
        cardHasValue(
            $tree['x_sjtsk'] ?? null
        )
        && cardHasValue(
            $tree['y_sjtsk'] ?? null
        )
    );

$hasDates =
    cardHasValue(
        $tree['planted'] ?? null,
        false
    )
    || cardHasValue(
        $tree['measured_at'] ?? null,
        false
    )
    || cardHasValue(
        $tree['last_insp_at'] ?? null,
        false
    )
    || cardHasValue(
        $tree['next_insp_at'] ?? null,
        false
    );

?>
<!DOCTYPE html>

<html lang="cs">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Karta stromu č. <?= cardEscape($treeNumber) ?>
    </title>

    <style>

        :root {
            --green: #246f38;
            --green-dark: #174b28;
            --green-light: #eef7f0;

            --border: #ccd7cf;
            --text: #1e2c23;
            --muted: #68746c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            background: #edf1ee;
            color: var(--text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .card-page {
            width: min(
                210mm,
                calc(100% - 24px)
            );

            margin: 18px auto;
            padding: 16mm;

            background: #ffffff;

            box-shadow:
                0 5px 25px
                rgba(0, 0, 0, 0.12);
        }

        .controls {
            display: flex;
            justify-content: flex-end;
            gap: 8px;

            margin-bottom: 18px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 38px;
            padding: 8px 14px;

            border: 1px solid var(--green);
            border-radius: 5px;

            background: var(--green);
            color: #ffffff;

            font-size: 14px;
            font-weight: 700;
            text-decoration: none;

            cursor: pointer;
        }

        .button--secondary {
            border-color: #66736a;
            background: #66736a;
        }

        .tree-dead-banner {
            margin: 0 0 20px;
            padding: 16px 20px;
            border: 2px solid #9b3028;
            border-left-width: 8px;
            border-radius: 8px;
            background: #fff0ed;
            color: #7b201b;
            font-size: 1.35rem;
            break-inside: avoid;
        }

        .card-header {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                auto;
            gap: 16px;

            padding-bottom: 14px;

            border-bottom:
                3px solid var(--green);
        }

        .card-header__project {
            margin-bottom: 6px;

            color: var(--muted);

            font-size: 13px;
            font-weight: 700;
        }

        .card-header h1 {
            margin: 0;

            color: var(--green-dark);

            font-size: 27px;
            line-height: 1.2;
        }

        .scientific-name {
            margin-top: 7px;

            color: var(--muted);

            font-size: 17px;
        }

        .tree-number {
            display: flex;
            align-items: center;
            justify-content: center;

            min-width: 72px;
            min-height: 72px;
            padding: 8px;

            border: 3px solid var(--green);
            border-radius: 50%;

            color: var(--green-dark);

            font-size: 24px;
            font-weight: 800;
        }

        .section {
            margin-top: 24px;
        }

        .section__title {
            margin: 0 0 8px;

            color: var(--green-dark);

            font-size: 18px;
        }

        .data-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 14px;
        }

        .data-table th,
        .data-table td {
            padding: 8px 9px;

            border-bottom:
                1px solid #e1e6e2;

            vertical-align: top;
            text-align: left;
        }

        .data-table th {
            width: 38%;

            color: #526056;

            font-weight: 600;
        }

        .assessment-table {
            width: 100%;

            border-collapse: collapse;
            table-layout: fixed;

            text-align: center;
        }

        .assessment-table th,
        .assessment-table td {
            padding: 8px 4px;

            border: 1px solid var(--border);

            text-align: center;
        }

        .assessment-table th {
            background: #f1f4f2;

            font-size: 12px;
        }

        .assessment-value {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 32px;
            min-height: 32px;
            padding: 4px 8px;

            border-radius: 17px;

            font-weight: 800;
        }

        .rating--green {
            background: #2e7d32;
            color: #ffffff;
        }

        .rating--blue {
            background: #1976d2;
            color: #ffffff;
        }

        .rating--yellow {
            background: #f2cf2f;
            color: #182019;
        }

        .rating--orange {
            background: #ef7d00;
            color: #ffffff;
        }

        .rating--red {
            background: #c62828;
            color: #ffffff;
        }

        .care-item {
            margin-bottom: 8px;
            padding: 10px 12px;

            border-left: 5px solid #839087;

            background: #f5f7f5;
        }

        .care-item--u0 {
            border-left-color: #c62828;
        }

        .care-item--u1 {
            border-left-color: #ef7d00;
        }

        .care-item--u2 {
            border-left-color: #1976d2;
        }

        .care-item--u3 {
            border-left-color: #2e7d32;
        }

        .care-item__header {
            display: flex;
            justify-content: space-between;
            gap: 12px;

            font-weight: 800;
        }

        .care-item__text {
            margin-top: 5px;

            line-height: 1.45;
        }

        .note-box {
            padding: 12px 14px;

            border-left: 5px solid var(--green);
            background: var(--green-light);

            line-height: 1.5;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .gallery-item {
            margin: 0;
            padding: 8px;

            border: 1px solid var(--border);
            border-radius: 6px;
        }

        .gallery-item img {
            display: block;

            width: 100%;
            height: 245px;

            object-fit: contain;

            background: #edf0ed;
        }

        .gallery-item figcaption {
            margin-top: 7px;

            color: var(--muted);

            font-size: 12px;
            line-height: 1.4;
        }

        @page {
            size: A4;
            margin: 13mm;
        }

        @media print {

            body {
                background: #ffffff;
            }

            .controls {
                display: none !important;
            }

            .card-page {
                width: auto;
                margin: 0;
                padding: 0;

                box-shadow: none;
            }

            .card-header,
            .section__title {
                break-after: avoid;
                page-break-after: avoid;
            }

            .section {
                break-inside: auto;
            }

            .data-table,
            .assessment-table,
            .note-box,
            .care-item,
            .gallery-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .data-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .gallery {
                break-before: page;
                page-break-before: always;
            }
			
			.history-banner {
    break-inside: avoid;
    page-break-inside: avoid;
}
			
        }

        @media (
            max-width: 700px
        ) {

            .card-page {
                width: 100%;
                margin: 0;
                padding: 18px;
            }

            .card-header {
                grid-template-columns: 1fr;
            }

            .tree-number {
                width: 66px;
                min-width: 66px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }
		
		.history-banner {
    margin-bottom: 22px;
    padding: 14px 16px;

    border: 1px solid #b8cbbb;
    border-left: 5px solid var(--green);
    border-radius: 5px;

    background: #f3f8f4;
}

.history-banner__title {
    margin-bottom: 7px;

    color: var(--green-dark);

    font-size: 17px;
    font-weight: 800;
}

.history-banner__details {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 24px;

    color: #465249;
    font-size: 13px;
}

.history-banner__note {
    margin-top: 8px;

    color: #39453c;

    font-size: 13px;
    line-height: 1.45;
}

    </style>

</head>

<body>

<main class="card-page">

<div class="controls">

    <?php if (($cardMode ?? '') === 'internal'): ?>

<a
    href="/map/public/"
    class="button button--secondary"
    id="back-to-map"
>
    Zpět na mapu
</a>

        <?php if (
            in_array(
                $_SESSION['active_project_role'] ?? 'viewer',
                [
                    'owner',
                    'admin',
                    'editor'
                ],
                true
            )
        ): ?>

            <a
                href="share_tree.php?id=<?= (int) $tree['id'] ?>"
                class="button button--secondary"
            >
                Sdílet kartu
            </a>

        <?php endif; ?>

    <?php endif; ?>
	
	<?php if (($cardMode ?? '') === 'history'): ?>

    <a
        href="<?= cardEscape(
            $historyBackUrl
            ?? '#'
        ) ?>"
        class="button button--secondary"
    >
        Zpět na strom
    </a>

<?php endif; ?>

    <button
        type="button"
        class="button"
        onclick="window.print()"
    >
        Tisk / uložit jako PDF
    </button>

</div>

<?php if (
    ($cardMode ?? '') === 'history'
    && !empty($historyMeta)
): ?>

    <div class="history-banner">

        <div class="history-banner__title">
            Historický stav stromu
        </div>

        <div class="history-banner__details">

            <?php if (
                !empty(
                    $historyMeta['snapshot_at']
                )
            ): ?>

                <span>
                    <strong>Uloženo:</strong>

                    <?= cardEscape(
                        date(
                            'j. n. Y H:i',
                            strtotime(
                                $historyMeta[
                                    'snapshot_at'
                                ]
                            )
                        )
                    ) ?>
                </span>

            <?php endif; ?>


            <?php if (
                !empty(
                    $historyMeta['username']
                )
            ): ?>

                <span>
                    <strong>Uložil:</strong>
                    <?= cardEscape(
                        $historyMeta['username']
                    ) ?>
                </span>

            <?php endif; ?>

        </div>


        <?php if (
            !empty(
                $historyMeta['history_note']
            )
        ): ?>

            <div class="history-banner__note">

                <strong>Poznámka:</strong>

                <?= cardText(
                    $historyMeta[
                        'history_note'
                    ]
                ) ?>

            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>

    <?php if (($tree['removal_type'] ?? '') === 'dead' || (string) ($tree['vitality'] ?? '') === '5'): ?>
        <div class="tree-dead-banner"><strong>Odumřelý jedinec</strong></div>
    <?php endif; ?>

    <header class="card-header">

        <div>

            <?php if (!empty($projectName)): ?>

                <div class="card-header__project">
                    Projekt:
                    <?= cardEscape($projectName) ?>
                </div>

            <?php endif; ?>

            <h1>
                <?= $czechName !== ''
                    ? cardEscape($czechName)
                    : 'Strom'
                ?>
            </h1>

            <?php if ($scientificName !== ''): ?>

                <div class="scientific-name">
                    <?= $scientificName ?>
                </div>

            <?php endif; ?>

        </div>

        <div
            class="tree-number"
            title="Číslo stromu v projektu"
        >
            <?= cardEscape($treeNumber) ?>
        </div>

    </header>


    <?php if ($hasDendrometry): ?>

        <section class="section">

            <h2 class="section__title">
                Dendrometrie
            </h2>

            <table class="data-table">

                <?php if ($dbhValues): ?>

                    <tr>
                        <th>
                            <?= count($dbhValues) > 1
                                ? 'Průměry kmene'
                                : 'Průměr kmene'
                            ?>
                        </th>

                        <td>
                            <?= cardEscape(
                                implode(
                                    '; ',
                                    $dbhValues
                                )
                            ) ?>
                            cm
                        </td>
                    </tr>

                <?php endif; ?>


                <?php if ($girthValues): ?>

                    <tr>
                        <th>
                            <?= count($girthValues) > 1
                                ? 'Obvody kmene'
                                : 'Obvod kmene'
                            ?>
                        </th>

                        <td>
                            <?= cardEscape(
                                implode(
                                    '; ',
                                    $girthValues
                                )
                            ) ?>
                            cm
                        </td>
                    </tr>

                <?php endif; ?>

                <?= cardRow(
                    'Výška',
                    $tree['height'] ?? null,
                    ' m'
                ) ?>

                <?= cardRow(
                    'Nasazení koruny',
                    $tree['canopy_h_m'] ?? null,
                    ' m'
                ) ?>

                <?= cardRow(
                    'Šířka koruny',
                    $tree['spread_m'] ?? null,
                    ' m'
                ) ?>

            </table>

        </section>

    <?php endif; ?>


    <?php if ($assessmentItems): ?>

        <section class="section">

            <h2 class="section__title">
                Hodnocení stromu
            </h2>

            <table class="assessment-table">

                <thead>
                    <tr>

                        <?php foreach ($assessmentItems as $item): ?>

                            <th
                                title="<?= cardEscape(
                                    $item['label']
                                ) ?>"
                            >
                                <?= cardEscape(
                                    $item['short']
                                ) ?>
                            </th>

                        <?php endforeach; ?>

                    </tr>
                </thead>

                <tbody>
                    <tr>

                        <?php foreach ($assessmentItems as $item): ?>

                            <?php
                            $field =
                                $item['field'];

                            $value =
                                $tree[$field] ?? '';

                            $ratingClass =
                                cardAssessmentClass(
                                    $field,
                                    $value
                                );
                            ?>

                            <td>
                                <span
                                    class="assessment-value <?= cardEscape(
                                        $ratingClass
                                    ) ?>"
                                    title="<?= cardEscape(
                                        $item['label']
                                    ) ?>"
                                >
                                    <?= cardEscape($value) ?>
                                </span>
                            </td>

                        <?php endforeach; ?>

                    </tr>
                </tbody>

            </table>

        </section>

    <?php endif; ?>


    <?php if (cardHasValue($tree['note_tree'] ?? null, false)): ?>

        <section class="section">

            <h2 class="section__title">
                Odborná poznámka ke stromu
            </h2>

            <div class="note-box">
                <?= cardText(
                    $tree['note_tree']
                ) ?>
            </div>

        </section>

    <?php endif; ?>


    <?php if (
        $careItems
        || cardHasValue(
            $tree['note_treatment'] ?? null,
            false
        )
        || cardHasValue(
            $tree['treatment_history'] ?? null,
            false
        )
    ): ?>

        <section class="section">

            <h2 class="section__title">
                Plán péče
            </h2>

            <?php foreach ($careItems as $care): ?>

                <div
                    class="care-item care-item--u<?= (int) $care['urgency'] ?>"
                >

                    <div class="care-item__header">

                        <span>
                            U<?= (int) $care['urgency'] ?>
                        </span>

                        <?php if (cardHasValue($care['cost'])): ?>

                            <span>
                                <?= cardPrice(
                                    $care['cost']
                                ) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        cardHasValue(
                            $care['treatment'],
                            false
                        )
                    ): ?>

                        <div class="care-item__text">
                            <?= cardText(
                                $care['treatment']
                            ) ?>
                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>


            <?php if (cardHasValue($tree['costs_total'] ?? null)): ?>

                <table class="data-table">
                    <tr>
                        <th>Celkové náklady</th>
                        <td>
                            <?= cardPrice(
                                $tree['costs_total']
                            ) ?>
                        </td>
                    </tr>
                </table>

            <?php endif; ?>


            <?php if (
                cardHasValue(
                    $tree['note_treatment'] ?? null,
                    false
                )
            ): ?>

                <h3>Poznámka k zásahům</h3>

                <div class="note-box">
                    <?= cardText(
                        $tree['note_treatment']
                    ) ?>
                </div>

            <?php endif; ?>


            <?php if (
                cardHasValue(
                    $tree['treatment_history'] ?? null,
                    false
                )
            ): ?>

                <h3>Historie zásahů</h3>

                <div class="note-box">
                    <?= cardText(
                        $tree['treatment_history']
                    ) ?>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <?php if ($hasDates): ?>

        <section class="section">

            <h2 class="section__title">
                Termíny
            </h2>

            <table class="data-table">

                <?php if (cardHasValue($tree['planted'] ?? null, false)): ?>
                    <tr>
                        <th>Datum výsadby</th>
                        <td><?= cardDate($tree['planted']) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if (cardHasValue($tree['measured_at'] ?? null, false)): ?>
                    <tr>
                        <th>Inventarizováno</th>
                        <td><?= cardDate($tree['measured_at']) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if (cardHasValue($tree['last_insp_at'] ?? null, false)): ?>
                    <tr>
                        <th>Poslední kontrola</th>
                        <td><?= cardDate($tree['last_insp_at']) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if (cardHasValue($tree['next_insp_at'] ?? null, false)): ?>
                    <tr>
                        <th>Příští kontrola</th>
                        <td><?= cardDate($tree['next_insp_at']) ?></td>
                    </tr>
                <?php endif; ?>

            </table>

        </section>

    <?php endif; ?>


    <?php if ($hasLocation): ?>

        <section class="section">

            <h2 class="section__title">
                Poloha
            </h2>

            <table class="data-table">

                <?php if (
                    cardHasValue($tree['lat_wgs84'] ?? null)
                    && cardHasValue($tree['lng_wgs84'] ?? null)
                ): ?>

                    <tr>
                        <th>WGS84</th>
                        <td>
                            <?= cardEscape($tree['lat_wgs84']) ?> N,
                            <?= cardEscape($tree['lng_wgs84']) ?> E
                        </td>
                    </tr>

                <?php endif; ?>


                <?php if (
                    cardHasValue($tree['x_sjtsk'] ?? null)
                    && cardHasValue($tree['y_sjtsk'] ?? null)
                ): ?>

						<tr>
							<th>S-JTSK</th>
							<td>
								Y:
								<?= cardEscape($tree['y_sjtsk']) ?>;
								X:
								<?= cardEscape($tree['x_sjtsk']) ?>
							</td>
						</tr>

                <?php endif; ?>

            </table>

        </section>

    <?php endif; ?>


    <?php if (
        cardHasValue(
            $tree['note_public'] ?? null,
            false
        )
    ): ?>

        <section class="section">

            <h2 class="section__title">
                Další poznámka
            </h2>

            <div class="note-box">
                <?= cardText(
                    $tree['note_public']
                ) ?>
            </div>

        </section>

    <?php endif; ?>


    <?php if ($gallery): ?>

        <section class="section gallery">

            <h2 class="section__title">
                Fotodokumentace
            </h2>

            <div class="gallery-grid">

                <?php foreach ($gallery as $photo): ?>

                    <figure class="gallery-item">

                        <img
                            src="<?= cardEscape(
                                $photo['url'] ?? ''
                            ) ?>"
                            alt="Fotografie stromu"
                        >

                        <?php if (
                            cardHasValue(
                                $photo['note_photo'] ?? null,
                                false
                            )
                        ): ?>

                            <figcaption>
                                <?= cardText(
                                    $photo['note_photo']
                                ) ?>
                            </figcaption>

                        <?php endif; ?>

                    </figure>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

</main>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const backToMap =
            document.getElementById('back-to-map');

        if (!backToMap) {
            return;
        }

        backToMap.addEventListener(
            'click',
            function (event) {

                /*
                 * Pokud byla karta otevřena
                 * z jiného panelu aplikace,
                 * vrátíme se do něj a kartu zavřeme.
                 */
                if (
    window.opener &&
    !window.opener.closed
) {
    event.preventDefault();

    const mapUrl =
        new URL(
            window.opener.location.href
        );

    mapUrl.searchParams.set(
        'focus_tree',
        <?= (int) $tree['id'] ?>
    );

    window.opener.location.href =
        mapUrl.toString();

    window.opener.focus();
    window.close();

    return;
}

                /*
                 * Bez openeru necháme běžný href,
                 * tedy návrat na /map/public/.
                 */

            }
        );

    }
);
</script>

</body>
</html>