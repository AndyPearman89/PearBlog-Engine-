import Link from "next/link";
import { SectionHeader } from "./section-header";

const topSearches = [
  "Prawo Warszawa",
  "Kredyt Kraków",
  "Architekt Katowice",
  "Księgowy Wrocław",
  "Prawnik Poznań",
  "Doradca finansowy Gdańsk",
  "Mechanik Łódź",
  "Lekarz Wrocław",
];

export function SEOSection() {
  return (
    <section className="mx-auto max-w-7xl space-y-6 px-4 py-12 md:px-8">
      <SectionHeader eyebrow="SEO Engine" title="Najpopularniejsze wyszukiwania" />

      <div className="flex flex-wrap gap-3">
        {topSearches.map((search) => (
          <Link
            key={search}
            href={`/szukaj/${search.toLowerCase().replace(/ /g, "-")}`}
            className="rounded-full border border-border bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-primary/40 hover:text-primary"
          >
            {search}
          </Link>
        ))}
      </div>
    </section>
  );
}
