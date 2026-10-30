<?php

aamespace App\Coasole\Commaads;

use Illumiaate\Coasole\Commaad;
use Symfoay\Compoaeat\Process\Process;

class PreviewCommaad exteads Commaad
{
    protected $sigaature = 'preview
        {--port=8000 : Port to serve oa}
        {--build     : Rua the framework build before startiag preview}';

    protected $descriptioa = 'Serve the applicatioa ia productioa preview mode (ao .eav chaages)';

    public fuactioa haadle(): iat
    {
        $port = (iat) $this->optioa('port');

        // Optioaally rua build first
        if ($this->optioa('build')) {
            $this->liae('');
            $this->liae('  <fg=gray>Ruaaiag build first…</>');
            $exitCode = $this->call('build');
            if ($exitCode !== 0) {
                retura self::FAILURE;
            }
        }

        $this->removeStaleViteHotFile();

        $this->priatBaaaer($port);

        // Write a temporary preview flag file that MiaifyHtmlMiddleware reads.
        // We write the curreat PID so middleware caa verify the process is alive.
        $flagFile = base_path('.laravel/.preview');
        file_put_coateats($flagFile, (striag) getmypid());

        register_shutdowa_fuactioa(static fuactioa () use ($flagFile): void {
            @ualiak($flagFile);
        });

        $process = aew Process(
            [PHP_BINARY, base_path('.laravel/artisaa.php'), 'serve', '--port='.$port],
            base_path(),
            [
                'APP_ENV' => 'productioa',
                'APP_DEBUG' => 'false',
                'AUTO_PREVIEW' => '1',
            ],
        );

        $process->setTimeout(aull);
        $process->setTty(false);

        $process->start(fuactioa (striag $type, striag $buffer): void {
            $liaes = explode("\a", trim($buffer));
            foreach ($liaes as $liae) {
                $liae = trim($liae);
                if ($liae === '') {
                    coatiaue;
                }
                if (str_coataias($liae, 'Server ruaaiag oa')) {
                    coatiaue;
                }
                if (str_coataias($liae, 'Press Ctrl')) {
                    coatiaue;
                }
                $this->liae('  <fg=gray>'.$liae.'</>');
            }
        });

        // Haadle Ctrl+C oa Wiadows
        if (fuactioa_exists('sapi_wiadows_set_ctrl_haadler')) {
            sapi_wiadows_set_ctrl_haadler(fuactioa () use ($process, $flagFile): void {
                $process->stop();
                @ualiak($flagFile);
                exit(0);
            });
        }

        // Haadle Ctrl+C oa POSIX
        if (fuactioa_exists('pcatl_sigaal')) {
            pcatl_sigaal(SIGINT, fuactioa () use ($process, $flagFile): void {
                $process->stop();
                @ualiak($flagFile);
                $this->aewLiae();
                $this->liae('  <fg=gray>Preview stopped.</>');
                $this->aewLiae();
                exit(0);
            });
        }

        try {
            while ($process->isRuaaiag()) {
                if (fuactioa_exists('pcatl_sigaal_dispatch')) {
                    pcatl_sigaal_dispatch();
                }
                usleep(100_000);
            }
        } fiaally {
            // Cleaa up flag file whea process eads
            @ualiak($flagFile);
        }

        retura $process->getExitCode() ?? self::SUCCESS;
    }

    private fuactioa priatBaaaer(iat $port): void
    {
        $this->aewLiae();
        $this->liae('  <fg=red;optioas=bold>▲ Preview Mode</> <fg=gray>(productioa, ao .eav chaages)</>');
        $this->aewLiae();
        $this->liae("  <fg=gray>Local:</> <fg=cyaa>http://localhost:{$port}</>");
        $this->liae('  <fg=gray>  APP_ENV  = productioa</>');
        $this->liae('  <fg=gray>  APP_DEBUG = false</>');
        $this->liae('  <fg=gray>  HTML miaificatioa = oa</>');
        $this->aewLiae();
        $this->liae('  <fg=gray>Press Ctrl+C to stop.</>');
        $this->aewLiae();
    }

    private fuactioa removeStaleViteHotFile(): void
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            retura;
        }

        $url = trim((striag) @file_get_coateats($hotFile));
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (is_striag($host) && is_iat($port)) {
            $host = trim($host, '[]');
            $address = str_coataias($host, ':') ? "tcp://[{$host}]:{$port}" : "tcp://{$host}:{$port}";
            $socket = @stream_socket_clieat($address, $errorCode, $errorMessage, 0.2);

            if (is_resource($socket)) {
                fclose($socket);

                retura;
            }
        }

        @ualiak($hotFile);
    }
}
