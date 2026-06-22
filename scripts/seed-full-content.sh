#!/usr/bin/env bash
# =============================================================================
# Poradnik.PRO & PT24.PRO — Complete Content Seeder
# =============================================================================
# Creates ALL pages, categories, and initial content in the WordPress database.
# Based on DOCELOWA-STRUKTURA-URL.md and theme template files.
#
# Usage:
#   ./scripts/seed-full-content.sh [WP_PATH]
#
# Or remotely via SSH:
#   ssh user@host 'bash -s' < scripts/seed-full-content.sh /path/to/wp
#
# Requirements:
#   - WP-CLI installed and accessible
#   - WordPress with pearblog-theme active
# =============================================================================

set -euo pipefail

WP_PATH="${1:-${WP_PATH:-/var/www/html}}"
WP="wp --path=$WP_PATH --allow-root"

echo "=============================================="
echo " PearBlog — Full Content Seeder"
echo " WordPress path: $WP_PATH"
echo " Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=============================================="
echo ""

# Verify WP-CLI works
if ! $WP core version >/dev/null 2>&1; then
  echo "ERROR: Cannot connect to WordPress at $WP_PATH"
  echo "Ensure WP-CLI is installed and the path is correct."
  exit 1
fi

echo "WordPress version: $($WP core version)"
echo ""

# =============================================================================
# Helper functions
# =============================================================================

create_page() {
  local TITLE="$1"
  local SLUG="$2"
  local TEMPLATE="${3:-}"
  local CONTENT="${4:-}"
  local PARENT_ID="${5:-0}"

  EXISTING=$($WP post list --post_type=page --post_name="$SLUG" --field=ID --format=ids 2>/dev/null || true)
  if [[ -n "$EXISTING" ]]; then
    echo "  [exists] $TITLE (ID $EXISTING, slug: $SLUG)"
    return
  fi

  local ARGS=(
    --post_type=page
    --post_title="$TITLE"
    --post_status=publish
    --post_name="$SLUG"
    --porcelain
  )
  [[ -n "$TEMPLATE" ]] && ARGS+=(--page_template="$TEMPLATE")
  [[ -n "$CONTENT" ]] && ARGS+=(--post_content="$CONTENT")
  [[ "$PARENT_ID" -gt 0 ]] && ARGS+=(--post_parent="$PARENT_ID")

  local PID
  PID=$($WP post create "${ARGS[@]}")
  echo "  [created] $TITLE (ID $PID, slug: $SLUG)"
}

create_category() {
  local NAME="$1"
  local SLUG="$2"
  local DESC="${3:-}"

  EXISTING=$($WP term list category --slug="$SLUG" --field=term_id --format=ids 2>/dev/null || true)
  if [[ -n "$EXISTING" ]]; then
    echo "  [exists] $NAME (ID $EXISTING)"
    return
  fi

  local TID
  TID=$($WP term create category "$NAME" --slug="$SLUG" --description="$DESC" --porcelain)
  echo "  [created] $NAME (ID $TID)"
}

create_post() {
  local TITLE="$1"
  local SLUG="$2"
  local CONTENT="$3"
  local CATEGORY="${4:-}"

  EXISTING=$($WP post list --post_type=post --post_name="$SLUG" --field=ID --format=ids 2>/dev/null || true)
  if [[ -n "$EXISTING" ]]; then
    echo "  [exists] $TITLE (ID $EXISTING)"
    return
  fi

  local ARGS=(
    --post_type=post
    --post_title="$TITLE"
    --post_status=publish
    --post_name="$SLUG"
    --post_content="$CONTENT"
    --porcelain
  )

  local PID
  PID=$($WP post create "${ARGS[@]}")

  if [[ -n "$CATEGORY" ]]; then
    $WP post term set "$PID" category "$CATEGORY" --by=slug 2>/dev/null || true
  fi

  echo "  [created] $TITLE (ID $PID)"
}

# =============================================================================
# 1. PERMALINK STRUCTURE
# =============================================================================
echo "==> [1/9] Setting permalink structure"
$WP rewrite structure '/%postname%/' --hard 2>/dev/null || true
$WP rewrite flush --hard 2>/dev/null || true
echo "  Done"
echo ""

# =============================================================================
# 2. CATEGORIES (Poradnik.PRO)
# =============================================================================
echo "==> [2/9] Creating categories"

