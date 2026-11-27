<script>
document.addEventListener("DOMContentLoaded", function() {
	
	const photoHistoryCancel =
    document.getElementById(
        'photo-history-cancel'
    );

    // 1) Hlavní collapsible
    document.querySelectorAll(".collapsible").forEach(btn => {
        btn.addEventListener("click", function() {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });

    // 2) Druhá úroveň
    document.querySelectorAll(".sub-collapsible").forEach(btn => {
        btn.addEventListener("click", function() {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });

    // 3) Třetí úroveň
    document.querySelectorAll(".sub2-collapsible").forEach(btn => {
        btn.addEventListener("click", function() {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });

    // 4) Checkboxy → update summary
    if (typeof updateSummary === "function") {
        document.querySelectorAll("input[type=checkbox]").forEach(ch => {
            ch.addEventListener("change", updateSummary);
        });

        // 5) Vlastní zásahy U0–U3
        ["u0", "u1", "u2", "u3"].forEach(section => {
            const input = document.querySelector(`input[name="treatment_${section}_custom"]`);
            if (input) input.addEventListener("input", updateSummary);
        });

        // 6) Ceny U0–U3
        document.querySelectorAll("input[name^='costs_tu']").forEach(cost => {
            cost.addEventListener("input", updateSummary);
        });
		updateSummary();
    }

    // --- NAŠEPTÁVAČ USR ID ---
    function suggestNextUsrId() {
        let max = 0;
        const dbMax = document.getElementById("last_usr_id");
        if (dbMax && !isNaN(dbMax.value)) {
            max = Number(dbMax.value);
        }
        document.querySelectorAll('input[name="usr_id"]').forEach(input => {
            const val = Number(input.value);
            if (!isNaN(val) && val > max) max = val;
        });
        return max + 1;
    }

    document.querySelectorAll('input[name="usr_id"]').forEach(input => {
        input.addEventListener("focus", function () {
            if (this.value.trim() === "") {
                this.value = suggestNextUsrId();
            }
        });
    });
    
    // --- AJAX VALIDACE DUPLICIT ---
    document.querySelectorAll('input[name="usr_id"]').forEach(input => {
        input.addEventListener("input", function () {
            const val = this.value.trim();
            if (val === "") return;

            const currentTreeId = document.querySelector('input[name="id"]')?.value || "0";
            fetch("check_usr_id.php?usr_id=" + encodeURIComponent(val)
                + "&tree_id=" + encodeURIComponent(currentTreeId), {credentials: "same-origin"})
                .then(r => r.text())
                .then(result => {
                    let warn = this.nextElementSibling;
                    if (!warn || !warn.classList.contains("usr-warning")) {
                        warn = document.createElement("div");
                        warn.classList.add("usr-warning");
                        warn.style.color = "red";
                        warn.style.fontSize = "12px";
                        warn.style.marginTop = "2px";
                        this.insertAdjacentElement("afterend", warn);
                    }

                    if (this.value.trim() !== val) return;
                    if (result.trim() === "exists") {
                        warn.textContent = "Toto číslo stromu už v projektu existuje. Uložení i tak proběhne.";
                        this.style.borderColor = "red";
                    } else if (result.trim() === "ok") {
                        warn.textContent = "";
                        this.style.borderColor = "";
                    } else {
                        warn.textContent = "Kontrolu čísla se nepodařilo provést. Uložení není blokováno.";
                        this.style.borderColor = "";
                    }
                })
                .catch(() => {
                    // Výpadek kontroly nesmí blokovat uložení stromu.
                });
        });
    });

    // BEZPEČNÉ PŘIŘAZENÍ POSLEDNÍHO ID (Tady byla ta fatální chyba!)
    const lastUsrInput = document.querySelector("#last_usr_id");
    const usrInput = document.querySelector("#usr_id");
    if (lastUsrInput && usrInput) {
		const usrIdInput = document.getElementById('usr_id');
		const lastUsrIdInput = document.getElementById('last_usr_id');

		if (
			usrIdInput &&
			lastUsrIdInput &&
			!window.treeFormMode.isEdit
		) {
			const lastId = parseInt(
				lastUsrIdInput.value,
				10
			) || 0;

			usrIdInput.value = lastId + 1;
		}
	}	
    
    // --- ŘAZENÍ, NÁHLEDY FOTEK Z GALERIE (NÁVRAT K FUNKČNÍ VERZI) ---
const photoInput =
    document.getElementById('photo-input');

const photoCameraInput =
    document.getElementById('photo-camera-input');

const previewContainer =
    document.getElementById('photo-preview-container');

const form =
    document.querySelector('form');
    
let selectedPhotos = [];

let deletedPhotoIds = [];

let existingPhotos =
    Array.isArray(window.loadedTree?.photos)
        ? window.loadedTree.photos.map(
            photo => ({
                id: Number(photo.id),
                url: photo.url || '',
                note: photo.note_photo || '',
                inHistory:
                    Number(photo.in_history) === 1,
                type: 'existing'
            })
        )
        : [];
		
		let photoOrder = [
    ...existingPhotos
];

const photoMetaRows=()=>getAllPhotos().filter(p=>p.type==='existing').map(p=>({id:Number(p.id),note:p.note||''}));
let photoMetaBefore=photoMetaRows();
window.TreePhotosDirty=()=>JSON.stringify(photoMetaRows())!==JSON.stringify(photoMetaBefore);
const photoMetaSave=document.createElement('button');photoMetaSave.type='button';photoMetaSave.textContent='Uložit popisky a pořadí fotografií';photoMetaSave.className='photo-meta-save';previewContainer?.after(photoMetaSave);
photoMetaSave.onclick=async()=>{
 if(photoUploading||!savedTree)return;
 photoUploading=true;window.TreeFormLifecycle?.uploading(true);photoMetaSave.disabled=true;
 const gallery=document.getElementById('tree-photos');if(gallery)gallery.inert=true;
 try {const payload={photos:photoMetaRows(),previous:photoMetaBefore};for(const k of ['id','project_id','tree_form_token','row_version'])payload[k]=form.elements[k]?.value||'';
 const response=await fetch('tree_photo_meta.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const result=await response.json();if(!response.ok||!result.success)throw Error(result.message||'Změny nebyly uloženy.');form.elements.row_version.value=result.row_version;photoMetaBefore=photoMetaRows();deletedPhotoIds=[];photoStatus.textContent='Popisky, pořadí a odebrání fotografií byly uloženy.';
 }catch(e){photoStatus.textContent=e.message;}finally{photoUploading=false;window.TreeFormLifecycle?.uploading(false);photoMetaSave.disabled=false;if(gallery)gallery.inert=false;}
};
let photoUploading=false;
const photoStatus=document.createElement('p');photoStatus.setAttribute('role','status');previewContainer?.before(photoStatus);
const savedTree=Number(window.treeFormMode?.treeId)>0;
for(const input of [photoInput,photoCameraInput])if(input)input.disabled=!savedTree;
photoMetaSave.hidden=!savedTree;
photoStatus.textContent=savedTree?'Vybraná fotografie se ihned nahraje. Popisky, pořadí a odebrání potvrďte tlačítkem pod galerií. JPG, PNG nebo WebP, nejvýše 5 MB a 24 megapixelů.':'Nejdříve uložte strom. Potom otevřete jeho editaci a přidejte fotografie.';
async function addSelectedPhotos(fileList,input){
 const files=Array.from(fileList||[]);if(input)input.value='';if(!files.length)return;
 if(!savedTree){alert('Nejdříve uložte strom.');return;}if(window.TreePhotosDirty()){alert('Nejdříve uložte popisky a pořadí fotografií.');return;}
 if(photoUploading){alert('Vyčkejte na dokončení nahrávání.');return;}
 photoUploading=true;window.TreeFormLifecycle?.uploading(true);
 const locks=[...form.querySelectorAll('button,input[type="submit"]'),photoInput,photoCameraInput].filter(Boolean),previous=locks.map(n=>n.disabled);locks.forEach(n=>n.disabled=true);
 let done=0,failed=[];
 try{for(const file of files){
  photoStatus.textContent='Nahrávám fotografii '+(done+failed.length+1)+' z '+files.length+'…';
  if(file.size>5*1024*1024){failed.push(file.name+': soubor je větší než 5 MB');continue;}
  const data=new FormData();for(const name of ['id','project_id','tree_form_token','row_version'])data.append(name,form.elements[name]?.value||'');data.append('photo',file);
  try{const response=await fetch('tree_photo_upload.php',{method:'POST',credentials:'same-origin',body:data});let result;try{result=await response.json();}catch{throw Error('Server nevrátil data. Zkontrolujte přihlášení a limit hostingu.');}if(!response.ok||!result.success)throw Error(result.message||'Nahrání selhalo.');
   const item={id:Number(result.photo.id),url:result.photo.url,note:'',inHistory:false,type:'existing'};existingPhotos.push(item);photoOrder.push(item);form.elements.row_version.value=result.row_version;done++;photoMetaBefore=photoMetaRows();updatePreview();
  }catch(e){failed.push(file.name+': '+e.message);break;}
 }}finally{photoUploading=false;window.TreeFormLifecycle?.uploading(false);locks.forEach((n,i)=>n.disabled=previous[i]);}
 photoStatus.textContent='Nahráno: '+done+' z '+files.length+'.'+(failed.length?' '+failed.join(' · '):' Fotografie jsou uložené.');
}


if (photoInput) {

    photoInput.addEventListener(
        'change',
        function (event) {

            addSelectedPhotos(
                event.target.files,
                photoInput
            );

        }
    );
}


if (photoCameraInput) {

    photoCameraInput.addEventListener(
        'change',
        function (event) {

            addSelectedPhotos(
                event.target.files,
                photoCameraInput
            );

        }
    );
}

function getAllPhotos() {
    return photoOrder;
}

function setAllPhotos(allPhotos) {

    photoOrder = [
        ...allPhotos
    ];

    existingPhotos =
        photoOrder.filter(
            item => item.type === 'existing'
        );

    selectedPhotos =
        photoOrder.filter(
            item => item.type === 'new'
        );
}

const photoHistoryDialog =
    document.getElementById(
        'photo-history-dialog'
    );

const photoHistoryDialogMessage =
    document.getElementById(
        'photo-history-dialog-message'
    );

const photoHistorySaveFirst =
    document.getElementById(
        'photo-history-save-first'
    );

const photoHistoryRemove =
    document.getElementById(
        'photo-history-remove'
    );

let pendingPhotoRemoval = null;


function closePhotoHistoryDialog() {

    if (!photoHistoryDialog) {
        return;
    }

    photoHistoryDialog.hidden = true;

    pendingPhotoRemoval = null;
}

if (
    window.treeHistorySavedForPhoto === true
    && photoHistoryDialog
    && photoHistoryDialogMessage
    && photoHistorySaveFirst
    && photoHistoryRemove
    && photoHistoryCancel
) {

    pendingPhotoRemoval = null;

    photoHistoryDialogMessage.textContent =
        'Stav stromu byl uložen do historie. '
        + 'Fotografie je nyní bezpečně zachována '
        + 'v historickém záznamu.';

    photoHistorySaveFirst.hidden = true;
    photoHistoryRemove.hidden = true;

    photoHistoryCancel.textContent =
        'OK';

    photoHistoryDialog.hidden =
        false;
}


function performPhotoRemoval() {

    if (!pendingPhotoRemoval) {
        return;
    }

    const currentPhotos =
        getAllPhotos();

    /*
     * Hledáme podle objektu / ID znovu,
     * protože pořadí se mohlo změnit.
     */
    const removeIndex =
        currentPhotos.findIndex(
            function(photo) {

                if (
                    pendingPhotoRemoval.type === 'existing'
                ) {
                    return (
                        photo.type === 'existing'
                        && Number(photo.id) ===
                           Number(
                               pendingPhotoRemoval.id
                           )
                    );
                }

                return (
                    photo === pendingPhotoRemoval
                );
            }
        );

    if (removeIndex === -1) {

        closePhotoHistoryDialog();

        return;
    }


    const removedItem =
        currentPhotos[removeIndex];


    if (
        removedItem.type === 'existing'
        && removedItem.id > 0
        && !deletedPhotoIds.includes(
            removedItem.id
        )
    ) {

        deletedPhotoIds.push(
            removedItem.id
        );
    }


    currentPhotos.splice(
        removeIndex,
        1
    );

    setAllPhotos(
        currentPhotos
    );

    closePhotoHistoryDialog();

    updatePreview();
}

if (photoHistoryRemove) {

    photoHistoryRemove.addEventListener(
        'click',
        function() {

            performPhotoRemoval();

        }
    );
}


document
    .querySelectorAll(
        '[data-photo-dialog-cancel]'
    )
    .forEach(function(element) {

        element.addEventListener(
            'click',
            function() {

                closePhotoHistoryDialog();

            }
        );

    });


if (photoHistorySaveFirst) {

    photoHistorySaveFirst.addEventListener(
        'click',
        function() {

            closePhotoHistoryDialog();

            const historySection =
                document.getElementById(
                    'tree-history'
                );

            const historyReturnTo =
                document.getElementById(
                    'history-return-to'
                );

            if (historyReturnTo) {
                historyReturnTo.value =
                    'photos';
            }

            if (historySection) {

                historySection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            } else {

                alert(
                    'Nejprve uložte stav stromu '
                    + 'do historie.'
                );
            }
        }
    );
}

function updatePreview() {
    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    const allPhotos = getAllPhotos();

    allPhotos.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'photo-item';

        const img = document.createElement('img');

        if (item.type === 'existing') {
            img.src = protectedTreePhotoUrl(item.url);
            img.alt = 'Existující fotografie stromu';
        } else {
            img.src = URL.createObjectURL(item.file);
            img.alt = 'Nová fotografie stromu';
        }

        const noteInput = document.createElement('textarea');
        noteInput.className = 'photo-note-input';noteInput.maxLength=1000;noteInput.setAttribute('aria-label','Popisek fotografie');
        noteInput.placeholder = 'Popisek fotky...';
        noteInput.value = item.note || '';

        noteInput.addEventListener('input', function(e) {
            item.note = e.target.value;
        });

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'photo-remove';
        removeBtn.innerHTML = '×';
        removeBtn.title = 'Odstranit fotografii';

removeBtn.addEventListener(
    'click',
    function(e) {

        e.preventDefault();

        const currentPhotos =
            getAllPhotos();

        const removedItem =
            currentPhotos[index];

        if (!removedItem) {
            return;
        }

        /*
         * Nová fotografie ještě není
         * uložená v databázi.
         * Tu můžeme prostě odebrat.
         */
        if (
            removedItem.type === 'new'
        ) {

            currentPhotos.splice(
                index,
                1
            );

            setAllPhotos(
                currentPhotos
            );

            updatePreview();

            return;
        }

        /*
         * Existující fotografie:
         * otevřeme vlastní dialog.
         */
		photoHistoryCancel.textContent =
    'Zrušit';

photoHistoryRemove.hidden =
    false; 
		 
        pendingPhotoRemoval =
            removedItem;

        if (
            removedItem.inHistory === true
        ) {

            photoHistoryDialogMessage.textContent =
                'Tato fotografie je již zachována '
                + 'v historii stromu. '
                + 'Můžete ji bezpečně odpojit '
                + 'z aktuální karty. '
                + 'V historických záznamech, '
                + 've kterých byla uložena, '
                + 'zůstane zachována.';

            photoHistorySaveFirst.hidden =
                true;

            photoHistoryRemove.textContent =
                'Odpojit fotografii';

        } else {

            photoHistoryDialogMessage.textContent =
                'Tato fotografie zatím není '
                + 'součástí žádného historického '
                + 'záznamu. Pokud ji nyní '
                + 'odpojíte, nebude zachována '
                + 'v žádné dosavadní historické '
                + 'kartě. Doporučujeme nejprve '
                + 'uložit aktuální stav stromu '
                + 'do historie.';

            photoHistorySaveFirst.hidden =
                false;

            photoHistoryRemove.textContent =
                'Přesto odpojit';
        }

        photoHistoryDialog.hidden =
            false;
    }
);

        const controls = document.createElement('div');
        controls.className = 'photo-controls';

        const leftBtn = document.createElement('button');
        leftBtn.type = 'button';
        leftBtn.className = 'photo-btn';
        leftBtn.innerHTML = '◀';
        leftBtn.title = 'Posunout doleva';
        leftBtn.disabled = index === 0;

        leftBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (index === 0) return;

            const currentPhotos = getAllPhotos();

            [
                currentPhotos[index - 1],
                currentPhotos[index]
            ] = [
                currentPhotos[index],
                currentPhotos[index - 1]
            ];

            setAllPhotos(currentPhotos);
            updatePreview();
        });

        const rightBtn = document.createElement('button');
        rightBtn.type = 'button';
        rightBtn.className = 'photo-btn';
        rightBtn.innerHTML = '▶';
        rightBtn.title = 'Posunout doprava';
        rightBtn.disabled = index === allPhotos.length - 1;

        rightBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (index >= allPhotos.length - 1) return;

            const currentPhotos = getAllPhotos();

            [
                currentPhotos[index],
                currentPhotos[index + 1]
            ] = [
                currentPhotos[index + 1],
                currentPhotos[index]
            ];

            setAllPhotos(currentPhotos);
            updatePreview();
        });

        controls.appendChild(leftBtn);
        controls.appendChild(rightBtn);

        const photoLink=document.createElement('a');photoLink.href=img.src;photoLink.target='_blank';photoLink.rel='noopener';photoLink.title='Zobrazit fotografii v plné velikosti';photoLink.setAttribute('aria-label',photoLink.title);photoLink.appendChild(img);
        div.appendChild(photoLink);
        div.appendChild(noteInput);
        div.appendChild(removeBtn);
        div.appendChild(controls);

        previewContainer.appendChild(div);
    });
}

updatePreview();

// Spolehlivé AJAX odeslání
if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if(photoUploading){alert('Nejprve vyčkejte na nahrání fotografií.');return;}if(window.TreePhotosDirty()){alert('Nejdříve uložte změny fotografií tlačítkem pod galerií.');return;}

        const latInput =
            document.getElementById('lat_wgs84');

        const lngInput =
            document.getElementById('lng_wgs84');

        const xInput =
            document.getElementById('x_sjtsk');

        const yInput =
            document.getElementById('y_sjtsk');

        const hasWgs84 =
            latInput &&
            lngInput &&
            latInput.value.trim() !== '' &&
            lngInput.value.trim() !== '';

        const hasSjtsk =
            xInput &&
            yInput &&
            xInput.value.trim() !== '' &&
            yInput.value.trim() !== '';

        if (!hasWgs84 && !hasSjtsk) {

            const continueWithoutLocation =
                window.confirm(
                    'Strom nemá zadanou polohu a nebude zobrazen v mapě.\n\n'
                    + 'Chcete jej přesto uložit?'
                );

            if (!continueWithoutLocation) {
                return;
            }
        }

        const submitBtn =
            document.querySelector('.submit-btn');

        if (!submitBtn) return;

        const originalText =
            submitBtn.innerHTML;

        submitBtn.innerHTML =
            'Ukládám do databáze... ⏳';

        // ... dál pokračuje tvůj současný kód
        submitBtn.disabled = true;
        submitBtn.style.background = '#666';

        const formData = new FormData(form);
		
		const allPhotos = getAllPhotos();

// Existující fotografie
existingPhotos.forEach((item, index) => {
    formData.append('existing_photo_ids[]', item.id);
    formData.append('existing_photo_notes[]', item.note || '');
    formData.append(
        'existing_photo_sort[]',
        allPhotos.indexOf(item)
    );
});

// Fotografie určené ke smazání
deletedPhotoIds.forEach(photoId => {
    formData.append('deleted_photo_ids[]', photoId);
});

        // Vymažeme starou vazbu z formuláře
        formData.delete('photos[]');

        // Vložíme naše živé soubory v pořadí, jaké sis naklikal
        selectedPhotos.forEach((item) => {
    formData.append('photos[]', item.file);
    formData.append('photo_notes[]', item.note || '');
    formData.append('photo_order[]', item.file.name);
    formData.append(
        'new_photo_sort[]',
        allPhotos.indexOf(item)
    );
});

try {

    window.TreeFormLifecycle?.saving();
    const response = await fetch(
        'tree_save.php',
        {
            method: 'POST',

            headers: {
                'X-Requested-With': 'TreeApp'
            },

            body: formData
        }
    );


    /*
     * HTTP chyba – například 500 nebo 503.
     */
    if (!response.ok) {

        let failure;try{failure=await response.json();}catch{}throw new Error(failure?.message||('Server odpověděl HTTP '+response.status));
    }


    /*
     * tree_save.php nám vrací JSON.
     */
    const result = await response.json();


    if (
        result.success !== true
        || !result.tree_id
    ) {

        throw new Error(
            'Server nepotvrdil uložení stromu.'
        );
    }


    window.TreeFormLifecycle?.saved();
    if (window.parent !== window && typeof window.parent.ProjectTreeSaved === 'function') {
        window.parent.ProjectTreeSaved();
    }
    // Reload the saved ID so a second submit updates it and photo uploads are enabled.
    window.location.href = result.next_url || result.card_url || ('karta.php?id=' + result.tree_id);


} catch (error) {
    window.TreeFormLifecycle?.failed();

    console.error(
        'Chyba při ukládání stromu:',
        error
    );

    alert(
        'Uložení se nepodařilo dokončit.\n\n'
        + error.message
    );

    submitBtn.innerHTML =
        originalText;

    submitBtn.disabled =
        false;

    submitBtn.style.background =
        '#2d7d2d';
}
    });
}
});

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

	function createAutocomplete(input, box, fetchUrl, onSelect, formatItem) {
    if (!input || !box) return;
    let requestId = 0, timer = null, controller = null;
    const cache = new Map();
    function close() { requestId++; clearTimeout(timer); controller?.abort(); box.style.display = "none"; }
    let activeIndex = -1;
    let items = [];
    input.addEventListener("input", () => {
        const currentRequest = ++requestId;
        clearTimeout(timer); controller?.abort(); items = []; activeIndex = -1;
        const q = input.value.trim();
        box.replaceChildren();
        if (q.length < 2) { box.style.display = "none"; return; }
        if (cache.has(q)) { items = cache.get(q); render(); return; }
        box.textContent = "Načítám nabídku…";
        box.style.display = "block";
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const response = await fetch(fetchUrl + encodeURIComponent(q), {signal: controller.signal});
                if (!response.ok) throw new Error("Autocomplete");
                const data = await response.json();
                if (currentRequest !== requestId) return;
                items = Array.isArray(data) ? data : [];
                if (cache.size >= 50) cache.delete(cache.keys().next().value);
                cache.set(q, items); render();
            } catch (error) {
                if (currentRequest === requestId && error.name !== 'AbortError') {
                    box.textContent = "Nabídku se nepodařilo načíst. Zkuste psát znovu.";
                }
            }
        }, 180);
    });

    function render() {
        box.innerHTML = "";
        if (!items || items.length === 0) {
            box.style.display = "none";
            return;
        }

        box.style.display = "block";

        items.forEach((item) => {
            const div = document.createElement("div");
            div.className = "suggestion-item";

            div.textContent = formatItem ? formatItem(item) : typeof item === "string"
                ? item
                : item.scientific_name + (item.author ? " " + item.author : "");

            div.addEventListener("mousedown", (e) => e.preventDefault());
            div.addEventListener("click", () => {
                onSelect(item);
                close();
            });

            box.appendChild(div);
        });
    }

    input.addEventListener("keydown", (e) => {
        const children = box.querySelectorAll(".suggestion-item");
        if (!children.length || box.style.display === "none") return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, children.length - 1);
            updateActive(children);
        }

        if (e.key === "ArrowUp") {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActive(children);
        }

        if (e.key === "Enter") {
            if (activeIndex >= 0) {
                e.preventDefault();
                children[activeIndex].click();
            }
        }

        if (e.key === "Escape") {
            close();
        }
    });

    function updateActive(children) {
        children.forEach((el, i) => {
            el.classList.toggle("active", i === activeIndex);
        });

        const active = children[activeIndex];
        if (active) {
            const boxRect = box.getBoundingClientRect();
            const elRect = active.getBoundingClientRect();

            if (elRect.bottom > boxRect.bottom) {
                box.scrollTop += elRect.bottom - boxRect.bottom;
            } else if (elRect.top < boxRect.top) {
                box.scrollTop -= boxRect.top - elRect.top;
            }
        }
    }

    document.addEventListener("click", (e) => {
        if (!box.contains(e.target) && e.target !== input) {
            close();
        }
    });
}


