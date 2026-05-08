<?php

namespace PhpstanUi;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UiErrorFormatter implements ErrorFormatter
{
    public function formatErrors(
        AnalysisResult $analysisResult,
        OutputInterface $output
    ): int {
        // Build JSON payload
        $files = [];
        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $file = $error->getFile();
            if (!isset($files[$file])) {
                $files[$file] = ['errors' => 0, 'messages' => []];
            }
            $files[$file]['errors']++;
            $files[$file]['messages'][] = [
                'message'   => $error->getMessage(),
                'line'      => $error->getLine(),
                'ignorable' => $error->canBeIgnored(),
                'tip'       => $error->getTip(),
            ];
        }

        $payload = [
            'totals' => [
                'errors'      => $analysisResult->getTotalErrorsCount(),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
            ],
            'files'  => $files,
            'errors' => array_map(function ($e) {
                return $e->getMessage();
            }, $analysisResult->getNotFileSpecificErrors()),
        ];

        // Write to temp file
        $tmpFile = sys_get_temp_dir() . '/phpstan-ui-result.json';
        file_put_contents($tmpFile, json_encode($payload));

        // Pick a port
        $port = 8742;

        $output->writeln("<info>PHPStan UI running at http://localhost:{$port}</info>");
        $output->writeln("<comment>Press Ctrl+C to stop.</comment>");

        // Open browser (cross-platform)
        $this->openBrowser("http://localhost:{$port}");

        // Start built-in PHP server
        $serverRoot = __DIR__ . '/../server';
        $routerFile = $serverRoot . '/router.php';

        passthru(
            PHP_BINARY . " -S localhost:{$port} -t {$serverRoot} {$routerFile}"
        );

        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }

    private function openBrowser(string $url): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            popen("start {$url}", 'r');
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open {$url}");
        } else {
            exec("xdg-open {$url}");
        }
    }
}