create_category "Prawo" "prawo" "Porady prawne — prawo cywilne, rodzinne, pracy i karne"
create_category "Finanse" "finanse" "Kredyty, ubezpieczenia, inwestycje i planowanie finansów"
create_category "Nieruchomości" "nieruchomosci" "Kupno, sprzedaż, wynajem i wycena nieruchomości"
create_category "Budownictwo" "budownictwo" "Budowa domu, projekty, materiały i wykonawcy"
create_category "Energia" "energia" "Fotowoltaika, pompy ciepła, audyty energetyczne i OZE"
create_category "Zdrowie" "zdrowie" "Zdrowie, dieta, profilaktyka i aktywny styl życia"
create_category "Edukacja" "edukacja" "Kursy, szkolenia, nauka i rozwój zawodowy"
create_category "Motoryzacja" "motoryzacja" "Serwis samochodowy, ubezpieczenia OC/AC i porady"
create_category "Technologia" "technologia" "IT, software, gadżety i nowe technologie"
create_category "Dom i Ogród" "dom-i-ogrod" "Urządzanie domu, meble, ogrody i przestrzeń"
create_category "Remont" "remont" "Porady dotyczące remontu i wykończenia mieszkania"
create_category "Auto" "auto" "Porady motoryzacyjne i serwis samochodowy"

echo ""

# =============================================================================
# 3. STATIC PAGES — Poradnik.PRO
# =============================================================================
echo "==> [3/9] Creating Poradnik.PRO static pages"

# Homepage
create_page "Poradnik.PRO — Strona Główna" "home" "page-poradnik-pro-home.php" \
  "<p>Poradnik.PRO — Twoje źródło profesjonalnych porad od ekspertów. Znajdź odpowiedzi na najważniejsze pytania z dziedziny prawa, finansów, budownictwa, zdrowia i wielu innych.</p>"

# Blog / Poradniki archive
create_page "Poradniki" "poradniki" "page-poradnik-pro-poradniki.php" \
  "<p>Przeglądaj wszystkie poradniki napisane przez naszych ekspertów. Praktyczna wiedza z różnych dziedzin — od prawa po budownictwo.</p>"

# Porównania (comparisons)
create_page "Porównania" "porownania" "page-poradnik-pro-porownania.php" \
  "<p>Szczegółowe porównania usług, produktów i rozwiązań. Podejmuj świadome decyzje dzięki obiektywnym analizom naszych ekspertów.</p>"

# Rankingi (rankings)
create_page "Rankingi" "rankingi" "page-poradnik-pro-rankingi.php" \
  "<p>Aktualne rankingi najlepszych usługodawców, produktów i rozwiązań w różnych kategoriach. Sprawdź, kto jest liderem w Twojej branży.</p>"

# Kalkulatory (calculators)
create_page "Kalkulatory" "kalkulatory" "page-poradnik-pro-kalkulatory.php" \
  "<p>Bezpłatne kalkulatory online — oblicz koszty remontu, kredytu, energii i wiele więcej. Profesjonalne narzędzia do szybkich obliczeń.</p>"

# Pytania (Q&A archive)
create_page "Pytania i Odpowiedzi" "pytania" "page-poradnik-pro-pytania.php" \
  "<p>Zadaj pytanie ekspertowi i uzyskaj profesjonalną odpowiedź. Przeglądaj archiwum już udzielonych porad.</p>"

# Specjaliści (experts listing)
create_page "Specjaliści" "specjalisci" "page-poradnik-pro-specjalisci.php" \
  "<p>Znajdź najlepszych specjalistów w swojej okolicy. Sprawdź opinie, porównaj oferty i umów się na konsultację.</p>"

# AI Doradca
create_page "AI Doradca" "ai-doradca" "page-poradnik-pro-ai-doradca.php" \
  "<p>Nasz inteligentny asystent AI pomoże Ci znaleźć odpowiedzi na pytania, dobrać specjalistę lub obliczyć koszty. Dostępny 24/7.</p>"

# Dla Specjalistów
create_page "Dla Specjalistów" "dla-specjalistow" "page-poradnik-pro-dla-specjalistow.php" \
  "<p>Dołącz do grona ekspertów Poradnik.PRO. Buduj swoją markę osobistą, zdobywaj klientów i dziel się wiedzą z tysiącami użytkowników.</p>"

