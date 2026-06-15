import { cn } from "@/lib/utils";
import type { HTMLAttributes } from "react";

interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  variant?: "default" | "success" | "secondary";
}

export function Badge({ className, variant = "default", ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold",
        {
          "border border-primary/20 bg-primary/5 text-primary": variant === "default",
          "border border-emerald-200 bg-emerald-50 text-emerald-700": variant === "success",
          "border border-secondary/20 bg-secondary/5 text-secondary": variant === "secondary",
        },
        className
      )}
      {...props}
    />
  );
}
