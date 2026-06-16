import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { SectionHeader } from "./section-header";

const questions = [
  {
    title: "Czy mogę wybudować dom na działce rolnej?",
    answers: 4,
  },
  {
    title: "Jak obniżyć ratę kredytu?",
    answers: 7,
  },
  {
    title: "Jaka forma działalności jest najlepsza dla freelancera?",
    answers: 5,
  },
  {
    title: "Czy warto kupić auto elektryczne w 2026?",
    answers: 8,
  },
];

export function QASection() {
  return (
    <Card className="space-y-5">
      <SectionHeader eyebrow="Q&A Engine" title="Ostatnie pytania użytkowników" />

      <div className="space-y-3">
        {questions.map((question) => (
          <article
            key={question.title}
            className="rounded-2xl border border-border bg-white p-4 transition hover:border-primary/30"
          >
            <h3 className="font-semibold">{question.title}</h3>
            <p className="mt-1 text-sm text-slate-600">
              {question.answers} odpowiedzi ekspertów
            </p>
          </article>
        ))}
      </div>

      <Button variant="dark">Zobacz wszystkie pytania</Button>
    </Card>
  );
}
