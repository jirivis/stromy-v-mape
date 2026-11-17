# Původ taxonomických podkladů

Kontrola dokumentace a auditního JSON: 17. 11. 2026. Nejde o novou úplnou taxonomickou revizi ani o nové stažení celého WCVP.

## Doložený zdroj

Govaerts, R.; Board of Trustees of the Royal Botanic Gardens, Kew. World Checklist of Vascular Plants (WCVP), v16, 4. 6. 2026. DOI https://doi.org/10.34885/egs6-cp24. Oficiální záznam: https://kew.iro.bl.uk/entities/product/998399a0-4b87-4a93-8303-6abc8a2de281. Záznam uvádí **CC BY 3.0**, https://creativecommons.org/licenses/by/3.0/ . Při dalším šíření zachovejte uvedení zdroje, licence a informace o provedených úpravách. Jde o nezávislý projekt, nikoli produkt schválený nebo podporovaný Kew.

Úpravou je výběr a párování 551 původních názvů aplikace, normalizace mezer a 11 individuálně posouzených případů. Auditní powo-matches.json obsahuje původní i zdrojové znění, způsob shody, status a odkaz. Zaznamenává datum stažení 9. 11. 2026 a SHA-256 zdrojového ZIPu. Tento hash zde přebíráme z dřívějšího auditního záznamu; původní archiv nebyl při této kontrole znovu stažen. Živý POWO se může mezitím změnit.

Souhrn: 473 shod názvu a autora, 67 shod názvu s rozdílným autorem, 11 posouzených případů. Statusy: 492 Accepted, 42 Synonym, 13 Artificial Hybrid, 2 Unplaced, 2 Illegitimate. **POWO ID tedy neznamená, že původní jméno je právě přijatým jménem.** Přijatý taxon je samostatný údaj. Rozdíly a posouzené případy jsou v author-review.csv; jde o podklad k redakci, ne příkaz k přepsání databáze.

Šest doplněných druhů má jednotlivé zdrojové odkazy v README.md. Kultivary se automaticky nepřiřazují a tento audit je taxonomicky neověřuje.

## Co není doloženo

Původní baseline dodal správce jako seznam sestavený s pomocí Copilotu s odkazem na POWO. To samo o sobě neurčuje primární zdroj českých jmen, kultivarů ani všech původních hodnot family/genus. Doplnění POWO ID tento nedostatek neodstraňuje. Neoznačujeme celý baseline.sql za ověřený export Kew ani za data pod CC BY.

Pro uzavření původu potřebujeme původní odkazy/exporty a jejich podmínky, případně doložené vlastní sestavení těchto částí. Pokud je nebude možné doložit, lze pro veřejnou distribuci vytvořit oddělený základ z doložených zdrojů a původní části ponechat jako volitelný místní import. Toto rozhodnutí dosud nebylo provedeno; stávající data ani instalační číselníky jsme touto dokumentační změnou neodstranili a nezměnili.

## Strojový přehled

provenance-audit.json shrnuje rozsah a omezení kontroly. Neobsahuje klientská data. Změny autorů a přijatých jmen mají projít náhledem a souhlasem správce; nejsou automaticky promítány do uložených stromů.

## Doplnění správce 17. 11. 2026

Správce upřesnil, že možnými zdroji původního seznamu byly BOTANY.cz a BioLib.cz; uvádí to z paměti, nikoli jako doložený export nebo licenci. Přesné záznamy a způsob převzetí nejsou známé. BOTANY.cz uvádí ochranu fotografií a většiny textů (https://botany.cz/cs/cookies/); to samo nerozhoduje o podmínkách použití jednotlivých názvů ani o původu tohoto seznamu. Oba weby proto evidujeme jako nepotvrzené zdrojové vodítko, nikoli jako ověřenou licenční oporu.
