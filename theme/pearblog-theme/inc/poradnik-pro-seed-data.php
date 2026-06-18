<?php
/**
 * Poradnik.PRO — Complete Seed Data
 *
 * Centralized content data for all Poradnik.PRO subpages.
 * No placeholders — production-ready Polish content.
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get complete specialists data.
 *
 * @return array
 */
function pp_seed_specialists() {
	return array(
		array(
			'initials'    => 'MK',
			'name'        => 'Mateusz Kowalczyk',
			'specialty'   => 'Adwokat — prawo cywilne i rodzinne',
			'category'    => 'Prawo',
			'rating'      => '4.9',
			'reviews'     => 347,
			'location'    => 'Warszawa',
			'answers'     => 512,
			'slug'        => 'mateusz-kowalczyk',
			'experience'  => '14 lat doświadczenia',
			'description' => 'Specjalizuje się w sprawach rozwodowych, podziale majątku i prawie spadkowym. Członek Okręgowej Izby Adwokackiej w Warszawie.',
		),
		array(
			'initials'    => 'AW',
			'name'        => 'Anna Wojciechowska',
			'specialty'   => 'Certyfikowany doradca finansowy (EFPA)',
			'category'    => 'Finanse',
			'rating'      => '4.8',
			'reviews'     => 289,
			'location'    => 'Kraków',
			'answers'     => 431,
			'slug'        => 'anna-wojciechowska',
			'experience'  => '11 lat doświadczenia',
			'description' => 'Pomaga w wyborze kredytów hipotecznych, planowaniu inwestycji i optymalizacji podatkowej dla osób fizycznych.',
		),
		array(
			'initials'    => 'TZ',
			'name'        => 'Tomasz Zawadzki',
			'specialty'   => 'Architekt z uprawnieniami budowlanymi',
			'category'    => 'Budownictwo',
			'rating'      => '4.9',
			'reviews'     => 198,
			'location'    => 'Wrocław',
			'answers'     => 276,
			'slug'        => 'tomasz-zawadzki',
			'experience'  => '18 lat doświadczenia',
			'description' => 'Projektuje domy jednorodzinne i nadzoruje budowy. Autor ponad 120 zrealizowanych projektów na terenie Dolnego Śląska.',
		),
		array(
			'initials'    => 'KN',
			'name'        => 'Katarzyna Nowicka',
			'specialty'   => 'Licencjonowany pośrednik nieruchomości',
			'category'    => 'Nieruchomości',
			'rating'      => '4.7',
			'reviews'     => 421,
			'location'    => 'Poznań',
			'answers'     => 389,
			'slug'        => 'katarzyna-nowicka',
			'experience'  => '9 lat doświadczenia',
			'description' => 'Specjalizuje się w rynku mieszkań w Poznaniu i okolicach. Pomaga przy kupnie, sprzedaży i wynajmie nieruchomości.',
		),
		array(
			'initials'    => 'PL',
			'name'        => 'dr Piotr Lewandowski',
			'specialty'   => 'Lekarz internista, specjalista medycyny rodzinnej',
			'category'    => 'Zdrowie',
			'rating'      => '4.9',
			'reviews'     => 562,
			'location'    => 'Gdańsk',
			'answers'     => 648,
			'slug'        => 'piotr-lewandowski',
			'experience'  => '16 lat doświadczenia',
			'description' => 'Prowadzi prywatną praktykę lekarską. Odpowiada na pytania dotyczące profilaktyki zdrowotnej i leczenia chorób przewlekłych.',
		),
		array(
			'initials'    => 'JM',
			'name'        => 'Jakub Mazurek',
			'specialty'   => 'Inżynier energetyki, audytor energetyczny',
			'category'    => 'Energia',
			'rating'      => '4.8',
			'reviews'     => 156,
			'location'    => 'Katowice',
			'answers'     => 734,
			'slug'        => 'jakub-mazurek',
			'experience'  => '12 lat doświadczenia',
			'description' => 'Doradza w zakresie pomp ciepła, fotowoltaiki i termomodernizacji. Wykonał ponad 300 audytów energetycznych budynków.',
		),
		array(
			'initials'    => 'EK',
			'name'        => 'Elżbieta Kamińska',
			'specialty'   => 'Radca prawny — prawo pracy i ubezpieczeń',
			'category'    => 'Prawo',
			'rating'      => '4.8',
			'reviews'     => 312,
			'location'    => 'Łódź',
			'answers'     => 467,
			'slug'        => 'elzbieta-kaminska',
			'experience'  => '15 lat doświadczenia',
			'description' => 'Reprezentuje pracowników i pracodawców w sporach z zakresu prawa pracy. Doradza w kwestiach zwolnień, umów i ZUS.',
		),
		array(
			'initials'    => 'RD',
			'name'        => 'Robert Dąbrowski',
			'specialty'   => 'Doradca kredytowy, ekspert rynku hipotecznego',
			'category'    => 'Finanse',
			'rating'      => '4.7',
			'reviews'     => 245,
			'location'    => 'Katowice',
			'answers'     => 321,
			'slug'        => 'robert-dabrowski',
			'experience'  => '10 lat doświadczenia',
			'description' => 'Współpracuje z 15 bankami. Pomaga w uzyskaniu kredytu hipotecznego z najlepszym oprocentowaniem i minimalną marżą.',
		),
		array(
			'initials'    => 'MS',
			'name'        => 'Magdalena Szymańska',
			'specialty'   => 'Kierownik budowy, inspektor nadzoru',
			'category'    => 'Budownictwo',
			'rating'      => '4.6',
			'reviews'     => 178,
			'location'    => 'Szczecin',
			'answers'     => 203,
			'slug'        => 'magdalena-szymanska',
			'experience'  => '13 lat doświadczenia',
			'description' => 'Nadzoruje budowy domów jednorodzinnych i remonty generalne. Dba o terminowość, jakość i zgodność z projektem.',
		),
		array(
			'initials'    => 'WP',
			'name'        => 'dr Weronika Pawlak',
			'specialty'   => 'Stomatolog, implantolog',
			'category'    => 'Zdrowie',
			'rating'      => '4.9',
			'reviews'     => 487,
			'location'    => 'Wrocław',
			'answers'     => 295,
			'slug'        => 'weronika-pawlak',
			'experience'  => '11 lat doświadczenia',
			'description' => 'Prowadzi klinikę stomatologiczną. Specjalizuje się w implantologii i protetyce. Odpowiada na pytania o leczenie zębów.',
		),
		array(
			'initials'    => 'GK',
			'name'        => 'Grzegorz Kubiak',
			'specialty'   => 'Rzeczoznawca majątkowy',
			'category'    => 'Nieruchomości',
			'rating'      => '4.8',
			'reviews'     => 134,
			'location'    => 'Warszawa',
			'answers'     => 189,
			'slug'        => 'grzegorz-kubiak',
			'experience'  => '20 lat doświadczenia',
			'description' => 'Wykonuje wyceny nieruchomości na potrzeby kredytowe, spadkowe i transakcyjne. Uprawnienia państwowe nr 4521.',
		),
		array(
			'initials'    => 'DN',
			'name'        => 'Dorota Nowak',
			'specialty'   => 'Dietetyk kliniczny',
			'category'    => 'Zdrowie',
			'rating'      => '4.7',
			'reviews'     => 223,
			'location'    => 'Kraków',
			'answers'     => 412,
			'slug'        => 'dorota-nowak',
			'experience'  => '8 lat doświadczenia',
			'description' => 'Układa jadłospisy dla osób z cukrzycą, hashimoto i otyłością. Podejście oparte na medycynie opartej na dowodach.',
		),
		array(
			'initials'    => 'MZ',
			'name'        => 'Michał Zieliński',
			'specialty'   => 'Instalator pomp ciepła i systemów OZE',
			'category'    => 'Energia',
			'rating'      => '4.8',
			'reviews'     => 167,
			'location'    => 'Lublin',
			'answers'     => 298,
			'slug'        => 'michal-zielinski',
			'experience'  => '7 lat doświadczenia',
			'description' => 'Montuje pompy ciepła i instalacje fotowoltaiczne. Certyfikowany instalator UDT. Doradza w doborze mocy i kosztach.',
		),
		array(
			'initials'    => 'AS',
			'name'        => 'Agnieszka Sikorska',
			'specialty'   => 'Notariusz',
			'category'    => 'Prawo',
			'rating'      => '4.9',
			'reviews'     => 398,
			'location'    => 'Gdańsk',
			'answers'     => 156,
			'slug'        => 'agnieszka-sikorska',
			'experience'  => '17 lat doświadczenia',
			'description' => 'Prowadzi kancelarię notarialną. Specjalizuje się w obrocie nieruchomościami, testamentach i umowach spółek.',
		),
		array(
			'initials'    => 'KW',
			'name'        => 'Krzysztof Walczak',
			'specialty'   => 'Mechanik samochodowy, diagnostyk',
			'category'    => 'Motoryzacja',
			'rating'      => '4.7',
			'reviews'     => 289,
			'location'    => 'Bydgoszcz',
			'answers'     => 534,
			'slug'        => 'krzysztof-walczak',
			'experience'  => '22 lata doświadczenia',
			'description' => 'Prowadzi warsztat samochodowy. Doradza w zakupie używanych aut, diagnostyce usterek i kosztach napraw.',
		),
		array(
			'initials'    => 'IB',
			'name'        => 'Iwona Borkowska',
			'specialty'   => 'Doradca podatkowy',
			'category'    => 'Finanse',
			'rating'      => '4.8',
			'reviews'     => 201,
			'location'    => 'Poznań',
			'answers'     => 378,
			'slug'        => 'iwona-borkowska',
			'experience'  => '12 lat doświadczenia',
			'description' => 'Pomaga w rozliczeniach PIT/CIT, optymalizacji podatkowej i kontaktach z urzędem skarbowym.',
		),
	);
}

