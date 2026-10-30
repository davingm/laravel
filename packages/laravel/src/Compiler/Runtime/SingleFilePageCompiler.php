<?php

declare(strict_types=1);

namespace Davingm\LaravelRuntime\Compiler\Runtime;

use InvalidArgumentException;

final class SingleFilePageCompiler
{
    /**
     * Convert a single-file page into a regular Blade layout section.
     * Ordinary Blade views are returned unchanged.
     */
    public static function prepareBlade(string $source): string
    {
        if (preg_match('/<laravelLayoute\b/i', $source)) {
            return self::prepareApplicationEntry($source);
        }

        if (! preg_match('/<pages\s*>/i', $source)) {
            return self::useDefaultLayoutWhenLegacyLayoutIsMissing($source);
        }

        $scriptCount = preg_match_all('~<script\s+setup\s+lang=(?:"ts"|\'ts\')\s*>(.*?)</script\s*>~is', $source);
        $pageCount = preg_match_all('~<pages\s*>(.*?)</pages\s*>~is', $source, $pages);
        $styleCount = preg_match_all('~<style\s+scoped\s*>(.*?)</style\s*>~is', $source);

        if ($scriptCount !== 1 || $pageCount !== 1 || $styleCount > 1) {
            throw new InvalidArgumentException('A single-file page requires one <script setup lang="ts"> block, one <pages> block, and at most one <style scoped> block.');
        }

        if (! trim($pages[1][0])) {
            throw new InvalidArgumentException('The <pages> block cannot be empty.');
        }

        if (preg_match('~<script\b(?!\s+setup\s+lang=(?:"ts"|\'ts\'))~i', $source)
            || preg_match('~<style\b(?!\s+scoped\s*>)~i', $source)) {
            throw new InvalidArgumentException('Single-file pages only support <script setup lang="ts"> and <style scoped>.');
        }

        if (preg_match('/@extends\s*\(/', $source)) {
            throw new InvalidArgumentException('Single-file pages use the framework layout automatically; remove @extends.');
        }

        $scope = substr(hash('sha256', $source), 0, 12);
        $compiled = preg_replace('~<script\s+setup\s+lang=(?:"ts"|\'ts\')\s*>.*?</script\s*>~is', '', $source);
        $compiled = preg_replace('~<style\s+scoped\s*>.*?</style\s*>~is', '', $compiled ?? $source);
        $compiled = preg_replace('/<pages\s*>/i', '<pages data-page-scope="'.$scope.'">', $compiled ?? $source, 1);
        $compiled = preg_replace('/<\/pages\s*>/i', '</pages>', $compiled ?? $source, 1);

        return $compiled ?? $source;
    }

    public static function isSingleFilePage(string $source): bool
    {
        return preg_match('/<pages\s*>/i', $source) === 1
            && preg_match('/<laravelLayoute\b/i', $source) !== 1;
    }

    /** @return array{scope: string, script: string, style: string}|null */
    public static function extract(string $source): ?array
    {
        if (! preg_match('/<pages\s*>/i', $source)) {
            return null;
        }

        if (preg_match_all('~<script\s+setup\s+lang=(?:"ts"|\'ts\')\s*>(.*?)</script\s*>~is', $source, $scripts) !== 1
            || preg_match_all('~<pages\s*>(.*?)</pages\s*>~is', $source) !== 1
            || preg_match_all('~<style\s+scoped\s*>(.*?)</style\s*>~is', $source, $styles) > 1) {
            throw new InvalidArgumentException('Invalid single-file page structure.');
        }

        return [
            'scope' => substr(hash('sha256', $source), 0, 12),
            'script' => trim($scripts[1][0]),
            'style' => trim($styles[1][0] ?? ''),
        ];
    }

    private static function prepareApplicationEntry(string $source): string
    {
        $pageRoots = preg_match_all('~<pages\s*>(.*?)</pages\s*>~is', $source, $pages);
        $layouts = preg_match_all('~<laravelLayoute\s*>(.*?)</laravelLayoute\s*>~is', $source, $layoutContents);
        $routers = preg_match_all('~<PageRouter\s*/\s*>~i', $source);

        if ($pageRoots !== 1 || $layouts !== 1 || $routers !== 1
            || ! preg_match('~^\s*<PageRouter\s*/\s*>\s*$~i', $layoutContents[1][0] ?? '')) {
            throw new InvalidArgumentException('src/index.blade.php must wrap one <PageRouter /> in <laravelLayoute> inside <pages>.');
        }

        return "@extends(\$frontendLayout)\n@section('content')\n{!! \$pageContent !!}\n@endsection";
    }

    private static function useDefaultLayoutWhenLegacyLayoutIsMissing(string $source): string
    {
        if (is_file(base_path('src/layouts/app.blade.php'))) {
            return $source;
        }

        return preg_replace(
            '/@extends\(\s*([\'\"])layouts\.app\\1\s*\)/',
            "@extends('layouts.default')",
            $source,
        ) ?? $source;
    }
}
