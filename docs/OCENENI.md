# Příprava ocenění (bez kalkulačky)

Stromy i plochy mají shodná pole:

| Pole | Význam |
| --- | --- |
| valuation_selected | Uložený výběr objektu k ocenění, výchozí 0. Nezávisí na částce. |
| valuation_value_czk | Poslední uložená hodnota v celých Kč; NULL znamená neoceněno, 0 skutečně nulovou hodnotu. |
| valuation_method | Identifikátor a verze metodiky; zatím NULL. |
| valuation_at | Čas provedení ocenění; zatím NULL. |

Interní ID, projekty a původní data se nemění. Výběr je společný pro objekt v projektu, nikoli osobní seznam uživatele. Zrušení výběru nesmí mazat částku. Změna vstupů musí v budoucím modulu označit výpočet za neaktuální; samotná uložená částka není důkaz aktuálnosti.

Tato změna nepřidává ovládání, výpočet AOPK ani exporty ocenění. Před zapnutím kalkulačky je nutné doplnit historii výpočtů s verzí metodiky, vstupy, výsledkem a autorem, oprávnění projektu a začlenění do tabulky/karet. Stávající obecná historie nemusí tato nová pole zachytit.

Nové instalace používají schema.sql. Stávající MariaDB: po záloze a kontrole správné databáze 07_valuation_prepare.sql, poté kontroly z 07_valuation_checks.json. Migrace nic nepřepočítává; opakování zachovává vyplněné hodnoty. ADD IF NOT EXISTS neopravuje nesprávný typ existujícího sloupce; kontrola jej musí odmítnout.

Migraci nezařazujte do prvního balíčku aktualizátoru 2.23.1. Nejdříve je nutné zavést nový protokol aktualizací. SQL a kontroly jsou zatím příprava pro následné vydání.
