import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";

const stats = [
  { value: "120 000+", label: "odpowiedzi" },
  { value: "8500+", label: "specjalistów" },
  { value: "1200+", label: "rankingów" },
  { value: "50+", label: "kalkulatorów" },
];

const categories = [
  "Prawo",
  "Finanse",
  "Nieruchomości",
  "Budowa domu",
  "Motoryzacja",
  "Zdrowie",
  "Biznes",
  "Technologia",
];

export function HeroSection() {
  return (
    <section className="mx-auto grid max-w-7xl gap-10 px-4 py-12 md:px-8 md:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
      <div className="space-y-8">
        <Badge>Premium platforma decyzji</Badge>

        <div className="space-y-5">
          <h1 className="text-4xl font-semibold leading-tight tracking-tight md:text-6xl">
            Znajdź odpowiedź od specjalisty.
          </h1>
          <p className="max-w-2xl text-base text-slate-600 md:text-xl">
            Poradniki, rankingi, kalkulatory i eksperci w jednym miejscu.
          </p>
        </div>

        <Card className="space-y-4">
          <form
            className="grid gap-3 md:grid-cols-[1.5fr_1fr_auto]"
            aria-label="Wyszukiwarka poradnik.pro"
          >
            <label className="sr-only" htmlFor="hero-query">
              Czego szukasz?
            </label>
            <Input id="hero-query" placeholder="Czego szukasz?" />

            <label className="sr-only" htmlFor="hero-category">
              Kategoria
            </label>
            <Select id="hero-category" defaultValue="">
              <option value="" disabled>
                Kategoria
              </option>
              {categories.map((cat) => (
                <option key={cat} value={cat}>
                  {cat}
                </option>
              ))}
            </Select>

            <Button type="submit">Szukaj</Button>
          </form>
        </Card>
      </div>

      <Card className="grid grid-cols-2 gap-4">
        {stats.map((stat) => (
          <div
            key={stat.label}
            className="rounded-2xl border border-border bg-white p-4 text-center"
          >
            <p className="text-2xl font-semibold text-slate-900 md:text-3xl">{stat.value}</p>
            <p className="mt-1 text-sm text-slate-600">{stat.label}</p>
          </div>
        ))}
      </Card>
    </section>
  );
}
