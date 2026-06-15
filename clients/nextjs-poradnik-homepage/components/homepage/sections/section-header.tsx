import { cn } from "@/lib/utils";

interface SectionHeaderProps {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  inverted?: boolean;
  className?: string;
}

export function SectionHeader({
  eyebrow,
  title,
  subtitle,
  inverted = false,
  className,
}: SectionHeaderProps) {
  return (
    <header className={cn("space-y-3 text-center md:text-left", className)}>
      {eyebrow ? (
        <p
          className={cn(
            "text-sm font-semibold uppercase tracking-[0.16em]",
            inverted ? "text-indigo-200" : "text-primary"
          )}
        >
          {eyebrow}
        </p>
      ) : null}
      <h2
        className={cn(
          "text-2xl font-semibold tracking-tight md:text-4xl",
          inverted ? "text-white" : "text-slate-900"
        )}
      >
        {title}
      </h2>
      {subtitle ? (
        <p
          className={cn(
            "max-w-3xl text-sm md:text-base",
            inverted ? "text-slate-200" : "text-slate-600"
          )}
        >
          {subtitle}
        </p>
      ) : null}
    </header>
  );
}
