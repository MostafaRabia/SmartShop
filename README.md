# SmartShop Setup & AI Notes

## Setup Instructions
- Requirements: PHP 8.2+, Composer, Node 18+, SQLite (default) or another DB driver, and npm.
- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Environment: copy `.env.example` to `.env`, then set the DB connection (SQLite default) and AI keys (see below).
- Generate app key: `php artisan key:generate`
- Generate database file (if using SQLite): `touch database/database.sqlite`
- Migrate and seed: `php artisan migrate:fresh --seed`
- Run app: `composer run dev`
- Tests (static analysis, style, blades, unit/feature, coverage): `composer test`
- If you encounter issue with `vite`, try `npm run build` before running tests.

## AI API Used & Rationale
- **Google Gemini** via `app/Services/GeminiService.php`
- Chosen because:
  - Native JSON/text generation suited for structured recommendation outputs
  - Low-latency “flash” models appropriate for lightweight suggestions
  - Simple key-based HTTP API, easy to swap or disable when no key is present
  - I have access to Google Cloud generative AI services
- The service is optional; if `GEMINI_API_KEY` or model settings are missing, the app falls back to deterministic heuristic recommendations.

### Required ENV for Gemini
```
GEMINI_API_KEY=your-key
GEMINI_MODEL=gemini-3-flash-preview
GEMINI_API_ENDPOINT=https://generativelanguage.googleapis.com/v1beta
GEMINI_TIMEOUT=8
GEMINI_CATALOG_CACHE_SECONDS=120
```

## Example Prompt Sent to Gemini
When recommending from the catalog, `GeminiService::buildCatalogPrompt()` crafts a structured prompt. Example (truncated):
```
You are a shopping assistant. Choose similar products ONLY from the catalog below.
Return ONLY a JSON array of product IDs (integers) from the catalog and nothing else.
...
Last viewed products:
- Name: Wireless Headphones; Price: 129.99; Desc: High-fidelity ANC over-ear...
- Name: Travel Mug; Price: 24.50; Desc: Stainless steel, leak-proof...

Catalog (format: id | name | price | short description):
- 12 | Bluetooth Speaker | 79.99 | Compact speaker with rich bass
- 25 | Yoga Mat | 35.00 | Non-slip, 6mm thick
- 31 | Laptop Stand | 42.00 | Adjustable aluminum stand

Excluded IDs: [12]
Number of items to return: 3
Respond like: [25, 31, 18]
```
This ensures Gemini returns only valid catalog IDs, respecting exclusions and limit.
