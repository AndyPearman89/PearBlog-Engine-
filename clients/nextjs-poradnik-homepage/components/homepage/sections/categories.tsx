import { Card } from "@/components/ui/card";
import { SectionHeader } from "./section-header";

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

export function CategoriesSection() {
  return (
    <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
      <SectionHeader
        eyebrow="Kategorie"
        title="Ekspercka wiedza i specjaliści w 8 kluczowych obszarach"
        subtitle="Każda kategoria łączy poradniki, rankingi i dostęp do zweryfikowanych ekspertów."
      />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {categories.map((category) => (
          <Card key={category.name} className="group cursor-pointer space-y-3 transition hover:-translate-y-0.5">
            <p className="text-3xl" aria-hidden="true">
              {category.icon}
            </p>
            <h3 className="text-lg font-semibold text-slate-900">{category.name}</h3>
            <div className="space-y-1 text-sm text-slate-600">
              <p>{category.guides} poradników</p>
              <p>{category.experts} ekspertów</p>
            </div>
          </Card>
        ))}
      </div>
    </section>
  );
}
