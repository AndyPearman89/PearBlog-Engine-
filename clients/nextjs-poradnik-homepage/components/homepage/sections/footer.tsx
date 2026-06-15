import Link from "next/link";

const footerColumns = [
  {
    title: "Poradniki",
    links: ["Prawo", "Finanse", "Nieruchomości", "Zdrowie", "Biznes"],
  },
  {
    title: "Rankingi",
    links: ["Konta bankowe", "Kredyty", "Ubezpieczenia", "Programy", "Pompy ciepła"],
  },
  {
    title: "Kalkulatory",
    links: ["Kredyt hipoteczny", "Zdolność kredytowa", "OC/AC", "Wynagrodzenia"],
  },
  {
    title: "Specjaliści",
    links: ["Prawnicy", "Księgowi", "Architekci", "Doradcy", "Lekarze"],
  },
  {
    title: "Firma",
    links: ["O nas", "Dla specjalistów", "Blog", "Praca", "Kontakt"],
  },
];

export function Footer() {
  return (
    <footer className="border-t border-border bg-white">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 text-sm text-slate-600 md:grid-cols-5 md:px-8">
        {footerColumns.map((column) => (
          <div key={column.title} className="space-y-3">
            <h3 className="font-semibold text-slate-900">{column.title}</h3>
            <ul className="space-y-1.5">
              {column.links.map((link) => (
                <li key={link}>
                  <Link href="#" className="transition hover:text-primary">
                    {link}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="mx-auto flex max-w-7xl flex-col gap-2 border-t border-border px-4 py-5 text-xs text-slate-500 md:flex-row md:items-center md:justify-between md:px-8">
        <p>© {new Date().getFullYear()} Poradnik.pro — Platforma decyzji i ekspertów</p>
        <div className="flex gap-4">
          <Link href="/regulamin" className="transition hover:text-primary">
            Regulamin
          </Link>
          <Link href="/polityka-prywatnosci" className="transition hover:text-primary">
            Polityka prywatności
          </Link>
          <Link href="/kontakt" className="transition hover:text-primary">
            Kontakt
          </Link>
        </div>
      </div>
    </footer>
  );
}
