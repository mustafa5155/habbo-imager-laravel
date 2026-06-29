<?php

namespace App\Support\HabboImaging;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HabboImagingSourceResolver
{
    public function __construct(
        private readonly HabboImagingSourceParser $parser,
    ) {
    }

    public function discover(string $hotel = 'com'): array
    {
        $externalVariablesUrl = "https://www.habbo.{$hotel}/gamedata/external_variables/1";
        $externalVariablesPayload = $this->fetchText($externalVariablesUrl);
        $variables = $this->parser->parseExternalVariables($externalVariablesPayload);

        $figuredataUrl = $this->normalizeUrl(
            $variables['external.figurepartlist.txt']
                ?? $variables['figuredata.xml.url']
                ?? $variables['flash.avatar.figuredata.url']
                ?? "https://www.habbo.{$hotel}/gamedata/figuredata/1",
            $hotel,
            $variables
        );

        $figuremapUrl = $this->normalizeUrl(
            $variables['flash.dynamic.avatar.download.configuration']
                ?? $variables['flash.avatar.figuremap.url']
                ?? $variables['avatar.figuremap.url']
                ?? '',
            $hotel,
            $variables
        );

        $assetBaseUrl = $this->normalizeUrl(
            $variables['flash.dynamic.avatar.download.url']
                ?? $variables['flash.avatar.asset.download.url']
                ?? $variables['avatar.asset.download.url']
                ?? '',
            $hotel,
            $variables
        );

        $assetNameTemplate = $this->normalizeAssetTemplate(
            $variables['flash.dynamic.avatar.download.name.template']
                ?? $variables['flash.avatar.asset.download.name.template']
                ?? $variables['avatar.asset.download.name.template']
                ?? '%libname%.swf',
            $variables
        );

        return [
            'hotel' => $hotel,
            'external_variables_url' => $externalVariablesUrl,
            'external_variables_payload' => $externalVariablesPayload,
            'variables' => $variables,
            'source_version' => $this->parser->resolveSourceVersion($externalVariablesPayload, $variables),
            'figuredata_url' => $figuredataUrl,
            'figuremap_url' => $figuremapUrl,
            'asset_base_url' => $assetBaseUrl,
            'asset_name_template' => $assetNameTemplate,
        ];
    }

    public function fetchText(string $url): string
    {
        return $this->send($url)->body();
    }

    public function fetchBinary(string $url): string
    {
        return $this->send($url)->body();
    }

    private function send(string $url): Response
    {
        if (trim($url) === '') {
            throw new RuntimeException('Habbo imaging source URL is empty.');
        }

        $response = Http::accept('*/*')
            ->withHeaders(['User-Agent' => 'HabboImager/1.0'])
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 400)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException("Unable to fetch Habbo imaging source: {$url} ({$response->status()})");
        }

        return $response;
    }

    private function normalizeUrl(?string $value, string $hotel, array $variables = []): ?string
    {
        $value = $this->expandVariables(trim((string) $value), $variables);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '//')) {
            return 'https:' . $value;
        }

        if (str_starts_with($value, '/')) {
            return "https://www.habbo.{$hotel}{$value}";
        }

        if (!preg_match('#^https?://#i', $value)) {
            $clientUrl = $this->expandVariables(trim((string) ($variables['flash.client.url'] ?? '')), $variables);

            if ($clientUrl !== '' && preg_match('#^https?://#i', $clientUrl)) {
                return rtrim($clientUrl, '/') . '/' . ltrim($value, '/');
            }

            return "https://www.habbo.{$hotel}/" . ltrim($value, '/');
        }

        return $value;
    }

    private function normalizeAssetTemplate(?string $value, array $variables = []): string
    {
        $value = $this->expandVariables(trim((string) $value), $variables);
        return $value !== '' ? $value : '%libname%.swf';
    }

    private function expandVariables(string $value, array $variables, int $depth = 0): string
    {
        if ($value === '' || $depth > 5) {
            return $value;
        }

        return preg_replace_callback('/\$\{([^}]+)\}/', function (array $matches) use ($variables, $depth) {
            $key = trim((string) ($matches[1] ?? ''));
            $replacement = (string) ($variables[$key] ?? '');

            if ($replacement === '') {
                return '';
            }

            return $this->expandVariables($replacement, $variables, $depth + 1);
        }, $value) ?? $value;
    }
}