function selectSpecies(item) {
        const form = document.querySelector('form[action="tree_save.php"]');
        let selected = form.querySelector('[name="taxonomy_selected"]');
        if (!selected) {
            selected = document.createElement('input');
            selected.type = 'hidden'; selected.name = 'taxonomy_selected';
            form.append(selected);
        }
        selected.value = '1';
        document.querySelector("#species").value = item.scientific_name;
        document.querySelector("#scientific_author").value = item.author || "";
        document.querySelector("#czech").value = item.czech_name || "";

        const gymnoFamilies = [
            "Pinaceae","Cupressaceae","Taxaceae",
            "Ginkgoaceae","Ephedraceae","Araucariaceae","Zamiaceae"
        ];

        const family = (item.family || "").trim();

        // TADY BYLA CHYBA: Skript hledal id="family", které neexistuje.

        // Vyplnění angio/gymno na základě čeledi z JSONu
        if (family !== "") {
            document.querySelector("#angio_gymno").value =
                gymnoFamilies.includes(family)
                    ? "gymnospermae"
                    : "angiospermae";
        } else {
            document.querySelector("#angio_gymno").value = "";
        }
    }

createAutocomplete(document.querySelector("#species"), document.querySelector("#species-suggestions"),
    "species_search.php?q=", selectSpecies);
