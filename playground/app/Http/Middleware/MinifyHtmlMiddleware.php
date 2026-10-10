<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinifyHtmlMiddleware
{
    /**
     * Minify HTML responses in production.
     *
     * Safe rules:
     *   - Collapse whitespace between tags
     *   - Strip HTML comments (except IE conditionals and scripts)
     *   - Remove whitespace-only text nodes between block elements
     *
     * Preserved:
     *   - Content inside <pre>, <textarea>, <script>, <style>
     *   - IE conditional comments <!--[if ...]>
     *   - JSON payload <script type="application/json">
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldMinify($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return $response;
        }

        $response->setContent($this->minify($content));

        return $response;
    }

    private function shouldMinify(Request $request, Response $response): bool
    {
        // Active when APP_ENV=production OR when preview mode is actively running.
        // We verify that the preview process is still alive so that stale flag files
        // from unclean shutdowns (e.g. Ctrl+C on Windows) do not pollute dev mode.
        $isProduction = config('app.env') === 'production';
        $isPreview = $this->isPreviewActive();

        if (! $isProduction && ! $isPreview) {
            return false;
        }

        // Only HTML responses
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return false;
        }

        // Skip AJAX / partial requests
        if ($request->ajax()) {
            return false;
        }

        return true;
    }

    private function isPreviewActive(): bool
    {
        if (env('AUTO_PREVIEW') === '1' || env('AUTO_PREVIEW') === true) {
            return true;
        }

        $flagFile = base_path('.laravel/.preview');
        if (! file_exists($flagFile)) {
            return false;
        }

        $pid = (int) trim((string) @file_get_contents($flagFile));
        if ($pid > 0 && ! $this->isProcessAlive($pid)) {
            @unlink($flagFile);

            return false;
        }

        return true;
    }

    private function isProcessAlive(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            @exec("tasklist /FI \"PID eq {$pid}\" 2>nul", $output);

            return str_contains(implode(' ', $output), (string) $pid);
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return file_exists("/proc/{$pid}");
    }

    private function minify(string $html): string
    {
        $protected = [];
        $index = 0;

        // ── 0. Strip laravel/boost browser logger before protecting blocks ────
        // This is a require-dev tool injected by InjectBoost middleware.
        // It has no place in production output — strip it first so it does not
        // end up in the protected blocks and gets silently preserved.
        $html = preg_replace(
            '/<script[^>]*id=["\']browser-logger-active["\'][^>]*>.*?<\/script>/si',
            '',
            $html,
        ) ?? $html;

        // ── 1. Protect blocks whose content must not be touched ───────────────
        // Strategy: find each opening tag, then find its matching closing tag
        // using stripos — no regex on the content itself, so no backtracking issues.
        $tags = ['script', 'style', 'pre', 'textarea'];

        foreach ($tags as $tag) {
            $open = '<'.$tag;
            $close = '</'.$tag.'>';
            $offset = 0;

            while (($start = stripos($html, $open, $offset)) !== false) {
                // Find end of opening tag (could have attributes)
                $openEnd = strpos($html, '>', $start);
                if ($openEnd === false) {
                    break;
                }

                $closePos = stripos($html, $close, $openEnd);
                if ($closePos === false) {
                    break;
                }

                $end = $closePos + strlen($close);
                $block = substr($html, $start, $end - $start);

                $placeholder = "\x00PROTECT{$index}\x00";
                $protected[$placeholder] = $block;
                $html = substr($html, 0, $start).$placeholder.substr($html, $end);

                // Continue searching after the placeholder
                $offset = $start + strlen($placeholder);
                $index++;
            }
        }

        // ── 2. Protect IE conditional comments ───────────────────────────────
        $html = preg_replace_callback(
            '/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/si',
            function (array $m) use (&$protected, &$index): string {
                $placeholder = "\x00PROTECT{$index}\x00";
                $protected[$placeholder] = $m[0];
                $index++;

                return $placeholder;
            },
            $html,
        ) ?? $html;

        // ── 3. Strip HTML comments ────────────────────────────────────────────
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        // ── 4. Collapse whitespace ────────────────────────────────────────────
        $html = preg_replace('/[ \t\r\n]+/', ' ', $html) ?? $html;
        $html = preg_replace('/> </', '><', $html) ?? $html;
        $html = trim($html);

        // ── 5. Restore protected blocks ───────────────────────────────────────
        foreach ($protected as $placeholder => $original) {
            $html = str_replace($placeholder, $original, $html);
        }

        return $html;
    }
}
