# MedCare Rendszer

Ez az AI-támogatott betegkezelő rendszer jelenleg háziorvosi rendelők által alkalmazható egyszerűen.
Mérnökinformatikus BSc-szakdolgozatom gyakorlati részeként készült. 

A rendszer támogatja az orvosi munkát a betegfelvétel egyszerűségével, a diagnózis rögzítés lehetőségével,
emellett a gyorsabb dokumentum kiállításban is segítséget nyújt. 
A mesterséges intelligencia a programban 3 helyen társul: laborleletek képről történő beolvasása, diagnosztikai javaslatok adása
és dokumentumszöveg fogalmazása.
**Minden MI-eredmény mentéséhez orvosi jóváhagyás szükséges**, a rendszer önállóan nem hozhat döntést.


## 1. Rendszerkövetelmények
PHP - 8.2 vagy újabb (8.3 ajánlott) `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` kiterjesztés szükséges
Composer - 2.x 
MySQL 8.0 / 10.4 vagy újabb, InnoDB tárolómotor, utf8mb4 kódolás 
Webszerver -  nem szükséges - fejlesztéshez a beépített `php artisan serve` elegendő 
Node.js / npm - **nem szükséges**  a felület a Tailwind CSS-t CDN-ről tölti be 
Internetkapcsolat - szükséges a CSS-keretrendszer és az MI-szolgáltatás eléréséhez 

A fejlesztés és a tesztelés XAMPP 8.2 környezetben, Windows 11 alatt történt.
A `package.json` a Laravel alapcsomagjából származó Vite- és Tailwind-függőségeket tartalmazza, de a rendszer ezeket nem használja: a felület a Tailwindet CDN-ről tölti be. Az `npm install` és az `npm run build` ezért kihagyható.

---

## 2. Telepítés
### 2.1. A függőségek telepítése 

(A vendor ettől függetlenül szándékosan rendelkezésre áll.)
```bash
composer install
```

### 2.2. A környezeti fájl előkészítése

```bash
cp .env.example .env
php artisan key:generate
```

Windowson a `cp` helyett `copy .env.example .env` használható.

### 2.3. Az adatbázis létrehozása

A migrálás nem hozza létre magát az adatbázist, csak a táblákat. Az üres adatbázist előbb létre kell hozni, például phpMyAdminban vagy parancssorból - erre utaló információt a .env.example fájlba is helyeztem el:

```sql
CREATE DATABASE betegkezelo_rendszer
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 2.4. Táblák és mintaadatok létrehozása

```bash
php artisan migrate:fresh --seed
```

A parancs eldobja a meglévő táblákat, újra létrehozza őket, majd feltölti a mintaadatokkal: négy felhasználóval, a tünettörzzsel és 18 pácienssel, akikhez ellátási esetek, tünetek, laborleletek, vitális mérések és diagnózisok tartoznak.
A seedelt dokumentumokhoz nem tartozik valódi PDF-fájl, ezért azok letöltése szándékosan 404-es választ ad. Működő dokumentum a felületen állítható ki.

### 2.5. Az MI-szolgáltatás beállítása

A mesterséges intelligenciát használó funkciókhoz saját API-kulcs szükséges. A kulcs a Google AI Studio oldalán igényelhető - ezt is feltüntettem a .env.example-ben: <https://aistudio.google.com/apikey>
A kulcsot a `.env` fájlba kell beírni:

```
GEMINI_API_KEY=ide_kerul_a_sajat_kulcs
AI_DIAGNOSIS_PROVIDER=gemini
AI_DIAGNOSIS_MODEL=gemini-3.5-flash-lite
```

A szolgáltató neve kisbetűvel írandó. Használható értékek: `gemini`, `openai`, `anthropic`. Ismeretlen érték esetén a rendszer hibát jelez, nem vált másik szolgáltatóra. A modellazonosítónak a kiválasztott szolgáltatóhoz kell tartoznia, és más szolgáltató esetén a hozzá tartozó API-kulcsot is meg kell adni.
**Az MI-kulcs nélkül a rendszer működik**, csak a három MI-funkció nem: a laborlelet-beolvasás, a diagnózisjavaslat és a dokumentumszöveg-javaslat hibaüzenettel áll meg. A kézi adatrögzítés, a diagnózis felvitele és a dokumentumkiállítás ettől függetlenül használható.

### 2.6. Indítás

```bash
php artisan serve
```

---

## 3. Belépési adatok

A mintaadatok négy felhasználót hoznak létre. Mindegyik jelszava: `password`

`peterkovacs@korhaz.hu` | Dr. Kovács Péter - orvos 
`annaszabo@korhaz.hu` | Dr. Szabó Anna - orvos 
`bencetoth@korhaz.hu` | Tóth Bence - asszisztens 
`eszternagy@korhaz.hu` | Nagy Eszter - asszisztens 

A két szerepkör eltérő jogosultságokkal rendelkezik. Az asszisztens felveheti a pácienst, rögzíthet tünetet, laboreredményt és vitális paramétert, de nem rögzíthet diagnózist, nem használhatja az MI-asszisztenst, nem állíthat ki dokumentumot, és nem fér hozzá a teljes kórtörténethez.

---

## 4. A telepítés ellenőrzése

A telepítés akkor sikeres, ha az alábbi lépések mindegyike működik:

1. A <http://127.0.0.1:8000> cím a bejelentkezési oldalra irányít.
2. A `peterkovacs@korhaz.hu` / `password` párossal a belépés sikeres, és megjelenik a kezdőlap a várólistákkal.
3. A pácienslistából megnyitható egy beteg adatlapja, és látszanak a tünetei, laborleletei és mérései.
4. A `bencetoth@korhaz.hu` fiókkal belépve a diagnózis és az MI-asszisztens menüpontjai nem érhetők el.
5. Egy nyitott ellátási esettel rendelkező páciensnél kiállítható egy dokumentum, és a keletkezett PDF letölthető.

Ha az MI-kulcs is be van állítva, további ellenőrzés:
6. Egy nyitott ellátási esetű, tünetekkel rendelkező páciensnél az MI-asszisztens javaslatot ad, amely elfogadható vagy elutasítható.

---

## 5. Automatizált tesztek

A tesztek memóriában futó SQLite adatbázist használnak, ezért nem érintik a fejlesztői adatbázist, és nem igényelnek MySQL-kapcsolatot. A külső MI-szolgáltatás nem hívódik meg, így a futtatás internetkapcsolat és API-kulcs nélkül is elvégezhető.
Egyetlen tesztcsoport futtatása:

```bash
php artisan test --filter AiAnalysisTest
```

---

## 6. Ismert korlátok

A rendszer prototípus, oktatási célra készült, éles betegadatok kezelésére nem alkalmas.

- A laborlelet-beolvasás a feltöltött fájlt **teljes egészében** átadja a külső MI-szolgáltatónak. A fájl fejlécében szereplő azonosító adatok kitakarása nincs megvalósítva, ezért kizárólag szintetikus vagy anonimizált leleteket szabad feltölteni.
- A diagnosztikai javaslatot kérő prompt nem tartalmaz nevet és TAJ-számot, csak életkort, nemet és a klinikai adatokat.
- A modell által megadott százalékos megbízhatóság a nyelvi modell saját becslése. Kalibráció nélkül nem tekinthető a diagnózis helyességének mérőszámául.
- A felület a Tailwind CSS-t CDN-ről tölti be, ezért internetkapcsolat nélkül formázás nélkül jelenik meg.
- A tesztek SQLite adatbázison futnak, így a MySQL-specifikus sorszintű zárolás helyessége nem méréssel igazolt.
- A rendszer nem támogatja a BNO-kódolást, és nem tartalmaz betegportált egyelőre.
