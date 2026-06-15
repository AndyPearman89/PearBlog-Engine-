<?php
/**
 * Template Name: Poradnik.PRO - AI Doradca
 * Description: AI advisor page — personalized recommendation flow (budget, location, goal → result).
 *
 * @package PearBlog
 * @version 5.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' }, accent: '#F59E0B' }, fontFamily: { display: ['Poppins','system-ui','sans-serif'], body: ['Inter','system-ui','sans-serif'] } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<!-- Breadcrumb -->
<nav class="max-w-4xl mx-auto px-4 py-4 text-sm text-slate-500">
    <a href="/" class="hover:text-brand">Strona główna</a>
    <span class="mx-1">/</span>
    <span class="text-slate-800">AI Doradca</span>
</nav>

<!-- Hero -->
<header class="max-w-4xl mx-auto px-4 py-12 text-center">
    <span class="inline-block bg-purple-100 text-purple-700 font-semibold text-xs uppercase tracking-wider px-3 py-1 rounded-full mb-4">🤖 AI Doradca</span>
    <h1 class="font-display text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Nie wiesz co wybrać? AI Ci pomoże.</h1>
    <p class="text-lg text-slate-600 max-w-xl mx-auto">Odpowiedz na kilka pytań, a nasz system dobierze najlepsze rozwiązanie — spersonalizowane do Twojej sytuacji.</p>
</header>

<!-- Advisor Form -->
<section class="max-w-3xl mx-auto px-4 py-8">
    <form id="ai-advisor-form" class="space-y-8 bg-slate-50 rounded-2xl p-8 border border-slate-200">

        <!-- Step 1: Category -->
        <div class="space-y-3">
            <label class="block font-semibold text-lg">1. Czego dotyczy Twoje pytanie?</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="ogrzewanie" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">🔥</span>
                        <span class="text-sm font-medium">Ogrzewanie</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="remont" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">🏠</span>
                        <span class="text-sm font-medium">Remont</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="prawo" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">⚖️</span>
                        <span class="text-sm font-medium">Prawo</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="finanse" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">💰</span>
                        <span class="text-sm font-medium">Finanse</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="energia" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">⚡</span>
                        <span class="text-sm font-medium">Energia</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="inne" class="peer hidden">
                    <div class="peer-checked:ring-2 peer-checked:ring-brand peer-checked:bg-blue-50 border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-100 transition">
                        <span class="text-2xl block mb-1">📋</span>
                        <span class="text-sm font-medium">Inne</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Step 2: Budget -->
        <div class="space-y-3">
            <label class="block font-semibold text-lg">2. Jaki masz budżet?</label>
            <select name="budget" class="w-full border border-slate-300 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="">Wybierz przedział…</option>
                <option value="do-5000">do 5 000 zł</option>
                <option value="5000-20000">5 000 – 20 000 zł</option>
                <option value="20000-50000">20 000 – 50 000 zł</option>
                <option value="50000-100000">50 000 – 100 000 zł</option>
                <option value="powyzej-100000">powyżej 100 000 zł</option>
                <option value="nie-wiem">Nie wiem jeszcze</option>
            </select>
        </div>

        <!-- Step 3: Location -->
        <div class="space-y-3">
            <label for="location" class="block font-semibold text-lg">3. Twoja lokalizacja</label>
            <input type="text" id="location" name="location" placeholder="np. Kraków, Katowice, Warszawa…" class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        </div>

        <!-- Step 4: Goal -->
        <div class="space-y-3">
            <label for="goal" class="block font-semibold text-lg">4. Opisz swój cel lub problem</label>
            <textarea id="goal" name="goal" rows="3" placeholder="np. Chcę wymienić ogrzewanie na tańsze, mam dom 120m², szukam rozwiązania na zimę 2026…" class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand focus:border-brand outline-none resize-none"></textarea>
        </div>

        <!-- Submit -->
        <button type="submit" class="w-full bg-brand text-white font-bold text-lg py-4 rounded-xl hover:bg-brand-dark transition shadow-lg shadow-brand/20">
            🤖 Otrzymaj rekomendację
        </button>
    </form>
</section>

<!-- Result Placeholder -->
<section id="ai-result" class="max-w-3xl mx-auto px-4 py-8 hidden">
    <div class="bg-gradient-to-br from-purple-50 to-blue-50 border border-purple-200 rounded-2xl p-8">
        <h2 class="font-display text-2xl font-bold mb-4">🎯 Twoja rekomendacja</h2>
        <div id="ai-result-content" class="text-slate-700 space-y-4">
            <!-- Populated by JS/API -->
        </div>
        <div class="mt-6 pt-6 border-t border-purple-100 grid sm:grid-cols-3 gap-4">
            <a href="/porownania/" class="text-center p-3 bg-white rounded-xl border hover:border-brand transition">
                <span class="block text-lg mb-1">🆚</span>
                <span class="text-sm font-medium">Porównania</span>
            </a>
            <a href="/rankingi/" class="text-center p-3 bg-white rounded-xl border hover:border-brand transition">
                <span class="block text-lg mb-1">🏆</span>
                <span class="text-sm font-medium">Rankingi</span>
            </a>
            <a href="/specjalisci/" class="text-center p-3 bg-white rounded-xl border hover:border-brand transition">
                <span class="block text-lg mb-1">🧑‍💼</span>
                <span class="text-sm font-medium">Specjaliści</span>
            </a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="max-w-4xl mx-auto px-4 py-12 border-t border-slate-100">
    <h2 class="font-display text-2xl font-bold text-center mb-8">Jak działa AI Doradca?</h2>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center p-6">
            <span class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 text-brand rounded-full text-xl font-bold mb-4">1</span>
            <h3 class="font-semibold mb-2">Opisujesz sytuację</h3>
            <p class="text-sm text-slate-600">Kategoria, budżet, lokalizacja i cel — AI potrzebuje kontekstu.</p>
        </div>
        <div class="text-center p-6">
            <span class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 text-brand rounded-full text-xl font-bold mb-4">2</span>
            <h3 class="font-semibold mb-2">AI analizuje opcje</h3>
            <p class="text-sm text-slate-600">System przeszukuje bazę poradników, rankingów i specjalistów.</p>
        </div>
        <div class="text-center p-6">
            <span class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 text-brand rounded-full text-xl font-bold mb-4">3</span>
            <h3 class="font-semibold mb-2">Dostajesz rekomendację</h3>
            <p class="text-sm text-slate-600">Spersonalizowana odpowiedź + linki do porównań, kalkulatorów i ekspertów.</p>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="max-w-3xl mx-auto px-4 py-12 border-t border-slate-100">
    <h2 class="font-display text-xl font-bold mb-6">Często zadawane pytania</h2>
    <div class="space-y-4">
        <details class="group border border-slate-200 rounded-xl">
            <summary class="flex items-center justify-between p-4 cursor-pointer font-medium hover:bg-slate-50 rounded-xl">
                Czy to jest darmowe?
                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
            </summary>
            <div class="px-4 pb-4 text-slate-600">Tak, korzystanie z AI Doradcy jest całkowicie bezpłatne. Nie wymagamy rejestracji.</div>
        </details>
        <details class="group border border-slate-200 rounded-xl">
            <summary class="flex items-center justify-between p-4 cursor-pointer font-medium hover:bg-slate-50 rounded-xl">
                Jak dokładne są rekomendacje?
                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
            </summary>
            <div class="px-4 pb-4 text-slate-600">AI bazuje na aktualnych danych z naszej bazy poradników i opinii. Rekomendacje są punktem wyjścia — ostateczną decyzję zawsze podejmujesz Ty.</div>
        </details>
        <details class="group border border-slate-200 rounded-xl">
            <summary class="flex items-center justify-between p-4 cursor-pointer font-medium hover:bg-slate-50 rounded-xl">
                Czy moje dane są bezpieczne?
                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
            </summary>
            <div class="px-4 pb-4 text-slate-600">Nie przechowujemy danych osobowych. Zapytania są anonimowe i służą wyłącznie do generowania odpowiedzi.</div>
        </details>
    </div>
</section>

<script>
document.getElementById('ai-advisor-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const result = document.getElementById('ai-result');
    const content = document.getElementById('ai-result-content');

    // Gather form data
    const data = {
        category: (form.category && form.category.value) || '',
        budget: (form.budget && form.budget.value) || '',
        location: (form.location && form.location.value) || '',
        goal: (form.goal && form.goal.value) || ''
    };

    // Show loading
    result.classList.remove('hidden');
    content.innerHTML = '<p class="text-center text-slate-500">⏳ Analizuję opcje…</p>';

    // Simulated response (replace with real API call)
    setTimeout(function() {
        content.innerHTML = `
            <p><strong>Na podstawie Twoich odpowiedzi:</strong></p>
            <ul class="list-disc list-inside space-y-2 ml-2">
                <li>Kategoria: <strong>${data.category || 'nie wybrano'}</strong></li>
                <li>Budżet: <strong>${data.budget || 'nie podano'}</strong></li>
                <li>Lokalizacja: <strong>${data.location || 'nie podano'}</strong></li>
            </ul>
            <div class="mt-4 p-4 bg-white rounded-xl border border-purple-100">
                <p class="font-semibold mb-2">💡 Rekomendacja:</p>
                <p>Skonfiguruj integrację z API AI, aby otrzymać spersonalizowaną rekomendację. W międzyczasie sprawdź nasze <a href="/porownania/" class="text-brand underline">porównania</a> i <a href="/rankingi/" class="text-brand underline">rankingi</a>.</p>
            </div>
        `;
    }, 1500);
});
</script>

<?php wp_footer(); ?>
</body>
</html>