createAutocomplete(document.querySelector("#czech"), document.querySelector("#czech-suggestions"),
    "species_search.php?q=", selectSpecies,
    (item) => (item.czech_name || "Bez českého názvu") + " — " + item.scientific_name);

// CULTIVAR
createAutocomplete(
    document.querySelector("#cultivar"),
    document.querySelector("#cultivar-suggestions"),
    "cultivar_search.php?q=",
    (name) => {
        document.querySelector("#cultivar").value = name;
    }
);

function updateSummary() {
    document.querySelectorAll(".collapsible").forEach(btn => {
        const content = btn.nextElementSibling;

        let labels = [];

        // 1) Checkboxy
        const checks = content.querySelectorAll("input[type=checkbox]:checked");
        checks.forEach(ch => {
    labels.push(ch.value);   // vypíše jen zkratku
});


        // 2) Vlastní zásahy U0–U3
        ["u0", "u1", "u2", "u3"].forEach(section => {
            const custom = content.querySelector(`input[name="treatment_${section}_custom"]`);
            if (custom && custom.value.trim() !== "") {
                labels.push(custom.value.trim());
            }
        });

        // 3) Cena (funguje pro U0–U3)
        const costInput = content.querySelector("input[name^='costs_tu']");
        const costValue = costInput && costInput.value ? ` (${costInput.value} Kč)` : "";

        // 4) Výpis do hlavičky
        if (labels.length > 0 || (costInput && costInput.value)) {
            btn.innerHTML = btn.dataset.title + " – " + labels.join(", ") + costValue;
        } else {
            btn.innerHTML = btn.dataset.title;
        }
    });

// 5) Celková cena ze všech naléhavostí
let total = 0;

["tu0", "tu1", "tu2", "tu3"].forEach(section => {
    const cost = document.querySelector(`input[name="costs_${section}"]`);
    if (cost && cost.value.trim() !== "" && !isNaN(cost.value)) {
        total += Number(cost.value);
    }
});

// zapsat total do inputu
const totalField = document.querySelector('input[name="costs_total"]');
if (totalField) {
    totalField.value = total > 0 ? total : "";
}

}

    // zvýraznění checkbox-item při zaškrtnutí
    document.querySelectorAll(".checkbox-item input[type=checkbox]").forEach(ch => {
        ch.addEventListener("change", function() {
            const box = this.closest(".checkbox-item");
            if (this.checked) {
                box.classList.add("checked");
            } else {
                box.classList.remove("checked");
            }
        });
    });

