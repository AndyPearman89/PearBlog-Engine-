<?php
/**
 * Template Name: Poradnik.PRO - FAQ
 *
 * FAQ page for Poradnik.pro with categorized questions and accordion-style answers.
 * Uses the shared purple Inter-based design system (no Tailwind).
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php pp_pro_shared_styles(); ?>
    <style>
        /* ===== FAQ PAGE HERO ===== */
        .faq-hero {
            background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
            padding: 56px 0;
        }
        .faq-hero h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .faq-hero .subtitle {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 600px;
        }

        /* ===== CATEGORY TABS ===== */
        .faq-section {
            padding: 64px 0;
        }
        .faq-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .faq-tab {
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .faq-tab:hover {
            background: #f3e8ff;
            color: var(--purple-primary);
            border-color: var(--purple-light);
        }
        .faq-tab.active {
            background: var(--purple-primary);
            color: #fff;
            border-color: var(--purple-primary);
        }

        /* ===== FAQ CATEGORY ===== */
        .faq-category {
            margin-bottom: 48px;
        }
        .faq-category-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .faq-category-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .faq-category-icon--ogolne { background: #e0e7ff; }
        .faq-category-icon--uzytkownicy { background: #dcfce7; }
        .faq-category-icon--specjalisci { background: #f3e8ff; }
        .faq-category-icon--rozliczenia { background: #fef3c7; }

        /* ===== FAQ ACCORDION ===== */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .faq-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .faq-item:hover {
            border-color: var(--purple-light);
            box-shadow: var(--shadow-sm);
        }
        .faq-item[open] {
            border-color: var(--purple-primary);
            box-shadow: var(--shadow-md);
        }
        .faq-item summary {
            padding: 20px 24px;
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            list-style: none;
            transition: background 0.2s;
        }
        .faq-item summary::-webkit-details-marker {
            display: none;
        }
        .faq-item summary::after {
            content: '+';
            font-size: 20px;
            font-weight: 700;
            color: var(--purple-primary);
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .faq-item[open] summary::after {
            content: '\2212';
            transform: rotate(180deg);
        }
        .faq-item[open] summary {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        .faq-item .faq-answer {
            padding: 20px 24px;
            font-size: 14px;
            line-height: 1.7;
            color: var(--gray-600);
        }
        .faq-item .faq-answer p {
            margin-bottom: 12px;
        }
        .faq-item .faq-answer p:last-child {
            margin-bottom: 0;
        }
        .faq-item .faq-answer ul {
            margin: 12px 0;
            padding-left: 20px;
            list-style: disc;
        }
        .faq-item .faq-answer ul li {
            margin-bottom: 6px;
            color: var(--gray-600);
        }
        .faq-item .faq-answer strong {
            color: var(--gray-800);
        }

        /* ===== CTA SECTION ===== */
        .faq-cta {
            background: linear-gradient(135deg, var(--purple-primary), #4c1d95);
            border-radius: var(--radius-xl);
            padding: 56px 40px;
            text-align: center;
            color: #fff;
            margin-bottom: 64px;
        }
        .faq-cta h2 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .faq-cta p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 28px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .faq-cta-btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: var(--radius-sm);
            background: var(--orange-cta);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            transition: background 0.2s;
        }
        .faq-cta-btn:hover {
            background: var(--orange-hover);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .faq-hero {
                padding: 40px 0;
            }
            .faq-hero h1 {
                font-size: 26px;
            }
            .faq-section {
                padding: 40px 0;
            }
            .faq-tabs {
                gap: 6px;
            }
            .faq-tab {
                padding: 8px 14px;
                font-size: 13px;
            }
            .faq-item summary {
                padding: 16px 18px;
                font-size: 14px;
            }
            .faq-item .faq-answer {
                padding: 16px 18px;
                font-size: 13px;
            }
            .faq-cta {
                padding: 40px 24px;
                border-radius: var(--radius-lg);
            }
            .faq-cta h2 {
                font-size: 20px;
            }
            .faq-category-title {
                font-size: 18px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php pp_pro_header( '' ); ?>

<main>
    <!-- HERO -->
    <section class="faq-hero">
        <div class="container">
            <nav class="breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
                <span class="sep">/</span>
                <span>FAQ</span>
            </nav>
            <h1>Czesto zadawane pytania</h1>
            <p class="subtitle">Znajdz odpowiedzi na najczesciej zadawane pytania dotyczace platformy Poradnik.pro, korzystania z uslug specjalistow oraz rozliczen.</p>
        </div>
    </section>

    <!-- FAQ CONTENT -->
    <section class="faq-section">
        <div class="container">

            <!-- CATEGORY TABS -->
            <div class="faq-tabs">
                <a href="#ogolne" class="faq-tab active">Ogolne</a>
                <a href="#dla-uzytkownikow" class="faq-tab">Dla uzytkownikow</a>
                <a href="#dla-specjalistow" class="faq-tab">Dla specjalistow</a>
                <a href="#rozliczenia" class="faq-tab">Rozliczenia</a>
            </div>

            <!-- OGOLNE -->
            <div class="faq-category" id="ogolne">
                <h2 class="faq-category-title">
                    <span class="faq-category-icon faq-category-icon--ogolne">&#9881;</span>
                    Ogolne
                </h2>
                <div class="faq-list">

                    <details class="faq-item">
                        <summary>Czym jest Poradnik.pro?</summary>
                        <div class="faq-answer">
                            <p>Poradnik.pro to platforma laczaca osoby poszukujace fachowej wiedzy z zweryfikowanymi specjalistami z roznych dziedzin. Umozliwiamy szybkie uzyskanie odpowiedzi na pytania z zakresu prawa, finansow, nieruchomosci, budownictwa i wielu innych branz.</p>
                            <p>Dzieki systemowi ocen i weryfikacji ekspertow masz pewnosc, ze otrzymujesz rzetelne informacje od sprawdzonych profesjonalistow.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy korzystanie z platformy jest bezplatne?</summary>
                        <div class="faq-answer">
                            <p>Tak, dla uzytkownikow szukajacych informacji platforma jest calkowicie bezplatna. Mozesz przegladac poradniki, rankingi, porownania oraz zadawac pytania specjalistom bez zadnych oplat.</p>
                            <p>Specjalisci moga korzystac z darmowego planu FREE lub wybrac platne pakiety PREMIUM i PREMIUM+ oferujace dodatkowe funkcje i wieksza widocznosc.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Jak dziala system weryfikacji specjalistow?</summary>
                        <div class="faq-answer">
                            <p>Kazdy specjalista rejestrujacy sie na platformie przechodzi proces weryfikacji obejmujacy:</p>
                            <ul>
                                <li>Potwierdzenie tozsamosci na podstawie dokumentu</li>
                                <li>Weryfikacje uprawnien zawodowych i certyfikatow</li>
                                <li>Sprawdzenie doswiadczenia i historii zawodowej</li>
                                <li>Potwierdzenie aktywnego prowadzenia dzialalnosci</li>
                            </ul>
                            <p>Dodatkowo specjalisci sa oceniani przez uzytkownikow, co pozwala utrzymac wysoki standard uslug.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Na jakie tematy moge znalezc porady?</summary>
                        <div class="faq-answer">
                            <p>Platforma obejmuje szeroki zakres tematow, w tym:</p>
                            <ul>
                                <li><strong>Prawo</strong> - prawo cywilne, karne, pracy, rodzinne, podatkowe</li>
                                <li><strong>Finanse</strong> - kredyty, inwestycje, oszczedzanie, podatki</li>
                                <li><strong>Nieruchomosci</strong> - kupno, sprzedaz, wynajem, wycena</li>
                                <li><strong>Budownictwo</strong> - projekty, remonty, instalacje, pozwolenia</li>
                                <li><strong>Motoryzacja</strong> - zakup, ubezpieczenia, naprawa, przeglady</li>
                            </ul>
                            <p>Lista kategorii jest stale rozszerzana na podstawie potrzeb uzytkownikow.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy moge korzystac z platformy anonimowo?</summary>
                        <div class="faq-answer">
                            <p>Przegladanie tresci na platformie nie wymaga rejestracji ani logowania. Mozesz swobodnie czytac poradniki, rankingi i odpowiedzi na pytania.</p>
                            <p>Jesli chcesz zadac pytanie specjaliscie lub zostawic opinie, wymagane jest utworzenie konta. Twoje dane osobowe nie sa jednak widoczne publicznie - w komentarzach mozesz uzywac pseudonimu.</p>
                        </div>
                    </details>

                </div>
            </div>

            <!-- DLA UZYTKOWNIKOW -->
            <div class="faq-category" id="dla-uzytkownikow">
                <h2 class="faq-category-title">
                    <span class="faq-category-icon faq-category-icon--uzytkownicy">&#128100;</span>
                    Dla uzytkownikow
                </h2>
                <div class="faq-list">

                    <details class="faq-item">
                        <summary>Jak znalezc odpowiedniego specjaliste?</summary>
                        <div class="faq-answer">
                            <p>Mozesz wyszukiwac specjalistow na kilka sposobow:</p>
                            <ul>
                                <li>Wpisz interesujacy Cie temat w wyszukiwarke na stronie glownej</li>
                                <li>Przegladaj kategorie specjalistow w zakladce "Specjalisci"</li>
                                <li>Filtruj wyniki wedlug lokalizacji, ocen, doswiadczenia i specjalizacji</li>
                                <li>Sprawdz rankingi najlepszych ekspertow w danej dziedzinie</li>
                            </ul>
                            <p>Kazdy profil specjalisty zawiera informacje o kwalifikacjach, opinie uzytkownikow oraz liczbe udzielonych odpowiedzi.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Jak moge zadac pytanie specjaliscie?</summary>
                        <div class="faq-answer">
                            <p>Aby zadac pytanie, przejdz do sekcji "Pytania i Odpowiedzi" lub kliknij przycisk "Zadaj pytanie" na profilu wybranego specjalisty. Opisz szczegolowo swoja sytuacje - im wiecej informacji podasz, tym dokladniejsza odpowiedz otrzymasz.</p>
                            <p>Pytanie zostanie przypisane do odpowiedniej kategorii i wyswietlone specjalistom, ktorzy moga na nie odpowiedziec. Zazwyczaj pierwsza odpowiedz otrzymujesz w ciagu 24 godzin.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy moge zostawic opinie o specjaliscie?</summary>
                        <div class="faq-answer">
                            <p>Tak, po skorzystaniu z porady specjalisty mozesz wystawic mu ocene w skali 1-5 gwiazdek oraz napisac komentarz opisujacy Twoje doswiadczenia. Opinie pomagaja innym uzytkownikom w wyborze odpowiedniego eksperta.</p>
                            <p>System opinii jest moderowany - usuwamy tresci niezgodne z regulaminem, obelzywe lub niezwiazane z usluga. Nie mozna usunac negatywnej opinii, o ile jest merytoryczna i zgodna z zasadami.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Ile czasu trzeba czekac na odpowiedz?</summary>
                        <div class="faq-answer">
                            <p>Czas oczekiwania na odpowiedz zalezy od kategorii pytania i dostepnosci specjalistow. W wiekszosci przypadkow:</p>
                            <ul>
                                <li><strong>Pytania ogolne</strong> - odpowiedz w ciagu 2-6 godzin</li>
                                <li><strong>Pytania specjalistyczne</strong> - odpowiedz w ciagu 12-24 godzin</li>
                                <li><strong>Pytania niszowe</strong> - do 48 godzin</li>
                            </ul>
                            <p>Jesli pytanie nie otrzyma odpowiedzi w ciagu 48 godzin, skontaktujemy sie z Toba i pomozemy znalezc odpowiedniego specjaliste.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy odpowiedzi specjalistow zastepuja wizyte w kancelarii lub gabinecie?</summary>
                        <div class="faq-answer">
                            <p>Odpowiedzi udzielane na platformie maja charakter informacyjny i edukacyjny. Specjalisci dostarczaja ogolnych wskazowek i wyjasnien, ktore pomagaja zrozumiec dana kwestie.</p>
                            <p>W przypadku spraw wymagajacych indywidualnej analizy dokumentow, reprezentacji prawnej lub szczegolowej diagnostyki, zalecamy umowienie bezposredniej konsultacji ze specjalista. Wielu ekspertow na platformie oferuje rowniez konsultacje online.</p>
                        </div>
                    </details>

                </div>
            </div>

            <!-- DLA SPECJALISTOW -->
            <div class="faq-category" id="dla-specjalistow">
                <h2 class="faq-category-title">
                    <span class="faq-category-icon faq-category-icon--specjalisci">&#127891;</span>
                    Dla specjalistow
                </h2>
                <div class="faq-list">

                    <details class="faq-item">
                        <summary>Jak zarejestrowac sie jako specjalista?</summary>
                        <div class="faq-answer">
                            <p>Rejestracja jako specjalista sklada sie z kilku krokow:</p>
                            <ul>
                                <li>Utworz konto na stronie "Dla specjalistow" podajac podstawowe dane</li>
                                <li>Wypelnij formularz weryfikacji z informacjami o kwalifikacjach</li>
                                <li>Przeslij skany dokumentow potwierdzajacych uprawnienia</li>
                                <li>Poczekaj na weryfikacje (zazwyczaj 1-3 dni robocze)</li>
                                <li>Po zatwierdzeniu uzupelnij profil i zacznij odpowiadac na pytania</li>
                            </ul>
                            <p>Rejestracja jest bezplatna, a plan FREE pozwala przetestowac platforme bez zobowiazan.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Jak wyglada moj profil specjalisty?</summary>
                        <div class="faq-answer">
                            <p>Profil specjalisty zawiera nastepujace elementy:</p>
                            <ul>
                                <li>Zdjecie profilowe i krotki opis (bio)</li>
                                <li>Lista specjalizacji i kompetencji</li>
                                <li>Doswiadczenie zawodowe i certyfikaty</li>
                                <li>Ocena od uzytkownikow i liczba udzielonych odpowiedzi</li>
                                <li>Opublikowane artykuly i poradniki</li>
                                <li>Dane kontaktowe (opcjonalnie)</li>
                            </ul>
                            <p>Profil mozesz edytowac w dowolnym momencie z poziomu panelu specjalisty.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Jakie sa dostepne pakiety i ile kosztuja?</summary>
                        <div class="faq-answer">
                            <p>Oferujemy trzy pakiety dostosowane do roznych potrzeb:</p>
                            <ul>
                                <li><strong>FREE (0 zl)</strong> - profil specjalisty, 5 odpowiedzi miesiecznie, podstawowa widocznosc</li>
                                <li><strong>PREMIUM (149 zl/mies.)</strong> - nielimitowane odpowiedzi, pozycjonowanie profilu, Lead Engine do 50 leadow, statystyki zaawansowane</li>
                                <li><strong>PREMIUM+ (349 zl/mies.)</strong> - TOP pozycjonowanie, nielimitowany Lead Engine, dedykowany opiekun, priorytetowe wsparcie 24/7</li>
                            </ul>
                            <p>Przy rozliczeniu rocznym otrzymujesz 20% rabatu. Szczegolowe porownanie funkcji znajdziesz na stronie <a href="<?php echo esc_url( home_url( '/cennik/' ) ); ?>" style="color: var(--purple-primary); font-weight: 600;">Cennik</a>.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czym jest Lead Engine i jak dziala?</summary>
                        <div class="faq-answer">
                            <p>Lead Engine to autorski system pozyskiwania klientow, ktory automatycznie dopasowuje zapytania uzytkownikow do Twojego profilu i specjalizacji. Dzieki niemu otrzymujesz powiadomienia o potencjalnych klientach w Twojej okolicy i branzy.</p>
                            <p>System analizuje tresc pytan, lokalizacje uzytkownika oraz Twoje kompetencje, aby dostarczac najlepiej dopasowane leady. W pakiecie PREMIUM masz do 50 leadow miesiecznie, a w PREMIUM+ - bez limitu.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy moge zmienic lub zrezygnowac z pakietu w dowolnym momencie?</summary>
                        <div class="faq-answer">
                            <p>Tak, mozesz zmienic pakiet lub zrezygnowac z subskrypcji w kazdej chwili z poziomu panelu specjalisty. Zmiana pakietu na wyzszy nastepuje natychmiast, a roznica w cenie jest naliczana proporcjonalnie.</p>
                            <p>W przypadku rezygnacji lub przejscia na nizszy pakiet, zmiany wchodza w zycie na poczatku nastepnego okresu rozliczeniowego. Do tego czasu zachowujesz dostep do wszystkich funkcji obecnego pakietu.</p>
                        </div>
                    </details>

                </div>
            </div>

            <!-- ROZLICZENIA -->
            <div class="faq-category" id="rozliczenia">
                <h2 class="faq-category-title">
                    <span class="faq-category-icon faq-category-icon--rozliczenia">&#128176;</span>
                    Rozliczenia
                </h2>
                <div class="faq-list">

                    <details class="faq-item">
                        <summary>Jakie metody platnosci sa akceptowane?</summary>
                        <div class="faq-answer">
                            <p>Akceptujemy nastepujace metody platnosci:</p>
                            <ul>
                                <li><strong>Karty platnicze</strong> - Visa, Mastercard, American Express</li>
                                <li><strong>Przelewy online</strong> - Przelewy24, PayU, BLIK</li>
                                <li><strong>Przelew tradycyjny</strong> - na podstawie faktury pro-forma</li>
                                <li><strong>PayPal</strong> - dla klientow miedzynarodowych</li>
                            </ul>
                            <p>Platnosci sa przetwarzane przez certyfikowanego operatora platnosci, co zapewnia pelne bezpieczenstwo transakcji.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy otrzymam fakture VAT?</summary>
                        <div class="faq-answer">
                            <p>Tak, do kazdej platnosci wystawiamy fakture VAT. Faktury sa generowane automatycznie i dostepne do pobrania w formacie PDF w panelu specjalisty w zakladce "Rozliczenia".</p>
                            <p>Jesli potrzebujesz faktury wystawionej na firme, upewnij sie, ze dane do faktury (NIP, nazwa firmy, adres) sa poprawnie uzupelnione w ustawieniach konta. Faktura jest wystawiana do 15. dnia nastepnego miesiaca.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Jak anulowac subskrypcje?</summary>
                        <div class="faq-answer">
                            <p>Aby anulowac subskrypcje:</p>
                            <ul>
                                <li>Zaloguj sie do panelu specjalisty</li>
                                <li>Przejdz do zakladki "Ustawienia" &rarr; "Subskrypcja"</li>
                                <li>Kliknij "Anuluj subskrypcje" i potwierdz decyzje</li>
                            </ul>
                            <p>Po anulowaniu zachowujesz dostep do platnych funkcji do konca oplaconego okresu rozliczeniowego. Twoj profil nie zostanie usuniety - przejdziesz automatycznie na plan FREE.</p>
                            <p>Nie pobieramy zadnych oplat za anulowanie. Mozesz wrocic do pakietu platnego w kazdym momencie.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Czy moge otrzymac zwrot pieniedzy?</summary>
                        <div class="faq-answer">
                            <p>Oferujemy 14-dniowa gwarancje satysfakcji. Jesli w ciagu 14 dni od pierwszej platnosci stwierdzisz, ze platforma nie spelnia Twoich oczekiwan, zwrocimy pelna kwote bez zadawania pytan.</p>
                            <p>Po uplywie 14 dni zwroty sa rozpatrywane indywidualnie. W przypadku problemow technicznych uniemozliwiajacych korzystanie z platformy, skontaktuj sie z naszym zespolem wsparcia.</p>
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Co sie stanie, gdy platnosc nie zostanie przetworzona?</summary>
                        <div class="faq-answer">
                            <p>W przypadku nieudanej platnosci (np. brak srodkow na karcie, wygasla karta) system automatycznie podejmie 3 proby pobrania platnosci w odstepach 3-dniowych.</p>
                            <p>Przed kazda proba otrzymasz powiadomienie e-mail z prosba o zaktualizowanie danych platniczych. Jesli po trzech probach platnosc nadal nie zostanie przetworzona, subskrypcja zostanie automatycznie zawieszona, a Twoje konto przejdzie na plan FREE.</p>
                            <p>Nie tracisz zadnych danych - po uregulowaniu platnosci wszystkie funkcje zostana przywrocone.</p>
                        </div>
                    </details>

                </div>
            </div>

            <!-- CTA SECTION -->
            <div class="faq-cta">
                <h2>Nie znalazles odpowiedzi?</h2>
                <p>Nasz zespol chetnie pomoze. Skontaktuj sie z nami, a odpowiemy na Twoje pytanie najszybciej jak to mozliwe.</p>
                <a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="faq-cta-btn">Skontaktuj sie z nami</a>
            </div>

        </div>
    </section>
</main>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
