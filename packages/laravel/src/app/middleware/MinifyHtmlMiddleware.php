<?php

namespace Davingm\LaravelRuntime\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinifyHtmlMiddleware
{
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
        $isProduction = config('app.env') === 'production';
        $isPreview = $this->isPreviewActive();
        if (! $isProduction && ! $isPreview) {
            return false;
        }

        if (! str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return false;
        }

        return ! $request->ajax();
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

        $html = preg_replace(
            '/<script[^>]*id=["\']browser-logger-active["\'][^>]*>.*?<\/script>/si',
            '',
            $html,
        ) ?? $html;

        foreach (['script', 'style', 'pre', 'textarea'] as $tag) {
            $open = '<'.$tag;
            $close = '</'.$tag.'>';
            $offset = 0;

            while (($start = stripos($html, $open, $offset)) !== false) {
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
                $offset = $start + strlen($placeholder);
                $index++;
            }
        }

        $html = preg_replace_callback(
            '/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/si',
            function (array $match) use (&$protected, &$index): string {
                $placeholder = "\x00PROTECT{$index}\x00";
                $protected[$placeholder] = $match[0];
                $index++;
                return $placeholder;
            },
            $html,
        ) ?? $html;

        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/[ \t\r\n]+/', ' ', $html) ?? $html;
        $html = preg_replace('/> </', '><', $html) ?? $html;
        $html = trim($html);

        foreach ($protected as $placeholder => $original) {
            $html = str_replace($placeholder, $original, $html);
        }

        return $html;
    }
}