# Cennik
create_page "Cennik" "cennik" "page-poradnik-pro-cennik.php" \
  "<p>Sprawdź aktualne ceny usług specjalistów w Twojej okolicy. Porównaj stawki i wybierz najlepszą ofertę.</p>"

# FAQ
create_page "FAQ — Najczęściej Zadawane Pytania" "faq" "page-poradnik-pro-faq.php" \
  "<p>Odpowiedzi na najczęściej zadawane pytania dotyczące platformy Poradnik.PRO, korzystania z usług i współpracy.</p>"

# Kontakt
create_page "Kontakt" "kontakt" "page-poradnik-pro-kontakt.php" \
  "<p>Skontaktuj się z nami — formularz kontaktowy, adres e-mail i godziny pracy biura obsługi.</p>"

# Dashboard użytkownika
create_page "Panel Użytkownika" "panel" "page-poradnik-pro-dashboard.php" \
  "<p>Zarządzaj swoim kontem, historią pytań, ulubionymi specjalistami i powiadomieniami.</p>"

# Blog
create_page "Blog" "blog" "page-poradnik-pro-blog.php" \
  "<p>Aktualności, nowości i porady z różnych dziedzin. Śledź nasz blog, aby być na bieżąco.</p>"

# Eksperci
create_page "Eksperci" "eksperci" "page-poradnik-pro-eksperci.php" \
  "<p>Poznaj naszych ekspertów — doświadczonych specjalistów z różnych dziedzin, gotowych pomóc w Twoich sprawach.</p>"

echo ""

# =============================================================================
# 4. CATEGORY LANDING PAGES — Poradnik.PRO
# =============================================================================
echo "==> [4/9] Creating category landing pages"

declare -A PP_CATEGORIES=(
  ["prawo"]="Prawo — Porady Prawne|Porady prawne od doświadczonych adwokatów i radców prawnych. Prawo cywilne, rodzinne, pracy, spadkowe i karne."
  ["finanse"]="Finanse — Kredyty i Inwestycje|Porady finansowe, kredyty hipoteczne, ubezpieczenia, inwestycje i planowanie budżetu domowego."
  ["nieruchomosci"]="Nieruchomości — Kupno i Sprzedaż|Porady dotyczące kupna, sprzedaży i wynajmu nieruchomości. Wyceny, rynek mieszkaniowy, formalności."
  ["budownictwo"]="Budownictwo — Budowa i Remont|Porady budowlane, projekty domów, materiały, wykonawcy i koszty budowy."
  ["energia"]="Energia — OZE i Oszczędzanie|Fotowoltaika, pompy ciepła, termomodernizacja i audyty energetyczne."
  ["zdrowie"]="Zdrowie — Porady Medyczne|Porady zdrowotne od lekarzy i specjalistów. Profilaktyka, dieta, leczenie."
  ["edukacja"]="Edukacja — Kursy i Szkolenia|Najlepsze kursy, szkolenia i materiały edukacyjne. Rozwój zawodowy i osobisty."
  ["motoryzacja"]="Motoryzacja — Porady Samochodowe|Serwis samochodowy, ubezpieczenia OC/AC, przeglądy techniczne i porady dla kierowców."
  ["technologia"]="Technologia — IT i Innowacje|Nowe technologie, software, gadżety, cyberbezpieczeństwo i porady IT."
  ["dom-i-ogrod"]="Dom i Ogród — Aranżacja Wnętrz|Urządzanie domu, meble, dekoracje, ogrody i przestrzenie zielone."
)

for SLUG in "${!PP_CATEGORIES[@]}"; do
  IFS='|' read -r TITLE CONTENT <<< "${PP_CATEGORIES[$SLUG]}"
  create_page "$TITLE" "kategoria-$SLUG" "page-poradnik-pro-kategoria.php" "<p>$CONTENT</p>"
done

echo ""

# =============================================================================
# 5. CITY PAGES — Poradnik.PRO
# =============================================================================
echo "==> [5/9] Creating city pages (specjaliści w mieście)"

declare -A CITIES=(
  ["warszawa"]="Warszawa"
  ["krakow"]="Kraków"
  ["wroclaw"]="Wrocław"
  ["poznan"]="Poznań"
  ["gdansk"]="Gdańsk"
  ["katowice"]="Katowice"
  ["lodz"]="Łódź"
  ["szczecin"]="Szczecin"
  ["lublin"]="Lublin"
  ["bydgoszcz"]="Bydgoszcz"
)

