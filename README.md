# WooCommerce Bulk Price Editor

Hromadně měňte ceny a popisy produktů podle kategorií. Podporuje jednoduché i variantní produkty s batch processingem pro velké objemy dat.

## Verze 1.1.0

### ✨ Hlavní funkce

- **Hromadná úprava cen** - Změňte ceny pro celé kategorie najednou
- **Úprava popisů** - Krátký i dlouhý popis (u variant se propisuje do rodiče)
- **Filtrování** - Podle kategorie a staré ceny
- **Náhled před změnou** - Zkontrolujte, co se změní, než to potvrdíte
- **Výběr konkrétních položek** - Checkboxy pro výběr jen některých produktů
- **Batch processing** - Automatické zpracování po 50 položkách
- **Výkonnostní limit** - Max 100 produktů v náhledu (ochrana serveru)
- **Bezpečnost** - Aktualizuje pouze vybrané produkty

### 📋 Požadavky

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+

### 🚀 Instalace

1. Nahrajte složku `woo-bulk-price-editor` do `/wp-content/plugins/`
2. Aktivujte plugin v administraci WordPressu
3. Najdete ho v menu **WooCommerce → Bulk Price Editor**

### 💡 Použití

1. **Vyberte kategorii** - Zvolte kategorii produktů
2. **Filtrujte podle ceny** (volitelné) - Zadejte starou cenu pro přesnější výběr
3. **Klikněte na "Náhled"** - Zobrazí se seznam produktů
4. **Vyberte produkty** - Zaškrtněte, které chcete změnit
5. **Zadejte nové hodnoty** - Nová cena a/nebo popisy
6. **Klikněte na "Aplikovat změny"** - Změny se provedou

### ⚠️ Důležité poznámky

- **Limit 100 produktů v náhledu** - Pokud najde více produktů, zobrazí se pouze prvních 100
- **Doporučení pro velké objemy**: Použijte filtr "Stará cena" nebo zpracujte produkty po menších dávkách (např. podle podkategorií)
- **Variantní produkty**: Popisy se propisují do hlavního (rodičovského) produktu

### 🔧 Changelog

#### 1.1.0 (2026-02-05)
- ✨ Přidána úprava popisů (krátký i dlouhý)
- ✨ Přidán výkonnostní limit (100 produktů v náhledu)
- 🐛 Opravena chyba s duplicitním HTML výpisem
- 🐛 Opravena chyba s resetováním formuláře
- 🐛 Opravena chyba s přesměrováním stránky
- 🎨 Zlepšená zpětná vazba pro uživatele
- 🎨 Odstraněn reset formuláře po aktualizaci

#### 1.0.0 (2026-02-04)
- 🎉 Prvotní vydání
- ✨ Hromadná úprava cen podle kategorií
- ✨ Podpora jednoduchých i variantních produktů
- ✨ Batch processing pro velké objemy

### 📝 Licence

GPL v2 or later

### 👤 Autor

Jitka Klingenbergová - [vyladeny-web.cz](https://vyladeny-web.cz/)

### 🔗 Repository

[github.com/juditth/woo-bulk-updater](https://github.com/juditth/woo-bulk-updater)
