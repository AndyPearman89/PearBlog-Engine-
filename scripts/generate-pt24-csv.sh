#!/bin/bash
##
## PT24 CSV Generator
## Generates CSV file for bulk landing page creation
##

set -e

OUTPUT_FILE="pt24-landings-100.csv"

echo "🚀 PT24 CSV Generator"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo

# CSV Header
echo "service,city,service_name,city_name" > "$OUTPUT_FILE"

# Services
declare -A SERVICES
SERVICES=(
  ["mechanik"]="Mechanik samochodowy"
  ["hydraulik"]="Hydraulik"
  ["elektryk"]="Elektryk samochodowy"
  ["laweta"]="Laweta"
  ["wulkanizacja"]="Wulkanizacja"
)

# Top 25 Polish Cities
declare -A CITIES
CITIES=(
  ["warszawa"]="Warszawa"
  ["krakow"]="Kraków"
  ["lodz"]="Łódź"
  ["wroclaw"]="Wrocław"
  ["poznan"]="Poznań"
  ["gdansk"]="Gdańsk"
  ["szczecin"]="Szczecin"
  ["bydgoszcz"]="Bydgoszcz"
  ["lublin"]="Lublin"
  ["katowice"]="Katowice"
  ["bialystok"]="Białystok"
  ["gdynia"]="Gdynia"
  ["czestochowa"]="Częstochowa"
  ["radom"]="Radom"
  ["sosnowiec"]="Sosnowiec"
  ["torun"]="Toruń"
  ["kielce"]="Kielce"
  ["rzeszow"]="Rzeszów"
  ["gliwice"]="Gliwice"
  ["zabrze"]="Zabrze"
  ["ruda-slaska"]="Ruda Śląska"
  ["bytom"]="Bytom"
  ["chorzow"]="Chorzów"
  ["tychy"]="Tychy"
  ["dabrowa-gornicza"]="Dąbrowa Górnicza"
)

# Generate combinations
count=0
for service_slug in "${!SERVICES[@]}"; do
  service_name="${SERVICES[$service_slug]}"

  for city_slug in "${!CITIES[@]}"; do
    city_name="${CITIES[$city_slug]}"

    echo "$service_slug,$city_slug,$service_name,$city_name" >> "$OUTPUT_FILE"
    ((count++))

    # Progress
    if (( count % 25 == 0 )); then
      echo "  ✓ Generated $count combinations..."
    fi
  done
done

echo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Success!"
echo
echo "Generated: $OUTPUT_FILE"
echo "Total combinations: $count"
echo "Services: ${#SERVICES[@]}"
echo "Cities: ${#CITIES[@]}"
echo
echo "Next steps:"
echo "  1. Run: python3 scripts/generate-pt24-pages.py"
echo "  2. Or: wp pt24 generate-pages --batch=$count --with-ai"
echo
