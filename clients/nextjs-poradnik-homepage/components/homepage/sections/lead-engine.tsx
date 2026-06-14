import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { SectionHeader } from "./section-header";

export function LeadEngineSection() {
  return (
    <section className="mx-auto max-w-7xl px-4 py-12 md:px-8">
      <Card className="space-y-6 bg-dark text-white">
        <SectionHeader
          eyebrow="Lead Engine"
          title="Potrzebujesz pomocy specjalisty?"
          subtitle="Wypełnij formularz i otrzymaj dopasowane oferty od ekspertów z Twojej okolicy."
          inverted
        />

        <form
          className="grid gap-3 md:grid-cols-3"
          aria-label="Formularz lead generation"
        >
          <input
            type="text"
            placeholder="Branża"
            className="h-12 rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary"
          />
          <input
            type="text"
            placeholder="Lokalizacja"
            className="h-12 rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary"
          />
          <textarea
            placeholder="Opis problemu"
            className="min-h-[48px] rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary md:col-span-2"
          />
          <Button variant="success" type="submit">
            Znajdź eksperta
          </Button>
        </form>
      </Card>
    </section>
  );
}
