import type { Metadata } from "next";
import type { ReactNode } from "react";
import "./globals.css";

export const metadata: Metadata = {
  title: "Poradnik.pro — eksperci, rankingi, kalkulatory i odpowiedzi",
  description:
    "Znajdź odpowiedź od specjalisty. Poradniki, rankingi, kalkulatory i eksperci w jednym miejscu.",
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="pl">
      <body>{children}</body>
    </html>
  );
}
