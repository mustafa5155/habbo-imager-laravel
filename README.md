# habbo-imager-laravel

A Laravel implementation of native Habbo imager using blob storage for files extracted from .swf assets.

## Utility

1. **Sync Command** — `php artisan habbo-imaging:sync` downloads figuredata, figuremap, and asset SWF files from Habbo's CDN, extracts all bitmaps and XML metadata, and stores them as blobs in the database.
2. **Blob Storage** — Asset images (PNGs extracted from SWF) and rendered composites are stored in database tables, eliminating the need for a file system of thousands of tiny images.
3. **Figure Inspector** — Parses figuredata XML to build a searchable index of set types (hair, hats, tops, etc.), their parts, color palettes, and library mappings.
4. **Dresser Engine** — Given a figure string (e.g. `hd-180-1.ch-210-66`), resolves the correct sprite for each body part across 8 directions, applies color multipliers, composites layers with proper z-ordering, and caches the result.
5. **Livewire UI** — Normal mode for quick avatar preview, Advanced mode with a full wardrobe browser to mix and match sets and colors.

## Setup

```bash
git clone https://github.com/mustafa5155/habbo-imager-laravel.git
cd habbo-imager-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Populate the database with Habbo assets:

```bash
php artisan habbo-imaging:sync
```

This will download all required assets from Habbo's servers. Depending on your connection, this takes a few minutes. Run it multiple times — it processes assets in batches and picks up where it left off.

Once complete, visit `/imager` to use the dresser.

Requirements: PHP 8.3+, GD extension, Laravel 13.
