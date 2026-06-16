import {
  Header,
  HeroSection,
  CategoriesSection,
  ExpertsSection,
  QASection,
  RankingsSection,
  CalculatorsSection,
  ReviewsSection,
  LeadEngineSection,
  SEOSection,
  Footer,
} from "@/components/homepage";

export default function Page() {
  return (
    <div className="min-h-screen bg-background text-slate-900">
      <Header />

      <main>
        <HeroSection />
        <CategoriesSection />
        <ExpertsSection />

        {/* Q&A + Rankings side-by-side on desktop */}
        <section className="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:px-8 lg:grid-cols-2">
          <QASection />
          <RankingsSection />
        </section>

        <CalculatorsSection />
        <ReviewsSection />
        <LeadEngineSection />
        <SEOSection />
      </main>

      <Footer />
    </div>
  );
}
