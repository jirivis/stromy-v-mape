fetch('/map/public/api/get_trees.php', {
    credentials: 'same-origin'
})
    .then(async response => {
        const data = await response.json();

        if (response.status === 401) {
            window.location.href = '/login.php';
            throw new Error('Uživatel není přihlášen.');
        }

        if (!response.ok) {
            throw new Error(data.message || 'Načtení stromů se nezdařilo.');
        }

        return data;
    })
    .then(trees => {
        console.log('Načtené stromy:', trees);

        // původní vykreslování stromů
    })
    .catch(error => {
        console.error(error);
    });