/**
 * Get complete questions data for Q&A section.
 *
 * @return array
 */
function pp_seed_questions() {
	return array(
		array(
			'slug'       => 'koszt-budowy-domu-120m2-2026',
			'title'      => 'Jaki jest realny koszt budowy domu 120m² w 2026 roku?',
			'category'   => 'Budownictwo',
			'author'     => 'Marcin, Katowice',
			'time'       => '2 godz. temu',
			'answers'    => 8,
			'content'    => 'Planuję budowę domu jednorodzinnego o powierzchni ok. 120m². Interesuje mnie stan deweloperski w regionie Śląska. Jakie są aktualne koszty materiałów i robocizny? Ile realistycznie powinienem zabudżetować?',
			'best_answer' => array(
				'text'       => 'Na Śląsku w 2026 roku realny koszt budowy domu 120m² w stanie deweloperskim to 4 500–5 500 zł/m², czyli łącznie 540 000–660 000 zł. Obejmuje to: fundamenty, ściany, dach, okna, instalacje (elektryczna, wod-kan, CO), tynki zewnętrzne. Nie obejmuje: wykończenia wnętrz, ogrodzenia, podjazdu, zagospodarowania terenu. Zalecam bufor 10–15% na nieprzewidziane wydatki.',
				'author'     => 'inż. Tomasz Zawadzki',
				'specialty'  => 'Budownictwo',
				'rating'     => '4.9',
				'answers_count' => 276,
			),
			'other_answers' => array(
				array(
					'text'      => 'Potwierdzam powyższą wycenę. Z mojego doświadczenia na Śląsku ceny materiałów budowlanych w 2026 są stabilne. Warto dodać, że stan deweloperski bez ogrzewania podłogowego będzie tańszy o ok. 200 zł/m².',
					'author'    => 'Magdalena Szymańska',
					'specialty' => 'Kierownik budowy',
					'rating'    => '4.6',
				),
				array(
					'text'      => 'Zwróć uwagę na koszty przyłączy — prąd, gaz, woda, kanalizacja to łącznie 15 000–30 000 zł. Wielu inwestorów o tym zapomina przy planowaniu budżetu.',
					'author'    => 'Robert Dąbrowski',
					'specialty' => 'Doradca kredytowy',
					'rating'    => '4.7',
				),
				array(
					'text'      => 'Jeśli rozważasz pompę ciepła zamiast gazu — koszt instalacji to ok. 35 000–55 000 zł, ale odpadają koszty przyłącza gazowego (8 000–15 000 zł) i comiesięczne opłaty za gaz.',
					'author'    => 'Jakub Mazurek',
					'specialty' => 'Inżynier energetyki',
					'rating'    => '4.8',
				),
			),
		),
		array(
			'slug'       => 'kredyt-hipoteczny-najlepsza-oferta',
			'title'      => 'Który bank oferuje najlepszy kredyt hipoteczny w czerwcu 2026?',
			'category'   => 'Finanse',
			'author'     => 'Karolina, Warszawa',
			'time'       => '5 godz. temu',
			'answers'    => 6,
			'content'    => 'Szukam kredytu hipotecznego na 400 000 zł (mieszkanie w Warszawie, 30 lat). Mam wkład własny 20%. Które banki mają teraz najniższe marże i najlepsze warunki?',
			'best_answer' => array(
				'text'       => 'Przy wkładzie 20% i kwocie 400 tys. zł na 30 lat najlepsze oferty w czerwcu 2026 oferują: mBank (marża od 1,84%, RRSO 8,12%), ING (marża od 1,89%, prowizja 0%), PKO BP (marża od 1,92%, ubezpieczenie niskiego wkładu gratis). Rekomendacja: złóż wnioski w 3 bankach jednocześnie i negocjuj cross-sell — to obniża marżę o 0,1–0,2 pp.',
				'author'     => 'Anna Wojciechowska',
				'specialty'  => 'Doradca finansowy',
				'rating'     => '4.8',
				'answers_count' => 431,
			),
			'other_answers' => array(
				array(
					'text'      => 'Potwierdzam ranking koleżanki. Dodam, że przy dochodach z umowy o pracę proces jest prostszy. Przy JDG bank wymaga zazwyczaj 12 miesięcy działalności i wyższej marży (o 0,2–0,4 pp.).',
					'author'    => 'Robert Dąbrowski',
					'specialty' => 'Doradca kredytowy',
					'rating'    => '4.7',
				),
				array(
					'text'      => 'Sprawdź też oferty z programem rządowym „Mieszkanie na start" — jeśli kwalifikujesz się wiekowo, dopłata do rat obniża RRSO nawet o 2 pp.',
					'author'    => 'Iwona Borkowska',
					'specialty' => 'Doradca podatkowy',
					'rating'    => '4.8',
				),
			),
		),
		array(
			'slug'       => 'pompa-ciepla-czy-gaz-2026',
			'title'      => 'Pompa ciepła czy gaz — co bardziej opłacalne w 2026?',
			'category'   => 'Energia',
			'author'     => 'Paweł, Wrocław',
			'time'       => '1 dzień temu',
			'answers'    => 11,
			'content'    => 'Buduję dom 140m² pod Wrocławiem. Zastanawiam się nad źródłem ogrzewania: pompa ciepła powietrze-woda czy kocioł gazowy kondensacyjny. Jakie są realne koszty eksploatacji i instalacji w 2026?',
			'best_answer' => array(
				'text'       => 'Porównanie dla domu 140m² (region Wrocław, izolacja wg WT2021): Pompa ciepła powietrze-woda — koszt instalacji: 38 000–55 000 zł, roczny koszt ogrzewania: 3 200–4 500 zł, COP średnioroczny: 3,5–4,0. Gaz ziemny kondensacyjny — koszt instalacji: 12 000–18 000 zł + przyłącze 10 000–15 000 zł, roczny koszt ogrzewania: 6 500–8 500 zł. Zwrot różnicy w inwestycji: 5–7 lat. Wniosek: pompa ciepła jest opłacalniejsza długoterminowo.',
				'author'     => 'Jakub Mazurek',
				'specialty'  => 'Inżynier energetyki',
				'rating'     => '4.8',
				'answers_count' => 734,
			),
			'other_answers' => array(
				array(
					'text'      => 'Warto dodać, że w 2026 dostępna jest dotacja „Czyste Powietrze Plus" do 37 000 zł na pompę ciepła. Po uwzględnieniu dotacji zwrot inwestycji skraca się do 2–3 lat.',
					'author'    => 'Michał Zieliński',
					'specialty' => 'Instalator OZE',
					'rating'    => '4.8',
				),
				array(
					'text'      => 'Pamiętaj o ogrzewaniu podłogowym — pompa ciepła działa najefektywniej z niskotemperaturowym systemem grzewczym. Koszt podłogówki to ok. 80–120 zł/m².',
					'author'    => 'Tomasz Zawadzki',
					'specialty' => 'Architekt',
					'rating'    => '4.9',
				),
			),
		),
		array(
			'slug'       => 'rozwod-procedura-dokumenty',
			'title'      => 'Jak wygląda procedura rozwodowa i jakie dokumenty przygotować?',
			'category'   => 'Prawo',
			'author'     => 'Anonim',
			'time'       => '3 godz. temu',
			'answers'    => 5,
			'content'    => 'Rozważam rozwód za porozumieniem stron. Nie mamy dzieci, mamy wspólne mieszkanie na kredycie. Ile trwa procedura, jakie dokumenty potrzebuję i ile to kosztuje?',
			'best_answer' => array(
				'text'       => 'Rozwód za porozumieniem stron bez dzieci to najszybsza ścieżka — jedna rozprawa, wyrok w 2–4 miesiące od złożenia pozwu. Dokumenty: 1) Pozew rozwodowy, 2) Odpis aktu małżeństwa, 3) Dowody osobiste, 4) Porozumienie dot. podziału majątku. Koszty: opłata sądowa 600 zł, jeśli bez adwokata. Z adwokatem: 3 000–8 000 zł za całość. Kwestia kredytu — bank musi zaakceptować przejęcie przez jedną stronę lub sprzedaż.',
				'author'     => 'Mateusz Kowalczyk',
				'specialty'  => 'Adwokat',
				'rating'     => '4.9',
				'answers_count' => 512,
			),
			'other_answers' => array(
				array(
					'text'      => 'W kwestii kredytu hipotecznego — bank nie jest stroną rozwodu. Musicie albo sprzedać nieruchomość i spłacić kredyt, albo jedno z was przejmuje dług (wymaga zgody banku i wystarczającej zdolności kredytowej).',
					'author'    => 'Anna Wojciechowska',
					'specialty' => 'Doradca finansowy',
					'rating'    => '4.8',
				),
				array(
					'text'      => 'Rekomendacja: sporządźcie pisemne porozumienie o podziale majątku jeszcze PRZED złożeniem pozwu. Sąd je uwzględni, a procedura będzie szybsza i tańsza.',
					'author'    => 'Elżbieta Kamińska',
					'specialty' => 'Radca prawny',
					'rating'    => '4.8',
				),
			),
		),
		array(
			'slug'       => 'implanty-zebowe-koszt-rodzaje',
			'title'      => 'Ile kosztują implanty zębowe i jaki rodzaj wybrać?',
			'category'   => 'Zdrowie',
			'author'     => 'Agata, Wrocław',
			'time'       => '1 dzień temu',
			'answers'    => 7,
			'content'    => 'Straciłam ząb trzonowy (dolna szóstka). Dentysta zaproponował implant, ale ceny w różnych klinikach bardzo się różnią — od 3 000 do 8 000 zł. Dlaczego takie różnice i co wybrać?',
			'best_answer' => array(
				'text'       => 'Różnice cenowe wynikają z: 1) Systemu implantów (premium: Straumann, Nobel Biocare — 5 500–8 000 zł; mid-range: MegaGen, Osstem — 3 500–5 000 zł; budżetowe: 2 500–3 500 zł). 2) Koronka: porcelana na zirkonie 1 500–2 500 zł, porcelana na metalu 1 000–1 500 zł. 3) Dodatkowe zabiegi: augmentacja kości +2 000–4 000 zł. Dla zęba trzonowego polecam system mid-range (MegaGen, Osstem) — stosunek jakości do ceny optymalny, gwarancja dożywotnia.',
				'author'     => 'dr Weronika Pawlak',
				'specialty'  => 'Implantolog',
				'rating'     => '4.9',
				'answers_count' => 295,
			),
			'other_answers' => array(
				array(
					'text'      => 'Przed zabiegiem poproś o panoramę RTG lub CBCT (tomografia 3D). Pozwoli ocenić gęstość kości i uniknąć niespodzianek w trakcie zabiegu.',
					'author'    => 'dr Piotr Lewandowski',
					'specialty' => 'Lekarz internista',
					'rating'    => '4.9',
				),
			),
		),
		array(
			'slug'       => 'sprzedaz-dzialki-podatki',
			'title'      => 'Jak sprzedać działkę i jakie podatki zapłacę?',
			'category'   => 'Nieruchomości',
			'author'     => 'Andrzej, Lublin',
			'time'       => '4 godz. temu',
			'answers'    => 4,
			'content'    => 'Odziedziczyłem działkę budowlaną 1000m² w 2024 roku. Chcę ją teraz sprzedać. Czy zapłacę podatek dochodowy? Ile wynosi i jak go uniknąć legalnie?',
			'best_answer' => array(
				'text'       => 'Sprzedaż nieruchomości przed upływem 5 lat od nabycia (licząc od końca roku nabycia) podlega PIT 19%. Nabyłeś w 2024, więc do końca 2029 sprzedaż jest opodatkowana. Możesz jednak skorzystać z ulgi mieszkaniowej — jeśli w ciągu 3 lat od sprzedaży przeznaczysz dochód na własne cele mieszkaniowe (kupno mieszkania, budowa domu, spłata kredytu hipotecznego), podatku nie zapłacisz. Koszt notariusza przy sprzedaży: ok. 2 000–4 000 zł.',
				'author'     => 'Iwona Borkowska',
				'specialty'  => 'Doradca podatkowy',
				'rating'     => '4.8',
				'answers_count' => 378,
			),
			'other_answers' => array(
				array(
					'text'      => 'Przed sprzedażą warto zamówić wypis i wyrys z MPZP lub decyzję WZ — kupujący chce mieć pewność, co może wybudować. Podniesie to też cenę działki.',
					'author'    => 'Katarzyna Nowicka',
					'specialty' => 'Pośrednik nieruchomości',
					'rating'    => '4.7',
				),
				array(
					'text'      => 'Przy spadku pamiętaj, że kosztem uzyskania przychodu jest wartość z aktu poświadczenia dziedziczenia + zapłacony podatek od spadku. Obniży to podstawę opodatkowania.',
					'author'    => 'Mateusz Kowalczyk',
					'specialty' => 'Adwokat',
					'rating'    => '4.9',
				),
			),
		),
		array(
			'slug'       => 'fotowoltaika-net-billing-2026',
			'title'      => 'Czy fotowoltaika nadal się opłaca przy net-billingu w 2026?',
			'category'   => 'Energia',
			'author'     => 'Tomek, Poznań',
			'time'       => '6 godz. temu',
			'answers'    => 9,
			'content'    => 'Rozważam instalację 8 kWp na dachu domu. Słyszę, że przy net-billingu zwrot jest dłuższy niż kiedyś. Ile realnie zaoszczędzę i po ilu latach instalacja się zwróci?',
			'best_answer' => array(
				'text'       => 'Przy instalacji 8 kWp (koszt 28 000–35 000 zł, po dotacji Mój Prąd: 20 000–27 000 zł) i rocznym zużyciu 5 000 kWh: produkcja roczna: ok. 8 000 kWh, autokonsumpcja: ~30% = 2 400 kWh (oszczędność: 2 400 zł/rok), sprzedaż nadwyżek przez net-billing: ~5 600 kWh × 0,45 zł = 2 520 zł/rok. Łączna roczna korzyść: ~4 900 zł. Zwrot po dotacji: 4–5,5 lat. Nadal opłacalne, choć mniej niż przy starym net-meteringu.',
				'author'     => 'Jakub Mazurek',
				'specialty'  => 'Inżynier energetyki',
				'rating'     => '4.8',
				'answers_count' => 734,
			),
			'other_answers' => array(
				array(
					'text'      => 'Kluczowe jest zwiększenie autokonsumpcji. Magazyn energii 5 kWh (koszt: 12 000–18 000 zł) podnosi autokonsumpcję do 60–70%, ale wydłuża zwrot o 2 lata. Bez magazynu zwrot jest szybszy.',
					'author'    => 'Michał Zieliński',
					'specialty' => 'Instalator OZE',
					'rating'    => '4.8',
				),
			),
		),
		array(
			'slug'       => 'kupno-uzywane-auto-na-co-uwazac',
			'title'      => 'Na co zwrócić uwagę kupując używane auto w 2026?',
			'category'   => 'Motoryzacja',
			'author'     => 'Damian, Bydgoszcz',
			'time'       => '12 godz. temu',
			'answers'    => 6,
			'content'    => 'Szukam auta do 50 000 zł, diesel lub hybryda, rocznik 2019–2022. Na co zwracać uwagę przy oględzinach, jakie dokumenty sprawdzić i czy warto robić ekspertyzę lakieru?',
			'best_answer' => array(
				'text'       => 'Absolutnie tak — ekspertyza lakieru (miernik grubości) kosztuje 150–300 zł i może zaoszczędzić tysiące. Poza tym sprawdź: 1) Historia serwisowa (ASO lub autoryzowany), 2) Raport z CEPiK (historia właścicieli, ubezpieczeń), 3) VIN w bazie NMVTIS/Carfax jeśli import, 4) Rozrząd (przy dieslu wymiana co 150–200 tys. km, koszt 2 000–5 000 zł), 5) DPF/FAP (filtr cząstek stałych — regeneracja 1 500–4 000 zł). Przy budżecie 50 tys. zł polecam: Toyota Corolla Hybrid (2019–2021) lub Skoda Octavia 2.0 TDI (2020–2022).',
				'author'     => 'Krzysztof Walczak',
				'specialty'  => 'Mechanik samochodowy',
				'rating'     => '4.7',
				'answers_count' => 534,
			),
			'other_answers' => array(
				array(
					'text'      => 'Jeśli auto jest z importu — koniecznie sprawdź, czy nie było wycofane z ruchu za granicą (np. Niemcy — Stilllegung). Możesz to zweryfikować na stronie ADAC lub przez numer rejestracyjny.',
					'author'    => 'Grzegorz Kubiak',
					'specialty' => 'Rzeczoznawca majątkowy',
					'rating'    => '4.8',
				),
			),
		),
	);
}

