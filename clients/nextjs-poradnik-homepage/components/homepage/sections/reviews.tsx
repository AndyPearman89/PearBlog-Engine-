import { Card } from "@/components/ui/card";
import { SectionHeader } from "./section-header";

export function ReviewsSection() {
  return (
    <section className="mx-auto max-w-7xl px-4 py-12 md:px-8">
      <Card className="grid gap-6 md:grid-cols-[1.2fr_0.8fr] md:items-center">
        <div className="space-y-3">
          <SectionHeader eyebrow="Opinie" title="Realne opinie użytkowników" />
          <p className="text-sm text-slate-600">
            Ocena platformy: 4.9/5 na podstawie zweryfikowanych opinii użytkowników.
          </p>
        </div>
        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
          <p className="text-sm font-medium uppercase tracking-[0.12em] text-emerald-600">
            Ocena platformy
          </p>
          <p className="mt-2 text-5xl font-semibold text-emerald-600">4.9/5</p>
          <p className="mt-1 text-sm text-emerald-700">na podstawie 2 841 opinii</p>
        </div>
      </Card>
    </section>
  );
}