function addCustomTreatment(section) {
    const text = document.querySelector(`input[name="treatment_${section}_custom"]`)?.value.trim();
    const price = document.querySelector(`input[name="treatment_${section}_price"]`)?.value.trim();

    if (text) {
        let item = text;
        if (price) item += ` – ${price} Kč`;
        return item;
    }
    return null;
}

</script>

<script>
window.updateSjtskFromWgs84 = async function (lat, lng) {

    const xInput =
        document.getElementById('x_sjtsk');

    const yInput =
        document.getElementById('y_sjtsk');

    if (!xInput || !yInput) {
        return false;
    }

    try {

        const url =
            'api/wgs84_to_sjtsk.php?lat=' +
            encodeURIComponent(lat) +
            '&lng=' +
            encodeURIComponent(lng);

        const response = await fetch(url, {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const data = await response.json();

        if (
            !data.success ||
            typeof data.x_sjtsk === 'undefined' ||
            typeof data.y_sjtsk === 'undefined'
        ) {
            throw new Error(
                'Neplatná odpověď převodu souřadnic.'
            );
        }

        xInput.value = data.x_sjtsk;
        yInput.value = data.y_sjtsk;

        return true;

    } catch (error) {

        console.error(
            'Převod WGS84 → S-JTSK selhal:',
            error
        );

        return false;
    }
};
</script>

<script>
(function () {

    const locationButton =
        document.getElementById('useDeviceLocation');

    const statusBox =
        document.getElementById('locationStatus');

    const latInput =
        document.getElementById('lat_wgs84');

    const lngInput =
        document.getElementById('lng_wgs84');

    if (!locationButton) {
        return;
    }

    let statusTimer = null;

    function showLocationStatus(message) {

        if (!statusBox) {
            return;
        }

        clearTimeout(statusTimer);

        statusBox.textContent = message;
        statusBox.hidden = false;

        statusTimer = setTimeout(function () {
            statusBox.hidden = true;
        }, 2500);
    }

    locationButton.addEventListener('click', function () {

        if (!navigator.geolocation) {
            alert('Prohlížeč nepodporuje geolokaci.');
            return;
        }

        locationButton.disabled = true;
        locationButton.textContent = '📍 Zjišťuji polohu…';

        let bestPosition = null;
        let watchId = null;
        let finished = false;

        function finishMeasurement() {

            if (finished) {
                return;
            }

            finished = true;
			
			clearTimeout(locationTimeout);

            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }

            locationButton.disabled = false;
            locationButton.textContent = '📍 Použít polohu';

            if (!bestPosition) {
                alert('Nepodařilo se získat polohu zařízení.');
                return;
            }

            const coords = bestPosition.coords;

latInput.value =
    coords.latitude.toFixed(7);

lngInput.value =
    coords.longitude.toFixed(7);

window.updateSjtskFromWgs84(
    coords.latitude,
    coords.longitude
);

const accuracy =
                Math.round(coords.accuracy * 10) / 10;

            if (accuracy <= 5) {

                showLocationStatus(
                    '✓ Poloha nastavena · přesnost ±' +
                    accuracy.toLocaleString('cs-CZ') +
                    ' m'
                );

            } else {

                showLocationStatus(
                    '⚠️ Přesnost měření nebyla příliš vysoká (±' +
                    accuracy.toLocaleString('cs-CZ') +
                    ' m). Pro lepší přesnost využijte průměrování polohy.'
                );

            }
        }

        const locationTimeout = setTimeout(function () {

            if (!bestPosition) {

                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                }

                finished = true;

                locationButton.disabled = false;
                locationButton.textContent =
                    '📍 Použít polohu';

                alert(
                    'Zařízení neposkytlo polohu ani během 8 sekund. ' +
                    'Zkontrolujte, zda má prohlížeč povolený přístup k poloze.'
                );
            }

        }, 8000);

        watchId = navigator.geolocation.watchPosition(

            function (position) {

                /*
                 * Jakmile získáme první polohu,
                 * spustíme dvousekundové měření.
                 */
                if (!bestPosition) {

                    bestPosition = position;

                    locationButton.textContent =
                        '📍 Zpřesňuji polohu…';

                    setTimeout(
                        finishMeasurement,
                        2000
                    );

                    return;
                }

                /*
                 * Z dalších měření ponecháme
                 * polohu s nejlepší přesností.
                 */
                if (
                    position.coords.accuracy <
                    bestPosition.coords.accuracy
                ) {
                    bestPosition = position;
                }

            },

            function (error) {

                console.warn(
                    'Chyba geolokace:',
                    error
                );

            },

            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 5000
            }

        );
		
    });

})();
</script>