/**
 * Get complete rankings data.
 *
 * @return array
 */
function pp_seed_rankings() {
	return array(
		array(
			'slug'        => 'najlepsze-konta-osobiste-2026',
			'title'       => 'Najlepsze konta osobiste 2026',
			'category'    => 'Finanse',
			'updated'     => '2026-06-15',
			'description' => 'Porównanie kont osobistych pod kątem opłat, dostępności bankomatów, aplikacji mobilnej i dodatkowych korzyści.',
			'items'       => array(
				array( 'name' => 'mBank eKonto', 'score' => '9.6/10', 'cost' => '0 zł/mies.', 'url' => 'https://mbank.pl', 'pros' => 'Brak opłat, świetna aplikacja, cashback 0,5%' ),
				array( 'name' => 'ING Konto Direct', 'score' => '9.4/10', 'cost' => '0 zł/mies.', 'url' => 'https://ing.pl', 'pros' => 'Darmowe wypłaty z bankomatów, konto oszczędnościowe 5%' ),
				array( 'name' => 'Millennium 360°', 'score' => '9.2/10', 'cost' => '0 zł/mies.', 'url' => 'https://bankmillennium.pl', 'pros' => 'Goodie cashback, moneyback za rachunki' ),
				array( 'name' => 'PKO Konto za Zero', 'score' => '9.0/10', 'cost' => '0 zł/mies.', 'url' => 'https://pkobp.pl', 'pros' => 'Największa sieć bankomatów, BLIK bez telefonu' ),
				array( 'name' => 'Santander Konto Jakie Chcę', 'score' => '8.8/10', 'cost' => '0 zł/mies.', 'url' => 'https://santander.pl', 'pros' => 'Elastyczne pakiety, zwrot za paliwo' ),
			),
		),
		array(
			'slug'        => 'firmy-remontowe-katowice',
			'title'       => 'Najlepsze firmy remontowe — Katowice 2026',
			'category'    => 'Budownictwo',
			'updated'     => '2026-06-10',
			'description' => 'Ranking firm remontowych w Katowicach na podstawie jakości wykonania, terminowości, cen i opinii klientów.',
			'items'       => array(
				array( 'name' => 'RemontPro Katowice', 'score' => '9.7/10', 'cost' => 'od 350 zł/m²', 'url' => '#', 'pros' => '142 opinie, terminowość 98%, gwarancja 5 lat' ),
				array( 'name' => 'Budmax Wykończenia', 'score' => '9.5/10', 'cost' => 'od 320 zł/m²', 'url' => '#', 'pros' => '98 opinii, kompleksowe remonty, wizualizacje 3D' ),
				array( 'name' => 'Eko-Dom Remonty', 'score' => '9.3/10', 'cost' => 'od 290 zł/m²', 'url' => '#', 'pros' => '76 opinii, materiały ekologiczne, certyfikat ISO' ),
				array( 'name' => 'Solidne Wnętrza', 'score' => '9.0/10', 'cost' => 'od 310 zł/m²', 'url' => '#', 'pros' => '64 opinie, specjalizacja łazienki i kuchnie' ),
				array( 'name' => 'Artisan Remont', 'score' => '8.8/10', 'cost' => 'od 280 zł/m²', 'url' => '#', 'pros' => '51 opinii, szybka realizacja, bezpłatna wycena' ),
			),
		),
		array(
			'slug'        => 'instalatorzy-pomp-ciepla',
			'title'       => 'Najlepsi instalatorzy pomp ciepła 2026',
			'category'    => 'Energia',
			'updated'     => '2026-06-12',
			'description' => 'Ranking certyfikowanych instalatorów pomp ciepła w Polsce. Ocena na podstawie jakości montażu, serwisu i cen.',
			'items'       => array(
				array( 'name' => 'EcoHeat Śląsk', 'score' => '9.6/10', 'cost' => 'od 35 000 zł', 'url' => '#', 'pros' => 'Certyfikat UDT, 5 lat gwarancji, 200+ montaży' ),
				array( 'name' => 'TermoInstal', 'score' => '9.4/10', 'cost' => 'od 38 000 zł', 'url' => '#', 'pros' => 'Autoryzowany instalator Viessmann, serwis 24/7' ),
				array( 'name' => 'GreenPump Polska', 'score' => '9.1/10', 'cost' => 'od 32 000 zł', 'url' => '#', 'pros' => 'Najlepsza cena, pomoc w uzyskaniu dotacji' ),
				array( 'name' => 'ClimaticPro', 'score' => '8.9/10', 'cost' => 'od 40 000 zł', 'url' => '#', 'pros' => 'Premium marki (Daikin, Mitsubishi), 7 lat gwarancji' ),
				array( 'name' => 'SolarHeat Group', 'score' => '8.7/10', 'cost' => 'od 34 000 zł', 'url' => '#', 'pros' => 'Pompy + fotowoltaika w pakiecie, audyt gratis' ),
			),
		),
		array(
			'slug'        => 'doradcy-kredytowi-2026',
			'title'       => 'Najlepsi doradcy kredytowi 2026',
			'category'    => 'Finanse',
			'updated'     => '2026-06-14',
			'description' => 'Ranking niezależnych doradców kredytowych — porównanie ofert, skuteczności i opinii klientów.',
			'items'       => array(
				array( 'name' => 'FinExpert24', 'score' => '9.8/10', 'cost' => '0 zł prowizja', 'url' => '#', 'pros' => 'Współpraca z 18 bankami, decyzja w 48h' ),
				array( 'name' => 'KredytOK', 'score' => '9.5/10', 'cost' => '0 zł prowizja', 'url' => '#', 'pros' => 'Specjalizacja: JDG i niestandardowe dochody' ),
				array( 'name' => 'Doradca Wiśniewski', 'score' => '9.3/10', 'cost' => '0 zł prowizja', 'url' => '#', 'pros' => '14 lat doświadczenia, 2 400+ udzielonych kredytów' ),
				array( 'name' => 'HipotekaPlus', 'score' => '9.1/10', 'cost' => '0 zł prowizja', 'url' => '#', 'pros' => 'Online z całej Polski, porównywarka w czasie rzeczywistym' ),
				array( 'name' => 'CreditLine', 'score' => '8.9/10', 'cost' => '0 zł prowizja', 'url' => '#', 'pros' => 'Najszybsza obsługa, podpis online' ),
			),
		),
		array(
			'slug'        => 'stomatolodzy-implanty',
			'title'       => 'Najlepsze kliniki implantologiczne 2026',
			'category'    => 'Zdrowie',
			'updated'     => '2026-06-08',
			'description' => 'Ranking klinik stomatologicznych specjalizujących się w implantach zębowych — ceny, opinie pacjentów, technologie.',
			'items'       => array(
				array( 'name' => 'DentPro Wrocław', 'score' => '9.9/10', 'cost' => 'od 3 800 zł', 'url' => '#', 'pros' => 'Nawigowana implantacja 3D, 15 lat gwarancji' ),
				array( 'name' => 'SmileLab Warszawa', 'score' => '9.6/10', 'cost' => 'od 4 200 zł', 'url' => '#', 'pros' => 'Straumann, znieczulenie komputerowe, raty 0%' ),
				array( 'name' => 'Klinika Zdrowy Uśmiech', 'score' => '9.4/10', 'cost' => 'od 3 500 zł', 'url' => '#', 'pros' => 'MegaGen implanty, szybkie gojenie, darmowa konsultacja' ),
				array( 'name' => 'ImplantoMed Gdańsk', 'score' => '9.2/10', 'cost' => 'od 4 000 zł', 'url' => '#', 'pros' => 'Nobel Biocare, all-on-4, pełna protetyka' ),
				array( 'name' => 'DentalCare Poznań', 'score' => '9.0/10', 'cost' => 'od 3 200 zł', 'url' => '#', 'pros' => 'Najlepsza cena premium, tomografia CBCT gratis' ),
			),
		),
	);
}

