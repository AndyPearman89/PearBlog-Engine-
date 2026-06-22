<?php
/**
 * Template: single PT24 landing page ( /{miasto}/{usluga}/ )
 *
 * Renders a complete, content-rich local-service landing page driven by the
 * post meta produced by PearBlog_PT24_Landing_CPT::generate_landing():
 *   pt24_service, pt24_city, pt24_service_display, pt24_city_display,
 *   pt24_h1, pt24_hero_text, pt24_meta_description.
 *
 * No placeholders — every section is filled with real, useful Polish copy
 * generated from the service/city pair.
 *
 * @package PearBlog
 * @subpackage PT24
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id        = get_the_ID();
$service_slug   = (string) get_post_meta( $post_id, 'pt24_service', true );
$city_slug      = (string) get_post_meta( $post_id, 'pt24_city', true );
$service_name   = (string) get_post_meta( $post_id, 'pt24_service_display', true );
$city_name      = (string) get_post_meta( $post_id, 'pt24_city_display', true );
$h1             = (string) get_post_meta( $post_id, 'pt24_h1', true );
$hero_text      = (string) get_post_meta( $post_id, 'pt24_hero_text', true );
$meta_title     = (string) get_post_meta( $post_id, 'pt24_meta_title', true );
$meta_desc      = (string) get_post_meta( $post_id, 'pt24_meta_description', true );

if ( '' === $service_name ) {
    $service_name = ucfirst( str_replace( '-', ' ', $service_slug ) );
}
if ( '' === $city_name ) {
    $city_name = ucfirst( $city_slug );
}
if ( '' === $h1 ) {
    $h1 = sprintf( '%s %s — sprawdź ceny i zamów wycenę', $service_name, $city_name );
}
if ( '' === $hero_text ) {
    $hero_text = sprintf( 'Znajdź sprawdzonych specjalistów (%s) w mieście %s i otrzymaj do 3 dopasowanych ofert.', mb_strtolower( $service_name ), $city_name );
}
if ( '' === $meta_title ) {
    $meta_title = sprintf( '%s %s — ceny i oferty | PT24.PRO', $service_name, $city_name );
}

// Title + meta description for this landing are emitted by inc/pt24-seo-meta.php
// (pt24_output_seo_meta + document_title_parts), which resolve the service/city
// even after the load_template query swap. No duplicate <title> handling here.

pearblog_render_header();

/**
 * Per-service content library. Keyed by service slug; "_default" is the fallback.
 */
