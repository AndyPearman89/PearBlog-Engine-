import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { SectionHeader } from "./section-header";

const experts = [
  {
    name: "Anna Kowalska",
    specialization: "Radca prawny",
    rating: 4.9,
    reviews: 281,
  },
  {
    name: "Michał Nowak",
    specialization: "Doradca kredytowy",
    rating: 4.8,
    reviews: 197,
  },
  {
    name: "Katarzyna Wiśniewska",
    specialization: "Architekt",
    rating: 4.9,
    reviews: 154,
  },
  {
    name: "Paweł Zieliński",
    specialization: "Księgowy",
    rating: 4.9,
    reviews: 223,
  },
];

export function ExpertsSection() {
  return (
    <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
      <SectionHeader eyebrow="Specjaliści" title="Polecani eksperci" />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {experts.map((expert) => (
          <Card key={expert.name} className="space-y-4">
            {/* Placeholder for expert photo */}
            <div
              className="h-40 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100"
              aria-hidden="true"
            />
            <div>
              <h3 className="text-lg font-semibold">{expert.name}</h3>
              <p className="text-sm text-slate-600">{expert.specialization}</p>
            </div>
            <p className="text-sm font-medium text-emerald-600">
              ★ {expert.rating} · {expert.reviews} opinii
            </p>
            <Button variant="outline" className="w-full">
              Zobacz profil
            </Button>
          </Card>
        ))}
      </div>
    </section>
  );
}