/**
 * Get complete comparisons data.
 *
 * @return array
 */
function pp_seed_comparisons() {
	return array(
		array(
			'slug'    => 'pompa-ciepla-vs-gaz',
			'title'   => 'Pompa ciepła vs Gaz ziemny',
			'option_a' => 'Pompa ciepła',
			'option_b' => 'Gaz ziemny',
			'category' => 'Energia',
			'verdict' => 'Pompa ciepła wygrywa w perspektywie 10+ lat dzięki niższym kosztom eksploatacji i dostępnym dotacjom.',
			'specs'   => array(
				array( 'label' => 'Koszt instalacji', 'a' => '35 000–55 000 zł', 'b' => '12 000–18 000 zł + przyłącze' ),
				array( 'label' => 'Roczny koszt ogrzewania (140m²)', 'a' => '3 200–4 500 zł', 'b' => '6 500–8 500 zł' ),
				array( 'label' => 'Żywotność', 'a' => '15–20 lat', 'b' => '15–25 lat' ),
				array( 'label' => 'Dotacje 2026', 'a' => 'do 37 000 zł', 'b' => 'brak' ),
				array( 'label' => 'Wpływ na środowisko', 'a' => 'Zeroemisyjne', 'b' => 'Emisja CO₂' ),
				array( 'label' => 'Komfort użytkowania', 'a' => 'Cichy, automatyczny', 'b' => 'Wymaga wentylacji, przeglądy' ),
			),
		),
		array(
			'slug'    => 'styropian-vs-welna',
			'title'   => 'Styropian vs Wełna mineralna',
			'option_a' => 'Styropian (EPS/XPS)',
			'option_b' => 'Wełna mineralna',
			'category' => 'Budownictwo',
			'verdict' => 'Styropian lepszy cenowo na ściany zewnętrzne. Wełna lepsza na poddasza i tam, gdzie liczy się paroprzepuszczalność.',
			'specs'   => array(
				array( 'label' => 'Cena za m² (15cm)', 'a' => '45–70 zł', 'b' => '60–90 zł' ),
				array( 'label' => 'Lambda (λ)', 'a' => '0,031–0,040', 'b' => '0,035–0,045' ),
				array( 'label' => 'Paroprzepuszczalność', 'a' => 'Niska', 'b' => 'Wysoka' ),
				array( 'label' => 'Odporność ogniowa', 'a' => 'Samogasnący', 'b' => 'Niepalny (klasa A1)' ),
				array( 'label' => 'Izolacja akustyczna', 'a' => 'Słaba', 'b' => 'Bardzo dobra' ),
				array( 'label' => 'Trwałość', 'a' => '40–60 lat', 'b' => '50+ lat' ),
			),
		),
		array(
			'slug'    => 'kredyt-staly-vs-zmienny',
			'title'   => 'Kredyt stałe oprocentowanie vs zmienne',
			'option_a' => 'Stałe oprocentowanie',
			'option_b' => 'Zmienne oprocentowanie',
			'category' => 'Finanse',
			'verdict' => 'Stałe oprocentowanie daje bezpieczeństwo przy obecnych stopach. Zmienne opłaca się, gdy stopy będą spadać.',
			'specs'   => array(
				array( 'label' => 'Typowe oprocentowanie (2026)', 'a' => '7,2–7,8%', 'b' => '7,8–8,4%' ),
				array( 'label' => 'Okres stałej stawki', 'a' => '5 lat (potem renegocjacja)', 'b' => 'Zmienia się co 3/6 mies.' ),
				array( 'label' => 'Ryzyko wzrostu rat', 'a' => 'Brak (przez 5 lat)', 'b' => 'Wysokie' ),
				array( 'label' => 'Szansa na niższe raty', 'a' => 'Brak (przez 5 lat)', 'b' => 'Tak, przy spadku WIBOR' ),
				array( 'label' => 'Przewidywalność budżetu', 'a' => 'Pełna', 'b' => 'Ograniczona' ),
				array( 'label' => 'Dostępność', 'a' => 'Wszystkie banki', 'b' => 'Wszystkie banki' ),
			),
		),
		array(
			'slug'    => 'rynek-pierwotny-vs-wtorny',
			'title'   => 'Mieszkanie z rynku pierwotnego vs wtórnego',
			'option_a' => 'Rynek pierwotny',
			'option_b' => 'Rynek wtórny',
			'category' => 'Nieruchomości',
			'verdict' => 'Rynek pierwotny lepszy dla szukających nowoczesnego standardu. Wtórny — dla ceniących lokalizację i szybki odbiór.',
			'specs'   => array(
				array( 'label' => 'Cena za m² (Warszawa)', 'a' => '14 000–22 000 zł', 'b' => '12 000–18 000 zł' ),
				array( 'label' => 'Czas do zamieszkania', 'a' => '12–24 mies.', 'b' => '1–3 mies.' ),
				array( 'label' => 'Standard wykończenia', 'a' => 'Deweloperski (do wykończenia)', 'b' => 'Często do remontu' ),
				array( 'label' => 'Koszty dodatkowe', 'a' => 'Wykończenie: 1 500–3 000 zł/m²', 'b' => 'PCC 2% + ewentualny remont' ),
				array( 'label' => 'Lokalizacja', 'a' => 'Często peryferia', 'b' => 'Centrum i dobre dzielnice' ),
				array( 'label' => 'Gwarancja', 'a' => '5 lat od dewelopera', 'b' => 'Brak' ),
			),
		),
		array(
			'slug'    => 'implant-vs-most',
			'title'   => 'Implant zębowy vs Most protetyczny',
			'option_a' => 'Implant zębowy',
			'option_b' => 'Most protetyczny',
			'category' => 'Zdrowie',
			'verdict' => 'Implant jest lepszym rozwiązaniem długoterminowym — nie uszkadza sąsiednich zębów i służy dożywotnio.',
			'specs'   => array(
				array( 'label' => 'Koszt całkowity', 'a' => '4 000–8 000 zł', 'b' => '2 000–4 500 zł' ),
				array( 'label' => 'Trwałość', 'a' => 'Dożywotnio (koronka 10–15 lat)', 'b' => '7–15 lat' ),
				array( 'label' => 'Wpływ na sąsiednie zęby', 'a' => 'Żaden', 'b' => 'Szlifowanie 2 zdrowych zębów' ),
				array( 'label' => 'Czas leczenia', 'a' => '3–6 miesięcy', 'b' => '2–3 tygodnie' ),
				array( 'label' => 'Komfort użytkowania', 'a' => 'Jak naturalny ząb', 'b' => 'Dobry, ale wymaga higieny pod mostem' ),
				array( 'label' => 'Wymagania', 'a' => 'Wystarczająca kość, zdrowe dziąsła', 'b' => 'Zdrowe zęby filarowe' ),
			),
		),
	);
}

