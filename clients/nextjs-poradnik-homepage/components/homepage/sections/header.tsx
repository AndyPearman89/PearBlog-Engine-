"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X } from "lucide-react";
import { Button } from "@/components/ui/button";

const navItems = [
  { label: "Poradniki", href: "/poradniki" },
  { label: "Rankingi", href: "/rankingi" },
  { label: "Kalkulatory", href: "/kalkulatory" },
  { label: "Pytania i Odpowiedzi", href: "/pytania" },
  { label: "Specjaliści", href: "/specjalisci" },
  { label: "Kontakt", href: "/kontakt" },
];

export function Header() {
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 border-b border-border/80 bg-white/90 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-8">
        <Link href="/" className="text-lg font-bold tracking-tight text-slate-900">
          Poradnik<span className="text-primary">.pro</span>
        </Link>

        {/* Desktop nav */}
        <nav className="hidden items-center gap-5 lg:flex" aria-label="Główna nawigacja">
          {navItems.map((item) => (
            <Link
              key={item.label}
              href={item.href}
              className="text-sm font-medium text-slate-600 transition hover:text-slate-900"
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-3">
          <Button size="sm" className="hidden sm:inline-flex" asChild>
            <Link href="/znajdz-specjaliste">Znajdź specjalistę</Link>
          </Button>

          {/* Mobile toggle */}
          <button
            className="flex h-10 w-10 items-center justify-center rounded-xl text-slate-700 lg:hidden"
            onClick={() => setMobileOpen(!mobileOpen)}
            aria-expanded={mobileOpen}
            aria-label="Menu mobilne"
          >
            {mobileOpen ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>
      </div>

      {/* Mobile menu */}
      {mobileOpen && (
        <nav className="border-t border-border bg-white px-4 py-4 lg:hidden" aria-label="Menu mobilne">
          <ul className="space-y-1">
            {navItems.map((item) => (
              <li key={item.label}>
                <Link
                  href={item.href}
                  className="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  onClick={() => setMobileOpen(false)}
                >
                  {item.label}
                </Link>
              </li>
            ))}
            <li className="pt-2">
              <Button className="w-full" asChild>
                <Link href="/znajdz-specjaliste">Znajdź specjalistę</Link>
              </Button>
            </li>
          </ul>
        </nav>
      )}
    </header>
  );
}
