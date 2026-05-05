#!/usr/bin/env python3
"""
PT24 Landing Page Generator
Generates local SEO landing pages using OpenAI API

Usage:
    export OPENAI_API_KEY="sk-..."
    python3 generate-pt24-pages.py

Requirements:
    pip install openai
"""

import csv
import os
import sys
import time
from datetime import datetime

try:
    from openai import OpenAI
except ImportError:
    print("❌ Error: openai package not installed")
    print("Install with: pip install openai")
    sys.exit(1)

# Configuration
INPUT_CSV = "pt24-landings-100.csv"
OUTPUT_CSV = "pt24-landings-with-content.csv"
OPENAI_MODEL = "gpt-4o-mini"
RATE_LIMIT_DELAY = 1.0  # seconds between requests

# Check API key
API_KEY = os.getenv('OPENAI_API_KEY')
if not API_KEY:
    print("❌ Error: OPENAI_API_KEY environment variable not set")
    print("Set with: export OPENAI_API_KEY='sk-...'")
    sys.exit(1)

client = OpenAI(api_key=API_KEY)

def generate_prompt(service_name, city_name):
    """Generate AI prompt for landing page content"""
    return f"""Napisz stronę usług lokalnych dla frazy: {service_name} {city_name}

Wymagania:
- Minimum 600 słów
- Nagłówek H1: "{service_name} {city_name}"
- Wprowadzenie (2-3 zdania)
- Sekcja "Najczęstsze problemy" (3-4 problemy w <ul>)
- Sekcja "Usługi" - lista 5-7 usług w <ul>
- Sekcja "Dlaczego warto" - 4 powody w <ul>
- CTA z numerem telefonu w <div class="pt24-cta">
- FAQ - 3 pytania z odpowiedziami (każde jako <h3> + <p>)
- Naturalne użycie miasta w tekście (minimum 5 razy)
- Styl: prosty, konkretny, jak lokalny fachowiec
- Bez sprzedażowego gadania, konkretne informacje

Format:
- Zwróć czysty HTML bez tagów <html>, <body>, <head>
- Tylko treść do wklejenia w WordPress
- Używaj <h1>, <h2>, <h3>, <p>, <ul>, <li>, <div>
- W CTA użyj: <a href="tel:+48123456789" class="pt24-phone-btn">📞 +48 123 456 789</a>

Przykład struktury:
<h1>{service_name} {city_name}</h1>
<div class="intro">
<p>Wprowadzenie...</p>
</div>
<h2>Najczęstsze problemy</h2>
<ul><li>Problem 1</li>...</ul>
<h2>Usługi</h2>
<ul><li>Usługa 1</li>...</ul>
<h2>Dlaczego warto</h2>
<ul><li>Powód 1</li>...</ul>
<div class="pt24-cta">
<p><strong>Potrzebujesz pomocy? Zadzwoń:</strong></p>
<a href="tel:+48123456789" class="pt24-phone-btn">📞 +48 123 456 789</a>
</div>
<h2>FAQ</h2>
<h3>Pytanie 1?</h3>
<p>Odpowiedź...</p>
"""

def generate_content(service, city, service_name, city_name):
    """Generate landing page content using OpenAI"""
    prompt = generate_prompt(service_name, city_name)

    try:
        response = client.chat.completions.create(
            model=OPENAI_MODEL,
            messages=[
                {"role": "system", "content": "Jesteś ekspertem w pisaniu lokalnych stron SEO dla usług. Piszesz konkretnie, bez lania wody, jak prawdziwy fachowiec."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7,
            max_tokens=2500
        )

        content = response.choices[0].message.content.strip()

        # Clean up common issues
        content = content.replace('```html', '').replace('```', '')

        return content

    except Exception as e:
        print(f"❌ Error generating content: {str(e)}")
        return None

def main():
    """Main generation process"""
    print("🚀 PT24 Landing Page Generator")
    print("=" * 50)
    print()

    # Check if input file exists
    if not os.path.exists(INPUT_CSV):
        print(f"❌ Error: {INPUT_CSV} not found")
        print("Run: ./scripts/generate-pt24-csv.sh first")
        sys.exit(1)

    # Read input CSV
    print(f"📖 Reading {INPUT_CSV}...")
    with open(INPUT_CSV, 'r', encoding='utf-8') as infile:
        reader = list(csv.DictReader(infile))
        total = len(reader)

    print(f"✓ Found {total} landing pages to generate")
    print()

    # Confirm
    response = input(f"Generate content for {total} pages? (y/n): ")
    if response.lower() != 'y':
        print("Aborted.")
        sys.exit(0)

    print()
    print("🤖 Generating content with OpenAI...")
    print("-" * 50)

    # Open output CSV
    with open(OUTPUT_CSV, 'w', encoding='utf-8', newline='') as outfile:
        fieldnames = ['service', 'city', 'service_name', 'city_name', 'title', 'slug', 'content']
        writer = csv.DictWriter(outfile, fieldnames=fieldnames)
        writer.writeheader()

        start_time = time.time()
        success_count = 0
        error_count = 0

        for i, row in enumerate(reader, 1):
            service = row['service']
            city = row['city']
            service_name = row['service_name']
            city_name = row['city_name']

            print(f"[{i}/{total}] {service_name} {city_name}...", end=' ', flush=True)

            content = generate_content(service, city, service_name, city_name)

            if content:
                writer.writerow({
                    'service': service,
                    'city': city,
                    'service_name': service_name,
                    'city_name': city_name,
                    'title': f"{service_name} {city_name}",
                    'slug': f"{service}-{city}",
                    'content': content
                })
                print("✓")
                success_count += 1
            else:
                print("✗ (error)")
                error_count += 1

            # Rate limiting
            if i < total:
                time.sleep(RATE_LIMIT_DELAY)

            # Progress update every 10 pages
            if i % 10 == 0:
                elapsed = time.time() - start_time
                avg_time = elapsed / i
                remaining = (total - i) * avg_time
                print(f"   Progress: {i}/{total} | Elapsed: {elapsed:.1f}s | ETA: {remaining:.1f}s")

    # Summary
    elapsed_total = time.time() - start_time
    print()
    print("=" * 50)
    print("✅ Generation complete!")
    print()
    print(f"Total pages: {total}")
    print(f"Success: {success_count}")
    print(f"Errors: {error_count}")
    print(f"Time: {elapsed_total:.1f}s ({elapsed_total/60:.1f} minutes)")
    print(f"Output: {OUTPUT_CSV}")
    print()
    print("Next steps:")
    print("  1. Review generated content in CSV")
    print("  2. Import to WordPress with WP All Import plugin")
    print("  3. Or use: wp pt24 import-csv " + OUTPUT_CSV)
    print()

if __name__ == "__main__":
    main()