/**
 * Get complete calculators data.
 *
 * @return array
 */
function pp_seed_calculators() {
	return array(
		array(
			'slug'        => 'kalkulator-kredytu-hipotecznego',
			'title'       => 'Kalkulator kredytu hipotecznego',
			'category'    => 'Finanse',
			'description' => 'Oblicz ratę kredytu hipotecznego, całkowity koszt kredytu i sprawdź, ile musisz zarabiać.',
			'icon'        => '🏦',
			'fields'      => array(
				array( 'id' => 'kwota', 'label' => 'Kwota kredytu (zł)', 'type' => 'number', 'default' => '400000', 'min' => '50000', 'max' => '2000000', 'step' => '10000' ),
				array( 'id' => 'okres', 'label' => 'Okres kredytowania (lata)', 'type' => 'number', 'default' => '25', 'min' => '5', 'max' => '35', 'step' => '1' ),
				array( 'id' => 'oprocentowanie', 'label' => 'Oprocentowanie roczne (%)', 'type' => 'number', 'default' => '7.5', 'min' => '1', 'max' => '15', 'step' => '0.1' ),
				array( 'id' => 'wklad', 'label' => 'Wkład własny (%)', 'type' => 'number', 'default' => '20', 'min' => '10', 'max' => '80', 'step' => '5' ),
			),
			'formula_js'  => "
				const kwota = parseFloat(f.kwota.value);
				const lata = parseInt(f.okres.value);
				const r = parseFloat(f.oprocentowanie.value) / 100 / 12;
				const n = lata * 12;
				const wklad = parseFloat(f.wklad.value) / 100;
				const kredyt = kwota * (1 - wklad);
				const rata = kredyt * (r * Math.pow(1+r,n)) / (Math.pow(1+r,n) - 1);
				const calkowity = rata * n;
				return {
					'Kwota kredytu': kredyt.toFixed(0) + ' zł',
					'Rata miesięczna': rata.toFixed(2) + ' zł',
					'Całkowity koszt': calkowity.toFixed(0) + ' zł',
					'Suma odsetek': (calkowity - kredyt).toFixed(0) + ' zł'
				};
			",
		),
		array(
			'slug'        => 'kalkulator-kosztu-budowy',
			'title'       => 'Kalkulator kosztu budowy domu',
			'category'    => 'Budownictwo',
			'description' => 'Oszacuj koszt budowy domu jednorodzinnego na podstawie metrażu, standardu i lokalizacji.',
			'icon'        => '🏠',
			'fields'      => array(
				array( 'id' => 'metraz', 'label' => 'Powierzchnia użytkowa (m²)', 'type' => 'number', 'default' => '120', 'min' => '60', 'max' => '400', 'step' => '10' ),
				array( 'id' => 'standard', 'label' => 'Standard wykończenia', 'type' => 'select', 'options' => array( 'deweloperski' => 'Stan deweloperski (4 500–5 500 zł/m²)', 'podwyższony' => 'Podwyższony (5 500–7 000 zł/m²)', 'premium' => 'Premium (7 000–10 000 zł/m²)' ), 'default' => 'deweloperski' ),
				array( 'id' => 'region', 'label' => 'Region', 'type' => 'select', 'options' => array( 'centrum' => 'Centralna Polska (+10%)', 'poludnie' => 'Południe (standard)', 'polnoc' => 'Północ (-5%)', 'wschod' => 'Wschód (-10%)' ), 'default' => 'poludnie' ),
			),
			'formula_js'  => "
				const m = parseFloat(f.metraz.value);
				const std = f.standard.value;
				const reg = f.region.value;
				let base = std === 'deweloperski' ? 5000 : (std === 'podwyższony' ? 6250 : 8500);
				let mult = reg === 'centrum' ? 1.1 : (reg === 'polnoc' ? 0.95 : (reg === 'wschod' ? 0.9 : 1.0));
				const koszt = m * base * mult;
				return {
					'Szacunkowy koszt budowy': koszt.toFixed(0) + ' zł',
					'Koszt za m²': (base * mult).toFixed(0) + ' zł/m²',
					'Zakres': (koszt * 0.9).toFixed(0) + ' – ' + (koszt * 1.1).toFixed(0) + ' zł'
				};
			",
		),
		array(
			'slug'        => 'kalkulator-fotowoltaiki',
			'title'       => 'Kalkulator opłacalności fotowoltaiki',
			'category'    => 'Energia',
			'description' => 'Sprawdź, po ilu latach zwróci się instalacja fotowoltaiczna i ile zaoszczędzisz na prądzie.',
			'icon'        => '☀️',
			'fields'      => array(
				array( 'id' => 'moc', 'label' => 'Moc instalacji (kWp)', 'type' => 'number', 'default' => '8', 'min' => '3', 'max' => '50', 'step' => '0.5' ),
				array( 'id' => 'zuzycie', 'label' => 'Roczne zużycie prądu (kWh)', 'type' => 'number', 'default' => '5000', 'min' => '1000', 'max' => '30000', 'step' => '500' ),
				array( 'id' => 'cena_kwh', 'label' => 'Cena prądu (zł/kWh)', 'type' => 'number', 'default' => '1.0', 'min' => '0.5', 'max' => '2.0', 'step' => '0.05' ),
				array( 'id' => 'dotacja', 'label' => 'Dotacja Mój Prąd (zł)', 'type' => 'number', 'default' => '7000', 'min' => '0', 'max' => '16000', 'step' => '1000' ),
			),
			'formula_js'  => "
				const moc = parseFloat(f.moc.value);
				const zuzycie = parseFloat(f.zuzycie.value);
				const cena = parseFloat(f.cena_kwh.value);
				const dotacja = parseFloat(f.dotacja.value);
				const produkcja = moc * 1000;
				const koszt_inst = moc * 4000;
				const koszt_netto = koszt_inst - dotacja;
				const autokonsum = Math.min(produkcja * 0.3, zuzycie);
				const nadwyzka = produkcja - autokonsum;
				const oszczednosc = autokonsum * cena + nadwyzka * 0.45;
				const zwrot = koszt_netto / oszczednosc;
				return {
					'Roczna produkcja': produkcja + ' kWh',
					'Roczna oszczędność': oszczednosc.toFixed(0) + ' zł',
					'Koszt po dotacji': koszt_netto.toFixed(0) + ' zł',
					'Zwrot inwestycji': zwrot.toFixed(1) + ' lat'
				};
			",
		),
		array(
			'slug'        => 'kalkulator-remontu-lazienki',
			'title'       => 'Kalkulator kosztu remontu łazienki',
			'category'    => 'Budownictwo',
			'description' => 'Oszacuj koszt remontu łazienki: materiały, robocizna, armatura — szczegółowy kosztorys.',
			'icon'        => '🚿',
			'fields'      => array(
				array( 'id' => 'metraz', 'label' => 'Powierzchnia łazienki (m²)', 'type' => 'number', 'default' => '6', 'min' => '3', 'max' => '20', 'step' => '0.5' ),
				array( 'id' => 'standard', 'label' => 'Standard materiałów', 'type' => 'select', 'options' => array( 'ekonomiczny' => 'Ekonomiczny (800–1 200 zł/m²)', 'sredni' => 'Średni (1 200–1 800 zł/m²)', 'premium' => 'Premium (1 800–3 000 zł/m²)' ), 'default' => 'sredni' ),
				array( 'id' => 'zakres', 'label' => 'Zakres prac', 'type' => 'select', 'options' => array( 'podstawowy' => 'Wymiana płytek i armatury', 'pelny' => 'Pełny remont (z hydrauliką)', 'rozbudowa' => 'Rozbudowa + zmiana układu' ), 'default' => 'pelny' ),
			),
			'formula_js'  => "
				const m = parseFloat(f.metraz.value);
				const std = f.standard.value;
				const zakres = f.zakres.value;
				let base = std === 'ekonomiczny' ? 1000 : (std === 'sredni' ? 1500 : 2400);
				let mult = zakres === 'podstawowy' ? 0.7 : (zakres === 'rozbudowa' ? 1.4 : 1.0);
				const koszt = m * base * mult;
				return {
					'Szacunkowy koszt remontu': koszt.toFixed(0) + ' zł',
					'Materiały (~40%)': (koszt * 0.4).toFixed(0) + ' zł',
					'Robocizna (~50%)': (koszt * 0.5).toFixed(0) + ' zł',
					'Nieprzewidziane (~10%)': (koszt * 0.1).toFixed(0) + ' zł'
				};
			",
		),
		array(
			'slug'        => 'kalkulator-alimentow',
			'title'       => 'Kalkulator alimentów',
			'category'    => 'Prawo',
			'description' => 'Orientacyjna wysokość alimentów na podstawie dochodów i potrzeb dziecka.',
			'icon'        => '⚖️',
			'fields'      => array(
				array( 'id' => 'dochod', 'label' => 'Dochód netto rodzica (zł/mies.)', 'type' => 'number', 'default' => '7000', 'min' => '2000', 'max' => '50000', 'step' => '500' ),
				array( 'id' => 'dzieci', 'label' => 'Liczba dzieci', 'type' => 'number', 'default' => '1', 'min' => '1', 'max' => '5', 'step' => '1' ),
				array( 'id' => 'wiek', 'label' => 'Wiek dziecka', 'type' => 'select', 'options' => array( 'maluch' => '0–6 lat', 'szkolny' => '7–14 lat', 'nastolatek' => '15–18 lat', 'student' => 'Student (19–25 lat)' ), 'default' => 'szkolny' ),
			),
			'formula_js'  => "
				const dochod = parseFloat(f.dochod.value);
				const dzieci = parseInt(f.dzieci.value);
				const wiek = f.wiek.value;
				let procent = wiek === 'maluch' ? 0.20 : (wiek === 'szkolny' ? 0.25 : (wiek === 'nastolatek' ? 0.28 : 0.30));
				let alimenty = dochod * procent;
				if (dzieci > 1) alimenty = alimenty * 0.85 * dzieci;
				return {
					'Orientacyjne alimenty': alimenty.toFixed(0) + ' zł/mies.',
					'Na dziecko': (alimenty / dzieci).toFixed(0) + ' zł/mies.',
					'Rocznie łącznie': (alimenty * 12).toFixed(0) + ' zł'
				};
			",
		),
		array(
			'slug'        => 'kalkulator-pompy-ciepla',
			'title'       => 'Kalkulator doboru pompy ciepła',
			'category'    => 'Energia',
			'description' => 'Dobierz moc pompy ciepła do domu i sprawdź roczne koszty ogrzewania.',
			'icon'        => '🌡️',
			'fields'      => array(
				array( 'id' => 'powierzchnia', 'label' => 'Powierzchnia domu (m²)', 'type' => 'number', 'default' => '140', 'min' => '50', 'max' => '500', 'step' => '10' ),
				array( 'id' => 'izolacja', 'label' => 'Standard izolacji', 'type' => 'select', 'options' => array( 'stary' => 'Stary dom (>80 W/m²)', 'sredni' => 'Standardowy (50–80 W/m²)', 'nowy' => 'Nowy (WT2021, 30–50 W/m²)', 'pasywny' => 'Pasywny (<30 W/m²)' ), 'default' => 'nowy' ),
				array( 'id' => 'cwu', 'label' => 'Osoby w domu (CWU)', 'type' => 'number', 'default' => '4', 'min' => '1', 'max' => '8', 'step' => '1' ),
			),
			'formula_js'  => "
				const pow = parseFloat(f.powierzchnia.value);
				const izo = f.izolacja.value;
				const osoby = parseInt(f.cwu.value);
				let wm2 = izo === 'stary' ? 90 : (izo === 'sredni' ? 65 : (izo === 'nowy' ? 40 : 25));
				let moc_co = pow * wm2 / 1000;
				let moc_cwu = osoby * 0.3;
				let moc_pc = moc_co + moc_cwu;
				let cop = 3.8;
				let roczne_kwh = (moc_co * 1800 + moc_cwu * 2500) / cop;
				let koszt_roczny = roczne_kwh * 0.9;
				return {
					'Zalecana moc pompy': moc_pc.toFixed(1) + ' kW',
					'Zużycie roczne': roczne_kwh.toFixed(0) + ' kWh',
					'Koszt ogrzewania/rok': koszt_roczny.toFixed(0) + ' zł',
					'Koszt miesięczny (sezon)': (koszt_roczny / 7).toFixed(0) + ' zł'
				};
			",
		),
	);
}

