<div>
    <style>
        .habbo-imager { display: grid; gap: 22px; }
        .habbo-imager__tabs { display: flex; flex-wrap: wrap; gap: 10px; }
        .habbo-imager__tab,
        .habbo-imager__gender,
        .habbo-imager__category {
            min-height: 38px;
            padding: 0 16px;
            border-radius: 999px;
            border: 1px solid rgba(110,220,255,0.18);
            background: rgba(8,42,67,0.54);
            color: #dffaff;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 12px;
        }
        .habbo-imager__gender {
            width: 58px;
            min-height: 46px;
            padding: 0;
            border-radius: 12px;
            background: rgba(7,31,52,0.78);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .habbo-imager__gender img {
            width: 28px;
            height: 28px;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
            display: block;
        }
        .habbo-imager__tab.is-active,
        .habbo-imager__gender.is-active,
        .habbo-imager__category.is-active {
            background: linear-gradient(135deg, rgba(63,200,255,0.28), rgba(22,133,182,0.48));
            border-color: rgba(110,220,255,0.4);
        }
        .habbo-imager__grid {
            display: grid;
            grid-template-columns: minmax(320px, 0.95fr) minmax(340px, 1.05fr);
            gap: 20px;
        }
        .habbo-imager__grid > * {
            min-width: 0;
        }
        .habbo-imager.is-advanced .habbo-imager__grid {
            grid-template-columns: minmax(340px, 0.78fr) minmax(0, 1.72fr);
            align-items: start;
        }
        .habbo-imager.is-advanced .habbo-imager__grid > .habbo-imager__card:first-child { order: 2; }
        .habbo-imager.is-advanced .habbo-imager__grid > .habbo-imager__card:last-child { order: 1; }
        .habbo-imager__card {
            border-radius: 24px;
            border: 1px solid rgba(110,220,255,0.14);
            background: rgba(8,42,67,0.42);
            padding: 20px;
            min-width: 0;
            overflow: hidden;
        }
        .habbo-imager.is-advanced .habbo-imager__card {
            border-color: rgba(110,220,255,0.16);
            background:
                radial-gradient(circle at top right, rgba(111, 212, 255, 0.12), transparent 32%),
                linear-gradient(180deg, rgba(8,42,67,0.82), rgba(5,28,45,0.9));
            box-shadow: 0 12px 26px rgba(3, 18, 31, 0.24);
        }
        .habbo-imager.is-advanced .habbo-imager__card h3 { color: #effcff; }
        .habbo-imager.is-advanced .habbo-imager__copy { color: #9bcfe4; }
        .habbo-imager__card h3 { margin: 0 0 10px; color: #effcff; font-size: 20px; }
        .habbo-imager__copy { margin: 0 0 16px; color: #9ca3af; line-height: 1.8; font-size: 14px; }
        .habbo-imager__fields { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .habbo-imager__field {
            display: grid;
            gap: 8px;
            min-width: 0;
        }
        .habbo-imager__field label,
        .habbo-imager__section-label {
            font-family: "Trebuchet MS", sans-serif;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #8ce7ff;
        }
        .habbo-imager__field input,
        .habbo-imager__field select,
        .habbo-imager__field textarea {
            width: 100%;
            min-width: 0;
            min-height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(110,220,255,0.18);
            background: rgba(8,42,67,0.68);
            color: #e8fbff;
            padding: 10px 12px;
            outline: none;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .habbo-imager__field textarea { min-height: 92px; resize: vertical; }
        .habbo-imager__preview-shell {
            display: grid;
            gap: 18px;
            justify-items: stretch;
            align-content: start;
            width: 100%;
            max-width: 300px;
            min-width: 0;
        }
        .habbo-imager__preview {
            width: min(100%, 330px);
            min-height: 330px;
            border-radius: 24px;
            border: 1px solid rgba(110,220,255,0.18);
            background: radial-gradient(circle at top, rgba(78,200,255,0.14), transparent 52%), linear-gradient(180deg, rgba(7,31,52,0.92), rgba(4,21,38,0.86));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-width: 0;
        }
        .habbo-imager.is-advanced .habbo-imager__preview {
            width: min(100%, 420px);
            min-height: 380px;
            border-color: rgba(110,220,255,0.16);
        }
        .habbo-imager__preview img { image-rendering: pixelated; max-width: 100%; max-height: none; }
        .habbo-imager__url {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(110,220,255,0.18);
            background: rgba(8,42,67,0.54);
            color: #dffaff;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.7;
            white-space: normal;
        }
        .habbo-imager.is-advanced .habbo-imager__url {
            border-color: rgba(110,220,255,0.16);
            background: rgba(8,42,67,0.68);
            color: #dffaff;
        }
        .habbo-imager__status { display: grid; gap: 10px; color: #9ca3af; width: 100%; }
        .habbo-imager__status strong { color: #effcff; }
        .habbo-imager__button {
            min-height: 40px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid rgba(110,220,255,0.22);
            background: linear-gradient(135deg, rgba(63,200,255,0.28), rgba(22,133,182,0.5));
            color: #eefcff;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 12px;
        }
        .habbo-imager__report { width: 100%; display: grid; gap: 12px; min-width: 0; }
        .habbo-imager__report-card { border-radius: 18px; border: 1px solid rgba(110,220,255,0.14); background: rgba(8,42,67,0.48); padding: 14px; min-width: 0; }
        .habbo-imager__report-grid { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); color: #9ca3af; font-size: 13px; }
        .habbo-imager__report-grid strong { color: #effcff; }
        .habbo-imager__notice { padding: 12px 14px; border-radius: 14px; border: 1px solid rgba(110,220,255,0.16); background: rgba(6,28,46,0.58); color: #9ca3af; line-height: 1.8; overflow-wrap: anywhere; word-break: break-word; }
        .habbo-imager__notice code { color: #effcff; }
        .habbo-imager__button-row,
        .habbo-imager__category-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .habbo-imager__palette { display: grid; gap: 10px; }
        .habbo-imager__swatches { display: grid; grid-template-columns: repeat(auto-fit, minmax(34px, 34px)); gap: 8px; }
        .habbo-imager__swatch {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.08);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
        }
        .habbo-imager__swatch.is-active { border-color: #8ce7ff; box-shadow: 0 0 0 2px rgba(110,220,255,0.2); }
        .habbo-imager__swatch-badge {
            position: absolute;
            right: 2px;
            bottom: 2px;
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .habbo-imager__swatch-badge img {
            image-rendering: pixelated;
            display: block;
        }
        .habbo-imager__picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 10px;
        }

        .habbo-imager__picker-grid--scroll {
            max-height: 320px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .habbo-imager__picker-tile {
            border-radius: 14px;
            border: 1px solid rgba(110,220,255,0.14);
            background: rgba(8,42,67,0.72);
            padding: 8px;
            display: grid;
            gap: 6px;
            justify-items: center;
            align-items: center;
            cursor: pointer;
        }

        .habbo-imager__picker-tile.is-active {
            border-color: #69c9ff;
            box-shadow: 0 0 0 2px rgba(105,201,255,0.2);
            background: rgba(8,42,67,0.96);
        }

        .habbo-imager__picker-tile img {
            max-width: 64px;
            max-height: 64px;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
            display: block;
        }

        .habbo-imager__picker-tile span {
            font-family: "Trebuchet MS", sans-serif;
            font-size: 9px;
            color: #9bcfe4;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: center;
        }
        .habbo-imager__items {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
            max-height: 560px;
            overflow: auto;
            padding-right: 4px;
        }
        .habbo-imager__item {
            border-radius: 14px;
            border: 1px solid rgba(110,220,255,0.14);
            background: rgba(7,31,52,0.76);
            padding: 10px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            min-height: 104px;
            position: relative;
            overflow: visible;
            content-visibility: auto;
            contain-intrinsic-size: 88px 104px;
        }
        .habbo-imager__item.is-active { border-color: #6bc9ff; box-shadow: 0 0 0 2px rgba(107,201,255,0.2); background: rgba(8,42,67,0.92); }
        .habbo-imager__item-thumb {
            width: 100%;
            min-height: 0;
            border-radius: 0;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }
        .habbo-imager__item-thumb.is-avatar-head img,
        .habbo-imager__item-thumb.is-avatar-body img {
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
        .habbo-imager__item-thumb.is-avatar-body {
            align-items: flex-end;
        }
        .habbo-imager__item-thumb img {
            width: auto;
            height: auto;
            max-width: none;
            max-height: none;
            display: block;
            flex: 0 0 auto;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
            object-fit: none;
        }
        .habbo-imager__item-thumb.is-avatar-body img { align-self: flex-end; }
        .habbo-imager__item-clear {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            border: 2px solid rgba(110,220,255,0.42);
            background: rgba(8,42,67,0.72);
            color: #d7ecf3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            line-height: 1;
            font-weight: 700;
        }
        .habbo-imager__item-badge {
            position: absolute;
            right: 4px;
            bottom: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
        }
        .habbo-imager__item-badge img {
            image-rendering: pixelated;
            display: block;
        }
        .habbo-imager__dresser-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }
        .habbo-imager__dresser-tab {
            width: 56px;
            height: 48px;
            border-radius: 10px;
            border: 1px solid rgba(110,220,255,0.14);
            background: rgba(7,31,52,0.78);
            color: #dffaff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            cursor: pointer;
            box-shadow: none;
        }
        .habbo-imager__dresser-tab.is-active {
            border-color: #69c9ff;
            box-shadow: 0 0 0 2px rgba(105, 201, 255, 0.24);
            background: rgba(8,42,67,0.96);
        }
        .habbo-imager__dresser-tab img {
            max-width: 40px;
            max-height: 38px;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
        .habbo-imager__dresser-tab-symbol {
            font-family: "Trebuchet MS", sans-serif;
            font-size: 16px;
            line-height: 1;
        }
        .habbo-imager__dresser-surface,
        .habbo-imager__palette-panel,
        .habbo-imager__control-panel {
            border-radius: 18px;
            border: 1px solid rgba(110,220,255,0.14);
            background: rgba(7,31,52,0.76);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
            min-width: 0;
        }
        .habbo-imager__dresser-surface {
            padding: 12px;
        }
        .habbo-imager__palette-grid,
        .habbo-imager__control-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            min-width: 0;
        }
        .habbo-imager__palette-panel,
        .habbo-imager__control-panel {
            padding: 14px;
        }
        .habbo-imager__panel-title {
            margin: 0 0 12px;
            color: #8ce7ff;
            font-family: "Trebuchet MS", sans-serif;
            font-size: 9px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .habbo-imager__control-buttons {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
        }
        .habbo-imager__control-button {
            padding: 6px;
            scale: 100%;
            image-rendering: pixelated;
            border-radius: 14px;
            border: 1px solid rgba(110, 220, 255, 0.14);
            background: rgba(8, 42, 67, 0.72);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            cursor: pointer;
        }
        .habbo-imager__control-button.is-active {
            border-color: #69c9ff;
            box-shadow: 0 0 0 2px rgba(105, 201, 255, 0.2);
        }
        .habbo-imager__control-button img {
            width: auto;
            height: auto;
            max-width: 84px;
            max-height: 108px;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
            display: block;
            flex: 0 0 auto;
        }
        .habbo-imager__control-button.is-head img {
            max-width: 72px;
            max-height: 72px;
        }
        .habbo-imager__control-button-text {
            font-family: "Trebuchet MS", sans-serif;
            font-size: 9px;
            color: #9bcfe4;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .habbo-imager__copy-button {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 10px;
            border: 1px solid rgba(110,220,255,0.18);
            background: rgba(8,42,67,0.62);
            color: #e8fbff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex: 0 0 auto;
        }
        .habbo-imager__field--copy {
            position: relative;
        }

        .habbo-imager__copy-wrap {
            position: relative;
            width: 100%;
        }

        .habbo-imager__copy-wrap textarea {
            padding-right: 44px;
        }

        .habbo-imager__copy-button--inside {
            position: absolute;
            right: 10px;
            bottom: 10px;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            border: 1px solid rgba(110,220,255,0.22);
            background: rgba(7,31,52,0.92);
            color: #dffaff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.15s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.22);
        }

        .habbo-imager__copy-button--inside:hover {
            border-color: rgba(140,231,255,0.48);
            background: rgba(12,52,82,0.96);
            transform: translateY(-1px);
        }
        .habbo-imager__copy-button--inside svg {
            width: 11px;
            height: 11px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .habbo-imager__copy-button svg {
            width: 15px;
            height: 15px;
        }
        .habbo-imager__asset-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); min-width: 0; }
        .habbo-imager__asset-card { border-radius: 16px; border: 1px solid rgba(110,220,255,0.12); background: rgba(4,24,41,0.58); padding: 12px; display: grid; gap: 10px; min-width: 0; }
        .habbo-imager__asset-thumb { min-height: 112px; border-radius: 12px; border: 1px solid rgba(110,220,255,0.12); background: radial-gradient(circle at top, rgba(78,200,255,0.12), transparent 52%), rgba(8,42,67,0.54); display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .habbo-imager__asset-thumb img { max-width: 100%; max-height: 100px; image-rendering: pixelated; }
        .habbo-imager__asset-meta { color: #9ca3af; font-size: 12px; line-height: 1.7; word-break: break-word; overflow-wrap: anywhere; }
        .habbo-imager__asset-meta strong { color: #effcff; }
        .habbo-imager__mini-list { display: grid; gap: 8px; color: #9ca3af; font-size: 13px; max-height: 220px; overflow: auto; min-width: 0; }
        .habbo-imager__mini-item { padding: 10px 12px; border-radius: 12px; background: rgba(4,24,41,0.55); border: 1px solid rgba(110,220,255,0.1); overflow-wrap: anywhere; word-break: break-word; }
        .habbo-imager__mini-item strong { color: #effcff; }

        @media (max-width: 991px) {
            .habbo-imager__grid,
            .habbo-imager__fields,
            .habbo-imager__report-grid,
            .habbo-imager__palette-grid,
            .habbo-imager__control-grid { grid-template-columns: 1fr; }
            .habbo-imager.is-advanced .habbo-imager__grid > .habbo-imager__card:first-child,
            .habbo-imager.is-advanced .habbo-imager__grid > .habbo-imager__card:last-child { order: initial; }
            .habbo-imager__items { grid-template-columns: repeat(6, minmax(0, 1fr)); }
            .habbo-imager__preview-shell { max-width: 100%; }
        }
        @media (max-width: 640px) {
            .habbo-imager__items { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .habbo-imager__control-buttons { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .habbo-imager__direction-wheel {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 320px;
        }

        .habbo-imager__direction-button {
            min-height: 104px;
        }

        .habbo-imager__direction-spacer {
            min-height: 104px;
        }
    </style>

    <div class="habbo-imager {{ $mode === 'advanced' ? 'is-advanced' : '' }}">
        <div class="habbo-imager__tabs">
            <button type="button" class="habbo-imager__tab {{ $mode === 'normal' ? 'is-active' : '' }}" wire:click="setMode('normal')">Normal Imager</button>
            <button type="button" class="habbo-imager__tab {{ $mode === 'advanced' ? 'is-active' : '' }}" wire:click="setMode('advanced')">Advanced Imager</button>
        </div>

        <div class="habbo-imager__grid">
            <div class="habbo-imager__card">
                @if ($mode === 'normal')
                    <h3>Normal Imager</h3>
                    <p class="habbo-imager__copy">This mode builds a live Habbo imaging URL from a username or a raw figure string. It's the quick public tool while the advanced local asset engine matures.</p>

                    <div class="habbo-imager__fields">
                        <div class="habbo-imager__field"><label>Habbo Name</label><input type="text" wire:model.live.debounce.250ms="username" placeholder="mustafa505"></div>
                        <div class="habbo-imager__field"><label>Figure String</label><input type="text" wire:model.live.debounce.250ms="figure" placeholder="Optional for direct figure rendering"></div>
                        <div class="habbo-imager__field"><label>Direction</label><select wire:model.live="direction">@for ($i = 0; $i <= 7; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                        <div class="habbo-imager__field"><label>Head Direction</label><select wire:model.live="headDirection">@for ($i = 0; $i <= 7; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                        <div class="habbo-imager__field"><label>Gesture</label><select wire:model.live="gesture">@foreach($gestures as $gestureOption)<option value="{{ $gestureOption }}">{{ strtoupper($gestureOption) }}</option>@endforeach</select></div>
                        <div class="habbo-imager__field">
                            <label>Action</label>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="action"
                                placeholder="std, wav, sit, lay, wlk, wav,wlk, wav,sit"
                            >
                        </div>
                        <div class="habbo-imager__field"><label>Size</label><select wire:model.live="size">@foreach($sizes as $sizeOption)<option value="{{ $sizeOption }}">{{ strtoupper($sizeOption) }}</option>@endforeach</select></div>
                        <div class="habbo-imager__field"><label>Head Only</label><select wire:model.live="headOnly"><option value="0">No</option><option value="1">Yes</option></select></div>
                    </div>
                @else
                    @php
                        $categoryMap = collect($advancedDresser['categories'] ?? [])->keyBy('key');
                        $activeCategoryKey = (string) data_get($advancedDresser, 'active_category', 'hd');
                        $navTabs = [
                            ['key' => 'hd', 'label' => 'Body', 'icon' => '/images/imager/dresser/body.png'],
                            ['key' => 'hr', 'label' => 'Hair', 'icon' => '/images/imager/dresser/hair.png'],
                            ['key' => 'ha', 'label' => 'Hats', 'icon' => '/images/imager/dresser/hats.png'],
                            ['key' => 'he', 'label' => 'Headwear', 'icon' => '/images/imager/dresser/headwear.png'],
                            ['key' => 'ea', 'label' => 'Eyewear', 'icon' => '/images/imager/dresser/eyewear.png'],
                            ['key' => 'fa', 'label' => 'Face Accs', 'icon' => '/images/imager/dresser/faceaccs.png'],
                            ['key' => 'ca', 'label' => 'Chest Accs', 'icon' => '/images/imager/dresser/chestaccs.png'],
                            ['key' => 'cc', 'label' => 'Jackets', 'icon' => '/images/imager/dresser/jackets.png'],
                            ['key' => 'cp', 'label' => 'Prints', 'icon' => '/images/imager/dresser/prints.png'],
                            ['key' => 'ch', 'label' => 'Tops', 'icon' => '/images/imager/dresser/tops.png'],
                            ['key' => 'lg', 'label' => 'Bottoms', 'icon' => '/images/imager/dresser/bottoms.png'],
                            ['key' => 'sh', 'label' => 'Shoes', 'icon' => '/images/imager/dresser/shoes.png'],
                            ['key' => 'wa', 'label' => 'Waist', 'icon' => '/images/imager/dresser/waist.png'],
                            ['key' => 'pt', 'label' => 'Pets', 'icon' => '/images/imager/dresser/pets.png'],
                            ['key' => 'mc', 'label' => 'Others', 'icon' => '/images/imager/dresser/more.png'],
                        ];
                        $bodyDirections = [2, 3, 4, 5, 6, 7, 0, 1];
                        $headDirections = [];
                        foreach ([-2, -1, 0, 1, 2] as $delta) {
                            $headDirections[] = (((int) $direction + $delta + 8) % 8);
                        }
                         $actionPresets = [
                            ['value' => '', 'label' => 'std'],
                            ['value' => 'wav', 'label' => 'wav'],
                            ['value' => 'wlk', 'label' => 'wlk'],
                            ['value' => 'sit', 'label' => 'sit'],
                            ['value' => 'lay', 'label' => 'lay'],
                            ['value' => 'wav,wlk', 'label' => 'wav+wlk'],
                            ['value' => 'wav,sit', 'label' => 'wav+sit'],

                        ];
                        $gesturePresets = [
                            ['value' => 'nrm', 'label' => 'nrm'],
                            ['value' => 'sml', 'label' => 'sml'],
                            ['value' => 'agr', 'label' => 'agr'],
                            ['value' => 'srp', 'label' => 'srp'],
                            ['value' => 'sad', 'label' => 'sad'],
                            ['value' => 'spk', 'label' => 'spk'],
                            ['value' => 'eyb', 'label' => 'eyb'],
                        ];
                        $bodyDirectionLayout = [
                            6, 7, 0,
                            5, null, 1,
                            4, 3, 2,
                        ];
                        $d = (int) $direction;

                        $validHeadDirs = [
                            ($d - 2 + 8) % 8,
                            ($d - 1 + 8) % 8,
                            $d,
                            ($d + 1) % 8,
                            ($d + 2) % 8,
                        ];

                        $wheel = [
                            6, 7, 0,
                            5, null, 1,
                            4, 3, 2,
                        ];

                        $headDirectionLayout = array_map(function ($slot) use ($validHeadDirs) {
                            return $slot !== null && in_array($slot, $validHeadDirs, true)
                                ? $slot
                                : null;
                        }, $wheel);
                    @endphp
                    <h3>Advanced Dresser</h3>
                    <p class="habbo-imager__copy">Build a Habbo figure string through locally synced wardrobe data. Pick a gender, choose a category, click an item, tweak colors, and the generated figure string and preview update together.</p>

                    <div class="habbo-imager__report" style="margin-bottom: 16px;">
                        <div>
                            <div class="habbo-imager__section-label" style="margin-bottom: 8px;">Gender</div>
                            <div class="habbo-imager__button-row">
                                @foreach (['M' => 'Male', 'F' => 'Female'] as $genderKey => $genderLabel)
                                    <button type="button" class="habbo-imager__gender {{ $advancedGender === $genderKey ? 'is-active' : '' }}" wire:click="setAdvancedGender('{{ $genderKey }}')" title="{{ $genderLabel }}">
                                        <img src="/images/imager/dresser/{{ $genderKey === 'M' ? 'male' : 'female' }}.png" alt="{{ $genderLabel }}" loading="eager" decoding="async">
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="habbo-imager__dresser-nav">
                            @foreach ($navTabs as $tab)
                                @php($tabData = $categoryMap->get($tab['key']) ?: [])
                                @if (!empty($tabData))
                                    <button type="button" class="habbo-imager__dresser-tab {{ $activeCategoryKey === $tab['key'] ? 'is-active' : '' }}" wire:click="setAdvancedCategory('{{ $tab['key'] }}')" title="{{ $tab['label'] }}">
                                        <img src="{{ $tab['icon'] }}" alt="{{ $tab['label'] }}" loading="eager" decoding="async">
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <div class="habbo-imager__dresser-surface">
                            <div class="habbo-imager__items">
                                @foreach (($advancedDresser['items'] ?? []) as $item)
                                    @php($thumbMode = (string) ($item['thumbnail_mode'] ?? 'isolated'))
                                    <button
                                        type="button"
                                        class="habbo-imager__item {{ !empty($item['selected']) ? 'is-active' : '' }}"
                                        wire:click="{{ !empty($item['clear_option']) ? "clearAdvancedCategorySelection('" . data_get($advancedDresser, 'active_category') . "')" : "selectAdvancedSet('" . data_get($advancedDresser, 'active_category') . "', " . (int) $item['set_id'] . ")" }}"
                                        wire:key="habbo-imager-item-main-{{ data_get($advancedDresser, 'active_category') }}-{{ $item['set_id'] ?: 'clear' }}"
                                        title="{{ !empty($item['clear_option']) ? 'Clear category' : 'Set ' . $item['set_id'] }}"
                                    >
                                        <div class="habbo-imager__item-thumb {{ $thumbMode === 'avatar-head' ? 'is-avatar-head' : ($thumbMode === 'avatar-body' ? 'is-avatar-body' : 'is-isolated') }}">
                                            @if (!empty($item['clear_option']))
                                                <span class="habbo-imager__item-clear" aria-hidden="true">&times;</span>
                                            @elseif (!empty($item['thumbnail_url']))
                                                <img src="{{ $item['thumbnail_url'] }}" alt="Set {{ $item['set_id'] }}" loading="{{ $loop->index < 24 ? 'eager' : 'lazy' }}" decoding="async" fetchpriority="{{ $loop->index < 12 ? 'high' : 'auto' }}" onload="habboImagerPixelThumb(this)">
                                            @else
                                                <span style="color: #9ca3af; font-size: 12px;">No thumb</span>
                                            @endif
                                        </div>
                                        @if (($item['club'] ?? 0) > 0)
                                            <span class="habbo-imager__item-badge"><img src="/images/imager/dresser/hc.png" alt="HC" loading="lazy" decoding="async"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="habbo-imager__palette-grid">
                            @if (!empty(data_get($advancedDresser, 'current_selection.color_slots')) && !empty($advancedDresser['current_palette']))
                                <div class="habbo-imager__palette-panel">
                                    <div class="habbo-imager__panel-title">Primary Color</div>
                                    <div class="habbo-imager__swatches">
                                        @foreach (data_get($advancedDresser, 'current_palette.colors', []) as $color)
                                            <button
                                                type="button"
                                                class="habbo-imager__swatch {{ (string) data_get($advancedDresser, 'current_selection.colors.0') === (string) $color['id'] ? 'is-active' : '' }}"
                                                style="background:#{{ ltrim((string) $color['hex'], '#') }}"
                                                wire:click="selectAdvancedColor('{{ data_get($advancedDresser, 'active_category') }}', 1, '{{ $color['id'] }}')"
                                                title="#{{ $color['hex'] }}"
                                            >
                                                @if (($color['club'] ?? 0) > 0)
                                                    <span class="habbo-imager__swatch-badge"><img src="/images/imager/dresser/hc.png" alt="HC" loading="lazy" decoding="async"></span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ((int) data_get($advancedDresser, 'current_selection.color_slots', 0) >= 2 && !empty($advancedDresser['secondary_palette']))
                                <div class="habbo-imager__palette-panel">
                                    <div class="habbo-imager__panel-title">Secondary Color</div>
                                    <div class="habbo-imager__swatches">
                                        @foreach (data_get($advancedDresser, 'secondary_palette.colors', []) as $color)
                                            <button
                                                type="button"
                                                class="habbo-imager__swatch {{ (string) data_get($advancedDresser, 'current_selection.colors.1') === (string) $color['id'] ? 'is-active' : '' }}"
                                                style="background:#{{ ltrim((string) $color['hex'], '#') }}"
                                                wire:click="selectAdvancedColor('{{ data_get($advancedDresser, 'active_category') }}', 2, '{{ $color['id'] }}')"
                                                title="#{{ $color['hex'] }}"
                                            >
                                                @if (($color['club'] ?? 0) > 0)
                                                    <span class="habbo-imager__swatch-badge"><img src="/images/imager/dresser/hc.png" alt="HC" loading="lazy" decoding="async"></span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="habbo-imager__control-grid">
                            <div class="habbo-imager__control-panel">
                                <div class="habbo-imager__panel-title">Body Direction</div>
                                <div class="habbo-imager__direction-wheel">
                                    @foreach ($bodyDirectionLayout as $index => $dir)
                                        @if ($dir === null)
                                            <div class="habbo-imager__direction-spacer" aria-hidden="true"></div>
                                        @else
                                            <button
                                                type="button"
                                                class="habbo-imager__control-button habbo-imager__direction-button {{ (int) $direction === $dir ? 'is-active' : '' }}"
                                                wire:click="setAdvancedBodyDirection({{ $dir }})"
                                                title="Body direction {{ $dir }}"
                                            >
                                                <img
                                                    src="{{ $this->staticFigureRenderUrl([
                                                        'figure' => (string) $figure,
                                                        'gender' => $advancedGender,
                                                        'direction' => $dir,
                                                        'head_direction' => $dir,
                                                        'gesture' => (string) $gesture,
                                                        'action' => (string) ($action !== '' ? $action : 'std'),
                                                        'head_only' => 0,
                                                    ]) }}"
                                                    alt="Direction {{ $dir }}"
                                                >
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="habbo-imager__control-panel">
                                <div class="habbo-imager__panel-title">Head Direction</div>

                                <div class="habbo-imager__direction-wheel">
                                    @foreach ($headDirectionLayout as $dir)
                                        @if ($dir === null)
                                            <div class="habbo-imager__direction-spacer"></div>
                                        @else
                                            <button
                                                type="button"
                                                class="habbo-imager__control-button is-head habbo-imager__direction-button {{ (int) $headDirection === $dir ? 'is-active' : '' }}"
                                                wire:click="setAdvancedHeadDirection({{ $dir }})"
                                                title="Head direction {{ $dir }}"
                                            >
                                                <img
                                                    src="{{ $this->staticFigureRenderUrl([
                                                        'figure' => (string) $figure,
                                                        'gender' => $advancedGender,
                                                        'direction' => $d,
                                                        'head_direction' => $dir,
                                                        'gesture' => (string) $gesture,
                                                        'action' => (string) ($action !== '' ? $action : 'std'),
                                                        'head_only' => 1
                                                    ]) }}"
                                                    alt="Head direction {{ $dir }}"
                                                >
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="habbo-imager__control-panel">
                                <div class="habbo-imager__panel-title">User Action</div>
                                <div class="habbo-imager__control-buttons">
                                    @foreach ($actionPresets as $preset)
                                        <button type="button" class="habbo-imager__control-button {{ (string) $action === (string) $preset['value'] ? 'is-active' : '' }}" wire:click="setAdvancedActionPreset('{{ $preset['value'] }}')" title="{{ strtoupper($preset['label']) }}">
                                            <img src="{{ $this->staticFigureRenderUrl(['figure' => (string) $figure, 'gender' => $advancedGender, 'direction' => (int) $direction, 'head_direction' => (string) ($preset['value'] !== '' ? $preset['value'] : 'std') === 'lay' ? (int) $direction : (int) $headDirection, 'gesture' => (string) $gesture, 'action' => (string) ($preset['value'] !== '' ? $preset['value'] : 'std'), 'head_only' => 0]) }}" alt="{{ strtoupper($preset['label']) }}">
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="habbo-imager__control-panel">
                                <div class="habbo-imager__panel-title">User Expression</div>
                                <div class="habbo-imager__control-buttons">
                                    @foreach ($gesturePresets as $preset)
                                        <button type="button" class="habbo-imager__control-button is-head {{ (string) $gesture === (string) $preset['value'] ? 'is-active' : '' }}" wire:click="setAdvancedGesturePreset('{{ $preset['value'] }}')" title="{{ strtoupper($preset['label']) }}">
                                            <img src="{{ $this->staticFigureRenderUrl(['figure' => (string) $figure, 'gender' => $advancedGender, 'direction' => (int) $direction, 'head_direction' => (string) ($action !== '' ? $action : 'std') === 'lay' ? (int) $direction : (int) $headDirection, 'gesture' => (string) $preset['value'], 'action' => (string) ($action !== '' ? $action : 'std'), 'head_only' => 1]) }}" alt="{{ strtoupper($preset['label']) }}">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="habbo-imager__control-panel">
                        <div class="habbo-imager__panel-title">Hand Items</div>

                        <div class="habbo-imager__button-row" style="margin-bottom: 10px;">
                            <button
                                type="button"
                                class="habbo-imager__tab {{ $handItemMode === 'crr' ? 'is-active' : '' }}"
                                wire:click="setAdvancedHandItemMode('crr')"
                            >
                                Carry
                            </button>

                            <button
                                type="button"
                                class="habbo-imager__tab {{ $handItemMode === 'drk' ? 'is-active' : '' }}"
                                wire:click="setAdvancedHandItemMode('drk')"
                            >
                                Drink
                            </button>
                        </div>

                        <div class="habbo-imager__picker-grid habbo-imager__picker-grid--scroll">
                            @forelse ($handItemCatalog as $item)
                                @php($value = $handItemMode . '=' . $item['value'])
                                <button
                                    type="button"
                                    class="habbo-imager__picker-tile {{ (string) $action === $value ? 'is-active' : '' }}"
                                    wire:click="setAdvancedHandItem('{{ $item['value'] }}')"
                                    title="{{ $item['label'] }}"
                                >
                                    <img src="{{ $item['thumb'] }}" alt="{{ $item['label'] }}" loading="lazy" decoding="async">
                                    <span>{{ $item['label'] }}</span>
                                </button>
                            @empty
                                <div class="habbo-imager__notice">No hand item assets found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="habbo-imager__control-panel" style="margin-top: 14px;">
                        <div class="habbo-imager__panel-title">Signs</div>
                        <div class="habbo-imager__picker-grid habbo-imager__picker-grid--scroll">
                            @forelse ($signCatalog as $item)
                                @php($value = 'sig=' . $item['value'])
                                <button
                                    type="button"
                                    class="habbo-imager__picker-tile {{ (string) $action === $value ? 'is-active' : '' }}"
                                    wire:click="setAdvancedSign('{{ $item['value'] }}')"
                                    title="{{ $item['label'] }}"
                                >
                                    <img src="{{ $item['thumb'] }}" alt="{{ $item['label'] }}" loading="lazy" decoding="async">
                                    <span>{{ $item['label'] }}</span>
                                </button>
                            @empty
                                <div class="habbo-imager__notice">No sign assets found.</div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            <div class="habbo-imager__card">
                @if ($mode === 'advanced')
                    <div class="habbo-imager__control-panel" style="margin-top: 14px;">
                        <div class="habbo-imager__panel-title">Preview Output</div>
                        <div class="habbo-imager__button-row">
                            <button
                                type="button"
                                class="habbo-imager__tab {{ $previewMode === 'static' ? 'is-active' : '' }}"
                                wire:click="setPreviewMode('static')"
                            >
                                Static
                            </button>

                            <button
                                type="button"
                                class="habbo-imager__tab {{ $previewMode === 'animated' ? 'is-active' : '' }}"
                                wire:click="setPreviewMode('animated')"
                            >
                                Animated
                            </button>
                        </div>

                        @if ($previewMode === 'animated' && !in_array(true, array_map(fn ($token) => in_array($token, ['wav', 'wlk', 'spk'], true), preg_split('/[,\s]+/', strtolower((string) $action)) ?: []), true))
                            <div class="habbo-imager__notice" style="margin-top: 10px;">
                                Animated preview works with WAV, WLK, and SPK actions.
                            </div>
                        @endif
                    </div>
                @endif
                <br>
                <br>
                <div class="habbo-imager__preview-shell">
                    
                    <div class="habbo-imager__preview habbo-imager__field--copy">
                        @if ($previewDisplayUrl )
                            <img src="{{ $mode === 'advanced' && $previewMode === 'static' && $figure ? $this->staticFigureRenderUrl([
                                'figure' => (string) $figure,
                                'gender' => $advancedGender,
                                'direction' => (int) $direction,
                                'head_direction' => (int) $headDirection,
                                'gesture' => (string) $gesture,
                                'action' => (string) ($action !== '' ? $action : 'std'),
                                'head_only' => (int) $headOnly,
                                'size' => (string) $size,
                            ]) : $previewDisplayUrl }}" alt="Habbo preview" loading="eager" decoding="async">
                        @else
                            <span style="color: #9ca3af;">Enter a Habbo name or figure string.</span>
                        @endif
                        @if ($mode === 'advanced')
                            @if ($this->debuggerUrl)
                                <a 
                                    href="{{ $this->debuggerUrl }}"
                                    target="_blank"
                                    class="habbo-imager__copy-button habbo-imager__copy-button--inside"
                                    aria-label="Open layer debugger"
                                    title="Open debugger"
                                >
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="M14.7 6.3l3 3"></path>
                                        <path d="M7 17l3-3"></path>
                                        <path d="M14 10l-4 4"></path>
                                    </svg>
                                </a>
                            @endif
                        @endif
                    </div>

                    @if ($mode === 'advanced')
                    
                    <div class="habbo-imager__field habbo-imager__field--copy">
                        <label>Generated Figure String</label>

                        <div class="habbo-imager__copy-wrap">
                            <textarea
                                wire:model.live.debounce.350ms="figure"
                                placeholder="Figure string will appear here. You can also paste one in and the dresser will try to load it."
                            ></textarea>

                            @if ($figure)
                                <button
                                    type="button"
                                    class="habbo-imager__copy-button habbo-imager__copy-button--inside"
                                    onclick="habboImagerCopyText(@js($figure))"
                                    aria-label="Copy figure string"
                                    title="Copy figure string"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 9.75A1.75 1.75 0 0 1 10.75 8h7.5A1.75 1.75 0 0 1 20 9.75v7.5A1.75 1.75 0 0 1 18.25 19h-7.5A1.75 1.75 0 0 1 9 17.25z"></path>
                                        <path d="M6.75 15H6A2 2 0 0 1 4 13V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v.75"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    @endif

                    <div class="habbo-imager__field habbo-imager__field--copy">
                        <label>Preview URL</label>

                        <div class="habbo-imager__copy-wrap">
                            <textarea
                                readonly
                                tabindex="-1"
                                placeholder="Preview URL will appear here."
                            >{{ $previewDisplayUrl  ?: 'Preview URL will appear here.' }}</textarea>

                            @if ($previewDisplayUrl )
                                <button
                                    type="button"
                                    class="habbo-imager__copy-button habbo-imager__copy-button--inside"
                                    onclick="habboImagerCopyText(@js($previewDisplayUrl ))"
                                    aria-label="Copy preview URL"
                                    title="Copy preview URL"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 9.75A1.75 1.75 0 0 1 10.75 8h7.5A1.75 1.75 0 0 1 20 9.75v7.5A1.75 1.75 0 0 1 18.25 19h-7.5A1.75 1.75 0 0 1 9 17.25z"></path>
                                        <path d="M6.75 15H6A2 2 0 0 1 4 13V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v.75"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($mode === 'advanced' && !empty($advancedSequence['frames']))
                        <div class="habbo-imager__report-card" style="margin-top: 12px;">
                            <div style="margin-bottom: 8px; color: #effcff; font-weight: 700;">Wave Frames</div>
                            <div class="habbo-imager__notice" style="margin-bottom: 10px;">
                                {{ data_get($advancedSequence, 'frame_count', 0) }} frames · {{ data_get($advancedSequence, 'frame_duration_ms', 0) }}ms each · Loop: {{ !empty($advancedSequence['loop']) ? 'Yes' : 'No' }}
                            </div>
                            <div class="habbo-imager__asset-grid">
                                @foreach (($advancedSequence['frames'] ?? []) as $frame)
                                    <div class="habbo-imager__asset-card">
                                        <div class="habbo-imager__asset-thumb">
                                            <img src="{{ $frame['render_url'] }}" alt="Wave frame {{ $frame['index'] }}">
                                        </div>
                                        <div class="habbo-imager__asset-meta">
                                            <strong>Frame {{ $frame['index'] }}</strong><br>
                                            Duration: {{ $frame['duration_ms'] }}ms<br>
                                            @if (!empty($frame['debug_path']))
                                                Debug: <code>{{ basename((string) $frame['debug_path']) }}</code>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($mode === 'advanced')
                        <div class="habbo-imager__report">
                            <div class="habbo-imager__report-card">
                                <div style="margin-bottom: 10px; color: #effcff; font-weight: 700;">
                                    {{ data_get($advancedDresser, 'active_category_label', 'Items') }}
                                </div>
                                <div class="habbo-imager__notice">
                                    Showing {{ count($advancedDresser['items'] ?? []) }} of {{ data_get($advancedDresser, 'item_total', 0) }} sets for the active dresser category.
                                </div>
                            </div>

                            <div class="habbo-imager__report-card">
                                <div class="habbo-imager__report-grid">
                                    <div><strong>Status:</strong> {{ $manifest['status'] ?? 'idle' }}</div>
                                    <div><strong>Locked:</strong> {{ !empty($manifest['locked']) ? 'Yes' : 'No' }}</div>
                                    <div><strong>Source Version:</strong> {{ $manifest['current_source_version'] ?? 'Not set' }}</div>
                                    <div><strong>Parsed Version:</strong> {{ $manifest['current_parsed_version'] ?? 'Not set' }}</div>
                                    <div><strong>Last Synced:</strong> {{ $manifest['last_synced_at'] ?? 'Never' }}</div>
                                    <div><strong>Pending Assets:</strong> {{ data_get($manifest, 'latest_version.metadata.asset_counts.pending', 0) }}</div>
                                    <div><strong>Extracted Assets:</strong> {{ data_get($manifest, 'latest_version.metadata.asset_counts.extracted', 0) }}</div>
                                    <div><strong>Completion:</strong> {{ data_get($manifest, 'latest_version.metadata.asset_details.completion_percent', 0) }}%</div>
                                </div>
                            </div>

                            @if (!empty(data_get($manifest, 'latest_version.metadata.asset_details.processed_sample')))
                                <div class="habbo-imager__report-card">
                                    <div style="margin-bottom: 10px; color: #effcff; font-weight: 700;">Last Processed Libraries</div>
                                    <div class="habbo-imager__mini-list">
                                        @foreach (data_get($manifest, 'latest_version.metadata.asset_details.processed_sample', []) as $library)
                                            <div class="habbo-imager__mini-item">
                                                <strong>{{ $library['library'] ?? 'Unknown library' }}</strong><br>
                                                Status: {{ $library['status'] ?? 'Unknown' }}<br>
                                                Extracted Files: {{ $library['extracted_file_count'] ?? 0 }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($mode === 'advanced')
                        <div class="habbo-imager__report-card">
                            <div style="margin-bottom: 10px; color: #effcff; font-weight: 700;">Renderer Debug</div>
                            <div class="habbo-imager__report-grid" style="margin-bottom: 12px;">
                                <div><strong>Requested Dir:</strong> {{ data_get($advancedRenderDebug, 'requested_direction', $direction) }}</div>
                                <div><strong>Requested Head Dir:</strong> {{ data_get($advancedRenderDebug, 'requested_head_direction', $headDirection) }}</div>
                                <div><strong>Hidden Layers:</strong> {{ count($advancedRenderDebug['hidden_layers'] ?? []) }}</div>
                                <div><strong>Fallback Layers:</strong> {{ count($advancedRenderDebug['fallback_layers'] ?? []) }}</div>
                            </div>

                            <div class="habbo-imager__mini-list" style="margin-bottom: 12px;">
                                <div class="habbo-imager__mini-item">
                                    <strong>Suppressed By Hidden Layers</strong><br>
                                    @if (empty($advancedReport['suppressed_hidden_matches']))
                                        None suppressed.
                                    @else
                                        @foreach (($advancedReport['suppressed_hidden_matches'] ?? []) as $part)
                                            <div>{{ strtoupper((string) ($part['part_type'] ?? '')) }} {{ $part['part_id'] ?? 0 }} · segment {{ $part['segment'] ?? 'n/a' }}</div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="habbo-imager__mini-item">
                                    <strong>Expected Layers Missing</strong><br>
                                    @if (empty($advancedRenderDebug['missing_matches']))
                                        No expected layers missing.
                                    @else
                                        @foreach (($advancedRenderDebug['missing_matches'] ?? []) as $part)
                                            <div>
                                                {{ strtoupper((string) ($part['part_type'] ?? '')) }} {{ $part['part_id'] ?? 0 }}
                                                · dir {{ $part['requested_direction'] ?? $part['target_direction'] ?? 'n/a' }}
                                                · {{ $part['missing_reason'] ?? 'missing' }}
                                                @if (!empty($part['available_source_directions']))
                                                    · seen dirs {{ implode(',', $part['available_source_directions']) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="habbo-imager__mini-item">
                                    <strong>Fallback Or Mirrored Layers</strong><br>
                                    @if (empty($advancedRenderDebug['fallback_layers']))
                                        No fallback or mirrored assets used.
                                    @else
                                        @foreach (($advancedRenderDebug['fallback_layers'] ?? []) as $layer)
                                            <div>
                                                {{ strtoupper((string) ($layer['part_type'] ?? '')) }} {{ $layer['part_id'] ?? 0 }}
                                                · {{ $layer['direction_resolution'] ?? 'exact' }}
                                                · req {{ $layer['requested_direction'] ?? 'n/a' }}
                                                · src {{ $layer['chosen_source_direction'] ?? 'n/a' }}
                                                @if (!empty($layer['used_mirroring']))
                                                    · mirrored
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="habbo-imager__mini-item">
                                    <strong>Final Selected Layer Stack</strong><br>
                                    @if (empty($advancedRenderDebug['layers']))
                                        No rendered layer stack yet.
                                    @else
                                        @foreach (($advancedRenderDebug['layers'] ?? []) as $layer)
                                            <div>
                                                {{ strtoupper((string) ($layer['part_type'] ?? '')) }} {{ $layer['part_id'] ?? 0 }}
                                                · {{ $layer['symbol_name'] ?? 'unknown-symbol' }}
                                                · req {{ $layer['requested_direction'] ?? 'n/a' }}
                                                · src {{ $layer['source_direction'] ?? 'n/a' }}
                                                @if (!empty($layer['mirrored']))
                                                    · mirrored
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.habboImagerPixelThumb = window.habboImagerPixelThumb || function (image) {
        if (!image || image.dataset.pixelSized === '1') {
            return;
        }
        image.dataset.pixelSized = '1';
    };
</script>
