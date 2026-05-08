<?php

namespace PhpstanUi;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UiErrorFormatter implements ErrorFormatter
{
    /**
     * @param AnalysisResult $analysisResult
     * @param OutputInterface $output
     * @return int
     */
    public function formatErrors($analysisResult, $output)
    {
        // Build JSON payload
        $files = array();
        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $file = $error->getFile();
            if (!isset($files[$file])) {
                $files[$file] = array('errors' => 0, 'messages' => array());
            }
            $files[$file]['errors']++;
            $files[$file]['messages'][] = array(
                'message'   => $error->getMessage(),
                'line'      => $error->getLine(),
                'ignorable' => $error->canBeIgnored(),
                'tip'       => $error->getTip(),
            );
        }

        $payload = array(
            'totals' => array(
                'errors'      => $analysisResult->getTotalErrorsCount(),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
            ),
            'files'  => $files,
            'errors' => array_map(function ($e) {
                return $e->getMessage();
            }, $analysisResult->getNotFileSpecificErrors()),
        );

        // Write to temp file
        $tmpFile = sys_get_temp_dir() . '/phpstan-ui-result.json';
        file_put_contents($tmpFile, json_encode($payload));

        // Pick a port
        $port = 8742;

        $output->writeln('<info>PHPStan UI running at http://localhost:' . $port . '</info>');
        $output->writeln('<comment>Press Ctrl+C to stop.</comment>');

        // Open browser (cross-platform)
        $this->openBrowser('http://localhost:' . $port);

        // Start built-in PHP server
        $serverRoot = dirname(__DIR__) . '/server';
        $routerFile = $serverRoot . '/router.php';

        passthru(
            PHP_BINARY . " -S localhost:{$port} -t " . escapeshellarg($serverRoot) . ' ' . escapeshellarg($routerFile)
        );

        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }

    /**
     * @param string $url
     * @return void
     */
    private function openBrowser($url)
    {
        if (defined('PHP_OS_FAMILY')) {
            $osFamily = PHP_OS_FAMILY;
        } else {
            // PHP <7.2 compatibility: fallback detection
            if (stripos(PHP_OS, 'WIN') === 0) {
                $osFamily = 'Windows';
            } elseif (stripos(PHP_OS, 'DAR') === 0) {
                $osFamily = 'Darwin';
            } else {
                $osFamily = 'Linux';
            }
        }

        if ($osFamily === 'Windows') {
            if (function_exists('popen')) {
                popen("start " . escapeshellarg($url), 'r');
            } else {
                exec("start " . escapeshellarg($url));
            }
        } elseif ($osFamily === 'Darwin') {
            exec("open " . escapeshellarg($url));
        } else {
            exec("xdg-open " . escapeshellarg($url));
        }
    }
}