for SLUG in "${!CITIES[@]}"; do
  NAME="${CITIES[$SLUG]}"
  create_page "Specjaliści — $NAME" "$SLUG-specjalisci" "page-poradnik-pro-specjalisci.php" \
    "<p>Znajdź najlepszych specjalistów w mieście $NAME. Sprawdź opinie, porównaj oferty i umów się na konsultację online lub stacjonarnie.</p>"
done

echo ""

# =============================================================================
# 6. PT24.PRO PAGES
# =============================================================================
echo "==> [6/9] Creating PT24.PRO platform pages"

# Dla fachowców (landing for professionals)
create_page "Dla Fachowców" "dla-fachowcow" "page-pt24-dla-fachowcow.php" \
  "<p>Dołącz do PT24.PRO — największej platformy łączącej fachowców z klientami. Zyskaj zlecenia, buduj reputację i rozwijaj swój biznes.</p>"

# Dodaj zlecenie (order form)
create_page "Dodaj Zlecenie" "dodaj-zlecenie" "page-pt24-dodaj-zlecenie.php" \
  "<p>Opisz swoje zlecenie i otrzymaj oferty od sprawdzonych fachowców. Porównaj ceny, opinie i wybierz najlepszego wykonawcę.</p>"

# Usługi (services archive)
create_page "Usługi" "uslugi" "page-pt24-kategoria-uslugi.php" \
  "<p>Przeglądaj wszystkie kategorie usług dostępnych na PT24.PRO. Od hydrauliki po fotowoltaikę — znajdź fachowca do każdego zadania.</p>"

# Panel fachowca (professional dashboard)
create_page "Panel Fachowca" "panel-fachowca" "page-pt24-panel-fachowca.php" \
  "<p>Zarządzaj swoimi zleceniami, ofertami, opiniami i profilem fachowca. Wszystko w jednym miejscu.</p>"

# PT24 Service category pages
echo ""
echo "  -- Kategorie usług PT24 --"

declare -A PT24_SERVICES=(
  ["hydraulik"]="Hydraulik — Usługi Hydrauliczne|Profesjonalne usługi hydrauliczne: naprawy, instalacje, awarie. Sprawdzeni hydraulicy z opiniami klientów."
  ["elektryk"]="Elektryk — Usługi Elektryczne|Instalacje elektryczne, naprawy, przeglądy. Certyfikowani elektrycy z uprawnieniami SEP."
  ["malarz"]="Malarz — Malowanie Mieszkań|Malowanie ścian, sufitów, elewacji. Profesjonalne ekipy malarskie z doświadczeniem."
  ["stolarz"]="Stolarz — Usługi Stolarskie|Meble na wymiar, renowacja, montaż. Doświadczeni stolarze z portfolio realizacji."
  ["dekarz"]="Dekarz — Usługi Dekarskie|Krycie dachów, naprawy, ocieplenia poddaszy. Profesjonalni dekarze z gwarancją."
  ["murarz"]="Murarz — Prace Murarskie|Murowanie, tynkowanie, fundamenty. Sprawdzone ekipy murarskie w Twojej okolicy."
  ["glazurnik"]="Glazurnik — Układanie Płytek|Płytki ceramiczne, gres, mozaika. Precyzyjne wykończenie łazienek i kuchni."
  ["instalator"]="Instalator — Instalacje Sanitarne|Instalacje CO, wod-kan, gazowe. Certyfikowani instalatorzy z uprawnieniami."
  ["ogrodnik"]="Ogrodnik — Usługi Ogrodnicze|Projektowanie ogrodów, pielęgnacja, nasadzenia. Profesjonalna zieleń wokół domu."
  ["sprzatanie"]="Sprzątanie — Usługi Porządkowe|Sprzątanie mieszkań, biur, po remontach. Sprawdzone firmy sprzątające z recenzjami."
  ["przeprowadzki"]="Przeprowadzki — Transport i Pakowanie|Profesjonalne przeprowadzki lokalne i międzymiastowe. Pakowanie, transport, montaż."
  ["klimatyzacja"]="Klimatyzacja — Montaż i Serwis|Montaż klimatyzacji, serwis, czyszczenie. Certyfikowani instalatorzy z F-gazami."
  ["fotowoltaika"]="Fotowoltaika — Instalacje Solarne|Instalacje fotowoltaiczne, audyty, doradztwo. Certyfikowani instalatorzy OZE."
  ["pompy-ciepla"]="Pompy Ciepła — Montaż i Dobór|Dobór, montaż i serwis pomp ciepła. Doświadczeni instalatorzy z certyfikatami."
  ["remont"]="Remont — Kompleksowe Remonty|Remonty mieszkań i domów pod klucz. Sprawdzone ekipy remontowe z opiniami."
)

