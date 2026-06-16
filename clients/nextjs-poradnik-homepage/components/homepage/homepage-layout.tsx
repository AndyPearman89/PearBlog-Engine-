import Link from "next/link";
import type { ReactNode } from "react";

const navItems = [
  "Poradniki",
  "Rankingi",
  "Kalkulatory",
  "Pytania i Odpowiedzi",
  "Specjaliści",
  "Kontakt",
];

const stats = [
  { value: "120 000+", label: "odpowiedzi" },
  { value: "8500+", label: "specjalistów" },
  { value: "1200+", label: "rankingów" },
  { value: "50+", label: "kalkulatorów" },
];

const categories = [
  { name: "Prawo", guides: 320, experts: 580, icon: "⚖️" },
  { name: "Finanse", guides: 460, experts: 740, icon: "💳" },
  { name: "Nieruchomości", guides: 290, experts: 410, icon: "🏡" },
  { name: "Budowa domu", guides: 380, experts: 520, icon: "🏗️" },
  { name: "Motoryzacja", guides: 210, experts: 330, icon: "🚗" },
  { name: "Zdrowie", guides: 520, experts: 620, icon: "🩺" },
  { name: "Biznes", guides: 410, experts: 430, icon: "📈" },
  { name: "Technologia", guides: 360, experts: 280, icon: "💻" },
];

const experts = [
  {
    name: "Anna Kowalska",
    specialization: "Radca prawny",
    rating: "4.9",
    reviews: "281 opinii",
  },
  {
    name: "Michał Nowak",
    specialization: "Doradca kredytowy",
    rating: "4.8",
    reviews: "197 opinii",
  },
  {
    name: "Katarzyna Wiśniewska",
    specialization: "Architekt",
    rating: "4.9",
    reviews: "154 opinie",
  },
  {
    name: "Paweł Zieliński",
    specialization: "Księgowy",
    rating: "4.9",
    reviews: "223 opinie",
  },
];

const questions = [
  {
    title: "Czy mogę wybudować dom na działce rolnej?",
    answers: "4 odpowiedzi ekspertów",
  },
  {
    title: "Jak obniżyć ratę kredytu?",
    answers: "7 odpowiedzi ekspertów",
  },
  {
    title: "Jaka forma działalności jest najlepsza dla freelancera?",
    answers: "5 odpowiedzi ekspertów",
  },
];

const rankings = [
  "Najlepsze konta osobiste 2026",
  "Najlepsze pompy ciepła 2026",
  "Najlepsze kredyty hipoteczne 2026",
  "Najlepsze programy księgowe",
];

const calculators = [
  "Kredyt hipoteczny",
  "Zdolność kredytowa",
  "Koszt budowy domu",
  "Kalkulator OC",
  "Kalkulator wynagrodzeń",
];

const topSearches = [
  "Prawo Warszawa",
  "Kredyt Kraków",
  "Architekt Katowice",
  "Księgowy Wrocław",
];

function SectionTitle({
  eyebrow,
  title,
  subtitle,
  inverted = false,
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  inverted?: boolean;
}) {
  return (
    <header className="space-y-3 text-center md:text-left">
      {eyebrow ? (
        <p className={`text-sm font-semibold uppercase tracking-[0.16em] ${inverted ? "text-indigo-200" : "text-primary"}`}>
          {eyebrow}
        </p>
      ) : null}
      <h2 className={`text-2xl font-semibold tracking-tight md:text-4xl ${inverted ? "text-white" : "text-slate-900"}`}>{title}</h2>
      {subtitle ? (
        <p className={`max-w-3xl text-sm md:text-base ${inverted ? "text-slate-200" : "text-slate-600"}`}>{subtitle}</p>
      ) : null}
    </header>
  );
}

function Panel({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <article className={`rounded-[20px] border border-border bg-white/80 p-5 shadow-[0_10px_30px_rgba(15,23,42,0.06)] backdrop-blur-sm md:p-6 ${className}`}>
      {children}
    </article>
  );
}