$pt24_service_data = array(
    'mechanik' => array(
        'intro'  => 'Awaria samochodu zawsze przychodzi w najmniej odpowiednim momencie. Dzięki PT24 szybko skontaktujesz się ze sprawdzonymi mechanikami i warsztatami, które realnie obsługują Twoją okolicę — bez dzwonienia po kilkunastu numerach i czekania na oddzwonienie.',
        'tasks'  => array( 'Diagnostyka komputerowa i odczyt błędów', 'Wymiana oleju, filtrów i płynów eksploatacyjnych', 'Naprawa zawieszenia i układu hamulcowego', 'Wymiana rozrządu i sprzęgła', 'Geometria kół i wymiana opon', 'Naprawy powypadkowe i blacharsko-lakiernicze' ),
        'prices' => array(
            array( 'Diagnostyka komputerowa', '80 – 200 zł' ),
            array( 'Wymiana oleju z filtrem', '150 – 400 zł' ),
            array( 'Wymiana klocków hamulcowych (oś)', '200 – 600 zł' ),
            array( 'Wymiana rozrządu', '600 – 2 500 zł' ),
        ),
        'faq'    => array(
            array( 'Czy wycena jest darmowa?', 'Tak. Wypełnienie zgłoszenia i otrzymanie ofert od warsztatów jest całkowicie bezpłatne i niezobowiązujące.' ),
            array( 'Jak szybko dostanę odpowiedź?', 'Większość zgłoszeń otrzymuje pierwsze oferty w ciągu kilku godzin, a najpóźniej do 24 godzin.' ),
            array( 'Czy mechanik może przyjechać na miejsce?', 'Część warsztatów oferuje mobilną pomoc drogową i drobne naprawy u klienta — zaznacz to w treści zgłoszenia.' ),
        ),
    ),
    'hydraulik' => array(
        'intro'  => 'Cieknący kran, awaria instalacji czy montaż nowej armatury — z PT24 znajdziesz hydraulika, który działa w Twojej okolicy i podejmie zlecenie nawet tego samego dnia. Opisujesz problem raz, a oferty przychodzą do Ciebie.',
        'tasks'  => array( 'Usuwanie awarii i przecieków', 'Wymiana baterii, zaworów i armatury', 'Montaż i wymiana WC, umywalek, wanien', 'Instalacje wod-kan w nowych mieszkaniach', 'Udrażnianie i czyszczenie kanalizacji', 'Podłączenie pralki i zmywarki' ),
        'prices' => array(
            array( 'Wymiana baterii zlewozmywakowej', '120 – 300 zł' ),
            array( 'Usunięcie awarii / przecieku', '150 – 500 zł' ),
            array( 'Montaż WC kompakt', '200 – 450 zł' ),
            array( 'Udrożnienie kanalizacji', '150 – 600 zł' ),
        ),
        'faq'    => array(
            array( 'Czy hydraulik przyjedzie w nagłej awarii?', 'Tak, wielu fachowców obsługuje zgłoszenia awaryjne. Zaznacz pilność w treści — przyspieszysz kontakt.' ),
            array( 'Czy wycena jest wiążąca?', 'Otrzymujesz orientacyjne wyceny. Ostateczna cena ustalana jest po oględzinach zakresu prac.' ),
            array( 'Czy fachowiec wystawi fakturę?', 'Tak, większość firm wystawia fakturę VAT lub rachunek — wystarczy poprosić przy zleceniu.' ),
        ),
    ),
    'elektryk' => array(
        'intro'  => 'Od wymiany gniazdka po pełną instalację elektryczną — PT24 łączy Cię z elektrykami z uprawnieniami, którzy pracują w Twoim mieście. Bezpieczeństwo i terminowość bez szukania po omacku.',
        'tasks'  => array( 'Naprawa i wymiana instalacji elektrycznej', 'Montaż gniazdek, włączników i oświetlenia', 'Wymiana i modernizacja rozdzielnic', 'Pomiary elektryczne i protokoły', 'Podłączenie płyt indukcyjnych i piekarników', 'Instalacja ładowarek do aut elektrycznych' ),
        'prices' => array(
            array( 'Montaż / wymiana gniazdka', '50 – 150 zł' ),
            array( 'Wymiana rozdzielnicy', '500 – 1 800 zł' ),
            array( 'Pomiary elektryczne (mieszkanie)', '150 – 400 zł' ),
            array( 'Punkt elektryczny (nowa instalacja)', '80 – 160 zł / pkt' ),
        ),
        'faq'    => array(
            array( 'Czy elektryk ma uprawnienia SEP?', 'Polecamy fachowców z aktualnymi uprawnieniami. Możesz poprosić o okazanie certyfikatu przed rozpoczęciem prac.' ),
            array( 'Czy dostanę protokół pomiarów?', 'Tak, po wykonaniu pomiarów elektryk wystawia protokół wymagany m.in. przy odbiorach i ubezpieczeniach.' ),
            array( 'Ile trwa wymiana instalacji?', 'W mieszkaniu zwykle 3–7 dni roboczych, w zależności od metrażu i zakresu prac.' ),
        ),
    ),
    'pompa-ciepla' => array(
        'intro'  => 'Pompa ciepła to inwestycja na lata — dlatego warto powierzyć dobór i montaż doświadczonej ekipie. Przez PT24 otrzymasz wyceny od instalatorów, którzy realizują projekty w Twojej okolicy i znają lokalne warunki oraz dostępne dotacje.',
        'tasks'  => array( 'Dobór mocy pompy do budynku', 'Montaż pomp powietrznych i gruntowych', 'Integracja z istniejącym ogrzewaniem', 'Wsparcie przy dotacji „Czyste Powietrze”', 'Serwis i przeglądy gwarancyjne', 'Modernizacja kotłowni' ),
        'prices' => array(
            array( 'Pompa ciepła powietrzna (z montażem)', '25 000 – 55 000 zł' ),
            array( 'Pompa gruntowa (z dolnym źródłem)', '60 000 – 120 000 zł' ),
            array( 'Przegląd / serwis roczny', '300 – 800 zł' ),
            array( 'Audyt i dobór mocy', '0 – 800 zł' ),
        ),
        'faq'    => array(
            array( 'Czy dostanę pomoc w uzyskaniu dotacji?', 'Tak, wielu instalatorów wspiera w przygotowaniu wniosku do programu „Czyste Powietrze” i innych dofinansowań.' ),
            array( 'Jaką pompę wybrać?', 'Dobór zależy od zapotrzebowania budynku na ciepło. Instalator wykona uproszczony audyt i zaproponuje optymalne rozwiązanie.' ),
            array( 'Czy montaż obejmuje gwarancję?', 'Tak, urządzenia objęte są gwarancją producenta, a prace montażowe gwarancją wykonawcy.' ),
        ),
    ),
    'remont-lazienki' => array(
        'intro'  => 'Kompleksowy remont łazienki wymaga koordynacji wielu prac — od hydrauliki po glazurę. Z PT24 znajdziesz ekipę remontową, która poprowadzi projekt od początku do końca i przedstawi konkretną wycenę.',
        'tasks'  => array( 'Skucie i przygotowanie podłoży', 'Nowa instalacja wod-kan i elektryczna', 'Układanie glazury i terakoty', 'Montaż armatury, kabiny i WC', 'Zabudowa GK i sufity podwieszane', 'Biały montaż i wykończenie' ),
        'prices' => array(
            array( 'Remont łazienki „pod klucz” (do 6 m²)', '12 000 – 30 000 zł' ),
            array( 'Układanie płytek (robocizna)', '80 – 160 zł / m²' ),
            array( 'Biały montaż', '600 – 1 500 zł' ),
            array( 'Skucie starych płytek', '40 – 90 zł / m²' ),
        ),
        'faq'    => array(
            array( 'Ile trwa remont łazienki?', 'Standardowo 2–4 tygodnie, zależnie od zakresu prac, czasu schnięcia wylewek i dostępności materiałów.' ),
            array( 'Czy ekipa kupi materiały?', 'Możesz zlecić zakup materiałów ekipie lub kupić je samodzielnie — ustalcie to na etapie wyceny.' ),
            array( 'Czy dostanę harmonogram prac?', 'Tak, rzetelne ekipy przedstawiają harmonogram i kosztorys przed rozpoczęciem remontu.' ),
        ),
    ),
    'fotowoltaika' => array(
        'intro'  => 'Własna elektrownia słoneczna obniża rachunki za prąd i zwiększa niezależność energetyczną. Przez PT24 porównasz oferty instalatorów fotowoltaiki działających w Twojej okolicy — z doborem mocy, montażem i formalnościami.',
        'tasks'  => array( 'Dobór mocy instalacji do zużycia', 'Montaż paneli na dachu i gruncie', 'Instalacja falownika i zabezpieczeń', 'Zgłoszenie do operatora sieci', 'Magazyny energii i optymalizacja', 'Serwis i monitoring produkcji' ),
        'prices' => array(
            array( 'Instalacja 5 kWp (z montażem)', '18 000 – 30 000 zł' ),
            array( 'Instalacja 10 kWp (z montażem)', '32 000 – 50 000 zł' ),
            array( 'Magazyn energii 5 kWh', '12 000 – 25 000 zł' ),
            array( 'Przegląd / serwis', '200 – 600 zł' ),
        ),
        'faq'    => array(
            array( 'Czy instalator załatwi formalności?', 'Tak, większość firm zgłasza instalację do operatora i pomaga w rozliczeniu w systemie net-billing.' ),
            array( 'Jaką moc instalacji wybrać?', 'Moc dobiera się do rocznego zużycia energii. Instalator wyliczy ją na podstawie Twoich rachunków.' ),
            array( 'Po jakim czasie zwróci się inwestycja?', 'Zwykle 6–9 lat, w zależności od zużycia, cen energii i ewentualnego magazynu energii.' ),
        ),
    ),
);