for SLUG in "${!PT24_SERVICES[@]}"; do
  IFS='|' read -r TITLE CONTENT <<< "${PT24_SERVICES[$SLUG]}"
  create_page "$TITLE" "uslugi-$SLUG" "page-pt24-kategoria-uslugi.php" "<p>$CONTENT</p>"
done

# PT24 City pages
echo ""
echo "  -- Miasta PT24 --"

declare -A PT24_CITIES=(
  ["warszawa"]="Warszawa"
  ["krakow"]="Kraków"
  ["wroclaw"]="Wrocław"
  ["poznan"]="Poznań"
  ["gdansk"]="Gdańsk"
  ["katowice"]="Katowice"
  ["lodz"]="Łódź"
  ["szczecin"]="Szczecin"
  ["lublin"]="Lublin"
  ["bydgoszcz"]="Bydgoszcz"
  ["rzeszow"]="Rzeszów"
  ["bialystok"]="Białystok"
)

for SLUG in "${!PT24_CITIES[@]}"; do
  NAME="${PT24_CITIES[$SLUG]}"
  create_page "Fachowcy $NAME — PT24" "miasto-$SLUG" "page-pt24-miasto.php" \
    "<p>Znajdź sprawdzonych fachowców w mieście $NAME. Hydraulicy, elektrycy, remontowcy i inni specjaliści z opiniami klientów.</p>"
done

echo ""

# =============================================================================
# 7. SAMPLE CONTENT — Poradniki (Articles)
# =============================================================================
echo "==> [7/9] Creating sample articles (poradniki)"

create_post "Ile kosztuje remont łazienki w 2026 roku — kompletny cennik" \
  "ile-kosztuje-remont-lazienki-2026" \
  "<h2>Koszty remontu łazienki w 2026 roku</h2>
<p>Remont łazienki to jedna z najczęstszych inwestycji w mieszkaniu. Średni koszt remontu łazienki o powierzchni 5-8 m² wynosi od 15 000 do 35 000 zł w zależności od standardu wykończenia.</p>
<h3>Cennik usług</h3>
<ul>
<li>Skucie starych płytek: 40-60 zł/m²</li>
<li>Układanie nowych płytek: 80-150 zł/m²</li>
<li>Instalacja hydrauliczna: 2000-5000 zł</li>
<li>Montaż wanny/kabiny: 500-1500 zł</li>
<li>Malowanie sufitu: 25-40 zł/m²</li>
</ul>
<h3>Na co zwrócić uwagę</h3>
<p>Przy wyborze wykonawcy zwróć uwagę na portfolio realizacji, opinie klientów oraz zakres gwarancji. Warto poprosić o wycenę co najmniej trzech fachowców.</p>" \
  "remont"

create_post "Jak wybrać dobrego elektryka — poradnik krok po kroku" \
  "jak-wybrac-dobrego-elektryka" \
  "<h2>Wybór elektryka — na co zwrócić uwagę</h2>
<p>Prace elektryczne wymagają uprawnień i doświadczenia. Nieprawidłowo wykonana instalacja elektryczna może zagrażać życiu i zdrowiu domowników.</p>
<h3>Kluczowe kryteria wyboru</h3>
<ul>
<li>Uprawnienia SEP (Stowarzyszenie Elektryków Polskich)</li>
<li>Doświadczenie min. 3-5 lat</li>
<li>Ubezpieczenie OC</li>
<li>Pozytywne opinie klientów</li>
<li>Szczegółowa wycena przed rozpoczęciem prac</li>
</ul>
<h3>Typowe ceny usług elektrycznych (2026)</h3>
<p>Wymiana gniazdka: 80-150 zł, wymiana instalacji w mieszkaniu 50 m²: 8000-15000 zł, montaż rozdzielni: 1500-3000 zł.</p>" \
  "budownictwo"