export function HomepageLayout() {
  return (
    <div className="min-h-screen bg-background text-slate-900">
      <header className="sticky top-0 z-50 border-b border-border/80 bg-white/90 backdrop-blur">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-8">
          <Link href="/" className="text-lg font-bold tracking-tight text-slate-900">
            Poradnik<span className="text-primary">.pro</span>
          </Link>
          <nav className="hidden items-center gap-5 lg:flex" aria-label="Główna nawigacja">
            {navItems.map((item) => (
              <Link key={item} href="#" className="text-sm font-medium text-slate-600 transition hover:text-slate-900">
                {item}
              </Link>
            ))}
          </nav>
          <button className="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
            Znajdź specjalistę
          </button>
        </div>
      </header>

      <main>
        <section className="mx-auto grid max-w-7xl gap-10 px-4 py-12 md:px-8 md:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
          <div className="space-y-8">
            <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1 text-xs font-semibold text-primary">
              Premium platforma decyzji
            </div>
            <div className="space-y-5">
              <h1 className="text-4xl font-semibold leading-tight tracking-tight md:text-6xl">Znajdź odpowiedź od specjalisty.</h1>
              <p className="max-w-2xl text-base text-slate-600 md:text-xl">
                Poradniki, rankingi, kalkulatory i eksperci w jednym miejscu.
              </p>
            </div>
            <Panel className="space-y-4">
              <form className="grid gap-3 md:grid-cols-[1.5fr_1fr_auto]" aria-label="Wyszukiwarka poradnik.pro">
                <label className="sr-only" htmlFor="query">
                  Czego szukasz?
                </label>
                <input
                  id="query"
                  type="text"
                  placeholder="Czego szukasz?"
                  className="h-12 rounded-xl border border-border bg-white px-4 text-sm outline-none ring-primary transition focus:ring-2"
                />
                <label className="sr-only" htmlFor="category">
                  Kategoria
                </label>
                <select
                  id="category"
                  className="h-12 rounded-xl border border-border bg-white px-4 text-sm text-slate-600 outline-none ring-primary transition focus:ring-2"
                  defaultValue=""
                >
                  <option value="" disabled>
                    Kategoria
                  </option>
                  {categories.map((category) => (
                    <option key={category.name} value={category.name}>
                      {category.name}
                    </option>
                  ))}
                </select>
                <button className="h-12 rounded-xl bg-primary px-5 text-sm font-semibold text-white transition hover:bg-[#4338CA]">
                  Szukaj
                </button>
              </form>
            </Panel>
          </div>

          <Panel className="grid grid-cols-2 gap-4">
            {stats.map((stat) => (
              <div key={stat.label} className="rounded-2xl border border-border bg-white p-4 text-center">
                <p className="text-2xl font-semibold text-slate-900 md:text-3xl">{stat.value}</p>
                <p className="mt-1 text-sm text-slate-600">{stat.label}</p>
              </div>
            ))}
          </Panel>
        </section>

        <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
          <SectionTitle
            eyebrow="Kategorie"
            title="Ekspercka wiedza i specjaliści w 8 kluczowych obszarach"
            subtitle="Każda kategoria łączy poradniki, rankingi i dostęp do zweryfikowanych ekspertów."
          />
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {categories.map((category) => (
              <Panel key={category.name} className="space-y-3">
                <p className="text-2xl" aria-hidden="true">
                  {category.icon}
                </p>
                <h3 className="text-lg font-semibold text-slate-900">{category.name}</h3>
                <p className="text-sm text-slate-600">{category.guides} poradników</p>
                <p className="text-sm text-slate-600">{category.experts} ekspertów</p>
              </Panel>
            ))}
          </div>
        </section>

        <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
          <SectionTitle eyebrow="Specjaliści" title="Polecani eksperci" />
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {experts.map((expert, index) => (
              <Panel key={expert.name} className="space-y-4">
                <div className="h-40 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100" aria-hidden="true" />
                <div>
                  <h3 className="text-lg font-semibold">{expert.name}</h3>
                  <p className="text-sm text-slate-600">{expert.specialization}</p>
                </div>
                <p className="text-sm font-medium text-emerald-600">
                  ★ {expert.rating} · {expert.reviews}
                </p>
                <button className="w-full rounded-xl border border-border px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                  Zobacz profil
                </button>
              </Panel>
            ))}
          </div>
        </section>

        <section className="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:px-8 lg:grid-cols-2">
          <Panel className="space-y-5">
            <SectionTitle eyebrow="Q&A Engine" title="Ostatnie pytania użytkowników" />
            <div className="space-y-3">
              {questions.map((question) => (
                <article key={question.title} className="rounded-2xl border border-border bg-white p-4">
                  <h3 className="font-semibold">{question.title}</h3>
                  <p className="mt-1 text-sm text-slate-600">{question.answers}</p>
                </article>
              ))}
            </div>
            <button className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Zobacz wszystkie pytania</button>
          </Panel>

          <Panel className="space-y-5">
            <SectionTitle eyebrow="Rankingi" title="Najczęściej czytane rankingi" />
            <div className="space-y-3">
              {rankings.map((ranking) => (
                <article key={ranking} className="rounded-2xl border border-border bg-white p-4">
                  <h3 className="font-semibold">{ranking}</h3>
                  <p className="mt-1 text-sm text-slate-600">Aktualizacja: 14.06.2026 · 18 240 odsłon</p>
                </article>
              ))}
            </div>
          </Panel>
        </section>

        <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
          <SectionTitle eyebrow="Kalkulatory" title="Narzędzia decyzji finansowych i życiowych" />
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            {calculators.map((calculator) => (
              <Panel key={calculator} className="text-center">
                <h3 className="text-base font-semibold">{calculator}</h3>
              </Panel>
            ))}
          </div>
        </section>

        <section className="mx-auto max-w-7xl px-4 py-12 md:px-8">
          <Panel className="grid gap-6 md:grid-cols-[1.2fr_0.8fr] md:items-center">
            <div className="space-y-3">
              <SectionTitle eyebrow="Opinie" title="Realne opinie użytkowników" />
              <p className="text-sm text-slate-600">Ocena platformy: 4.9/5 na podstawie zweryfikowanych opinii użytkowników.</p>
            </div>
            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
              <p className="text-sm font-medium uppercase tracking-[0.12em] text-emerald-600">Ocena platformy</p>
              <p className="mt-2 text-5xl font-semibold text-emerald-600">4.9/5</p>
            </div>
          </Panel>
        </section>

        <section className="mx-auto max-w-7xl px-4 py-12 md:px-8">
          <Panel className="space-y-6 bg-slate-900 text-white">
            <SectionTitle
              eyebrow="Lead Engine"
              title="Potrzebujesz pomocy specjalisty?"
              subtitle="Wypełnij formularz i otrzymaj dopasowane oferty od ekspertów z Twojej okolicy."
              inverted
            />
            <form className="grid gap-3 md:grid-cols-3" aria-label="Formularz lead generation">
              <label className="sr-only" htmlFor="legacy-lead-branza">Branża</label>
              <input
                id="legacy-lead-branza"
                type="text"
                placeholder="Branża"
                className="h-12 rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-slate-300"
              />
              <label className="sr-only" htmlFor="legacy-lead-lokalizacja">Lokalizacja</label>
              <input
                id="legacy-lead-lokalizacja"
                type="text"
                placeholder="Lokalizacja"
                className="h-12 rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-slate-300"
              />
              <label className="sr-only" htmlFor="legacy-lead-problem">Opis problemu</label>
              <textarea
                id="legacy-lead-problem"
                placeholder="Opis problemu"
                className="min-h-[48px] rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-300 md:col-span-2"
              />
              <button className="h-12 rounded-xl bg-success px-5 text-sm font-semibold text-white transition hover:bg-[#059669] md:col-span-1">
                Znajdź eksperta
              </button>
            </form>
          </Panel>
        </section>

        <section className="mx-auto max-w-7xl space-y-6 px-4 py-12 md:px-8">
          <SectionTitle eyebrow="SEO Engine" title="Najpopularniejsze wyszukiwania" />
          <div className="flex flex-wrap gap-3">
            {topSearches.map((search) => (
              <Link
                key={search}
                href="#"
                className="rounded-full border border-border bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-primary/40 hover:text-primary"
              >
                {search}
              </Link>
            ))}
          </div>
        </section>
      </main>

      <footer className="border-t border-border bg-white">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 text-sm text-slate-600 md:grid-cols-5 md:px-8">
          {["Poradniki", "Rankingi", "Kalkulatory", "Specjaliści", "Firma"].map((column) => (
            <div key={column} className="space-y-2">
              <h3 className="font-semibold text-slate-900">{column}</h3>
              <ul className="space-y-1">
                <li>
                  <Link href="#">Przykładowa sekcja</Link>
                </li>
                <li>
                  <Link href="#">Zobacz więcej</Link>
                </li>
              </ul>
            </div>
          ))}
        </div>
        <div className="mx-auto flex max-w-7xl flex-col gap-2 border-t border-border px-4 py-5 text-xs text-slate-500 md:flex-row md:items-center md:justify-between md:px-8">
          <p>© {new Date().getFullYear()} Poradnik.pro</p>
          <div className="flex gap-4">
            <Link href="#">Regulamin</Link>
            <Link href="#">Polityka prywatności</Link>
            <Link href="#">Kontakt</Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
