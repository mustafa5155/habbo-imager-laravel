# habbo-imager-laravel

A Laravel implementation of native Habbo imager using blob storage for files extracted from .swf assets.

## Utility

1. **Sync Command** — `php artisan habbo-imaging:sync` downloads figuredata, figuremap, and asset SWF files from Habbo's CDN, extracts all bitmaps and XML metadata, and stores them as blobs in the database.
2. **Blob Storage** — Asset images (PNGs extracted from SWF) and rendered composites are stored in database tables, eliminating the need for a file system of thousands of tiny images.
3. **Figure Inspector** — Parses figuredata XML to build a searchable index of set types (hair, hats, tops, etc.), their parts, color palettes, and library mappings.
4. **Dresser Engine** — Given a figure string (e.g. `hd-180-1.ch-210-66`), resolves the correct sprite for each body part across 8 directions, applies color multipliers, composites layers with proper z-ordering, and caches the result.
5. **Livewire UI** — Normal mode for quick avatar preview, Advanced mode with a full wardrobe browser to mix and match sets and colors.

## Setup

### Requirements
- PHP 8.3+
- GD extension (for image compositing)
- Composer
- Laravel 13
- SQLite (default) or MySQL

### Installation

```bash
git clone https://github.com/mustafa5155/habbo-imager-laravel.git
cd habbo-imager-laravel
composer install
```

### Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to configure your database. The default is SQLite (zero config):

```
DB_CONNECTION=sqlite
```

For MySQL, use:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=habbo_imager
DB_USERNAME=root
DB_PASSWORD=
```

### Database

Run migrations to create all required tables:

```bash
php artisan migrate
```

This creates these tables:
- `habbo_imaging_versions` — tracks sync runs and metadata versions
- `habbo_imaging_assets` — tracks each SWF library asset and its extraction status
- `habbo_imaging_asset_blobs` — stores extracted bitmap PNGs by symbol name
- `habbo_imaging_render_blobs` — caches fully composited figure renders
- `habbo_imaging_xml_documents` — stores XML/manifest data extracted from SWF files

### Storage

```bash
php artisan storage:link
```

### Populate Assets

Download and extract all Habbo figure assets:

```bash
php artisan habbo-imaging:sync
```

This processes assets in batches. Run it multiple times — it picks up where it left off until all libraries are extracted.

## Usage

Visit `/imager` in your browser after serving:

```bash
php artisan serve
```

- **Normal mode** — Enter a figure string or username to preview
- **Advanced mode** — Browse categories, pick sets, change colors, and build a figure visually
- **Layer Debugger** — Visit `/imager/debug/layers` with a figure to inspect individual compositing layers

## Known Issues

- **Figure string accuracy** — Some figure strings produce cropped or misaligned output compared to the official Habbo client. The dresser uses its own composition logic rather than the game's exact rendering pipeline, so results won't always match what you'd see in-game.

- **Slow initial load** — The first page visit after serving the project can be slow if the sync command hasn't finished populating the blob tables. Views may time out or appear broken until `php artisan habbo-imaging:sync` completes. Run the sync before pointing anyone at the site.
