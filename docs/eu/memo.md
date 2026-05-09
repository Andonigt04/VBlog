# VBlog — Ikaslearen Eskuliburua

## Zer da hau?

VBlog nahita zaurgarri egindako web segurtasun-aplikazio bat da.

---

## Sarbidea

```bash
sudo docker compose up --build -d
# Aplikazioa http://localhost helbidean dago
```

---

## Zure Eginkizuna

**Erabiltzaile anonimotik root-era eskalatu**, ahultasun-kate bat jarraituz.

**Azken helburua:** Zerbitzarian `root` gisa komandoak exekutatzea.

---

## 0. Fasea — Errebistaketa (Kontu gabe)

<details>
<summary>1. Aholkua</summary>

Bide ezkutuek izen laburrak eta generikoak izaten dituzte. Bilatu hitz hauek: `backup`, `debug`, `admin`, `old`, `internal`, `logs`

</details>

<details>
<summary>2. Aholkua</summary>

Azpidomenu ezkutuek izen teknikoak izaten dituzte:
- `dev`, `admin`, `internal`
- Barne-azpiegitura batean erabiliak, zerbitzu publikoetan ez

Aurkitu ondoren, zure `/etc/hosts` fitxategian lerro bat gehitu beharko duzu.

</details>

<details>
<summary>3. Aholkua</summary>

Bilatu:
- Kredentzialak testu arruntean
- Aplikazioaren informazio teknikoa (bertsioak, DB kontrolatzailea)
- Barne-dokumentazioa

</details>

---

## 1. Fasea — Erregistratutako Erabiltzailea

### Galdera nagusia
> Saio hasi ondoren, zer ikus eta alda dezaket?

### Trabatuta zaude?

<details>
<summary>1. Aholkua</summary>

Bilatu zure baimenak kontrolatzen dituen eremua. Aholkua: "r" hizkiarekin hasten da.

</details>

<details>
<summary>2. Aholkua</summary>

Erabiltzaileek profil-endpointak dituzte. Bilatu:
- Eguneratze-endpoint bat (seguruenik PUT metodoarekin)
- POST/PUT onartzen dituzten API endpointak URL ikusgaia aldatu gabe

</details>

---

## 2. Fasea — Pribilegioen Eskalada

<details>
<summary>1. Aholkua</summary>

Aplikazioak baimenen bat eskatzen al dizu eremu jakin batzuk aldatzeko? Ala bidaltzen duzunean fidatzen da?

</details>

<details>
<summary>2. Aholkua</summary>

Admin bilakatzen bazara, bilatu administrazio-bide bat. Normalean:
- `/admin`
- `/dashboard`
- `/panel`

</details>

---

## 3. Fasea — Barne Sarbidea

<details>
<summary>1. Aholkua</summary>

Linux/Mac-en, editatu `/etc/hosts` eta gehitu:

```
127.0.0.1  dev.vblog.local
```

Ondoren saiatu `http://dev.vblog.local` helbidean sartzen.

</details>

<details>
<summary>2. Aholkua</summary>

Bilatu:
- APIa dokumentatzen duten HTML fitxategiak
- Exekuzio-arrastoen erregistroak
- Zerbitzariaren informazio teknikoa

</details>

---

## 3. Fasea — Administrazio Panela

<details>
<summary>1. Aholkua</summary>

Saiatu admin saioaren bidez API endpointak enumeratzen. `/api/admin/` azpian daude publikoki dokumentatu gabeko endpointak.

</details>

<details>
<summary>2. Aholkua</summary>

Endpoint batek `path` parametroa onartzen du zerbitzariko fitxategiak irakurtzeko. Zer gertatzen da `../../etc/passwd` jartzen baduzu?

</details>

<details>
<summary>3. Aholkua</summary>

Beste endpoint batek postak bilatzeko `filter` testu-parametroa onartzen du. Saiatu komatxo bakunaren `'` bat gehitzen balioaren amaieran. Erantzunean ezohikoa ikusten al duzu?

</details>

<details>
<summary>4. Aholkua</summary>

Fitxategi-igoera endpoint bat dago. Zerbitzariak zuk aukeratutako izenarekin gordetzen du fitxategia. Zer gertatzen da `.php` fitxategi bat igotzen baduzu?

</details>

---

## 4. Fasea — Sistemako Eskalada (root)

<details>
<summary>1. Aholkua</summary>

Exekutatu `sudo -l` zure shell-etik. Ba al dago root gisa exekuta dezakezun binariren bat?

</details>

<details>
<summary>2. Aholkua</summary>

Bilatu GTFOBins-en aurkitutako binarioa. `sudo`-rekin eskalatzeko teknika normalean `-exec` erabiltzen du.

</details>

<details>
<summary>3. Aholkua</summary>

Ba al dago `/tmp`-n `SUID` bita aktibatuta duen binariren bat? Saiatu `ls -la /tmp/`.

</details>