<script>
(function () {

    const startButton =
        document.getElementById('startLocationAveraging');

    const stopButton =
        document.getElementById('stopLocationAveraging');

    const cancelButton =
        document.getElementById('cancelLocationAveraging');

    const panel =
        document.getElementById('locationAveragingPanel');

    const timeElement =
        document.getElementById('locationAverageTime');

    const countElement =
        document.getElementById('locationAverageCount');

    const accuracyElement =
        document.getElementById('locationAverageAccuracy');

    const bestAccuracyElement =
        document.getElementById('locationAverageBestAccuracy');

    const usedCountElement =
        document.getElementById('locationAverageUsedCount');

    const latInput =
        document.getElementById('lat_wgs84');

    const lngInput =
        document.getElementById('lng_wgs84');

    if (!startButton) {
        return;
    }

    let watchId = null;
    let timerId = null;
    let startTime = null;

    let samples = [];

    function formatAccuracy(value) {

        return (
            (Math.round(value * 10) / 10)
                .toLocaleString('cs-CZ') +
            ' m'
        );

    }


    function updateUsedSamplesDisplay() {

        if (!samples.length) {
            usedCountElement.textContent = '0';
            return;
        }

        /*
         * Za použitelné považujeme vzorky,
         * které nejsou výrazně horší
         * než nejlepší získaný fix.
         */
        const bestAccuracy = Math.min(
            ...samples.map(
                sample => sample.accuracy
            )
        );

        const accuracyLimit = Math.max(
            bestAccuracy * 2,
            bestAccuracy + 5
        );

        const usableSamples =
            samples.filter(
                sample =>
                    sample.accuracy <= accuracyLimit
            );

        usedCountElement.textContent =
            usableSamples.length;

    }


    function resetPanel() {

        timeElement.textContent = '0';
        countElement.textContent = '0';
        accuracyElement.textContent = '—';
        bestAccuracyElement.textContent = '—';
        usedCountElement.textContent = '0';

    }


    function stopWatch() {

        if (watchId !== null) {

            navigator.geolocation.clearWatch(
                watchId
            );

            watchId = null;
        }

        if (timerId !== null) {

            clearInterval(timerId);
            timerId = null;
        }

    }


    startButton.addEventListener(
        'click',
        function () {

            if (!navigator.geolocation) {

                alert(
                    'Prohlížeč nepodporuje geolokaci.'
                );

                return;
            }

            samples = [];

            resetPanel();

            panel.hidden = false;

            startButton.disabled = true;

            startButton.textContent =
                '🎯 Průměrování probíhá…';

            startTime = Date.now();

            timerId = setInterval(
                function () {

                    const seconds =
                        Math.floor(
                            (
                                Date.now() -
                                startTime
                            ) / 1000
                        );

                    timeElement.textContent =
                        seconds;

                },
                250
            );


            watchId =
                navigator.geolocation.watchPosition(

                    function (position) {

                        const coords =
                            position.coords;

                        const sample = {

                            lat:
                                coords.latitude,

                            lng:
                                coords.longitude,

                            accuracy:
                                coords.accuracy,

                            timestamp:
                                position.timestamp

                        };

                        samples.push(sample);

                        countElement.textContent =
                            samples.length;

                        accuracyElement.textContent =
                            formatAccuracy(
                                sample.accuracy
                            );

                        const bestAccuracy =
                            Math.min(
                                ...samples.map(
                                    item =>
                                        item.accuracy
                                )
                            );

                        bestAccuracyElement.textContent =
                            formatAccuracy(
                                bestAccuracy
                            );

                        updateUsedSamplesDisplay();

                    },

                    function (error) {

                        console.warn(
                            'Chyba při průměrování polohy:',
                            error
                        );

                    },

                    {
                        enableHighAccuracy: true,
                        maximumAge: 0,
                        timeout: 15000
                    }

                );

        }
    );


    stopButton.addEventListener(
        'click',
        function () {

            stopWatch();

            startButton.disabled = false;

            startButton.textContent =
                '🎯 Průměrovat polohu';

            if (!samples.length) {

                panel.hidden = true;

                alert(
                    'Nebyl získán žádný vzorek polohy.'
                );

                return;
            }


            /*
             * Najdeme nejlepší dosaženou přesnost.
             */
            const bestAccuracy =
                Math.min(
                    ...samples.map(
                        sample =>
                            sample.accuracy
                    )
                );


            /*
             * Zahodíme výrazně horší fixy.
             *
             * Například při nejlepší přesnosti 4 m
             * použijeme fixy do 9 m.
             */
            const accuracyLimit =
                Math.max(
                    bestAccuracy * 2,
                    bestAccuracy + 5
                );


            const usableSamples =
                samples.filter(
                    sample =>
                        sample.accuracy <=
                        accuracyLimit
                );


            if (!usableSamples.length) {

                panel.hidden = true;

                alert(
                    'Nepodařilo se získat použitelnou polohu.'
                );

                return;
            }


            /*
             * Vážený průměr.
             *
             * Přesnější měření mají vyšší váhu.
             * Váha = 1 / accuracy²
             */
            let weightedLat = 0;
            let weightedLng = 0;
            let weightSum = 0;

            usableSamples.forEach(
                function (sample) {

                    const accuracy =
                        Math.max(
                            sample.accuracy,
                            1
                        );

                    const weight =
                        1 /
                        (
                            accuracy *
                            accuracy
                        );

                    weightedLat +=
                        sample.lat *
                        weight;

                    weightedLng +=
                        sample.lng *
                        weight;

                    weightSum +=
                        weight;

                }
            );


            const averageLat =
                weightedLat /
                weightSum;

            const averageLng =
                weightedLng /
                weightSum;


latInput.value =
    averageLat.toFixed(7);

lngInput.value =
    averageLng.toFixed(7);

window.updateSjtskFromWgs84(
    averageLat,
    averageLng
);

panel.hidden = true;


            const statusBox =
                document.getElementById(
                    'locationStatus'
                );

            if (statusBox) {

                statusBox.textContent =
                    '✓ Poloha zprůměrována · ' +
                    usableSamples.length +
                    ' z ' +
                    samples.length +
                    ' vzorků · nejlepší přesnost ±' +
                    formatAccuracy(
                        bestAccuracy
                    );

                statusBox.hidden = false;

                setTimeout(
                    function () {
                        statusBox.hidden = true;
                    },
                    3500
                );

            }

        }
    );


    cancelButton.addEventListener(
        'click',
        function () {

            stopWatch();

            samples = [];

            panel.hidden = true;

            startButton.disabled = false;

            startButton.textContent =
                '🎯 Průměrovat polohu';

        }
    );

})();
</script>

