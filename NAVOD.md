# WooCommerce Bulk Price Editor - Návod k použití

## Instalace

1. Nahrajte celou složku `woo-bulk-price-editor` do `/wp-content/plugins/`
2. Přejděte do WordPress administrace > Pluginy
3. Najděte "WooCommerce Bulk Price Editor" a klikněte na "Aktivovat"

## Použití

### Základní postup

1. **Přejděte na stránku pluginu**
   - V WordPress administraci klikněte na WooCommerce > Hromadná úprava cen

2. **Vyberte kategorii**
   - Z rozbalovacího menu vyberte kategorii produktů, u kterých chcete změnit ceny
   - V závorce vidíte počet produktů v kategorii

3. **Nastavte ceny**
   - **Původní cena (volitelné)**: Pokud chcete změnit pouze produkty s konkrétní cenou (např. 129), zadejte ji sem
   - **Nová cena (povinné)**: Zadejte novou cenu, která se má nastavit (např. 159)

4. **Zobrazte náhled**
   - Klikněte na "Zobrazit náhled změn"
   - Plugin zobrazí tabulku se všemi produkty, které budou změněny
   - Uvidíte název produktu, typ (jednoduchý/variantní), varianty a původní vs. novou cenu

5. **Aplikujte změny**
   - Pokud je náhled v pořádku, klikněte na "Aplikovat změny"
   - Potvrzení dialogem
   - Plugin provede změny a zobrazí výsledky

### Příklady použití

#### Příklad 1: Změna všech produktů v kategorii
- **Kategorie**: Trika
- **Původní cena**: *nechte prázdné*
- **Nová cena**: 299
- **Výsledek**: Všechny produkty v kategorii "Trika" budou mít cenu 299 Kč

#### Příklad 2: Změna pouze produktů s konkrétní cenou
- **Kategorie**: Kalhoty
- **Původní cena**: 129
- **Nová cena**: 159
- **Výsledek**: Pouze produkty v kategorii "Kalhoty" s cenou 129 Kč budou změněny na 159 Kč

#### Příklad 3: Variantní produkty
- **Kategorie**: Mikiny
- **Původní cena**: 399
- **Nová cena**: 449
- **Výsledek**: Všechny varianty (velikosti, barvy) produktů s cenou 399 Kč budou změněny na 449 Kč

## Důležité informace

### Co plugin mění
- ✅ Regular price (běžná cena)
- ✅ Jednoduché produkty
- ✅ Všechny varianty variantních produktů
- ✅ Automaticky vyčistí WooCommerce cache

### Co plugin nemění
- ❌ Sale price (akční cena) - zůstává nezměněna
- ❌ Produkty mimo vybranou kategorii
- ❌ Produkty s jinou cenou (pokud je zadána původní cena)

### Bezpečnost
- Plugin používá WooCommerce API, ne přímé SQL dotazy
- Všechny změny jsou prováděny bezpečně s kontrolou oprávnění
- Doporučujeme vždy nejprve použít náhled změn
- Pro velké změny doporučujeme zálohu databáze

### Technické detaily

**Proč WooCommerce API?**
WooCommerce ukládá ceny na více místech v databázi:
- `wp_postmeta` (hlavní ceny)
- `wp_wc_product_meta_lookup` (cache pro vyhledávání)
- Různé transients a cache

Použití WooCommerce API zajišťuje:
- Správnou aktualizaci všech tabulek
- Vyčištění cache
- Spuštění WooCommerce hooks
- Kompatibilitu s budoucími verzemi

## Výkon a velké objemy dat

Plugin je optimalizován pro práci s velkými objemy produktů (500-1000 variant).

### Co plugin dělá automaticky:
- ✅ Zvyšuje PHP limity (5 minut, 512MB paměti)
- ✅ Čistí cache každých 50 produktů
- ✅ Zobrazuje varování při více než 100 změnách
- ✅ Informuje o délce zpracování

### Tipy pro velké změny:
1. **Použijte náhled** - zkontrolujte, kolik produktů bude změněno
2. **Odškrtněte nepotřebné** - snižte počet změn na minimum
3. **Neopouštějte stránku** - během zpracování zůstaňte na stránce
4. **Rozdělte do dávek** - pokud máte více než 1000 změn, rozdělte je do více kategorií

### ⚠️ DŮLEŽITÉ: Záloha před změnami

**Plugin nemá funkci "vrátit zpět"!** Před použitím si vytvořte zálohu:

**Možnost 1: Export produktů (doporučeno)**
```
WooCommerce > Produkty > Export
```
- Exportuje všechny produkty do CSV
- Můžete je později importovat zpět

**Možnost 2: Záloha databáze**
- Přes phpMyAdmin nebo hosting panel
- Zálohujte celou databázi před změnou

**Možnost 3: Testovací kategorie**
- Vytvořte testovací kategorii s 2-3 produkty
- Otestujte změny nejdříve na ní
- Pak aplikujte na produkční data

### Pokud dochází k timeoutům:
Kontaktujte svého webhostingu a požádejte o zvýšení:
- `max_execution_time` na 600 sekund
- `memory_limit` na 1024M


## Řešení problémů

### Plugin se nezobrazuje v menu
- Zkontrolujte, že je WooCommerce aktivní
- Zkontrolujte, že máte oprávnění `manage_woocommerce`

### Změny se neprojevují na webu
- Vyčistěte cache (WooCommerce, WordPress, server)
- Zkontrolujte, že produkty mají správně nastavenou kategorii

### Chyba při aktualizaci
- Zkontrolujte PHP error log
- Ujistěte se, že používáte PHP 7.4 nebo novější
- Zkontrolujte, že WooCommerce je aktuální

## Podpora

Pro podporu nebo hlášení chyb kontaktujte autora pluginu.

## Changelog

### 1.0.0
- První vydání
- Podpora jednoduchých a variantních produktů
- Filtrování podle kategorie a ceny
- Náhled změn
- Bezpečné použití WooCommerce API
