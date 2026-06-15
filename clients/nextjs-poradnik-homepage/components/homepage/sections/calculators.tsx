import { Card } from "@/components/ui/card";
import { SectionHeader } from "./section-header";

const calculators = [
  { name: "Kredyt hipoteczny", icon: "🏠" },
  { name: "Zdolność kredytowa", icon: "📊" },
  { name: "Koszt budowy domu", icon: "🧱" },
  { name: "Kalkulator OC", icon: "🚗" },
  { name: "Kalkulator wynagrodzeń", icon: "💰" },
];

export function CalculatorsSection() {
  return (
    <section className="mx-auto max-w-7xl space-y-8 px-4 py-12 md:px-8">
      <SectionHeader eyebrow="Kalkulatory" title="Narzędzia decyzji finansowych i życiowych" />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        {calculators.map((calculator) => (
          <Card
            key={calculator.name}
            className="cursor-pointer space-y-2 text-center transition hover:-translate-y-0.5"
          >
            <p className="text-2xl" aria-hidden="true">
              {calculator.icon}
            </p>
            <h3 className="text-base font-semibold">{calculator.name}</h3>
          </Card>
        ))}
      </div>
    </section>
  );
}