<script>
(function () {

    const editButton =
        document.getElementById('editLocationManually');

    const panel =
        document.getElementById('manualLocationPanel');

    const confirmButton =
        document.getElementById('confirmManualLocation');

    const cancelButton =
        document.getElementById('cancelManualLocation');
		
		const zoomToDeviceButton =
    document.getElementById(
        'zoomToDeviceLocation'
    );

    const latInput =
        document.getElementById('lat_wgs84');

    const lngInput =
        document.getElementById('lng_wgs84');
		
	const xSjtskInput =
		document.getElementById('x_sjtsk');

	const ySjtskInput =
		document.getElementById('y_sjtsk');

    if (!editButton || !panel || typeof L === 'undefined') {
        return;
    }


    let map = null;

    /*
     * Marker stromu, který uživatel ručně přesouvá.
     */
    let treeMarker = null;

    /*
     * Aktuální GNSS poloha zařízení.
     */
	let deviceMarker = null;
	let accuracyCircle = null;
	let deviceWatchId = null;
	let lastDeviceLatLng = null;
	let initialGnssFitDone = false;

    let selectedLat = null;
    let selectedLng = null;


    function getCurrentTreePosition() {

        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);

        if (
            Number.isFinite(lat) &&
            Number.isFinite(lng)
        ) {
            return {
                lat: lat,
                lng: lng
            };
        }

        return null;
    }
	
	async function getTreePositionForMap() {

    /*
     * 1. Přednost má existující WGS84.
     */
    const wgsPosition =
        getCurrentTreePosition();

    if (wgsPosition) {
        return wgsPosition;
    }


    /*
     * 2. Pokud WGS84 chybí,
     * zkusíme S-JTSK.
     */
    if (!xSjtskInput || !ySjtskInput) {
        return null;
    }

    const x =
        parseFloat(xSjtskInput.value);

    const y =
        parseFloat(ySjtskInput.value);

    if (
        !Number.isFinite(x) ||
        !Number.isFinite(y)
    ) {
        return null;
    }


    /*
     * 3. Převod S-JTSK → WGS84.
     */
    try {

        const url =
            'api/sjtsk_to_wgs84.php?x=' +
            encodeURIComponent(x) +
            '&y=' +
            encodeURIComponent(y);

        const response =
            await fetch(
                url,
                {
                    credentials: 'same-origin'
                }
            );

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const data =
            await response.json();

        if (
            data.success !== true ||
            !Number.isFinite(Number(data.lat)) ||
            !Number.isFinite(Number(data.lng))
        ) {
            throw new Error(
                'Neplatná odpověď převodu.'
            );
        }

        return {
            lat: Number(data.lat),
            lng: Number(data.lng)
        };

    } catch (error) {

        console.error(
            'Převod S-JTSK → WGS84 pro mapu selhal:',
            error
        );

        return null;
    }
}


    /*
     * --------------------------------------------------
     * POLOHA STROMU
     * --------------------------------------------------
     */

    function setTreeMarkerPosition(lat, lng) {

        selectedLat = lat;
        selectedLng = lng;

        if (!treeMarker) {

            treeMarker = L.marker(
                [lat, lng],
                {
                    draggable: true,
                    zIndexOffset: 1000
                }
            ).addTo(map);

            treeMarker.bindTooltip(
                'Poloha stromu',
                {
                    direction: 'top',
                    offset: [0, -10]
                }
            );

            treeMarker.on(
                'dragend',
                function () {

                    const position =
                        treeMarker.getLatLng();

                    selectedLat =
                        position.lat;

                    selectedLng =
                        position.lng;
                }
            );

        } else {

            treeMarker.setLatLng(
                [lat, lng]
            );

        }
    }


    /*
     * --------------------------------------------------
     * AKTUÁLNÍ GNSS POLOHA ZAŘÍZENÍ
     * --------------------------------------------------
     */

    function updateDevicePosition(position) {

        if (!map) {
            return;
        }

        const coords =
            position.coords;

        const lat =
            coords.latitude;

        const lng =
            coords.longitude;

        const accuracy =
            coords.accuracy;
			
		lastDeviceLatLng =
    L.latLng(lat, lng);

if (zoomToDeviceButton) {
    zoomToDeviceButton.disabled = false;
}	


        /*
         * Modrý bod bez závislosti na obrázkové ikoně.
         */
        if (!deviceMarker) {

            deviceMarker =
                L.circleMarker(
                    [lat, lng],
                    {
                        radius: 7,
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1
                    }
                ).addTo(map);

            deviceMarker.bindTooltip(
                'Aktuální poloha zařízení',
                {
                    direction: 'top'
                }
            );

        } else {

            deviceMarker.setLatLng(
                [lat, lng]
            );

        }


        /*
         * Kruh odpovídající deklarované
         * přesnosti Geolocation API.
         */
        if (!accuracyCircle) {

            accuracyCircle =
                L.circle(
                    [lat, lng],
                    {
                        radius: accuracy,
                        weight: 1,
                        opacity: 0.7,
                        fillOpacity: 0.12
                    }
                ).addTo(map);

        } else {

            accuracyCircle.setLatLng(
                [lat, lng]
            );

            accuracyCircle.setRadius(
                accuracy
            );

        }


        const roundedAccuracy =
            Math.round(
                accuracy * 10
            ) / 10;

        deviceMarker.setTooltipContent(
            'Aktuální poloha · ±' +
            roundedAccuracy.toLocaleString('cs-CZ') +
            ' m'
        );
		
		/*
 * Při prvním GNSS fixu nastavíme vhodný výřez.
 */
if (!initialGnssFitDone) {

    initialGnssFitDone = true;

    const treePosition =
        getCurrentTreePosition();

    if (treePosition) {

        const treeLatLng =
            L.latLng(
                treePosition.lat,
                treePosition.lng
            );

        const deviceLatLng =
            L.latLng(lat, lng);

        const distance =
            treeLatLng.distanceTo(
                deviceLatLng
            );

        /*
         * Pokud jsme maximálně 500 m od stromu,
         * zobrazíme strom i GNSS polohu.
         */
        if (distance <= 500) {

            const bounds =
                L.latLngBounds(
                    treeLatLng,
                    deviceLatLng
                );

            map.fitBounds(
                bounds,
                {
                    padding: [50, 50],
                    maxZoom: 20
                }
            );

        }

        /*
         * Pokud je GNSS daleko, pohled ponecháme
         * na původní poloze stromu.
         */

} else {

    /*
     * Strom nemá souřadnice.
     * GNSS pouze zobrazíme v mapě,
     * ale pohled automaticky neměníme.
     */

}

}
		
    }


if (zoomToDeviceButton) {

    zoomToDeviceButton.addEventListener(
        'click',
        function () {

            if (!map || !lastDeviceLatLng) {
                return;
            }

            map.setView(
                lastDeviceLatLng,
                20
            );

        }
    );
}

    function startDeviceLocation() {

        if (
            !navigator.geolocation ||
            deviceWatchId !== null
        ) {
            return;
        }

        deviceWatchId =
            navigator.geolocation.watchPosition(

                updateDevicePosition,

                function (error) {

                    console.warn(
                        'GNSS poloha v ruční mapě:',
                        error
                    );

                },

                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 15000
                }
            );
    }


    function stopDeviceLocation() {

        if (deviceWatchId !== null) {

            navigator.geolocation.clearWatch(
                deviceWatchId
            );

            deviceWatchId = null;
        }
    }


    /*
     * --------------------------------------------------
     * MAPA
     * --------------------------------------------------
     */

