import { Card } from "@/components/ui/card";
import { SectionHeader } from "./section-header";

const rankings = [
  { title: "Najlepsze konta osobiste 2026", views: "21 340", updated: "14.06.2026" },
  { title: "Najlepsze pompy ciepła 2026", views: "18 240", updated: "12.06.2026" },
  { title: "Najlepsze kredyty hipoteczne 2026", views: "32 410", updated: "13.06.2026" },
  { title: "Najlepsze programy księgowe", views: "12 890", updated: "10.06.2026" },
];

export function RankingsSection() {
  return (
    <Card className="space-y-5">
      <SectionHeader eyebrow="Rankingi" title="Najczęściej czytane rankingi" />

      <div className="space-y-3">
        {rankings.map((ranking) => (
          <article
            key={ranking.title}
            className="rounded-2xl border border-border bg-white p-4 transition hover:border-primary/30"
          >
            <h3 className="font-semibold">{ranking.title}</h3>
            <p className="mt-1 text-sm text-slate-600">
              Aktualizacja: {ranking.updated} · {ranking.views} odsłon
            </p>
          </article>
        ))}
      </div>
    </Card>
  );
}