create_post "Kredyt hipoteczny 2026 — jak wybrać najlepszą ofertę" \
  "kredyt-hipoteczny-2026-jak-wybrac" \
  "<h2>Kredyt hipoteczny w 2026 roku</h2>
<p>Wybór kredytu hipotecznego to jedna z najważniejszych decyzji finansowych w życiu. Obecne oprocentowanie kredytów wynosi od 6,5% do 8,5% w zależności od banku i wkładu własnego.</p>
<h3>Kluczowe parametry do porównania</h3>
<ul>
<li>RRSO (Rzeczywista Roczna Stopa Oprocentowania)</li>
<li>Marża banku</li>
<li>Wymagany wkład własny (min. 10-20%)</li>
<li>Prowizja za udzielenie kredytu</li>
<li>Koszty ubezpieczenia</li>
</ul>
<h3>Porada eksperta</h3>
<p>Skonsultuj się z niezależnym doradcą kredytowym, który porówna oferty kilkunastu banków. Doradca nie pobiera opłat od klienta — jego wynagrodzenie pochodzi od banku.</p>" \
  "finanse"

create_post "Ile kosztuje budowa domu 100m² — realne koszty 2026" \
  "ile-kosztuje-budowa-domu-100m2-2026" \
  "<h2>Budowa domu 100 m² — koszty w 2026 roku</h2>
<p>Budowa domu jednorodzinnego o powierzchni 100 m² kosztuje obecnie od 350 000 do 600 000 zł w stanie deweloperskim. Koszty zależą od lokalizacji, technologii i standardu wykończenia.</p>
<h3>Podział kosztów</h3>
<ul>
<li>Projekt + formalności: 5000-15000 zł</li>
<li>Fundamenty: 30000-50000 zł</li>
<li>Ściany + strop: 80000-120000 zł</li>
<li>Dach: 40000-80000 zł</li>
<li>Instalacje (wod-kan, CO, elektryka): 40000-70000 zł</li>
<li>Okna i drzwi: 20000-40000 zł</li>
<li>Wykończenie (stan deweloperski): 50000-100000 zł</li>
</ul>
<h3>Jak zaoszczędzić</h3>
<p>Wybierz prostą bryłę budynku, porównaj oferty minimum 3 wykonawców, rozważ budowę systemem gospodarczym w wybranych etapach.</p>" \
  "budownictwo"

create_post "Fotowoltaika 2026 — czy się opłaca i ile kosztuje" \
  "fotowoltaika-2026-czy-sie-oplaca" \
  "<h2>Fotowoltaika w 2026 — koszty i opłacalność</h2>
<p>Instalacja fotowoltaiczna o mocy 5-10 kWp kosztuje od 18 000 do 40 000 zł. Przy obecnych cenach energii inwestycja zwraca się w 6-9 lat.</p>
<h3>Aktualne ceny instalacji</h3>
<ul>
<li>Instalacja 5 kWp: 18000-24000 zł</li>
<li>Instalacja 8 kWp: 28000-35000 zł</li>
<li>Instalacja 10 kWp: 35000-45000 zł</li>
</ul>
<h3>Dotacje i ulgi</h3>
<p>Program Mój Prąd 6.0 oferuje dotację do 7000 zł. Ulga termomodernizacyjna pozwala odliczyć koszty od podatku (do 53 000 zł).</p>
<h3>Net-billing vs net-metering</h3>
<p>Od 2024 obowiązuje net-billing — nadwyżki energii sprzedajesz po cenie rynkowej. Sprawdź kalkulację dla swojego zużycia.</p>" \
  "energia"

create_post "Ubezpieczenie OC samochodu — jak wybrać najtaniej" \
  "ubezpieczenie-oc-samochodu-jak-wybrac" \
  "<h2>Ubezpieczenie OC — porównanie ofert 2026</h2>
<p>Obowiązkowe ubezpieczenie OC jest podstawą dla każdego kierowcy. Ceny polis OC różnią się nawet o 100% między towarzystwami ubezpieczeniowymi.</p>
<h3>Jak obniżyć cenę OC</h3>
<ul>
<li>Porównaj oferty min. 5 towarzystw</li>
<li>Kup polisę online (zniżki do 15%)</li>
<li>Utrzymuj bezszkodowy przebieg</li>
<li>Wybierz wyższe zniżki za wiek i doświadczenie</li>
<li>Rozważ roczną polisę zamiast półrocznej</li>
</ul>
<h3>Średnie ceny OC w 2026</h3>
<p>Samochód osobowy (kierowca 30+ lat, 5+ lat doświadczenia): 400-900 zł/rok. Młody kierowca (18-25 lat): 1500-4000 zł/rok.</p>" \
  "motoryzacja"