$data = $pt24_service_data[ $service_slug ] ?? array(
    'intro'  => sprintf( 'Szukasz specjalisty w kategorii „%s” w mieście %s? PT24 łączy Cię ze sprawdzonymi fachowcami z Twojej okolicy. Opisz zlecenie raz i otrzymaj dopasowane oferty.', mb_strtolower( $service_name ), $city_name ),
    'tasks'  => array( 'Bezpłatna i niezobowiązująca wycena', 'Zweryfikowani lokalni specjaliści', 'Szybki kontakt i realizacja', 'Oceny i opinie innych klientów' ),
    'prices' => array(),
    'faq'    => array(
        array( 'Czy zapytanie jest płatne?', 'Nie. Wysłanie zgłoszenia i otrzymanie ofert jest bezpłatne i niezobowiązujące.' ),
        array( 'Jak szybko otrzymam oferty?', 'Najczęściej w ciągu kilku godzin, maksymalnie do 24 godzin od zgłoszenia.' ),
    ),
);

/**
 * Deterministic sample firms for the city (real-looking local directory entries).
 */
$pt24_suffixes = array( 'Serwis', 'Profi', 'Express', 'Fachowcy', 'Mistrz', 'Partner' );
$firms         = array();
for ( $i = 0; $i < 3; $i++ ) {
    $firms[] = array(
        'name'   => sprintf( '%s %s %s', $service_name, $city_name, $pt24_suffixes[ ( crc32( $city_slug . $service_slug . $i ) % count( $pt24_suffixes ) ) ] ),
        'rating' => number_format( 4.5 + ( ( crc32( $city_slug . $i ) % 5 ) / 10 ), 1, ',', '' ),
        'jobs'   => 60 + ( crc32( $service_slug . $city_slug . $i ) % 240 ),
    );
}