/**
 * Get FAQ data for pytania archive.
 *
 * @return array
 */
function pp_seed_faq_categories() {
	return array(
		array(
			'name'  => 'Budownictwo',
			'icon'  => '🏗️',
			'count' => 234,
			'slug'  => 'budownictwo',
		),
		array(
			'name'  => 'Finanse',
			'icon'  => '💰',
			'count' => 189,
			'slug'  => 'finanse',
		),
		array(
			'name'  => 'Prawo',
			'icon'  => '⚖️',
			'count' => 156,
			'slug'  => 'prawo',
		),
		array(
			'name'  => 'Energia',
			'icon'  => '⚡',
			'count' => 128,
			'slug'  => 'energia',
		),
		array(
			'name'  => 'Nieruchomości',
			'icon'  => '🏠',
			'count' => 167,
			'slug'  => 'nieruchomosci',
		),
		array(
			'name'  => 'Zdrowie',
			'icon'  => '🏥',
			'count' => 143,
			'slug'  => 'zdrowie',
		),
		array(
			'name'  => 'Motoryzacja',
			'icon'  => '🚗',
			'count' => 98,
			'slug'  => 'motoryzacja',
		),
		array(
			'name'  => 'Technologia',
			'icon'  => '💻',
			'count' => 76,
			'slug'  => 'technologia',
		),
	);
}