async function createMap() {

    const currentPosition =
        await getTreePositionForMap();

        const startLat =
            currentPosition
                ? currentPosition.lat
                : 49.8;

        const startLng =
            currentPosition
                ? currentPosition.lng
                : 15.5;

        const startZoom =
            currentPosition
                ? 20
                : 7;

        map = L.map(
            'manualLocationMap',
            {
                minZoom: 6,
                maxZoom: 22
            }
        ).setView(
            [startLat, startLng],
            startZoom
        );


        /*
         * OSM je výchozí podklad.
         */
        const osm =
            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 22,
                    maxNativeZoom: 19,
                    attribution:
                        '&copy; OpenStreetMap contributors'
                }
            );


        // Výchozí podklad ruční opravy polohy je OpenStreetMap.
        osm.addTo(map);
        SVMContextMap.panes(map);
        Promise.all([
            SVMContextMap.trees(map, <?= (int)$activeProjectId ?>, <?= (int)$id ?>),
            SVMContextMap.areas(map, <?= (int)$activeProjectId ?>, null)
        ]).catch(()=>{const info=document.querySelector('.manual-location-info');if(info)info.textContent='Okolní objekty se nepodařilo načíst. Polohu můžete upravit, případně obnovte stránku.';});
const ortho =
    L.tileLayer.wms(
        'https://ags.cuzk.gov.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer',
        {
            minZoom: 6,
            maxZoom: 22,
            layers:'0',format:'image/jpeg',version:'1.3.0',
            attribution: '© ČÚZK'
        }
    );
	
	/*
 * Katastrální mapa ČÚZK.
 * Překryvná vrstva – lze ji zobrazit
 * nad ortofotem, OSM i bílým podkladem.
 */
