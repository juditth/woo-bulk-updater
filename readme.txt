=== WooCommerce Bulk Price Editor ===
Contributors: yourname
Tags: woocommerce, bulk edit, price, products, categories
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hromadně měňte ceny produktů podle kategorií pro jednoduché i variantní produkty ve WooCommerce.

== Description ==

WooCommerce Bulk Price Editor umožňuje rychle a bezpečně měnit ceny produktů podle kategorií. Plugin podporuje jak jednoduché, tak variantní produkty a používá WooCommerce API pro zajištění správné aktualizace databáze.

= Hlavní funkce =

* **Hromadná změna cen** - Změňte ceny všech produktů v kategorii najednou
* **Filtrování podle ceny** - Volitelně změňte pouze produkty s konkrétní původní cenou
* **Podpora variantních produktů** - Automaticky aktualizuje všechny varianty
* **Náhled změn** - Zobrazte si přehled změn před jejich aplikací
* **Bezpečné použití WooCommerce API** - Zajišťuje správnou aktualizaci všech tabulek a cache
* **Jednoduché rozhraní** - Intuitivní administrační stránka

= Jak to funguje =

1. Vyberte kategorii produktů
2. Volitelně zadejte původní cenu (pokud chcete změnit pouze produkty s konkrétní cenou)
3. Zadejte novou cenu
4. Zobrazte náhled změn
5. Aplikujte změny

Plugin používá WooCommerce API místo přímých SQL dotazů, což zajišťuje:
* Správnou aktualizaci všech databázových tabulek
* Vyčištění cache
* Spuštění WooCommerce hooks
* Kompatibilitu s budoucími verzemi WooCommerce

= Požadavky =

* WordPress 5.8 nebo novější
* WooCommerce 5.0 nebo novější
* PHP 7.4 nebo novější

== Installation ==

1. Nahrajte složku `woo-bulk-price-editor` do adresáře `/wp-content/plugins/`
2. Aktivujte plugin v menu 'Pluginy' ve WordPressu
3. Přejděte na WooCommerce > Hromadná úprava cen
4. Vyberte kategorii a nastavte nové ceny

== Frequently Asked Questions ==

= Funguje plugin s variantními produkty? =

Ano! Plugin automaticky aktualizuje všechny varianty variantních produktů.

= Je bezpečné používat tento plugin? =

Ano. Plugin používá oficiální WooCommerce API pro aktualizaci cen, což zajišťuje správnou aktualizaci databáze a kompatibilitu s WooCommerce.

= Mohu vrátit změny zpět? =

Aktuálně plugin nepodporuje funkci undo. Doporučujeme vždy nejprve použít náhled změn a případně vytvořit zálohu databáze před velkými změnami.

= Mohu změnit pouze produkty s konkrétní cenou? =

Ano! Stačí vyplnit pole "Původní cena" a změní se pouze produkty s touto cenou v dané kategorii.

= Mění plugin i sale price? =

Aktuálně plugin mění pouze regular price (běžnou cenu). Sale price zůstává nezměněna.

== Screenshots ==

1. Administrační stránka s formulářem pro výběr kategorie a nastavení cen
2. Náhled změn před aplikací
3. Výsledky po úspěšné aktualizaci cen

== Changelog ==

= 1.0.0 =
* První vydání
* Podpora jednoduchých produktů
* Podpora variantních produktů
* Filtrování podle kategorie
* Filtrování podle původní ceny
* Náhled změn před aplikací
* Bezpečné použití WooCommerce API

== Upgrade Notice ==

= 1.0.0 =
První vydání pluginu.
