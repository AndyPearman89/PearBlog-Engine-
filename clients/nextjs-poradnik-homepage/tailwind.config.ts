import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./app/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: "#4F46E5",
          hover: "#4338CA",
          light: "#EEF2FF",
        },
        secondary: {
          DEFAULT: "#7C3AED",
          hover: "#6D28D9",
        },
        success: {
          DEFAULT: "#10B981",
          hover: "#059669",
        },
        dark: "#0F172A",
        background: "#F8FAFC",
        border: "#E2E8F0",
      },
      borderRadius: {
        "2xl": "20px",
        xl: "16px",
        lg: "12px",
      },
      boxShadow: {
        card: "0 10px 30px rgba(15, 23, 42, 0.06)",
        "card-hover": "0 20px 40px rgba(15, 23, 42, 0.10)",
      },
      fontFamily: {
        sans: [
          "Inter",
          "-apple-system",
          "BlinkMacSystemFont",
          "Segoe UI",
          "sans-serif",
        ],
      },
      maxWidth: {
        "7xl": "1280px",
      },
    },
  },
  plugins: [],
};

export default config;