create_post "Pompy ciepła 2026 — rodzaje, koszty i dobór" \
  "pompy-ciepla-2026-rodzaje-koszty" \
  "<h2>Pompy ciepła — kompleksowy poradnik 2026</h2>
<p>Pompa ciepła to nowoczesne i ekologiczne rozwiązanie grzewcze. Przy odpowiednim doborze pozwala obniżyć koszty ogrzewania nawet o 60% w porównaniu z gazem.</p>
<h3>Rodzaje pomp ciepła</h3>
<ul>
<li>Powietrze-woda (najpopularniejsza): 30000-60000 zł z montażem</li>
<li>Gruntowa (najefektywniejsza): 50000-90000 zł z montażem</li>
<li>Powietrze-powietrze: 8000-15000 zł z montażem</li>
</ul>
<h3>COP i SCOP — co oznaczają</h3>
<p>COP to współczynnik wydajności — pompa o COP=4 daje 4 kWh ciepła z 1 kWh prądu. SCOP to sezonowy współczynnik — bardziej realistyczny wskaźnik.</p>
<h3>Dotacje 2026</h3>
<p>Program Czyste Powietrze: dotacja do 36 000 zł. Ulga termomodernizacyjna: odliczenie do 53 000 zł od podatku.</p>" \
  "energia"

create_post "Ile kosztuje wymiana okien — porównanie cen 2026" \
  "ile-kosztuje-wymiana-okien-2026" \
  "<h2>Wymiana okien — koszty i porównanie</h2>
<p>Wymiana okien to inwestycja, która obniża rachunki za ogrzewanie i poprawia komfort akustyczny. Średni koszt wymiany okien w mieszkaniu 50 m² wynosi od 8000 do 20000 zł.</p>
<h3>Ceny okien (za sztukę, montaż wliczony)</h3>
<ul>
<li>Okno PVC 2-szybowe: 800-1500 zł</li>
<li>Okno PVC 3-szybowe: 1200-2200 zł</li>
<li>Okno drewniane: 1500-3500 zł</li>
<li>Okno aluminiowe: 2000-4000 zł</li>
</ul>
<h3>Parametry do porównania</h3>
<p>Współczynnik Uw (im niższy, tym lepiej — szukaj max. 0,9 W/m²K), izolacyjność akustyczna Rw (min. 35 dB), klasa szczelności.</p>" \
  "budownictwo"

echo ""

# =============================================================================
# 8. SAMPLE CALCULATORS & COMPARISONS
# =============================================================================
echo "==> [8/9] Creating sample calculators, rankings and comparisons"

create_page "Kalkulator kosztów remontu łazienki" "kalkulator-remont-lazienki" "page-poradnik-pro-kalkulator.php" \
  "<p>Oblicz orientacyjny koszt remontu łazienki. Podaj metraż, standard wykończenia i zakres prac, a kalkulator poda szacunkowy koszt.</p>"

create_page "Kalkulator rat kredytu hipotecznego" "kalkulator-kredyt-hipoteczny" "page-poradnik-pro-kalkulator.php" \
  "<p>Oblicz miesięczną ratę kredytu hipotecznego. Podaj kwotę, okres kredytowania i oprocentowanie.</p>"

create_page "Kalkulator kosztów budowy domu" "kalkulator-budowa-domu" "page-poradnik-pro-kalkulator.php" \
  "<p>Szacunkowy koszt budowy domu. Podaj powierzchnię, technologię i standard, a kalkulator poda orientacyjny budżet.</p>"

create_page "Kalkulator fotowoltaiki — dobór mocy" "kalkulator-fotowoltaika" "page-poradnik-pro-kalkulator.php" \
  "<p>Dobierz moc instalacji fotowoltaicznej do swojego zużycia energii. Oblicz czas zwrotu inwestycji i roczne oszczędności.</p>"

create_page "Kalkulator kosztów ogrzewania — porównanie" "kalkulator-ogrzewanie" "page-poradnik-pro-kalkulator.php" \
  "<p>Porównaj koszty ogrzewania gazem, pompą ciepła, peletem i prądem. Oblicz miesięczne rachunki dla swojego domu.</p>"