const katastralniMapa =
    L.tileLayer.wms(
        'https://services.cuzk.gov.cz/wms/local-KM-wms.asp',
        {
            layers: 'KN',
            format: 'image/png',
            transparent: true,
            version: '1.3.0',
            maxZoom: 22,
            attribution: '© ČÚZK'
        }
    );
	
	/*
 * Bílý podklad – vhodný zejména
 * pro samostatné zobrazení katastrální mapy.
 */
const whiteBackground =
    L.gridLayer({
        attribution: ''
    });

whiteBackground.createTile =
    function () {

        const tile =
            document.createElement('div');

        tile.style.width = '256px';
        tile.style.height = '256px';
        tile.style.background = '#ffffff';

        return tile;
    };


        /*
         * Uživatel může případně přepnout podklad.
         */
L.control.layers(
    {
        'OpenStreetMap': osm,
        'Ortofoto ČÚZK': ortho,
        'Bílý podklad': whiteBackground
    },
    {
        'Katastrální mapa ČÚZK':
            katastralniMapa
    },
    {
        collapsed: true
    }
).addTo(map);


        // Pan under a fixed crosshair; the finger does not cover the destination.
        const targetCross=document.createElement('div');targetCross.textContent='＋';targetCross.hidden=true;targetCross.style.cssText='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:38px;line-height:1;color:#a51e12;text-shadow:0 0 3px white;z-index:900;pointer-events:none';map.getContainer().append(targetCross);
        let centerMode=false;
        const centerTools=document.createElement('div');centerTools.className='tools';
        const centerToggle=document.createElement('button'),centerApply=document.createElement('button');centerToggle.type=centerApply.type='button';centerToggle.textContent='Zaměřování křížem';centerToggle.setAttribute('aria-pressed','false');centerApply.textContent='Umístit strom pod kříž';centerApply.hidden=true;
        centerToggle.onclick=()=>{centerMode=!centerMode;targetCross.hidden=centerApply.hidden=!centerMode;centerToggle.setAttribute('aria-pressed',String(centerMode));};
        centerApply.onclick=()=>{const p=map.getCenter();setTreeMarkerPosition(p.lat,p.lng);};centerTools.append(centerToggle,centerApply);map.getContainer().before(centerTools);

        if (currentPosition) {

            setTreeMarkerPosition(
                currentPosition.lat,
                currentPosition.lng
            );

        }


        /*
         * Kliknutí do mapy přesune pouze strom.
         * GNSS bod zařízení zůstává nezávislý.
         */
        map.on(
            'click',
            function (event) {
                if(centerMode)return;
                setTreeMarkerPosition(
                    event.latlng.lat,
                    event.latlng.lng
                );

            }
        );
    }


    /*
     * --------------------------------------------------
     * OTEVŘENÍ MAPY
     * --------------------------------------------------
     */

editButton.addEventListener(
    'click',
    async function () {

        panel.hidden = false;

        initialGnssFitDone = false;

const currentPosition =
    await getTreePositionForMap();


if (!map) {

    await createMap();

            } else {

                if (currentPosition) {

                    setTreeMarkerPosition(
                        currentPosition.lat,
                        currentPosition.lng
                    );

                    map.setView(
                        [
                            currentPosition.lat,
                            currentPosition.lng
                        ],
                        20
                    );
                }

                setTimeout(
                    function () {
                        map.invalidateSize();
                    },
                    0
                );
            }


            /*
             * GNSS sledujeme pouze tehdy,
             * když je ruční mapa otevřená.
             */
            startDeviceLocation();

            editButton.disabled = true;
        }
    );


    /*
     * --------------------------------------------------
     * POTVRZENÍ
     * --------------------------------------------------
     */

    confirmButton.addEventListener(
        'click',
        async function () {

            if (
                selectedLat === null ||
                selectedLng === null
            ) {

                alert(
                    'Nejprve vyber polohu v mapě.'
                );

                return;
            }


            latInput.value =
                selectedLat.toFixed(7);

            lngInput.value =
                selectedLng.toFixed(7);


            /*
             * Automatický přepočet S-JTSK.
             */
            await window.updateSjtskFromWgs84(
                selectedLat,
                selectedLng
            );


            stopDeviceLocation();

            panel.hidden = true;
            editButton.disabled = false;
        }
    );


    /*
     * --------------------------------------------------
     * ZRUŠENÍ
     * --------------------------------------------------
     */

    cancelButton.addEventListener(
        'click',
        function () {

            stopDeviceLocation();

            panel.hidden = true;
            editButton.disabled = false;
        }
    );

})();
</script>