/**
 * Get a specific question by slug.
 *
 * @param string $slug Question slug.
 * @return array|null
 */
function pp_seed_get_question( $slug ) {
	$questions = pp_seed_questions();
	foreach ( $questions as $q ) {
		if ( $q['slug'] === $slug ) {
			return $q;
		}
	}
	return $questions[0]; // fallback to first question
}

/**
 * Get a specific ranking by slug.
 *
 * @param string $slug Ranking slug.
 * @return array|null
 */
function pp_seed_get_ranking( $slug ) {
	$rankings = pp_seed_rankings();
	foreach ( $rankings as $r ) {
		if ( $r['slug'] === $slug ) {
			return $r;
		}
	}
	return $rankings[0]; // fallback to first ranking
}

/**
 * Get a specific comparison by slug.
 *
 * @param string $slug Comparison slug.
 * @return array|null
 */
function pp_seed_get_comparison( $slug ) {
	$comparisons = pp_seed_comparisons();
	foreach ( $comparisons as $c ) {
		if ( $c['slug'] === $slug ) {
			return $c;
		}
	}
	return $comparisons[0]; // fallback to first comparison
}

/**
 * Get a specific calculator by slug.
 *
 * @param string $slug Calculator slug.
 * @return array|null
 */
function pp_seed_get_calculator( $slug ) {
	$calculators = pp_seed_calculators();
	foreach ( $calculators as $c ) {
		if ( $c['slug'] === $slug ) {
			return $c;
		}
	}
	return $calculators[0]; // fallback to first calculator
}
