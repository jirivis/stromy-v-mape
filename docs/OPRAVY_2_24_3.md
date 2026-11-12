# Aktualizace 2.24.3 z verze 2.24.2

- Tabulka projektu v horní liště až napravo od uživatele.
- Otevřené filtry na PC 480 px: odstraněno přebití původním !important omezením 220 px; mobil zachován.
- GPKG má stejný základ názvu jako QGZ; všechny relativní zdroje QGIS odkazují na nový název.
- Vitalita 0 při importu znamená nehodnoceno (NULL), nikoli vymyšlený stupeň vitality. Platí ve společné validaci tabulkového a GPKG importu.
- Fotoimport rozpozná Strom_01 a strom-01. Nerozpoznané složky a soubory nad 5 MB zůstanou v náhledu označené jako přeskočené; lze odškrtnout také další fotografie a pokračovat po chybě nahrávání. Originály se automaticky nezmenšují.
- Export se v uživatelském rozhraní nejprve připraví jako celý ZIP, teprve poté začne samostatný GET download. Chyby HTTP/neplatné odpovědi při přípravě se zobrazí. Hotový ZIP je soukromý, omezený na uživatele, projekt a jednu hodinu; práva se ověřují znovu. Podpora byte ranges a opětovného stažení bez přepočtu popisků. Expirované soubory se odstraňují při další přípravě exportu.
- Content-Length se bere přes fstat z otevřeného ZIPu, nikoli z případně zastaralé PHP stat cache dočasného souboru. Tím se předchází nesouladu délky po změně ZIPu.

Příčina původního přerušení na WEDOS nebyla potvrzena serverovým logem. Nový postup odděluje náročnou přípravu od přenosu a dovoluje opakovat přenos, ale neodstraňuje pevné časové/diskové limity hostingu. Po nasazení ověřit konkrétní projekt s popisky. Aktuální již dodaný ZIP 2.24.2 se nemění; nový podepsaný balíček má číslo 2.24.3 a nevyžaduje změny databáze.
