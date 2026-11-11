#!/usr/bin/env php
<?php

declare(strict_types=1);

final class ReleaseVersion
{
    public static function normalize(string $version): string
    {
        $version = trim($version);
        $version = preg_replace('/^v/i', '', $version) ?? $version;

        if (preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $version) !== 1) {
            throw new InvalidArgumentException("Invalid stable semantic version [{$version}]. Use MAJOR.MINOR.PATCH.");
        }

        return 'v'.$version;
    }

    /** @param list<string> $tags */
    public static function latest(array $tags): ?string
    {
        $versions = [];

        foreach ($tags as $tag) {
            try {
                $versions[] = self::normalize($tag);
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        if ($versions === []) {
            return null;
        }

        usort($versions, self::compare(...));

        return $versions[array_key_last($versions)];
    }

    public static function bump(?string $latest, string $part): string
    {
        $parts = $latest === null ? [0, 0, 0] : array_map('intval', explode('.', substr(self::normalize($latest), 1)));

        switch ($part) {
            case 'major':
                $parts[0]++;
                $parts[1] = 0;
                $parts[2] = 0;
                break;
            case 'minor':
                $parts[1]++;
                $parts[2] = 0;
                break;
            case 'patch':
                $parts[2]++;
                break;
            default:
                throw new InvalidArgumentException("Unknown version part [{$part}].");
        }

        return 'v'.implode('.', $parts);
    }

    public static function compare(string $left, string $right): int
    {
        $leftParts = array_map('intval', explode('.', substr(self::normalize($left), 1)));
        $rightParts = array_map('intval', explode('.', substr(self::normalize($right), 1)));

        return $leftParts <=> $rightParts;
    }

    /** @param list<string> $tags */
    public static function hasTag(array $tags, string $version): bool
    {
        foreach ($tags as $tag) {
            try {
                if (self::normalize($tag) === self::normalize($version)) {
                    return true;
                }
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return false;
    }
}

final class SplitReleaseCommand
{
    /** @var array<string, array{label: string, path: string, remote: string, repository: string, composer: string, packagist: string}> */
    private const PACKAGES = [
        'runtime' => [
            'label' => 'Laravel Runtime',
            'path' => 'packages/laravel',
            'remote' => 'runtime',
            'repository' => 'https://github.com/davingm/laravel-runtime.git',
            'composer' => 'davingm/laravel-runtime',
            'packagist' => 'https://packagist.org/packages/davingm/laravel-runtime',
        ],
        'devtools' => [
            'label' => 'Laravel Devtools',
            'path' => 'packages/devtools',
            'remote' => 'devtools',
            'repository' => 'https://github.com/davingm/laravel-devtools.git',
            'composer' => 'davingm/laravel-devtools',
            'packagist' => 'https://packagist.org/packages/davingm/laravel-devtools',
        ],
    ];

    private string $root;
    private mixed $terminalInput = null;
    private bool $stdinEnded = false;

    public function __construct()
    {
        $this->root = dirname(__DIR__);
    }

    /** @param list<string> $arguments */
    public function run(array $arguments): int
    {
        if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
            $this->usage();

            return 0;
        }

        if ($arguments !== []) {
            fwrite(STDERR, "Unknown option: ".implode(' ', $arguments)."\n");
            $this->usage();

            return 1;
        }

        try {
            $this->assertRepository();
            $selected = $this->choosePackages();
            $this->assertMainIsSynchronized();

            $versions = [];
            $remoteMains = [];
            foreach ($selected as $key) {
                $package = self::PACKAGES[$key];
                $this->assertRemote($package['remote'], $package['repository']);
                $latest = ReleaseVersion::latest($this->remoteTags($package['remote']));
                $versions[$key] = $this->chooseVersion($package['label'], $latest);
                $remoteMains[$key] = $this->fetchRemoteMain($package['remote']);
            }

            $this->assertIndexIsClean();
            $this->commitPackageChanges($selected);

            $commit = $this->git(['rev-parse', 'HEAD']);
            $splitCommits = [];
            foreach ($selected as $key) {
                $splitCommit = $this->splitPackage($key);
                $splitCommits[$key] = $this->prepareSplitForPush($key, $splitCommit, $remoteMains[$key]);
            }

            $this->line('');
            $this->line('Rencana rilis:');
            $this->line('  Commit monorepo: '.$commit);
            foreach ($selected as $key) {
                $package = self::PACKAGES[$key];
                $split = $splitCommits[$key];
                $branchAction = $split['pushBranch'] ? 'update main' : 'main unchanged';
                $this->line("  {$package['label']}: {$versions[$key]} -> {$package['remote']}/main ({$branchAction}, {$split['commit']})");
            }
            $this->line('  Packagist diperbarui melalui webhook GitHub setelah tag didorong.');

            if (! $this->confirm('Lanjutkan push commit dan package release?', false)) {
                $this->line('Dibatalkan; commit source mungkin sudah tersimpan lokal, tetapi belum ada push.');

                return 0;
            }

            foreach ($selected as $key) {
                if (ReleaseVersion::hasTag($this->remoteTags(self::PACKAGES[$key]['remote']), $versions[$key])) {
                    throw new RuntimeException("Remote tag [{$versions[$key]}] appeared during planning; no push was attempted.");
                }
                if ($this->fetchRemoteMain(self::PACKAGES[$key]['remote']) !== $remoteMains[$key]) {
                    throw new RuntimeException('A package main branch changed during planning. No push was attempted; rerun the release command.');
                }
            }

            $this->pushMonorepo($commit);
            foreach ($selected as $key) {
                $this->releasePackage($key, $versions[$key], $splitCommits[$key]);
            }

            $this->line('');
            $this->line('Rilis selesai. Verifikasi setiap versi di halaman Packagist:');
            foreach ($selected as $key) {
                $package = self::PACKAGES[$key];
                $this->line("  {$package['composer']} {$versions[$key]} — {$package['packagist']}");
            }

            return 0;
        } catch (Throwable $exception) {
            fwrite(STDERR, "\nGagal: {$exception->getMessage()}\n");

            return 1;
        }
    }

    private function assertRepository(): void
    {
        $root = $this->git(['rev-parse', '--show-toplevel']);
        if (realpath($root) !== realpath($this->root)) {
            throw new RuntimeException('Run this command from the Laravel monorepo.');
        }

        $this->assertRemote('origin', 'https://github.com/davingm/laravel.git');

        foreach (self::PACKAGES as $package) {
            $manifestPath = $this->root.DIRECTORY_SEPARATOR.$package['path'].DIRECTORY_SEPARATOR.'composer.json';
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            if (($manifest['name'] ?? null) !== $package['composer']) {
                throw new RuntimeException("Unexpected Composer package name in [{$package['path']}/composer.json].");
            }
        }
    }

    private function isExpectedRepository(string $url, string $repository): bool
    {
        $url = strtolower(trim($url));
        $expected = 'davingm/'.$repository;

        return str_ends_with($url, 'github.com:'.$expected.'.git')
            || str_ends_with($url, 'github.com/'.$expected.'.git')
            || str_ends_with($url, 'github.com:'.$expected)
            || str_ends_with($url, 'github.com/'.$expected);
    }

    /** @return list<string> */
    private function choosePackages(): array
    {
        $this->line('Package mana yang akan dirilis?');
        $this->line('  1) Runtime (packages/laravel)');
        $this->line('  2) Devtools (packages/devtools)');
        $this->line('  3) Keduanya [default]');

        return match ($this->ask('Pilihan', '3')) {
            '1' => ['runtime'],
            '2' => ['devtools'],
            '3' => ['runtime', 'devtools'],
            default => throw new InvalidArgumentException('Choose 1, 2, or 3.'),
        };
    }

    private function assertMainIsSynchronized(): void
    {
        $branch = $this->git(['branch', '--show-current']);
        if ($branch !== 'main') {
            throw new RuntimeException("Current branch is [{$branch}], not [main]. Switch to main before releasing.");
        }

        $this->git(['fetch', '--quiet', 'origin', 'main']);
        $head = $this->git(['rev-parse', 'HEAD']);
        $originHead = $this->git(['rev-parse', 'origin/main']);
        $isBasedOnOrigin = $this->gitResult(['merge-base', '--is-ancestor', $originHead, $head]);
        if ($isBasedOnOrigin['exitCode'] !== 0) {
            $counts = $this->git(['rev-list', '--left-right', '--count', 'origin/main...HEAD']);
            [$behind, $ahead] = array_map('intval', preg_split('/\s+/', trim($counts)) ?: [0, 0]);
            throw new RuntimeException("Local main and origin/main have diverged (local is {$ahead} ahead and {$behind} behind). Sync them before release.");
        }
    }

    private function assertRemote(string $remote, string $repository): void
    {
        $expectedName = basename($repository, '.git');
        $fetchUrls = preg_split('/\R/', trim($this->git(['remote', 'get-url', '--all', $remote]))) ?: [];
        $pushUrls = preg_split('/\R/', trim($this->git(['remote', 'get-url', '--push', '--all', $remote]))) ?: [];

        foreach (array_merge($fetchUrls, $pushUrls) as $url) {
            if (! $this->isExpectedRepository($url, $expectedName)) {
                throw new RuntimeException("Remote [{$remote}] must point to [{$repository}], but found [{$url}].");
            }
        }

        if (count($pushUrls) !== 1) {
            throw new RuntimeException("Remote [{$remote}] must have exactly one push URL.");
        }
    }

    /** @return list<string> */
    private function remoteTags(string $remote): array
    {
        $output = $this->git(['ls-remote', '--tags', '--refs', $remote]);
        $tags = [];

        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if (preg_match('~^[0-9a-f]+\s+refs/tags/(.+)$~i', $line, $matches) === 1) {
                $tags[] = $matches[1];
            }
        }

        return $tags;
    }

    private function fetchRemoteMain(string $remote): string
    {
        $this->git(['fetch', '--quiet', '--no-tags', $remote, 'refs/heads/main']);
        $commit = $this->git(['rev-parse', 'FETCH_HEAD']);

        if (preg_match('/^[0-9a-f]{40,64}$/i', $commit) !== 1) {
            throw new RuntimeException("Could not read [{$remote}/main].");
        }

        return $commit;
    }

    private function chooseVersion(string $label, ?string $latest): string
    {
        $this->line('');
        $this->line($label.' — versi terbaru: '.($latest ?? '(belum ada tag stabil)'));
        $this->line('  1) Patch  '.ReleaseVersion::bump($latest, 'patch'));
        $this->line('  2) Minor  '.ReleaseVersion::bump($latest, 'minor'));
        $this->line('  3) Major  '.ReleaseVersion::bump($latest, 'major'));
        $this->line('  4) Masukkan versi sendiri');

        $choice = $this->ask('Pilih bump', '1');
        $version = match ($choice) {
            '1' => ReleaseVersion::bump($latest, 'patch'),
            '2' => ReleaseVersion::bump($latest, 'minor'),
            '3' => ReleaseVersion::bump($latest, 'major'),
            '4' => ReleaseVersion::normalize($this->ask('Versi baru (contoh 1.2.3)', '')),
            default => throw new InvalidArgumentException('Choose 1, 2, 3, or 4.'),
        };

        if ($latest !== null && ReleaseVersion::compare($version, $latest) <= 0) {
            throw new InvalidArgumentException("Version [{$version}] must be greater than latest tag [{$latest}].");
        }

        return $version;
    }

    private function assertIndexIsClean(): void
    {
        if ($this->gitResult(['diff', '--cached', '--quiet'])['exitCode'] !== 0) {
            throw new RuntimeException('The Git index has staged changes. Commit or unstage them before using the release CLI.');
        }
    }

    /** @param list<string> $selected */
    private function commitPackageChanges(array $selected): void
    {
        $paths = array_map(static fn (string $key): string => self::PACKAGES[$key]['path'], $selected);
        $status = $this->git(['status', '--short', '--untracked-files=all', '--', ...$paths]);
        if (trim($status) === '') {
            $this->line('Tidak ada perubahan package terpilih untuk di-commit; rilis akan memakai HEAD saat ini.');

            return;
        }

        $this->line('');
        $this->line('Perubahan package yang akan di-commit:');
        $this->line($status);
        if (! $this->confirm('Stage hanya folder package terpilih?', false)) {
            throw new RuntimeException('Commit dibatalkan.');
        }

        $this->git(['add', '--', ...$paths]);
        $staged = $this->git(['diff', '--cached', '--name-only']);
        if (trim($staged) === '') {
            throw new RuntimeException('Tidak ada perubahan yang masuk ke staging.');
        }

        $this->line('File yang akan masuk commit:');
        $this->line($staged);
        $message = trim($this->ask('Pesan commit', ''));
        if ($message === '') {
            throw new RuntimeException('Pesan commit tidak boleh kosong.');
        }
        if (! $this->confirm("Commit dengan pesan [{$message}]?", false)) {
            throw new RuntimeException('Commit dibatalkan. Perubahan tetap berada di staging.');
        }

        $this->git(['commit', '-m', $message]);
    }

    private function pushMonorepo(string $commit): void
    {
        $originHead = $this->git(['rev-parse', 'origin/main']);
        if ($commit !== $originHead) {
            $this->line('Push commit source ke origin/main...');
            $this->git(['push', 'origin', 'main']);
        }
    }

    private function splitPackage(string $key): string
    {
        $package = self::PACKAGES[$key];
        $split = $this->git(['subtree', 'split', '--prefix='.$package['path'], 'HEAD']);
        $lines = preg_split('/\R/', trim($split)) ?: [];
        $splitCommit = trim((string) end($lines));
        if (preg_match('/^[0-9a-f]{40,64}$/i', $splitCommit) !== 1) {
            throw new RuntimeException("Could not read the split commit for [{$package['path']}].");
        }

        $this->line('');
        $this->line("Split {$package['path']}:");
        $this->line($this->git(['show', '--stat', '--oneline', $splitCommit]));

        return $splitCommit;
    }

    /** @param array{commit: string, pushBranch: bool} $split */
    private function releasePackage(string $key, string $version, array $split): void
    {
        $package = self::PACKAGES[$key];
        if (ReleaseVersion::hasTag($this->remoteTags($package['remote']), $version)) {
            throw new RuntimeException("Remote tag [{$version}] appeared during planning; package branch was not pushed.");
        }

        if ($split['pushBranch']) {
            $this->line("Push {$package['remote']}/main...");
            $this->git(['push', $package['remote'], $split['commit'].':refs/heads/main']);
        } else {
            $this->line("{$package['remote']}/main sudah berisi source yang sama; hanya tag yang akan didorong.");
        }

        $this->line("Push tag {$version}...");
        $this->git(['push', $package['remote'], $split['commit'].':refs/tags/'.$version]);
        $this->line("Rilis {$package['composer']} {$version} didorong. Packagist akan diperbarui oleh webhook GitHub jika webhook package aktif.");
    }

    /** @return array{commit: string, pushBranch: bool} */
    private function prepareSplitForPush(string $key, string $splitCommit, string $remoteMain): array
    {
        $package = self::PACKAGES[$key];
        $ancestor = $this->gitResult(['merge-base', '--is-ancestor', $remoteMain, $splitCommit]);
        if ($ancestor['exitCode'] === 0) {
            return ['commit' => $splitCommit, 'pushBranch' => true];
        }
        if ($ancestor['exitCode'] !== 1) {
            throw new RuntimeException("Could not compare [{$package['remote']}/main] with the package split.");
        }

        $remoteTree = $this->git(['rev-parse', $remoteMain.'^{tree}']);
        $splitTree = $this->git(['rev-parse', $splitCommit.'^{tree}']);
        if ($remoteTree === $splitTree) {
            return ['commit' => $remoteMain, 'pushBranch' => false];
        }

        $splitHistory = $this->git(['log', '--format=%H%x09%T', $splitCommit]);
        foreach (preg_split('/\R/', trim($splitHistory)) ?: [] as $line) {
            [$historicalCommit, $historicalTree] = array_pad(explode("\t", $line, 2), 2, '');
            if ($historicalTree !== $remoteTree) {
                continue;
            }

            $mergeCommit = $this->git([
                'commit-tree',
                $splitTree,
                '-p',
                $remoteMain,
                '-p',
                $splitCommit,
                '-m',
                'chore: sync '.$package['path'].' with package repository history',
            ]);

            if (
                $this->gitResult(['merge-base', '--is-ancestor', $remoteMain, $mergeCommit])['exitCode'] !== 0
                || $this->gitResult(['merge-base', '--is-ancestor', $splitCommit, $mergeCommit])['exitCode'] !== 0
                || $this->git(['rev-parse', $mergeCommit.'^{tree}']) !== $splitTree
            ) {
                throw new RuntimeException("Could not safely connect [{$package['remote']}/main] to the package split.");
            }

            $this->line("Remote tree matches historical package split {$historicalCommit}; preserving both histories.");

            return ['commit' => $mergeCommit, 'pushBranch' => true];
        }

        throw new RuntimeException(
            "[{$package['remote']}/main] has commits or files not included in the split for [{$package['path']}]. "
            .'No push was attempted because the remote tree has no matching checkpoint in the split history. Review and reconcile the package source manually, '
            .'commit and push the sync to origin/main, then rerun composer release. Do not force-push or overwrite the package branch.'
        );
    }

    private function ask(string $prompt, string $default): string
    {
        echo $prompt.($default === '' ? ': ' : " [{$default}]: ");
        fflush(STDOUT);

        $input = $this->stdinEnded ? false : fgets(STDIN);
        if ($input === false) {
            $this->stdinEnded = true;
            $this->terminalInput ??= $this->openTerminalInput();
            if (! is_resource($this->terminalInput)) {
                throw new RuntimeException('No interactive input is available. Run composer release in a terminal.');
            }
            $input = fgets($this->terminalInput);
        }
        if ($input === false) {
            throw new RuntimeException('Input ended before the release flow completed.');
        }

        $input = trim($input);

        return $input === '' ? $default : $input;
    }

    private function confirm(string $prompt, bool $default): bool
    {
        $answer = strtolower($this->ask($prompt.' (y/n, ya/tidak)', $default ? 'y' : 'n'));
        if (! in_array($answer, ['y', 'yes', 's', 'setuju', 'ya', 'iya', 'n', 'no', 'tidak'], true)) {
            throw new InvalidArgumentException('Jawab y/ya/s untuk setuju atau n/tidak untuk membatalkan.');
        }

        return in_array($answer, ['y', 'yes', 's', 'setuju', 'ya', 'iya'], true);
    }

    private function openTerminalInput(): mixed
    {
        $devices = DIRECTORY_SEPARATOR === '\\' ? ['CONIN$'] : ['/dev/tty'];
        foreach ($devices as $device) {
            $stream = @fopen($device, 'r');
            if (is_resource($stream)) {
                return $stream;
            }
        }

        return null;
    }

    /** @param list<string> $arguments */
    private function git(array $arguments): string
    {
        $result = $this->gitResult($arguments);
        if ($result['exitCode'] !== 0) {
            $command = implode(' ', array_map('escapeshellarg', ['git', ...$arguments]));
            $detail = trim($result['stderr']."\n".$result['stdout']);
            throw new RuntimeException("Command failed: {$command}".($detail === '' ? '' : "\n{$detail}"));
        }

        return trim($result['stdout']);
    }

    /** @param list<string> $arguments
     *  @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function gitResult(array $arguments): array
    {
        $pipes = [];
        $process = proc_open(['git', ...$arguments], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->root, null, ['bypass_shell' => true]);

        if (! is_resource($process)) {
            throw new RuntimeException('Could not start Git. Run this command from Git Bash or ensure Git is on PATH.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout === false ? '' : $stdout,
            'stderr' => $stderr === false ? '' : $stderr,
        ];
    }

    private function usage(): void
    {
        $this->line('Usage: composer release');
        $this->line('Starts the interactive split-release flow for packages/laravel and packages/devtools.');
    }

    private function line(string $message): void
    {
        fwrite(STDOUT, $message.PHP_EOL);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit((new SplitReleaseCommand())->run(array_slice($argv, 1)));
}
