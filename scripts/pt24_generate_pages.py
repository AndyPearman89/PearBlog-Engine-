#!/usr/bin/env python3
"""
PT24.PRO - Automated Local Services Page Generator

Generates local service pages using OpenAI API and WordPress WP-CLI.
Example: mechanik + warszawa = page content
"""

import os
import sys
import csv
import time
import subprocess
import argparse
from typing import List, Dict, Optional

try:
    import openai
except ImportError:
    print("Error: openai package not installed")
    print("Install: pip3 install openai")
    sys.exit(1)


class PT24ContentGenerator:
    """Generates content for PT24 local services platform"""

    def __init__(self, api_key: str, wp_path: str = '/var/www/pt24.pro'):
        """
        Initialize generator

        Args:
            api_key: OpenAI API key
            wp_path: Path to WordPress installation
        """
        self.client = openai.OpenAI(api_key=api_key)
        self.wp_path = wp_path
        self.model = "gpt-4o-mini"  # Cost-effective model

    def generate_local_page_content(self, category: str, city: str) -> str:
        """
        Generate content for a local service page

        Args:
            category: Service category (e.g., 'mechanik')
            city: City name (e.g., 'warszawa')

        Returns:
            HTML content for the page
        """
        prompt = f"""Napisz kompletną stronę usług lokalnych dla frazy:

{category} {city}

WYMAGANIA:

1. STRUKTURA:
   - H1: {{Usługa}} {{Miasto}}
   - Wprowadzenie (2-3 zdania, problem klienta)
   - H2: Najczęstsze problemy w {{Miasto}}
   - Lista 4-5 typowych problemów
   - H2: Usługi {{Usługa}} w {{Miasto}}
   - Lista 5-6 usług
   - H2: Dlaczego lokalny {{Usługa}}
   - 3 korzyści (checkmarki)
   - FAQ (3 pytania)

2. TON:
   - Bezpośredni, jak lokalny fachowiec
   - Bez lania wody
   - Konkretnie
   - Bez generycznych fraz ("najwyższa jakość")

3. DŁUGOŚĆ: 500-700 słów

4. SEO:
   - Naturalne użycie miasta (4-6 razy)
   - Naturalne użycie usługi (5-8 razy)
   - Bez keyword stuffing
   - Lokalne konteksty (dzielnice, okolice)

5. FORMAT: HTML z tagami (h1, h2, p, ul, li)

GENERUJ TERAZ dla: {category} {city}"""

        try:
            response = self.client.chat.completions.create(
                model=self.model,
                messages=[
                    {"role": "system", "content": "Jesteś ekspertem w tworzeniu treści SEO dla lokalnych usług w Polsce."},
                    {"role": "user", "content": prompt}
                ],
                max_tokens=1500,
                temperature=0.7
            )

            return response.choices[0].message.content

        except Exception as e:
            print(f"Error generating content: {e}")
            return None

    def generate_meta_tags(self, category: str, city: str) -> Dict[str, str]:
        """Generate SEO meta title and description"""

        prompt = f"""Wygeneruj SEO meta title i meta description dla strony: {category} {city}

WYMAGANIA:
1. TITLE: Max 60 znaków, Format: {{Usługa}} {{Miasto}} - Sprawdzeni fachowcy | PT24.pro
2. DESCRIPTION: Max 160 znaków, Zawiera miasto i usługę, Wezwanie do działania, 2-3 korzyści

Odpowiedź w formacie:
TITLE: [title]
DESCRIPTION: [description]"""

        try:
            response = self.client.chat.completions.create(
                model=self.model,
                messages=[{"role": "user", "content": prompt}],
                max_tokens=200,
                temperature=0.5
            )

            content = response.choices[0].message.content
            lines = content.strip().split('\n')

            title = ""
            description = ""

            for line in lines:
                if line.startswith('TITLE:'):
                    title = line.replace('TITLE:', '').strip()
                elif line.startswith('DESCRIPTION:'):
                    description = line.replace('DESCRIPTION:', '').strip()

            return {
                'title': title,
                'description': description
            }

        except Exception as e:
            print(f"Error generating meta tags: {e}")
            return {'title': '', 'description': ''}

    def create_wordpress_post(self, category: str, city: str, content: str, meta: Dict[str, str]) -> Optional[int]:
        """
        Create WordPress post using WP-CLI

        Returns:
            Post ID or None if failed
        """
        title = f"{category.capitalize()} {city.capitalize()}"

        # Escape content for shell
        content_escaped = content.replace('"', '\\"').replace('$', '\\$')

        # Create post
        cmd = [
            'wp', 'post', 'create',
            '--post_type=pt24_local',
            f'--post_title={title}',
            f'--post_content={content_escaped}',
            '--post_status=publish',
            '--allow-root',
            '--path=' + self.wp_path,
            '--porcelain'  # Returns only post ID
        ]

        try:
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            post_id = int(result.stdout.strip())

            # Set meta fields
            self._set_post_meta(post_id, 'pt24_local_category', category)
            self._set_post_meta(post_id, 'pt24_local_city', city)
            self._set_post_meta(post_id, 'pt24_local_generated', 'true')

            if meta.get('title'):
                self._set_post_meta(post_id, '_yoast_wpseo_title', meta['title'])
            if meta.get('description'):
                self._set_post_meta(post_id, '_yoast_wpseo_metadesc', meta['description'])

            # Set taxonomy terms
            self._set_taxonomy(post_id, 'pt24_service_cat', category)
            self._set_taxonomy(post_id, 'pt24_city', city)

            return post_id

        except subprocess.CalledProcessError as e:
            print(f"Error creating post: {e}")
            print(f"Output: {e.output}")
            return None

    def _set_post_meta(self, post_id: int, key: str, value: str):
        """Set post meta field"""
        cmd = [
            'wp', 'post', 'meta', 'set',
            str(post_id), key, value,
            '--allow-root',
            '--path=' + self.wp_path
        ]
        subprocess.run(cmd, check=True, capture_output=True)

    def _set_taxonomy(self, post_id: int, taxonomy: str, term: str):
        """Set taxonomy term"""
        cmd = [
            'wp', 'post', 'term', 'set',
            str(post_id), taxonomy, term,
            '--allow-root',
            '--path=' + self.wp_path
        ]
        subprocess.run(cmd, check=True, capture_output=True)

    def check_existing_page(self, category: str, city: str) -> bool:
        """Check if page already exists"""
        cmd = [
            'wp', 'post', 'list',
            '--post_type=pt24_local',
            '--meta_key=pt24_local_category',
            f'--meta_value={category}',
            '--format=count',
            '--allow-root',
            '--path=' + self.wp_path
        ]

        try:
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            count = int(result.stdout.strip())

            if count > 0:
                # Additional check for city
                cmd2 = [
                    'wp', 'post', 'list',
                    '--post_type=pt24_local',
                    '--meta_key=pt24_local_city',
                    f'--meta_value={city}',
                    '--format=count',
                    '--allow-root',
                    '--path=' + self.wp_path
                ]
                result2 = subprocess.run(cmd2, capture_output=True, text=True, check=True)
                return int(result2.stdout.strip()) > 0

            return False

        except:
            return False

    def generate_bulk(self, csv_file: str, rate_limit: int = 2):
        """
        Generate pages from CSV file

        Args:
            csv_file: Path to CSV with columns: category,city
            rate_limit: Seconds to wait between requests
        """
        print(f"📁 Reading CSV: {csv_file}")

        with open(csv_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            total = 0
            created = 0
            skipped = 0

            for row in reader:
                total += 1
                category = row.get('category', '').strip().lower()
                city = row.get('city', '').strip().lower()

                if not category or not city:
                    print(f"⚠️  Skipping empty row: {row}")
                    continue

                # Check if already exists
                if self.check_existing_page(category, city):
                    print(f"⏭️  Skip (exists): {category} {city}")
                    skipped += 1
                    continue

                print(f"\n🔄 Generating: {category} {city}")

                # Generate content
                content = self.generate_local_page_content(category, city)
                if not content:
                    print(f"❌ Failed to generate content")
                    continue

                # Generate meta tags
                meta = self.generate_meta_tags(category, city)

                # Create post
                post_id = self.create_wordpress_post(category, city, content, meta)

                if post_id:
                    print(f"✅ Created: {category} {city} (Post ID: {post_id})")
                    created += 1
                else:
                    print(f"❌ Failed to create post")

                # Rate limiting
                if rate_limit > 0:
                    time.sleep(rate_limit)

            print(f"\n{'='*60}")
            print(f"📊 SUMMARY:")
            print(f"   Total rows:    {total}")
            print(f"   Created:       {created}")
            print(f"   Skipped:       {skipped}")
            print(f"   Failed:        {total - created - skipped}")
            print(f"{'='*60}")


def main():
    parser = argparse.ArgumentParser(description='PT24.PRO Content Generator')
    parser.add_argument('--api-key', help='OpenAI API key (or set OPENAI_API_KEY env var)')
    parser.add_argument('--wp-path', default='/var/www/pt24.pro', help='WordPress path')
    parser.add_argument('--category', help='Service category (e.g., mechanik)')
    parser.add_argument('--city', help='City name (e.g., warszawa)')
    parser.add_argument('--csv', help='CSV file with bulk data')
    parser.add_argument('--rate-limit', type=int, default=2, help='Seconds between requests')

    args = parser.parse_args()

    # Get API key
    api_key = args.api_key or os.getenv('OPENAI_API_KEY')
    if not api_key:
        print("❌ Error: OpenAI API key required")
        print("   Set via: --api-key or OPENAI_API_KEY environment variable")
        sys.exit(1)

    # Initialize generator
    generator = PT24ContentGenerator(api_key, args.wp_path)

    # Single page generation
    if args.category and args.city:
        print(f"🔄 Generating single page: {args.category} {args.city}")

        # Check if exists
        if generator.check_existing_page(args.category, args.city):
            print(f"⚠️  Page already exists!")
            sys.exit(0)

        # Generate
        content = generator.generate_local_page_content(args.category, args.city)
        if not content:
            print("❌ Failed to generate content")
            sys.exit(1)

        meta = generator.generate_meta_tags(args.category, args.city)
        post_id = generator.create_wordpress_post(args.category, args.city, content, meta)

        if post_id:
            print(f"✅ Success! Post ID: {post_id}")
        else:
            print("❌ Failed to create post")
            sys.exit(1)

    # Bulk generation from CSV
    elif args.csv:
        generator.generate_bulk(args.csv, args.rate_limit)

    else:
        print("❌ Error: Specify either --category and --city, or --csv")
        parser.print_help()
        sys.exit(1)


if __name__ == '__main__':
    main()