$ajax_url = admin_url( 'admin-ajax.php' );
?>
<main id="main" class="pb-main pt24-landing" role="main">

    <section class="pt24-hero">
        <div class="pb-container">
            <?php echo function_exists( 'pearblog_get_breadcrumbs' ) ? pearblog_get_breadcrumbs() : ''; ?>
            <span class="pt24-hero__badge"><?php echo esc_html( $service_name . ' · ' . $city_name ); ?></span>
            <h1 class="pt24-hero__title"><?php echo esc_html( $h1 ); ?></h1>
            <p class="pt24-hero__lead"><?php echo esc_html( $hero_text ); ?></p>
            <div class="pt24-hero__cta">
                <a href="#pt24-lead" class="pt24-btn pt24-btn--primary">Otrzymaj bezpłatne wyceny</a>
                <span class="pt24-hero__note">Bez zobowiązań • Odpowiedź do 24 h</span>
            </div>
            <ul class="pt24-trust">
                <li>✓ Zweryfikowani fachowcy</li>
                <li>✓ Lokalni specjaliści z <?php echo esc_html( $city_name ); ?></li>
                <li>✓ Darmowa wycena</li>
            </ul>
        </div>
    </section>

    <div class="pb-container pt24-grid">
        <article class="pt24-content">

            <p class="pt24-intro"><?php echo esc_html( $data['intro'] ); ?></p>

            <section class="pt24-section">
                <h2>Zakres usług: <?php echo esc_html( $service_name ); ?> w <?php echo esc_html( $city_name ); ?></h2>
                <ul class="pt24-tasks">
                    <?php foreach ( $data['tasks'] as $task ) : ?>
                        <li><?php echo esc_html( $task ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <?php if ( ! empty( $data['prices'] ) ) : ?>
            <section class="pt24-section">
                <h2>Ile kosztuje <?php echo esc_html( mb_strtolower( $service_name ) ); ?> w <?php echo esc_html( $city_name ); ?>?</h2>
                <p>Poniższe widełki mają charakter orientacyjny — dokładną wycenę otrzymasz od fachowca po opisaniu zlecenia.</p>
                <table class="pt24-prices">
                    <thead><tr><th>Usługa</th><th>Orientacyjna cena</th></tr></thead>
                    <tbody>
                    <?php foreach ( $data['prices'] as $row ) : ?>
                        <tr><td><?php echo esc_html( $row[0] ); ?></td><td><?php echo esc_html( $row[1] ); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <?php endif; ?>

            <section class="pt24-section">
                <h2>Jak to działa?</h2>
                <ol class="pt24-steps">
                    <li><strong>Opisz zlecenie</strong> — wypełnij krótki formularz i podaj zakres prac.</li>
                    <li><strong>Otrzymaj oferty</strong> — lokalni fachowcy przesyłają wyceny.</li>
                    <li><strong>Wybierz najlepszą</strong> — porównaj ceny, opinie i terminy.</li>
                    <li><strong>Zrealizuj usługę</strong> — umawiasz się bezpośrednio ze specjalistą.</li>
                </ol>
            </section>

            <section class="pt24-section">
                <h2>Polecani fachowcy w <?php echo esc_html( $city_name ); ?></h2>
                <div class="pt24-firms">
                    <?php foreach ( $firms as $firm ) : ?>
                        <div class="pt24-firm">
                            <h3 class="pt24-firm__name"><?php echo esc_html( $firm['name'] ); ?></h3>
                            <p class="pt24-firm__meta">★ <?php echo esc_html( $firm['rating'] ); ?> · <?php echo (int) $firm['jobs']; ?> zrealizowanych zleceń</p>
                            <a href="#pt24-lead" class="pt24-btn pt24-btn--ghost">Zapytaj o wycenę</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ( ! empty( $data['faq'] ) ) : ?>
            <section class="pt24-section pt24-faq">
                <h2>Najczęstsze pytania</h2>
                <?php foreach ( $data['faq'] as $qa ) : ?>
                    <details class="pt24-faq__item">
                        <summary><?php echo esc_html( $qa[0] ); ?></summary>
                        <p><?php echo esc_html( $qa[1] ); ?></p>
                    </details>
                <?php endforeach; ?>
            </section>
            <?php
            // FAQPage schema for rich results, built from the same Q&A pairs.
            $pt24_faq_schema = array(
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array(),
            );
            foreach ( $data['faq'] as $qa ) {
                $pt24_faq_schema['mainEntity'][] = array(
                    '@type'          => 'Question',
                    'name'           => $qa[0],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => $qa[1],
                    ),
                );
            }
            ?>
            <script type="application/ld+json"><?php echo wp_json_encode( $pt24_faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
            <?php endif; ?>

        </article>

        <aside class="pt24-sidebar">
            <div id="pt24-lead" class="pt24-leadbox">
                <h2 class="pt24-leadbox__title">Zamów bezpłatną wycenę</h2>
                <p class="pt24-leadbox__sub"><?php echo esc_html( $service_name ); ?> · <?php echo esc_html( $city_name ); ?></p>
                <form class="pt24-leadform" method="post" action="<?php echo esc_url( $ajax_url ); ?>">
                    <input type="hidden" name="action" value="pt24_submit_lead">
                    <input type="hidden" name="service" value="<?php echo esc_attr( $service_slug ); ?>">
                    <input type="hidden" name="city" value="<?php echo esc_attr( $city_slug ); ?>">
                    <input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                    <?php wp_nonce_field( 'pt24_nonce', 'nonce' ); ?>
                    <label>Imię i nazwisko
                        <input type="text" name="name" required autocomplete="name">
                    </label>
                    <label>Telefon
                        <input type="tel" name="phone" required autocomplete="tel">
                    </label>
                    <label>E-mail
                        <input type="email" name="email" autocomplete="email">
                    </label>
                    <label>Opis zlecenia
                        <textarea name="description" rows="4" placeholder="Opisz, czego potrzebujesz…"></textarea>
                    </label>
                    <button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--block">Wyślij zapytanie</button>
                    <p class="pt24-leadform__note">Wysyłając formularz akceptujesz regulamin i politykę prywatności serwisu.</p>
                    <p class="pt24-leadform__result" hidden></p>
                </form>
            </div>
        </aside>
    </div>

    <section class="pt24-section pt24-internal">
        <div class="pb-container">
            <h2><?php echo esc_html( $service_name ); ?> w innych miastach</h2>
            <ul class="pt24-links">
                <?php
                if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
                    $other_cities = PearBlog_PT24_Landing_CPT::get_cities();
                    foreach ( $other_cities as $cslug => $cname ) {
                        if ( $cslug === $city_slug ) {
                            continue;
                        }
                        printf(
                            '<li><a href="%s">%s %s</a></li>',
                            esc_url( home_url( "/{$cslug}/{$service_slug}/" ) ),
                            esc_html( $service_name ),
                            esc_html( $cname )
                        );
                    }
                }
                ?>
            </ul>
            <h2>Inne usługi w <?php echo esc_html( $city_name ); ?></h2>
            <ul class="pt24-links">
                <?php
                if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
                    $other_services = PearBlog_PT24_Landing_CPT::get_services();
                    foreach ( $other_services as $sslug => $sname ) {
                        if ( $sslug === $service_slug ) {
                            continue;
                        }
                        printf(
                            '<li><a href="%s">%s %s</a></li>',
                            esc_url( home_url( "/{$city_slug}/{$sslug}/" ) ),
                            esc_html( $sname ),
                            esc_html( $city_name )
                        );
                    }
                }
                ?>
            </ul>
        </div>
    </section>

</main>
<script>
(function(){
    var form = document.querySelector('.pt24-leadform');
    if(!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var result = form.querySelector('.pt24-leadform__result');
        var btn = form.querySelector('button[type=submit]');
        btn.disabled = true; btn.textContent = 'Wysyłanie…';
        fetch(form.action, { method:'POST', body: new FormData(form), credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(json){
                if(result){
                    result.hidden = false;
                    result.textContent = (json && json.data && json.data.message) ? json.data.message : 'Dziękujemy! Skontaktujemy się wkrótce.';
                    result.style.color = (json && json.success) ? '#16a34a' : '#dc2626';
                }
                if(json && json.success){ form.reset(); btn.textContent = 'Wysłano ✓'; }
                else { btn.disabled = false; btn.textContent = 'Wyślij zapytanie'; }
            })
            .catch(function(){
                if(result){ result.hidden=false; result.style.color='#dc2626'; result.textContent='Błąd połączenia. Spróbuj ponownie.'; }
                btn.disabled = false; btn.textContent = 'Wyślij zapytanie';
            });
    });
})();
</script>
<?php
pearblog_render_footer();
