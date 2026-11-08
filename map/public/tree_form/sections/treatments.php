<?php
/**
 * Vrátí atribut checked pro hodnotu uloženou v databázi.
 * Podporuje JSON pole i starší text oddělený čárkou/středníkem.
 */
if (!function_exists('treatmentChecked')) {
    function treatmentChecked(string $field, string $value, array $tree): string
    {
        $raw = $tree[$field] ?? '';

        if (is_array($raw)) {
            $selected = $raw;
        } else {
            $raw = trim((string)$raw);
            $selected = [];

            if ($raw !== '') {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $selected = $decoded;
                } else {
                    $selected = preg_split('/\s*[,;|\r\n]+\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                }
            }
        }

        $selected = array_map(
            static fn($item): string => trim((string)$item),
            $selected
        );

        return in_array($value, $selected, true) ? 'checked' : '';
    }
}

if (!function_exists('treeFormValue')) {
    function treeFormValue(array $tree, string $field): string
    {
        return htmlspecialchars(
            (string)($tree[$field] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
?>

<!-- 🟧 SEKCE 4 — OŠETŘENÍ -->
   <h2>Plán péče</h2>

<div class="collapsible" data-title="Ošetření naléhavost 0">Ošetření naléhavost 0</div>
<div class="content">

    <div class="sub-collapsible">Řez stromů dle A02 002</div>
    <div class="sub-content">

    <div class="sub2-collapsible">Řezy zakládací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RZK" <?= treatmentChecked('treatment_u0', 'S-RZK', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RZK – Řez zapěstování koruny
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RPV" <?= treatmentChecked('treatment_u0', 'S-RPV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RPV – Řez při výsadbě
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RV" <?= treatmentChecked('treatment_u0', 'S-RV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RV – Řez výchovný
    </span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy udržovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RB" <?= treatmentChecked('treatment_u0', 'S-RB', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RB – Řez bezpečnostní</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RZ" <?= treatmentChecked('treatment_u0', 'S-RZ', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RZ – Řez zdravotní</span>
</label>

<div class="section-divider"></div>

        <p><strong>Lokální řezy:</strong></p>
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RLPV" <?= treatmentChecked('treatment_u0', 'S-RLPV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLPV – Úprava volného profilu koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RLSP" <?= treatmentChecked('treatment_u0', 'S-RLSP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLSP – Lokální redukce směrem k překážce</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RLLR" <?= treatmentChecked('treatment_u0', 'S-RLLR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLLR – Lokální redukce z důvodu stabilizace</span>
</label>

<div class="section-divider"></div>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OV" <?= treatmentChecked('treatment_u0', 'S-OV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑OV – Odstranění výmladků</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy stabilizační</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RO" <?= treatmentChecked('treatment_u0', 'S-RO', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RO – Redukce obvodová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-SSK" <?= treatmentChecked('treatment_u0', 'S-SSK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RS" <?= treatmentChecked('treatment_u0', 'S-RS', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RS – Řez sesazovací</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy tvarovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RTHL" <?= treatmentChecked('treatment_u0', 'S-RTHL', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTHL – Řez na hlavu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RTCR" <?= treatmentChecked('treatment_u0', 'S-RTCR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTCR – Řez na čípek ramenový</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RTPP" <?= treatmentChecked('treatment_u0', 'S-RTPP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTPP – Řez popouštěcí</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RTZP" <?= treatmentChecked('treatment_u0', 'S-RTZP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTZP – Řez živých plotů a stěn</span>
</label>

    </div>

    <div class="sub2-collapsible">Řezy opravné a rekonstrukční</div>
    <div class="sub2-content">
        <label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-RRK" <?= treatmentChecked('treatment_u0', 'S-RRK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RRK – Řez rekonstrukční</span>
</label>

    </div>

    </div>

	<div class="sub2-collapsible">Řez ovocných stromů dle C02 005</div>
	<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RK" <?= treatmentChecked('treatment_u0', 'O-RK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RK – Řez na korunku ovocných stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RV" <?= treatmentChecked('treatment_u0', 'O-RV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RV – Řez výchovný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RP" <?= treatmentChecked('treatment_u0', 'O-RP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RP – Řez ovocných dřevin prosvětlovací – průklest</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RO" <?= treatmentChecked('treatment_u0', 'O-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RO – Řez opravný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RA" <?= treatmentChecked('treatment_u0', 'O-RA', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RA – Řez ovocných dřevin zdravotní – asanační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-OV" <?= treatmentChecked('treatment_u0', 'O-OV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑OV – Odstranění vlků a výmladků ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RZM" <?= treatmentChecked('treatment_u0', 'O-RZM', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZM – Řez ovocných dřevin zmlazovací mírný</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RZS" <?= treatmentChecked('treatment_u0', 'O-RZS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZS – Řez ovocných dřevin zmlazovací střední</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="O-RZH" <?= treatmentChecked('treatment_u0', 'O-RZH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZH – Řez ovocných dřevin zmlazovací hluboký</span>
</label>

</div>

<div class="sub2-collapsible">Kácení stromů dle A02 005</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-KV" <?= treatmentChecked('treatment_u0', 'S-KV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KV – Kácení stromů volné</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-KSP" <?= treatmentChecked('treatment_u0', 'S-KSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KSP – Kácení stromů s přetažením</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-KPV" <?= treatmentChecked('treatment_u0', 'S-KPV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPV – Postupné kácení s volnou dopadovou plochou</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-KPP" <?= treatmentChecked('treatment_u0', 'S-KPP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPP – Postupné kácení s překážkou v dopadové ploše</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-US" <?= treatmentChecked('treatment_u0', 'S-US', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑US – Úprava pařezu seříznutím</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OR" <?= treatmentChecked('treatment_u0', 'S-OR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OR – Odstranění pařezu ruční (klučením)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OK" <?= treatmentChecked('treatment_u0', 'S-OK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OK – Odstranění pařezu klučením těžkou mechanizací</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OF" <?= treatmentChecked('treatment_u0', 'S-OF', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OF – Odstranění pařezu frézováním</span>
</label>

</div>

<div class="sub2-collapsible">Bezpečnostní vazby dle A02 004</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VDH" <?= treatmentChecked('treatment_u0', 'S-VDH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDH – vazba dynamická v horní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VDD" <?= treatmentChecked('treatment_u0', 'S-VDD', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDD – vazba dynamická v dolní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VSV" <?= treatmentChecked('treatment_u0', 'S-VSV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSV – vazba statická vrtaná</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VSP" <?= treatmentChecked('treatment_u0', 'S-VSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSP – vazba statická podkladnicová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VO" <?= treatmentChecked('treatment_u0', 'S-VO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VO – instalace obruče</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VP" <?= treatmentChecked('treatment_u0', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – instalace podpěry</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VK" <?= treatmentChecked('treatment_u0', 'S-VK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VK – revizní kontrola již instalovaného stabilizačního systému</span>
</label>

</div>

<div class="sub2-collapsible">Speciální zásahy na stromech dle A02 009</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RO" <?= treatmentChecked('treatment_u0', 'SZ-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RO – Řízená obvodová redukce (retrenchment)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RR" <?= treatmentChecked('treatment_u0', 'SZ-RR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RR – Řez regenerační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RB" <?= treatmentChecked('treatment_u0', 'SZ-RB', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RB – Bezpečnostní řez stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RLLR" <?= treatmentChecked('treatment_u0', 'SZ-RLLR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RLLR – Lokální redukce stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-SSK" <?= treatmentChecked('treatment_u0', 'SZ-SSK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RS" <?= treatmentChecked('treatment_u0', 'SZ-RS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RS – Sesazovací řez</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-RT" <?= treatmentChecked('treatment_u0', 'SZ-RT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RT – Zapěstování koruny sesazených stromů a torz</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-ST" <?= treatmentChecked('treatment_u0', 'SZ-ST', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ST – Sesazení stromu na torzo</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-OU" <?= treatmentChecked('treatment_u0', 'SZ-OU', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OU – Management okolního porostu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-OS" <?= treatmentChecked('treatment_u0', 'SZ-OS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OS – Úprava stanovištních poměrů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-KO" <?= treatmentChecked('treatment_u0', 'SZ-KO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KO – Konzervační ošetření poranění</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-KZ" <?= treatmentChecked('treatment_u0', 'SZ-KZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KZ – Konzervační ošetření dutin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-ZZ" <?= treatmentChecked('treatment_u0', 'SZ-ZZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ZZ – Ochrana senescentních stromů proti zvěři</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-MH" <?= treatmentChecked('treatment_u0', 'SZ-MH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑MH – Mechanická ochrana proti hmyzím škůdcům</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-CHP" <?= treatmentChecked('treatment_u0', 'SZ-CHP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHP – Ochrana postřikem proti škůdcům/chorobám</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-CHI" <?= treatmentChecked('treatment_u0', 'SZ-CHI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHI – Ochrana mikro‑injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-VI" <?= treatmentChecked('treatment_u0', 'SZ-VI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑VI – Podpora vitality injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-JO" <?= treatmentChecked('treatment_u0', 'SZ-JO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑JO – Odstraňování jmelí / poloparazitů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-LO" <?= treatmentChecked('treatment_u0', 'SZ-LO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LO – Odstranění lián z koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="SZ-LR" <?= treatmentChecked('treatment_u0', 'SZ-LR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LR – Redukce lián</span>
</label>

</div>


<div class="sub2-collapsible">Ostatní zásahy</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-HRI" <?= treatmentChecked('treatment_u0', 'S-HRI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRI – Instalace hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-HRK" <?= treatmentChecked('treatment_u0', 'S-HRK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRK – Revizní kontrola již instalovaného hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OKT" <?= treatmentChecked('treatment_u0', 'S-OKT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OKT – Odstranění/oprava kotvení mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-OUV" <?= treatmentChecked('treatment_u0', 'S-OUV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OUV – Odstranění/oprava úvazku mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-TP" <?= treatmentChecked('treatment_u0', 'S-TP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TP – Přístrojový test stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-TVV" <?= treatmentChecked('treatment_u0', 'S-TVV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVV – Specializovaný průzkum stromu detailní ze země</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-TVL" <?= treatmentChecked('treatment_u0', 'S-TVL', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVL – Specializovaný průzkum stromu detailní s využitím lezecké techniky</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u0[]" value="S-VP" <?= treatmentChecked('treatment_u0', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – Instalace podpěry koruny či kosterních větví (uveď počet podpěr)</span>
</label>

</div>

<div class="sub2-collapsible">Vlastní zásah (text)</div>
<div class="sub2-content">
    <label>Zásahy dle uživatele:</label>
    <input type="text" name="treatment_u0_custom" value="<?= treeFormValue($tree, 'treatment_u0_custom') ?>" placeholder="Např. chybějící, nebo speciální zásahy mimo metodiku">
</div>

    <label>Náklady naléhavost 0</label>
    <input type="number" name="costs_tu0" step="any" value="<?= treeFormValue($tree, 'costs_tu0') ?>">

</div>

<div class="collapsible" data-title="Ošetření naléhavost 1">Ošetření naléhavost 1</div>
<div class="content">

    <div class="sub-collapsible">Řez stromů dle A02 002</div>
    <div class="sub-content">

    <div class="sub2-collapsible">Řezy zakládací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RZK" <?= treatmentChecked('treatment_u1', 'S-RZK', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RZK – Řez zapěstování koruny
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RPV" <?= treatmentChecked('treatment_u1', 'S-RPV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RPV – Řez při výsadbě
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RV" <?= treatmentChecked('treatment_u1', 'S-RV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RV – Řez výchovný
    </span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy udržovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RB" <?= treatmentChecked('treatment_u1', 'S-RB', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RB – Řez bezpečnostní</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RZ" <?= treatmentChecked('treatment_u1', 'S-RZ', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RZ – Řez zdravotní</span>
</label>

<div class="section-divider"></div>

        <p><strong>Lokální řezy:</strong></p>
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RLPV" <?= treatmentChecked('treatment_u1', 'S-RLPV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLPV – Úprava volného profilu koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RLSP" <?= treatmentChecked('treatment_u1', 'S-RLSP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLSP – Lokální redukce směrem k překážce</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RLLR" <?= treatmentChecked('treatment_u1', 'S-RLLR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLLR – Lokální redukce z důvodu stabilizace</span>
</label>

<div class="section-divider"></div>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OV" <?= treatmentChecked('treatment_u1', 'S-OV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑OV – Odstranění výmladků</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy stabilizační</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RO" <?= treatmentChecked('treatment_u1', 'S-RO', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RO – Redukce obvodová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-SSK" <?= treatmentChecked('treatment_u1', 'S-SSK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RS" <?= treatmentChecked('treatment_u1', 'S-RS', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RS – Řez sesazovací</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy tvarovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RTHL" <?= treatmentChecked('treatment_u1', 'S-RTHL', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTHL – Řez na hlavu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RTCR" <?= treatmentChecked('treatment_u1', 'S-RTCR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTCR – Řez na čípek ramenový</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RTPP" <?= treatmentChecked('treatment_u1', 'S-RTPP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTPP – Řez popouštěcí</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RTZP" <?= treatmentChecked('treatment_u1', 'S-RTZP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTZP – Řez živých plotů a stěn</span>
</label>

    </div>

    <div class="sub2-collapsible">Řezy opravné a rekonstrukční</div>
    <div class="sub2-content">
        <label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-RRK" <?= treatmentChecked('treatment_u1', 'S-RRK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RRK – Řez rekonstrukční</span>
</label>

    </div>

    </div>

	<div class="sub2-collapsible">Řez ovocných stromů dle C02 005</div>
	<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RK" <?= treatmentChecked('treatment_u1', 'O-RK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RK – Řez na korunku ovocných stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RV" <?= treatmentChecked('treatment_u1', 'O-RV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RV – Řez výchovný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RP" <?= treatmentChecked('treatment_u1', 'O-RP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RP – Řez ovocných dřevin prosvětlovací – průklest</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RO" <?= treatmentChecked('treatment_u1', 'O-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RO – Řez opravný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RA" <?= treatmentChecked('treatment_u1', 'O-RA', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RA – Řez ovocných dřevin zdravotní – asanační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-OV" <?= treatmentChecked('treatment_u1', 'O-OV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑OV – Odstranění vlků a výmladků ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RZM" <?= treatmentChecked('treatment_u1', 'O-RZM', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZM – Řez ovocných dřevin zmlazovací mírný</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RZS" <?= treatmentChecked('treatment_u1', 'O-RZS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZS – Řez ovocných dřevin zmlazovací střední</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="O-RZH" <?= treatmentChecked('treatment_u1', 'O-RZH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZH – Řez ovocných dřevin zmlazovací hluboký</span>
</label>

</div>

<div class="sub2-collapsible">Kácení stromů dle A02 005</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-KV" <?= treatmentChecked('treatment_u1', 'S-KV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KV – Kácení stromů volné</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-KSP" <?= treatmentChecked('treatment_u1', 'S-KSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KSP – Kácení stromů s přetažením</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-KPV" <?= treatmentChecked('treatment_u1', 'S-KPV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPV – Postupné kácení s volnou dopadovou plochou</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-KPP" <?= treatmentChecked('treatment_u1', 'S-KPP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPP – Postupné kácení s překážkou v dopadové ploše</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-US" <?= treatmentChecked('treatment_u1', 'S-US', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑US – Úprava pařezu seříznutím</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OR" <?= treatmentChecked('treatment_u1', 'S-OR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OR – Odstranění pařezu ruční (klučením)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OK" <?= treatmentChecked('treatment_u1', 'S-OK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OK – Odstranění pařezu klučením těžkou mechanizací</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OF" <?= treatmentChecked('treatment_u1', 'S-OF', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OF – Odstranění pařezu frézováním</span>
</label>

</div>

<div class="sub2-collapsible">Bezpečnostní vazby dle A02 004</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VDH" <?= treatmentChecked('treatment_u1', 'S-VDH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDH – vazba dynamická v horní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VDD" <?= treatmentChecked('treatment_u1', 'S-VDD', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDD – vazba dynamická v dolní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VSV" <?= treatmentChecked('treatment_u1', 'S-VSV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSV – vazba statická vrtaná</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VSP" <?= treatmentChecked('treatment_u1', 'S-VSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSP – vazba statická podkladnicová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VO" <?= treatmentChecked('treatment_u1', 'S-VO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VO – instalace obruče</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VP" <?= treatmentChecked('treatment_u1', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – instalace podpěry</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VK" <?= treatmentChecked('treatment_u1', 'S-VK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VK – revizní kontrola již instalovaného stabilizačního systému</span>
</label>

</div>

<div class="sub2-collapsible">Speciální zásahy na stromech dle A02 009</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RO" <?= treatmentChecked('treatment_u1', 'SZ-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RO – Řízená obvodová redukce (retrenchment)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RR" <?= treatmentChecked('treatment_u1', 'SZ-RR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RR – Řez regenerační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RB" <?= treatmentChecked('treatment_u1', 'SZ-RB', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RB – Bezpečnostní řez stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RLLR" <?= treatmentChecked('treatment_u1', 'SZ-RLLR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RLLR – Lokální redukce stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-SSK" <?= treatmentChecked('treatment_u1', 'SZ-SSK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RS" <?= treatmentChecked('treatment_u1', 'SZ-RS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RS – Sesazovací řez</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-RT" <?= treatmentChecked('treatment_u1', 'SZ-RT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RT – Zapěstování koruny sesazených stromů a torz</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-ST" <?= treatmentChecked('treatment_u1', 'SZ-ST', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ST – Sesazení stromu na torzo</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-OU" <?= treatmentChecked('treatment_u1', 'SZ-OU', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OU – Management okolního porostu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-OS" <?= treatmentChecked('treatment_u1', 'SZ-OS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OS – Úprava stanovištních poměrů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-KO" <?= treatmentChecked('treatment_u1', 'SZ-KO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KO – Konzervační ošetření poranění</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-KZ" <?= treatmentChecked('treatment_u1', 'SZ-KZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KZ – Konzervační ošetření dutin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-ZZ" <?= treatmentChecked('treatment_u1', 'SZ-ZZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ZZ – Ochrana senescentních stromů proti zvěři</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-MH" <?= treatmentChecked('treatment_u1', 'SZ-MH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑MH – Mechanická ochrana proti hmyzím škůdcům</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-CHP" <?= treatmentChecked('treatment_u1', 'SZ-CHP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHP – Ochrana postřikem proti škůdcům/chorobám</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-CHI" <?= treatmentChecked('treatment_u1', 'SZ-CHI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHI – Ochrana mikro‑injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-VI" <?= treatmentChecked('treatment_u1', 'SZ-VI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑VI – Podpora vitality injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-JO" <?= treatmentChecked('treatment_u1', 'SZ-JO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑JO – Odstraňování jmelí / poloparazitů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-LO" <?= treatmentChecked('treatment_u1', 'SZ-LO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LO – Odstranění lián z koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="SZ-LR" <?= treatmentChecked('treatment_u1', 'SZ-LR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LR – Redukce lián</span>
</label>

</div>


<div class="sub2-collapsible">Ostatní zásahy</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-HRI" <?= treatmentChecked('treatment_u1', 'S-HRI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRI – Instalace hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-HRK" <?= treatmentChecked('treatment_u1', 'S-HRK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRK – Revizní kontrola již instalovaného hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OKT" <?= treatmentChecked('treatment_u1', 'S-OKT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OKT – Odstranění/oprava kotvení mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-OUV" <?= treatmentChecked('treatment_u1', 'S-OUV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OUV – Odstranění/oprava úvazku mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-TP" <?= treatmentChecked('treatment_u1', 'S-TP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TP – Přístrojový test stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-TVV" <?= treatmentChecked('treatment_u1', 'S-TVV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVV – Specializovaný průzkum stromu detailní ze země</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-TVL" <?= treatmentChecked('treatment_u1', 'S-TVL', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVL – Specializovaný průzkum stromu detailní s využitím lezecké techniky</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u1[]" value="S-VP" <?= treatmentChecked('treatment_u1', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – Instalace podpěry koruny či kosterních větví (uveď počet podpěr)</span>
</label>

</div>

<div class="sub2-collapsible">Vlastní zásah (text)</div>
<div class="sub2-content">
    <label>Zásahy dle uživatele:</label>
    <input type="text" name="treatment_u1_custom" value="<?= treeFormValue($tree, 'treatment_u1_custom') ?>" placeholder="Např. chybějící, nebo speciální zásahy mimo metodiku">
</div>

    <label>Náklady naléhavost 1</label>
    <input type="number" name="costs_tu1" step="any" value="<?= treeFormValue($tree, 'costs_tu1') ?>">

</div>

<div class="collapsible" data-title="Ošetření naléhavost 2">Ošetření naléhavost 2</div>
<div class="content">

    <div class="sub-collapsible">Řez stromů dle A02 002</div>
    <div class="sub-content">

    <div class="sub2-collapsible">Řezy zakládací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RZK" <?= treatmentChecked('treatment_u2', 'S-RZK', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RZK – Řez zapěstování koruny
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RPV" <?= treatmentChecked('treatment_u2', 'S-RPV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RPV – Řez při výsadbě
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RV" <?= treatmentChecked('treatment_u2', 'S-RV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RV – Řez výchovný
    </span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy udržovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RB" <?= treatmentChecked('treatment_u2', 'S-RB', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RB – Řez bezpečnostní</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RZ" <?= treatmentChecked('treatment_u2', 'S-RZ', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RZ – Řez zdravotní</span>
</label>

<div class="section-divider"></div>

        <p><strong>Lokální řezy:</strong></p>
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RLPV" <?= treatmentChecked('treatment_u2', 'S-RLPV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLPV – Úprava volného profilu koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RLSP" <?= treatmentChecked('treatment_u2', 'S-RLSP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLSP – Lokální redukce směrem k překážce</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RLLR" <?= treatmentChecked('treatment_u2', 'S-RLLR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLLR – Lokální redukce z důvodu stabilizace</span>
</label>

<div class="section-divider"></div>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OV" <?= treatmentChecked('treatment_u2', 'S-OV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑OV – Odstranění výmladků</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy stabilizační</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RO" <?= treatmentChecked('treatment_u2', 'S-RO', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RO – Redukce obvodová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-SSK" <?= treatmentChecked('treatment_u2', 'S-SSK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RS" <?= treatmentChecked('treatment_u2', 'S-RS', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RS – Řez sesazovací</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy tvarovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RTHL" <?= treatmentChecked('treatment_u2', 'S-RTHL', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTHL – Řez na hlavu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RTCR" <?= treatmentChecked('treatment_u2', 'S-RTCR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTCR – Řez na čípek ramenový</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RTPP" <?= treatmentChecked('treatment_u2', 'S-RTPP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTPP – Řez popouštěcí</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RTZP" <?= treatmentChecked('treatment_u2', 'S-RTZP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTZP – Řez živých plotů a stěn</span>
</label>

    </div>

    <div class="sub2-collapsible">Řezy opravné a rekonstrukční</div>
    <div class="sub2-content">
        <label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-RRK" <?= treatmentChecked('treatment_u2', 'S-RRK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RRK – Řez rekonstrukční</span>
</label>

    </div>

    </div>

	<div class="sub2-collapsible">Řez ovocných stromů dle C02 005</div>
	<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RK" <?= treatmentChecked('treatment_u2', 'O-RK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RK – Řez na korunku ovocných stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RV" <?= treatmentChecked('treatment_u2', 'O-RV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RV – Řez výchovný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RP" <?= treatmentChecked('treatment_u2', 'O-RP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RP – Řez ovocných dřevin prosvětlovací – průklest</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RO" <?= treatmentChecked('treatment_u2', 'O-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RO – Řez opravný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RA" <?= treatmentChecked('treatment_u2', 'O-RA', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RA – Řez ovocných dřevin zdravotní – asanační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-OV" <?= treatmentChecked('treatment_u2', 'O-OV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑OV – Odstranění vlků a výmladků ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RZM" <?= treatmentChecked('treatment_u2', 'O-RZM', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZM – Řez ovocných dřevin zmlazovací mírný</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RZS" <?= treatmentChecked('treatment_u2', 'O-RZS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZS – Řez ovocných dřevin zmlazovací střední</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="O-RZH" <?= treatmentChecked('treatment_u2', 'O-RZH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZH – Řez ovocných dřevin zmlazovací hluboký</span>
</label>

</div>

<div class="sub2-collapsible">Kácení stromů dle A02 005</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-KV" <?= treatmentChecked('treatment_u2', 'S-KV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KV – Kácení stromů volné</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-KSP" <?= treatmentChecked('treatment_u2', 'S-KSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KSP – Kácení stromů s přetažením</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-KPV" <?= treatmentChecked('treatment_u2', 'S-KPV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPV – Postupné kácení s volnou dopadovou plochou</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-KPP" <?= treatmentChecked('treatment_u2', 'S-KPP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPP – Postupné kácení s překážkou v dopadové ploše</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-US" <?= treatmentChecked('treatment_u2', 'S-US', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑US – Úprava pařezu seříznutím</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OR" <?= treatmentChecked('treatment_u2', 'S-OR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OR – Odstranění pařezu ruční (klučením)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OK" <?= treatmentChecked('treatment_u2', 'S-OK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OK – Odstranění pařezu klučením těžkou mechanizací</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OF" <?= treatmentChecked('treatment_u2', 'S-OF', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OF – Odstranění pařezu frézováním</span>
</label>

</div>

<div class="sub2-collapsible">Bezpečnostní vazby dle A02 004</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VDH" <?= treatmentChecked('treatment_u2', 'S-VDH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDH – vazba dynamická v horní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VDD" <?= treatmentChecked('treatment_u2', 'S-VDD', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDD – vazba dynamická v dolní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VSV" <?= treatmentChecked('treatment_u2', 'S-VSV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSV – vazba statická vrtaná</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VSP" <?= treatmentChecked('treatment_u2', 'S-VSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSP – vazba statická podkladnicová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VO" <?= treatmentChecked('treatment_u2', 'S-VO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VO – instalace obruče</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VP" <?= treatmentChecked('treatment_u2', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – instalace podpěry</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VK" <?= treatmentChecked('treatment_u2', 'S-VK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VK – revizní kontrola již instalovaného stabilizačního systému</span>
</label>

</div>

<div class="sub2-collapsible">Speciální zásahy na stromech dle A02 009</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RO" <?= treatmentChecked('treatment_u2', 'SZ-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RO – Řízená obvodová redukce (retrenchment)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RR" <?= treatmentChecked('treatment_u2', 'SZ-RR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RR – Řez regenerační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RB" <?= treatmentChecked('treatment_u2', 'SZ-RB', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RB – Bezpečnostní řez stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RLLR" <?= treatmentChecked('treatment_u2', 'SZ-RLLR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RLLR – Lokální redukce stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-SSK" <?= treatmentChecked('treatment_u2', 'SZ-SSK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RS" <?= treatmentChecked('treatment_u2', 'SZ-RS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RS – Sesazovací řez</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-RT" <?= treatmentChecked('treatment_u2', 'SZ-RT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RT – Zapěstování koruny sesazených stromů a torz</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-ST" <?= treatmentChecked('treatment_u2', 'SZ-ST', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ST – Sesazení stromu na torzo</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-OU" <?= treatmentChecked('treatment_u2', 'SZ-OU', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OU – Management okolního porostu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-OS" <?= treatmentChecked('treatment_u2', 'SZ-OS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OS – Úprava stanovištních poměrů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-KO" <?= treatmentChecked('treatment_u2', 'SZ-KO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KO – Konzervační ošetření poranění</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-KZ" <?= treatmentChecked('treatment_u2', 'SZ-KZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KZ – Konzervační ošetření dutin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-ZZ" <?= treatmentChecked('treatment_u2', 'SZ-ZZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ZZ – Ochrana senescentních stromů proti zvěři</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-MH" <?= treatmentChecked('treatment_u2', 'SZ-MH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑MH – Mechanická ochrana proti hmyzím škůdcům</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-CHP" <?= treatmentChecked('treatment_u2', 'SZ-CHP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHP – Ochrana postřikem proti škůdcům/chorobám</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-CHI" <?= treatmentChecked('treatment_u2', 'SZ-CHI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHI – Ochrana mikro‑injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-VI" <?= treatmentChecked('treatment_u2', 'SZ-VI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑VI – Podpora vitality injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-JO" <?= treatmentChecked('treatment_u2', 'SZ-JO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑JO – Odstraňování jmelí / poloparazitů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-LO" <?= treatmentChecked('treatment_u2', 'SZ-LO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LO – Odstranění lián z koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="SZ-LR" <?= treatmentChecked('treatment_u2', 'SZ-LR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LR – Redukce lián</span>
</label>

</div>


<div class="sub2-collapsible">Ostatní zásahy</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-HRI" <?= treatmentChecked('treatment_u2', 'S-HRI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRI – Instalace hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-HRK" <?= treatmentChecked('treatment_u2', 'S-HRK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRK – Revizní kontrola již instalovaného hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OKT" <?= treatmentChecked('treatment_u2', 'S-OKT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OKT – Odstranění/oprava kotvení mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-OUV" <?= treatmentChecked('treatment_u2', 'S-OUV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OUV – Odstranění/oprava úvazku mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-TP" <?= treatmentChecked('treatment_u2', 'S-TP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TP – Přístrojový test stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-TVV" <?= treatmentChecked('treatment_u2', 'S-TVV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVV – Specializovaný průzkum stromu detailní ze země</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-TVL" <?= treatmentChecked('treatment_u2', 'S-TVL', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVL – Specializovaný průzkum stromu detailní s využitím lezecké techniky</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u2[]" value="S-VP" <?= treatmentChecked('treatment_u2', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – Instalace podpěry koruny či kosterních větví (uveď počet podpěr)</span>
</label>

</div>

<div class="sub2-collapsible">Vlastní zásah (text)</div>
<div class="sub2-content">
    <label>Zásahy dle uživatele:</label>
    <input type="text" name="treatment_u2_custom" value="<?= treeFormValue($tree, 'treatment_u2_custom') ?>" placeholder="Např. chybějící, nebo speciální zásahy mimo metodiku">
</div>

    <label>Náklady naléhavost 2</label>
    <input type="number" name="costs_tu2" step="any" value="<?= treeFormValue($tree, 'costs_tu2') ?>">

</div>

</div>

<div class="collapsible" data-title="Ošetření naléhavost 3">Ošetření naléhavost 3</div>
<div class="content">

    <div class="sub-collapsible">Řez stromů dle A02 002</div>
    <div class="sub-content">

    <div class="sub2-collapsible">Řezy zakládací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RZK" <?= treatmentChecked('treatment_u3', 'S-RZK', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RZK – Řez zapěstování koruny
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RPV" <?= treatmentChecked('treatment_u3', 'S-RPV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RPV – Řez při výsadbě
    </span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RV" <?= treatmentChecked('treatment_u3', 'S-RV', $tree) ?>>

    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>

    <span class="checkbox-text">
        S‑RV – Řez výchovný
    </span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy udržovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RB" <?= treatmentChecked('treatment_u3', 'S-RB', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RB – Řez bezpečnostní</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RZ" <?= treatmentChecked('treatment_u3', 'S-RZ', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RZ – Řez zdravotní</span>
</label>

<div class="section-divider"></div>

        <p><strong>Lokální řezy:</strong></p>
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RLPV" <?= treatmentChecked('treatment_u3', 'S-RLPV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLPV – Úprava volného profilu koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RLSP" <?= treatmentChecked('treatment_u3', 'S-RLSP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLSP – Lokální redukce směrem k překážce</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RLLR" <?= treatmentChecked('treatment_u3', 'S-RLLR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RLLR – Lokální redukce z důvodu stabilizace</span>
</label>

<div class="section-divider"></div>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OV" <?= treatmentChecked('treatment_u3', 'S-OV', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑OV – Odstranění výmladků</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy stabilizační</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RO" <?= treatmentChecked('treatment_u3', 'S-RO', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RO – Redukce obvodová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-SSK" <?= treatmentChecked('treatment_u3', 'S-SSK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RS" <?= treatmentChecked('treatment_u3', 'S-RS', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RS – Řez sesazovací</span>
</label>
    </div>

    <div class="sub2-collapsible">Řezy tvarovací</div>
    <div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RTHL" <?= treatmentChecked('treatment_u3', 'S-RTHL', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTHL – Řez na hlavu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RTCR" <?= treatmentChecked('treatment_u3', 'S-RTCR', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTCR – Řez na čípek ramenový</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RTPP" <?= treatmentChecked('treatment_u3', 'S-RTPP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTPP – Řez popouštěcí</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RTZP" <?= treatmentChecked('treatment_u3', 'S-RTZP', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RTZP – Řez živých plotů a stěn</span>
</label>

    </div>

    <div class="sub2-collapsible">Řezy opravné a rekonstrukční</div>
    <div class="sub2-content">
        <label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-RRK" <?= treatmentChecked('treatment_u3', 'S-RRK', $tree) ?>>
    <span class="checkbox-box">
        <span class="checkbox-icon">✔</span>
    </span>
    <span class="checkbox-text">S‑RRK – Řez rekonstrukční</span>
</label>

    </div>

    </div>

	<div class="sub2-collapsible">Řez ovocných stromů dle C02 005</div>
	<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RK" <?= treatmentChecked('treatment_u3', 'O-RK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RK – Řez na korunku ovocných stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RV" <?= treatmentChecked('treatment_u3', 'O-RV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RV – Řez výchovný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RP" <?= treatmentChecked('treatment_u3', 'O-RP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RP – Řez ovocných dřevin prosvětlovací – průklest</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RO" <?= treatmentChecked('treatment_u3', 'O-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RO – Řez opravný ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RA" <?= treatmentChecked('treatment_u3', 'O-RA', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RA – Řez ovocných dřevin zdravotní – asanační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-OV" <?= treatmentChecked('treatment_u3', 'O-OV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑OV – Odstranění vlků a výmladků ovocných dřevin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RZM" <?= treatmentChecked('treatment_u3', 'O-RZM', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZM – Řez ovocných dřevin zmlazovací mírný</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RZS" <?= treatmentChecked('treatment_u3', 'O-RZS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZS – Řez ovocných dřevin zmlazovací střední</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="O-RZH" <?= treatmentChecked('treatment_u3', 'O-RZH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">O‑RZH – Řez ovocných dřevin zmlazovací hluboký</span>
</label>

</div>

<div class="sub2-collapsible">Kácení stromů dle A02 005</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-KV" <?= treatmentChecked('treatment_u3', 'S-KV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KV – Kácení stromů volné</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-KSP" <?= treatmentChecked('treatment_u3', 'S-KSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KSP – Kácení stromů s přetažením</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-KPV" <?= treatmentChecked('treatment_u3', 'S-KPV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPV – Postupné kácení s volnou dopadovou plochou</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-KPP" <?= treatmentChecked('treatment_u3', 'S-KPP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑KPP – Postupné kácení s překážkou v dopadové ploše</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-US" <?= treatmentChecked('treatment_u3', 'S-US', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑US – Úprava pařezu seříznutím</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OR" <?= treatmentChecked('treatment_u3', 'S-OR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OR – Odstranění pařezu ruční (klučením)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OK" <?= treatmentChecked('treatment_u3', 'S-OK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OK – Odstranění pařezu klučením těžkou mechanizací</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OF" <?= treatmentChecked('treatment_u3', 'S-OF', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OF – Odstranění pařezu frézováním</span>
</label>

</div>

<div class="sub2-collapsible">Bezpečnostní vazby dle A02 004</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VDH" <?= treatmentChecked('treatment_u3', 'S-VDH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDH – vazba dynamická v horní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VDD" <?= treatmentChecked('treatment_u3', 'S-VDD', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VDD – vazba dynamická v dolní úrovni</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VSV" <?= treatmentChecked('treatment_u3', 'S-VSV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSV – vazba statická vrtaná</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VSP" <?= treatmentChecked('treatment_u3', 'S-VSP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VSP – vazba statická podkladnicová</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VO" <?= treatmentChecked('treatment_u3', 'S-VO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VO – instalace obruče</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VP" <?= treatmentChecked('treatment_u3', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – instalace podpěry</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VK" <?= treatmentChecked('treatment_u3', 'S-VK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VK – revizní kontrola již instalovaného stabilizačního systému</span>
</label>

</div>

<div class="sub2-collapsible">Speciální zásahy na stromech dle A02 009</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RO" <?= treatmentChecked('treatment_u3', 'SZ-RO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RO – Řízená obvodová redukce (retrenchment)</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RR" <?= treatmentChecked('treatment_u3', 'SZ-RR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RR – Řez regenerační</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RB" <?= treatmentChecked('treatment_u3', 'SZ-RB', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RB – Bezpečnostní řez stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RLLR" <?= treatmentChecked('treatment_u3', 'SZ-RLLR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RLLR – Lokální redukce stromů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-SSK" <?= treatmentChecked('treatment_u3', 'SZ-SSK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑SSK – Stabilizace sekundární koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RS" <?= treatmentChecked('treatment_u3', 'SZ-RS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RS – Sesazovací řez</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-RT" <?= treatmentChecked('treatment_u3', 'SZ-RT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑RT – Zapěstování koruny sesazených stromů a torz</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-ST" <?= treatmentChecked('treatment_u3', 'SZ-ST', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ST – Sesazení stromu na torzo</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-OU" <?= treatmentChecked('treatment_u3', 'SZ-OU', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OU – Management okolního porostu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-OS" <?= treatmentChecked('treatment_u3', 'SZ-OS', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑OS – Úprava stanovištních poměrů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-KO" <?= treatmentChecked('treatment_u3', 'SZ-KO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KO – Konzervační ošetření poranění</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-KZ" <?= treatmentChecked('treatment_u3', 'SZ-KZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑KZ – Konzervační ošetření dutin</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-ZZ" <?= treatmentChecked('treatment_u3', 'SZ-ZZ', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑ZZ – Ochrana senescentních stromů proti zvěři</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-MH" <?= treatmentChecked('treatment_u3', 'SZ-MH', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑MH – Mechanická ochrana proti hmyzím škůdcům</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-CHP" <?= treatmentChecked('treatment_u3', 'SZ-CHP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHP – Ochrana postřikem proti škůdcům/chorobám</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-CHI" <?= treatmentChecked('treatment_u3', 'SZ-CHI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑CHI – Ochrana mikro‑injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-VI" <?= treatmentChecked('treatment_u3', 'SZ-VI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑VI – Podpora vitality injektáží</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-JO" <?= treatmentChecked('treatment_u3', 'SZ-JO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑JO – Odstraňování jmelí / poloparazitů</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-LO" <?= treatmentChecked('treatment_u3', 'SZ-LO', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LO – Odstranění lián z koruny</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="SZ-LR" <?= treatmentChecked('treatment_u3', 'SZ-LR', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">SZ‑LR – Redukce lián</span>
</label>

</div>


<div class="sub2-collapsible">Ostatní zásahy</div>
<div class="sub2-content">
<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-HRI" <?= treatmentChecked('treatment_u3', 'S-HRI', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRI – Instalace hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-HRK" <?= treatmentChecked('treatment_u3', 'S-HRK', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑HRK – Revizní kontrola již instalovaného hromosvodu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OKT" <?= treatmentChecked('treatment_u3', 'S-OKT', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OKT – Odstranění/oprava kotvení mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-OUV" <?= treatmentChecked('treatment_u3', 'S-OUV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑OUV – Odstranění/oprava úvazku mladého stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-TP" <?= treatmentChecked('treatment_u3', 'S-TP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TP – Přístrojový test stromu</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-TVV" <?= treatmentChecked('treatment_u3', 'S-TVV', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVV – Specializovaný průzkum stromu detailní ze země</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-TVL" <?= treatmentChecked('treatment_u3', 'S-TVL', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑TVL – Specializovaný průzkum stromu detailní s využitím lezecké techniky</span>
</label>

<label class="checkbox-item">
    <input type="checkbox" name="treatment_u3[]" value="S-VP" <?= treatmentChecked('treatment_u3', 'S-VP', $tree) ?>>
    <span class="checkbox-box"><span class="checkbox-icon">✔</span></span>
    <span class="checkbox-text">S‑VP – Instalace podpěry koruny či kosterních větví (uveď počet podpěr)</span>
</label>

</div>

<div class="sub2-collapsible">Vlastní zásah (text)</div>
<div class="sub2-content">
    <label>Zásahy dle uživatele:</label>
    <input type="text" name="treatment_u3_custom" value="<?= treeFormValue($tree, 'treatment_u3_custom') ?>" placeholder="Např. chybějící, nebo speciální zásahy mimo metodiku">
</div>

    <label>Náklady naléhavost 3</label>
    <input type="number" name="costs_tu3" step="any" value="<?= treeFormValue($tree, 'costs_tu3') ?>">

</div>

    <label>Celkové náklady</label>
    <input type="number" name="costs_total" step="any" value="<?= treeFormValue($tree, 'costs_total') ?>">

    <label>Poznámka k ošetření</label>
    <textarea name="note_treatment"><?= treeFormValue($tree, 'note_treatment') ?></textarea>

    <label>Historie ošetření</label>
    <textarea name="treatment_history"><?= treeFormValue($tree, 'treatment_history') ?></textarea>
