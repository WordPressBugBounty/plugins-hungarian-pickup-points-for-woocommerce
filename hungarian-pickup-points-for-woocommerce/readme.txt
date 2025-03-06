=== Csomagpontok és Címkék WooCommerce-hez ===
Contributors: passatgt
Tags: gls, postapont, foxpost, packeta, dpd
Requires at least: 6.0
Tested up to: 6.7.2
Stable tag: 3.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Csomagpont választó és címkenyomtató WooCommerce-hez, házhozszállításhoz is. MPL, Foxpost, GLS, DPD, Express One, Postapont, Packeta és még sok más

== Description ==

Ezzel a bővítménnyel megjeleníthetsz egy térképes felületet a pénztár oldalon, ahol a vásárló a kiválasztott szolgáltatók átvételi helyei közül választhat. A beállításokban minden szolgáltatóhoz külön árazást állíthatsz be és a megjelenő felület színét is módosíthatód a Testreszabás menüpontban.
A PRO verzióval címkét is generálhatsz, házhozszállításos rendelésekhez is, illetve saját csomagkövetési oldalt is létrehozhatsz, egyedi automatizálásokkal a csomag státusza alapján.

> **PRO verzió**
> A bővítménynek elérhető a PRO verziója 30 Euróért, amelyet itt vásárolhatsz meg: [https://visztpeter.me](https://visztpeter.me/woocommerce-csomagpont-integracio//)
> A licensz kulcs egy weboldalon aktiválható, 1 évig érvényes és természetesen e-mailes support is jár hozzá beállításhoz, testreszabáshoz, konfiguráláshoz.
> A vásárlással támogathatod a fejlesztést akkor is, ha esetleg a PRO verzióban elérhető funkciókra nincs szükséged.

= Funkciók =

* Postapont, Foxpost, Packeta(Csomagküldő), GLS, Express One, DPD és Pick Pack Pont(Sprinter), DPD és Sameday(easybox) választó egy térképen
* A PRO verzióban lehetőség van Foxpost, Packeta, GLS, DPD, Posta(MPL), Sameday, Express One, Trans-Sped ZERO, Csomagpiac címkét generálni, nyomtatni, házhozszállításra is, nem csak csomagpontra _PRO_
* A csomagpont listát minden nap automatikusan szinkronizálja, így mindig az aktuálisat mutatja bővítmény frissítése nélkül
* Jól kinéző, gyors, egyszerű térképes felület
* Irányítószám(számlázási adatoknál) alapján automatikusan belenagyít a megfelelő megyére, nem kell helymeghatározást engedélyezni
* Árak beállításai feltételek szerint(termék kategória, súly, kosár végösszeg, térfogat stb...)
* Csomagkövetési adatok szinkronizálása, így az admin felületen láthatod a csomag állapotát és automatán változtathatsz rendelés státuszt is _PRO_
* Saját gyedi csomagkövetési oldalt hozhatsz létre a vásárlód számára, így nem kell a futárszolgálat oldalára linkelned _PRO_
* Utánvét díj beállítása csomagpontokhoz vagy bármilyen más szállítási módhoz, fix összegben vagy százalékban _PRO_
* A kiválasztott csomagpontot szállítási címként tárolja el, így minden WooCommerce levélben, profilban, admin felületen automatikusan megjelenik
* A rendelésből eltávolítható a kiválasztott csomagpont(ha másik szállítási módot szeretne a vásárló), vagy lecserélhető másik pontra is
* Lehet saját pontokat hozzáadni, egyedi ikonnal
* Lehet saját címkét generálni a csomagokhoz, például ha saját magad végzed a kiszállítást _PRO_
* Szállítólevelek kezelése: MPL, Foxpost, Express One és DPD esetében lehetőség van napi zárásra, jegyzékzárása, gyűjtő lista létrehozására(összefoglaló néven szállítólevél) _PRO_
* A Megjelenés / Testreszabás / WooCommerce menüpontban módosíthatod a színeket és egyéb megjelenítési beállításokat
* Mobilon is egyszerűen és gyorsan működik
* Rendeléskezelőben szűrhetők a rendelések csomagpont szolgáltató szerint
* Számlázz.hu, Woo Billingo Plus, WooCommerce Shipment Tracking és Yith WooCommerce Order Tracking kompatibilitás(PRO verzió)
* Kompatibilis a Webshippy és iLogistic logisztikai megoldásokkal

== Installation ==

1. Töltsd le a bővítményt
2. Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
3. WooCommerce / Beállítások / Szállítás / Csomagpont menüben nézd át a beállításokat
3. Megjelenés / Testreszabás / WooCommerce / Csomagpont menüben beállíthatod a színeket
4. Ha minden jól megy, működik

== Screenshots ==

1. Térképes felület
2. Beállítások oldal

== Changelog ==

3.5.1
* Foxpost Packeta támogatás: a Foxpost beállításokban bepipálhatod, hogy Packeta csomagpontok és automaták esetén is a Foxpost-al generáljon címkét(a térképre simán a Packeta pontokat állítsd be pluszban)
* Nem kell API kulcs packeta csomagpont adatbázis letöltéshez

3.5.0.3
* WebshopEngine kötelező paraméter kitöltése GLS esetében

3.5.0.2
* Külföldi DPD Weblabel javítás

3.5.0.1
* mb_substr használata substr helyett

3.5
* Felhasználó szerepkör és belépett felhasználó feltétel árazásnál
* "Nagyobb, vagy egyenlő" és "kevesebb, vagy egyenlő" feltétel árazásnál
* DPD Weblabel-nél ha nincs súly megadva csomagpontos rendelésnél, 1kg-ot használ alaprételmezetten
* DPD Shipping API javítások: ref1 paraméter a rendelésszám, utánvét referenciaszám limitálása 14 karakterre

3.4.12
* Middleware használata DPD-nél a Weblabel kivezetése miatt

3.4.11
* Sameday biztosítás javítás

3.4.10
* GLS külföldi csomagpot utánvét kerekítés javítás
* Sameday-nél megadható a maximum biztosítási összeg a beállításokban
* Összes külföldi DPD pont támogatása

3.4.9
* Csomagpiac bugfix(cím hossz limit)
* DPD csomagsúly javítás és egyéb shipping api javítások(külföldi irányítószám, futár megjegyzés)
* Apróbb térkép teljesítmény javítás
* GLS-nél a csomagkövetés automatizálásnál ha a kézbesítve van kijelölve, de a csoamg visszaszállítás után kerül kézbesítésre, akkor nem fut le az automatizálás
* Pénztár blokk javítás WC 9.5-el
* WC 9.5 kompatibilitás megjelölés

3.4.8.4
* Telefonszám javítás külföldi szállítási cím esetén

3.4.8.3
* Kvikk csomagméret javítás
* DPD Shipping API esetén cím 2. sor külön mezőben, hogy ne legyen gond a karakterlimittel
* Vásárlónak küldött levélben a szállítási címnél látszik a szolgáltató neve is, nem csak a csomagpont típusa

3.4.8.2
* DPD Shipping API külföldi szállítás esetén 101-es szerviz kód használata

3.4.8.1
* Csomagméret támogatás: megadható, hogy milyen dobozokba csoagolsz, ezt automatán összepárosítja a rendelésben lévő termékek mérete alapján, de manuálisan is meg lehet adni egyedi méretet minden rendelésekhez(MPL-nél ha csomaguatomatában adsz fel csomagot, kötelező a méretet megadni)

3.4.7
* Packeta Foxpost kezelés külön csomagpontként
* Utánvét összeg javítás részben visszatérített rendelés esetén
* DPD sima A6-os címkeméret támogatás
* YayCurrency kompatibilitás
* MPL csomagfeladás automatából opció

3.4.6.1 & 3.4.6.2
* Csomagpont import bug javítás

3.4.6
* Kvikk Foxpost támogatás(Packetán keresztül)

3.4.5.2
* WP 6.7 és WC 9.4 kompatibilitás

3.4.5.1
* Utánvét díj hibajavítás

3.4.5
* Express One címkeméret javítás
* DPD Weblabel hibakezelés javítás
* Ha egyedi rendelés státuszokat használsz és nem fut le az automatizálás, megadható a beállításokban a rendelés állapot azonosítója
* Utánvét elrejtés javítás pénztár blokk esetében

3.4.4
* Kvikk QR kód beolvasás csomagoláshoz
* vp_woo_pont_trigger_tracking_email_* filter, amivel kóddal kikapcsolható a csomagkövetéses e-mail

3.4.3.1
* Automatizálás javítás
* DPD Weblabel név 40 karakter limit
* PHP warning javítás címkegenerálás közben

3.4.3
* Utánvét díj adókulcs osztály beállítható
* DPD Weblabel esetében a cégnév is átküldésre kerül
* DPD szállítólevél generálás javítás
* PHP warning javítás rendelések oldalon
* vp_woo_pont_label_generate_error action, ami hibás címkegeneráláskor fut le
* A rendelésben el lehet menteni a "_vp_woo_pont_package_count" metában egy számot és akkor több csomagos címkét generál automatán

3.4.2.2
* GLS utánvét hibajavítás

3.4.2.1
* Utánvét PHP warning javítás

3.4.2
* GLS EUR és CZK kerekítés javítás
* Utánvét díjnál megadható fix díj + százalék is
* Egyedi címke PDF generálás javítás
* Kvikk IPN hívás kezelés(csomagtörlés)
* Beállítások gomb javítása, ha egyedi mezőben történt módosítás

3.4.1.1
* Duplikált címkegenerálás javítás
* Kvikk címketörlés javítás(ha már az appon törölve lett, akkor engedi letörölni Woo-ból is)

3.4.1
* DPD Shipping API működik csomagpontra / AlzaBox-ra is
* MPL címkegenerálás postapontra hibajavítás
* Kompatibilitás megjelölése WooCommerce 9.3-al

3.4.0.2
* Pénztár oldal bugfix

3.4
* Csomagsúly korrekció: címke beállításokban megadhatod, hogy feltételek alapján mennyivel legyen több az automatán kalkulált súly(pl a csomagolás súlyát hozzáadhatod így)
* MPL esetén összevonásra került a Coop / Mol / Mediamart Postapontok néven, hogy egyszerűbb legyen
* DPD csomagpontok szét lettek bontva külön Pickup csomagpontokra és AlzaBox automatákra(így külön lehet árakat beállítani)
* Rendelések csomagpont szűrőnél lehet szolgáltatóra is szűrni
* Sameday több csomag feladása funkció
* Fámafutár bekapcsolása Kvikk-nél
* Javítás Curcy kompatibilitáshoz

3.3.5.1
* API hívás timeout módosítás

3.3.5
* Packeta Packeta átnevezése Packeta Z-Pont-ra
* Packeta Z-Box ikon csere
* Lapozás javítása szállítóleveleknél

3.3.4
* Packeta külföldi Z-Box árazás javítás
* MPL törékeny csomag feltétel beállítható szállítási osztályra is
* GLS csomagkövetés javítások
* Árazás beállítás hibajavítás
* Express One többcsomagos szállítás
* DPD csomagpont lista import javítás

3.3.3.3
* Kvikk javítások(A5-ös címkeméret és csomagkövetés állapot szín rendelések táblázatban)

3.3.3.2
* DPD Shipping API utánvétes csomag javítás

3.3.3.1
* Kvikk JS bug javítás

3.3.3
* DPD Shipping API javítás és DEV mód kapcsoló
* Számlázz.hu kompatibilitás fejlesztés: a csomagkövetés automatizálásnál beállítható, hogy fizetettnek jelölje a rendelést, így a számla is fizetettnek lesz jelölve

3.3.2
* DPD Shipping API élesben is használható Weblabel helyett
* Postapont import javítás
* Kompatibilitás megjelölése WP 6.6-al
* Requires Plugins fejléc

3.3.1.2
* Webshippy DPD és Pick Pack Pont javítás
* DPD-nél D-COD-PREDICT szolgáltatás használata, ha az utánvét nélküli D-PREDICT-re van állítva
* Kompatibilis megjelölése legújabb Woo verzióval

3.3.1.1
* Az eltűnt szállítólevelek nem lesznek eltűnve

3.3.1
* Kvikk javítások
* Trans-Sped ZERO mini ládaméret

3.3.0.2
* Címke létrehozva e-mail előnézet hibajavítás
* Kvikk csomagkövetés frissítés javítás

3.3
* Kvikk szolgáltató kompatibilitás
* Kompatibilitás megjelölése legújabb Woo verzióval
* Ha van -1 ár kizárás miatt, akkor az érvényes lesz akkor is, ha az árazás logika a legdrágább opcióra van rakva
* HPOS esetén gyorsabb szállítólevelek betöltés
* VP Extra Díjak bővítmény kompatibilitás javítás
* Szállítóleveleknél nem tölti újra az oldalt amikor generálod a jegyzékzárást
* PHP warning javítása beállítások oldalon
* Ékezetes karakterek javítása egyedi címkén
* Postapont adatbázis import javítás(előfordul, hogy nincs koordináta a forrásban)

3.2.11
* Trans-Sped ZERO beállításokban megadható ideiglenesen egyedi felvételi dátum

3.2.10
* vp_woo_pont_get_carrier_from_order filter

3.2.9.1
* Blokk pénztár javítások

3.2.8.3
* Packeta timeout emelés

3.2.8.1
* GLS bug javítás

3.2.8
* DPD import megy auth adatok nélkül
* vp_woo_pont_after_foxpost_label_created action a Foxpost által küldött egyéb adatok feldolgozásához és tárolásához
* Csomagpiac hibaüzenet kijelzés javítás
* MPL Terjedelemes kezelés extra szolgáltatás
* GLS többcsomagos címke letöltés javítás
* MPL többcsomagos címke biztosítási összeg javítás
* Sameday token csere javítás

3.2.7
* DPD Predict szolgáltatás

3.2.6
* Telefonszám validálás fejlesztése
* Blokkos pénztár és kosár oldalon ár kijelzés javítása
* Sameday beállítások javítás
* CSomagpiac MPL címkegenerálás javítás

3.2.5
* GLS csomagpont lista import javítás(kiszűri a duplikált pontokat)
* PHP warning hibajavítás class-pro.php-ben

3.2.4
* Ajax hibajavítás
* Extra díjak kompatibilitás javítás

3.2.3
* Packeta MPL támogatás
* Ajax hívás hibajavítás

3.2.1
* Kompatibilitás az extra díjak bővítménnyel(hogy lehessen pl egy-egy csomagpont szolgáltatóhoz plusz díjat hozzáadni)
* PHP warning javítás

3.2.0.1
* Bugfix kosár oldalon

3.2
* Külföldi DPD csomagpontok
* Csomagpiac MPL támogatás
* Opcionális megjegyzés írása a szolgáltatókhoz a térképen feltételekkel
* Automatizálás bugfix
* Packeta hibanaplózás

3.1.2
* Csoportos címkegenerálás javítás, ha oldal újratöltés nélkül választasz még ki más rendeléseket generálásra
* Posta esetében törékeny termékcímke beállítható, így automatán rárakja ezt az extra szolgáltatást a csomagra
* Foxpsot címke törlés javítás
* X-Currency kompatibilitás
* GLS csomagkövetési infóban látszik a helyszín is
* Placeholder szöveg a szállítási módok párosításánál, ha nincs még konfigurálva egy szolgáltató sem
* vp_woo_pont_validate_phone_number filterrel lehet módosítnai, hogy mikor validálja a telefonszámot(pl házhozszállításnál ne)
* vp_woo_pont_cod_fee filterrel módosítható a kiszámolt utánvét díj
* Csomagkövetés oldal PHP Warning javítás
* Adminfelületen az engedélyezett szolgáltatóknál kiírja, hogy hány elérhető pont van az adott szolgáltatónál(adatbázis oszlop)

3.1.1
* Árváltozás(meglévő PRO felhasználókat nem érinti)
* Kosárösszeg alapú árazásnál pénznemváltó bővítményem támogatása
* Telepítés varázsló újraindítás gomb
* JS kompatibilitási probléma javítása néhány témával
* ExpressOne címkeméret javítás
* Posta értéknyilvántartás korrekció

3.1.0.1
* CSS bugfix

3.1
* A térkép szín és megjelenés beállításai átkerültek a Szállítás / Csomagpontok menübe
* Opiconálisan megjeleníthető jelölődoboz a térképen a szolgáltató szűrőknél(lásd fenti beállítás)
* Telefonszám validáció: bekapcsolható, hogy validálja a magyar telefonszámokat. Elfogad mindenféle formátumot mindaddig, amíg átalakítható normál +36-os számra és csak akkor ír hibát, ha az nem sikerült.
* WooPayments Multicurrency kompatibilitás
* Ha címkegenerálás után rendelésállapotot kell módosítani és a csoportos címkegeneráláskor egy rendéles állapoton belül vagy a rendelések táblázatban, akkor elrejti a táblázatból az állapotmódosított rendeléseket
* Pár új classnév a pont.php sablonban, hogy könnyebben lehessen CSS-t használni rajta
* Telefonszám nélküli rendelés bug javítása
* GLS thermo címke csoportos nyomtatás javítása
* MPL több csomagos extra szolgáltatásos címkegenerálás javítás
* Több darabos címke nyomtatás javítás
* Hibajavítás Astra és Jupiter sablonokhoz
* vp_woo_pont_print_js_compat filterrel be lehet tölteni egy alternatív print.js-t fájlt, ha esetleg gond van a nyomtatási előnézettel

3.0.7
* MPL címke javítása, ha manuális generálásnál ki van kapcsolva mindegyik extra szolgáltatás, de a beállításokban fixen meg van adva(utóbbi felülírta az előbbi üres kiválasztását)
* Ha a szállítási módhoz nincs társítva szolgáltató, akkor rendelésen belül megkér rá, hogy válassz egyet
* Rendelésen belül manuális címkegenerálás után elrejti a címkegenerálás gombot
* Szállítólevelek új oldalon nyílnak meg
* Szolgáltató beállításoknál a súgó link jó helyre mutat
* GLS "ExchangeService" szolgáltatás
* GLS "ShopReturnService" és "ExchangeService" szolgáltatás esetén két oldalra nyomja a címkét a javasolt A6-os címkeméret esetén(mivel ilyenkor 2 címke van)
* Fordítási hiányosság javítása
* Pactic csomagpontok letöltésénél tömörítve tölti le, így elvileg hatékonyabban tud menni
* Csomagkövetési szám csak a kiválasztott levélben látszik, nem az összesben

3.0.6
* Beállítások javítása(ha üresen ment el értéket random) - ha a 3.0 verzió óta módosítottál a beállításokon, akkor légy szíves nézd meg, hogy minden jól van e beállítva a címkék / csomagkövetés fülek alatt.
* Hibajavítás a rendelések táblázatban a nyomtatás gombhoz
* Bugfix csoportos címkenyomtatás összefűzéshez
* Bugfix GLS A6-os javasolt címkemérethez
* Bugfix MPL-hez

3.0
* Csoportos címkegenerlás: új UI, oldal újratöltés nélkül tudsz csoportosan címkéket létrehozni háttérművelet nélkül
* Csomagkövetés e-mail: futárnak átadva, csomagponton átvehető és kiszállítás alatt e-mail küldése a csomagkövetési infó alapján a vásárlónak
* Teljesen átrendezett, logikusabb beállítások
* GLS és Foxpost álló A6-os címkeméret(elforgatja a fekvőt)
* Javasolt címkeméretek: az A6-os méret minden szolgáltatónál elérhető és ha ezt használod, akkor csoportos nyomtatásnál egybefűzi őket A4-es lapra attól függetlenül, melyik szolgáltatótól van
* DPD Shipping API kompatibilitás(még beta)
* Címke létrehozva e-mail: beállítható, hogy a generált PDF fájlt továbbítsa egy e-mail címre, csatolmányként a címkével(pl automata nyomtatás így könnyen beállítható)
* A csomagkövetési infó a rendelések listában is frissíthető és írja, hogy mikor volt legutóbb frissítve
* Ha a csomag kézbesítve lett, többet nem frissíti a csomagkövetési infót(kivéve posta utánvét esetében, mert ott még van pluszban egy "utánvét kifizetve" esemény)
* Csomagkövetési infót éjjel nem szinkronizál, feleslegesen ne legyenek api hívások(posta esetében gyorsan el tud fogyni a keret) 
* A vp_woo_pont_tracking_sync_interval filterrel beállítható, hogy milyen időközönként szinkronizáljon csomagkövetési infót(alapból 2 óra)
* Packeta csomagpont lista import saját adatbázisból megy, így nem kell 50+mb-os JSON fájlt letölteni/feldolgozni(és megy api kulcs nélkül is)
* HPOS javítás szállítóleveleknél
* Egyedi címkénél sablon előnézet lehetőség
* Cosmagpont választó blokk javítás
* vp_woo_pont_metabox_after_generate_options action, amivel a címkegenerálás dobozban lehet plusz mezőket megjeleníteni
* Rate meta csak a pénztár blokk használata esetén kerül mentésre
* PHP 8.1+ kompatibilitás(8.3-al is tesztelve)
* .local domain esetében aktív a PRO verzió lincesz kulcs nélkül
* Webshippy kompatibiltiás javítás
* Posta értéknyilvántartás javítás 0Ft-os rendelés esetén
* Százalékos utánvét díjat a nettó ár alapján számolja