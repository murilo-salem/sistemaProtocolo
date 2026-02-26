<?php

namespace Tests\Functional;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ControllerSyntaxTest extends TestCase
{
    public function testAllControllerFilesHaveValidPhpSyntax(): void
    {
        $base = APP_ROOT . '/app/control';
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

        $checked = 0;

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
            $output = shell_exec($cmd);

            $this->assertNotNull($output, 'Falha ao executar lint para: ' . $path);
            $this->assertStringContainsString('No syntax errors detected', $output, 'Erro de sintaxe em: ' . $path . "\n" . $output);
            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
    }
}