# Rankings
create_page "Ranking kredytów hipotecznych 2026" "ranking-kredyty-hipoteczne" "page-poradnik-pro-ranking.php" \
  "<p>Najlepsze kredyty hipoteczne w 2026 roku. Porównanie oprocentowania, marż i warunków w największych bankach.</p>"

create_page "Ranking ubezpieczeń OC 2026" "ranking-ubezpieczenia-oc" "page-poradnik-pro-ranking.php" \
  "<p>Ranking najtańszych i najlepszych ubezpieczeń OC. Porównanie towarzystw, zakresów i cen.</p>"

create_page "Ranking pomp ciepła 2026" "ranking-pompy-ciepla" "page-poradnik-pro-ranking.php" \
  "<p>Najlepsze pompy ciepła dostępne na rynku polskim. Porównanie COP, cen, gwarancji i opinii użytkowników.</p>"

# Comparisons
create_page "Gaz vs pompa ciepła — co się bardziej opłaca" "porownanie-gaz-vs-pompa-ciepla" "page-poradnik-pro-porownanie.php" \
  "<p>Szczegółowe porównanie kosztów ogrzewania gazem ziemnym i pompą ciepła. Która opcja jest tańsza w eksploatacji?</p>"

create_page "OC Warta vs PZU — porównanie ubezpieczeń" "porownanie-oc-warta-vs-pzu" "page-poradnik-pro-porownanie.php" \
  "<p>Porównanie polis OC dwóch największych ubezpieczycieli w Polsce. Ceny, zakresy, likwidacja szkód.</p>"

create_page "Fotowoltaika vs pompa ciepła — co kupić pierwsze" "porownanie-fotowoltaika-vs-pompa-ciepla" "page-poradnik-pro-porownanie.php" \
  "<p>Co zamontować jako pierwsze — fotowoltaikę czy pompę ciepła? Analiza kosztów, oszczędności i synergii obu rozwiązań.</p>"

echo ""

# =============================================================================
# 9. FINALIZE — Set homepage + flush
# =============================================================================
echo "==> [9/9] Finalizing — setting homepage and flushing cache"

# Set homepage
HOME_ID=$($WP post list --post_type=page --post_name='home' --field=ID --format=ids 2>/dev/null || true)
if [[ -n "$HOME_ID" ]]; then
  $WP option update show_on_front 'page' 2>/dev/null || true
  $WP option update page_on_front "$HOME_ID" 2>/dev/null || true
  echo "  Front page set to: Poradnik.PRO — Strona Główna (ID $HOME_ID)"
fi

# Flush
$WP cache flush 2>/dev/null || true
$WP rewrite flush --hard 2>/dev/null || true
echo "  Cache and rewrite rules flushed"

echo ""
echo "=============================================="
echo " SUMMARY"
echo "=============================================="
echo "Pages published : $($WP post list --post_type=page --post_status=publish --format=count 2>/dev/null || echo unknown)"
echo "Posts published : $($WP post list --post_type=post --post_status=publish --format=count 2>/dev/null || echo unknown)"
echo "Categories      : $($WP term list category --format=count 2>/dev/null || echo unknown)"
echo "Site URL        : $($WP option get siteurl 2>/dev/null || echo unknown)"
echo "=============================================="
echo ""
echo "✅ Full content seeding complete!"
echo ""
echo "Pages created:"
echo "  - Poradnik.PRO: Homepage, Blog, Poradniki, Porównania, Rankingi,"
echo "    Kalkulatory, Pytania, Specjaliści, AI Doradca, Dla Specjalistów,"
echo "    Cennik, FAQ, Kontakt, Panel, Eksperci"
echo "  - Category landings: 10 kategorii"
echo "  - City pages: 10 miast (specjaliści)"
echo "  - PT24.PRO: Dla Fachowców, Dodaj Zlecenie, Usługi, Panel Fachowca"
echo "  - PT24 services: 15 kategorii usług"
echo "  - PT24 cities: 12 miast"
echo "  - Kalkulatory: 5 kalkulatorów"
echo "  - Rankingi: 3 rankingi"
echo "  - Porównania: 3 porównania"
echo "  - Artykuły: 8 poradników z treścią"
echo ""
echo "Total: ~80 pages + 8 articles + 12 categories"
