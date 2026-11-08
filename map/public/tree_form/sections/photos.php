<div class="photo-upload-wrapper">

    <div class="photo-upload-actions">

        <label class="photo-upload-btn">
            📁 Vybrat fotografie
            <input
                type="file"
                id="photo-input"
                accept="image/*"
                multiple
                style="display:none;"
            >
        </label>

        <label class="photo-upload-btn">
            📷 Vyfotit strom
            <input
                type="file"
                id="photo-camera-input"
                accept="image/*"
                capture="environment"
                style="display:none;"
            >
        </label>

    </div>

    <p style="font-size: 13px; color: #666; margin-top: 10px;">
        Můžete přidat více fotografií a měnit jejich pořadí.
        První fotografie vlevo je hlavní.
    </p>

    <div
        id="photo-preview-container"
        class="photo-grid"
    ></div>

</div>

<!-- TLAČÍTKO PRO ULOŽENÍ STROMU -->
<button type="submit" class="submit-btn">
    <?= $is_edit ? 'Uložit změny' : 'Přidat strom' ?>
</button>