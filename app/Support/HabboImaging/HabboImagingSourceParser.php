<?php

namespace App\Support\HabboImaging;

use RuntimeException;
use SimpleXMLElement;

class HabboImagingSourceParser
{
    public function parseExternalVariables(string $payload): array
    {
        $variables = [];

        foreach (preg_split('/\r\n|\r|\n/', $payload) as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $variables[trim($key)] = trim($value);
        }

        return $variables;
    }

    public function resolveSourceVersion(string $externalVariablesPayload, array $variables): string
    {
        $candidates = [
            $variables['flash.client.revision'] ?? null,
            $variables['flash.client.url'] ?? null,
            $variables['flash.avatar.figuremap.url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return $this->normalizeVersionToken($candidate);
            }
        }

        return 'habbo-' . substr(md5($externalVariablesPayload), 0, 16);
    }

    public function parseFiguredata(string $xml): array
    {
        $root = $this->loadXml($xml, 'figuredata');
        $setTypes = [];
        $palettes = [];
        $paletteCount = 0;
        $setCount = 0;
        $partCount = 0;

        if (isset($root->colors->palette)) {
            $paletteCount = count($root->colors->palette);

            foreach ($root->colors->palette as $paletteNode) {
                $paletteId = (string) ($paletteNode['id'] ?? '');

                if ($paletteId === '') {
                    continue;
                }

                $colors = [];

                foreach ($paletteNode->color as $colorNode) {
                    $colorId = (string) ($colorNode['id'] ?? '');

                    if ($colorId === '') {
                        continue;
                    }

                    $colors[] = [
                        'id' => $colorId,
                        'index' => (int) ($colorNode['index'] ?? 0),
                        'club' => (int) ($colorNode['club'] ?? 0),
                        'selectable' => ((string) ($colorNode['selectable'] ?? '0')) === '1',
                        'hex' => strtoupper(trim((string) $colorNode)),
                    ];
                }

                usort($colors, fn (array $left, array $right) => $left['index'] <=> $right['index']);

                $palettes[$paletteId] = [
                    'id' => (int) $paletteId,
                    'colors' => $colors,
                ];
            }
        }

        if (isset($root->sets->settype)) {
            foreach ($root->sets->settype as $setTypeNode) {
                $type = (string) ($setTypeNode['type'] ?? '');

                if ($type === '') {
                    continue;
                }

                $sets = [];

                foreach ($setTypeNode->set as $setNode) {
                    $setId = (string) ($setNode['id'] ?? '');

                    if ($setId === '') {
                        continue;
                    }

                    $parts = [];

                    foreach ($setNode->part as $partNode) {
                        $parts[] = [
                            'id' => (int) ($partNode['id'] ?? 0),
                            'type' => (string) ($partNode['type'] ?? ''),
                            'index' => (int) ($partNode['index'] ?? 0),
                            'colorable' => ((string) ($partNode['colorable'] ?? '0')) === '1',
                            'colorindex' => (int) ($partNode['colorindex'] ?? 0),
                        ];
                    }

                    $hiddenLayers = [];

                    foreach (($setNode->hiddenlayers->layer ?? []) as $hiddenLayerNode) {
                        $partType = trim((string) ($hiddenLayerNode['parttype'] ?? ''));

                        if ($partType !== '') {
                            $hiddenLayers[] = $partType;
                        }
                    }

                    $setCount++;
                    $partCount += count($parts);

                    $sets[$setId] = [
                        'id' => (int) ($setNode['id'] ?? 0),
                        'gender' => (string) ($setNode['gender'] ?? ''),
                        'club' => (int) ($setNode['club'] ?? 0),
                        'colorable' => ((string) ($setNode['colorable'] ?? '0')) === '1',
                        'selectable' => ((string) ($setNode['selectable'] ?? '0')) === '1',
                        'preselectable' => ((string) ($setNode['preselectable'] ?? '0')) === '1',
                        'parts' => $parts,
                        'hiddenlayers' => array_values(array_unique($hiddenLayers)),
                    ];
                }

                $setTypes[$type] = [
                    'type' => $type,
                    'palette_id' => (int) ($setTypeNode['paletteid'] ?? 0),
                    'mandatory_m' => (int) ($setTypeNode['mand_m_0'] ?? 0),
                    'mandatory_f' => (int) ($setTypeNode['mand_f_0'] ?? 0),
                    'sets' => $sets,
                ];
            }
        }

        return [
            'summary' => [
                'palette_count' => $paletteCount,
                'set_type_count' => count($setTypes),
                'set_count' => $setCount,
                'part_count' => $partCount,
            ],
            'palettes' => $palettes,
            'set_types' => $setTypes,
        ];
    }

    public function parseFiguremap(string $xml): array
    {
        $root = $this->loadXml($xml, 'figuremap');
        $libraries = [];
        $partIndex = [];
        $partLinkCount = 0;

        foreach ($root->lib as $libraryNode) {
            $libraryName = (string) ($libraryNode['id'] ?? '');

            if ($libraryName === '') {
                continue;
            }

            $parts = [];

            foreach ($libraryNode->part as $partNode) {
                $type = (string) ($partNode['type'] ?? '');
                $partId = (string) ($partNode['id'] ?? '');

                if ($type === '' || $partId === '') {
                    continue;
                }

                $parts[] = [
                    'type' => $type,
                    'id' => (int) $partId,
                ];

                $partIndex["{$type}:{$partId}"][] = $libraryName;
                $partLinkCount++;
            }

            $libraries[$libraryName] = [
                'id' => $libraryName,
                'revision' => (string) ($libraryNode['revision'] ?? ''),
                'parts' => $parts,
            ];
        }

        foreach ($partIndex as $key => $libraryNames) {
            $partIndex[$key] = array_values(array_unique($libraryNames));
        }

        return [
            'summary' => [
                'library_count' => count($libraries),
                'part_link_count' => $partLinkCount,
            ],
            'libraries' => $libraries,
            'part_index' => $partIndex,
        ];
    }

    private function loadXml(string $payload, string $label): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $root = simplexml_load_string($payload, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();

        if (!$root instanceof SimpleXMLElement) {
            throw new RuntimeException("Unable to parse {$label} XML.");
        }

        return $root;
    }

    private function normalizeVersionToken(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9._-]+/', '-', trim($value));
        $value = trim((string) $value, '-._');

        return $value !== '' ? substr($value, 0, 180) : 'habbo-' . now()->format('YmdHis');
    }
}
