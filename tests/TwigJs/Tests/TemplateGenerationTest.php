<?php

namespace TwigJs\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Source;
use TwigJs\Twig\TwigJsExtension;
use TwigJs\JsCompiler;

class TemplateGenerationTest extends TestCase
{
    /**
     * @dataProvider getGenerationTests
     */
    #[DataProvider('getGenerationTests')]
    public function testGenerate($inputFile, $outputFile)
    {
        $env = new Environment(new FilesystemLoader(__DIR__.'/Fixture/templates', getcwd()));
        $env->addExtension(new TwigJsExtension());
        $env->setCompiler(new JsCompiler($env));

        $source = file_get_contents($inputFile);

        $expected = file_get_contents($outputFile);
        $actual = $env->compileSource(new Source($source, basename($inputFile), $inputFile));

        $expected = \str_replace("\r\n", "\n", $expected);

        self::assertEquals(
            $expected,
            $actual
        );
    }

    public static function getGenerationTests()
    {
        $tests = array();
        $files = new \RecursiveDirectoryIterator(
            __DIR__ . '/Fixture/templates',
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        foreach ($files as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile()) {
                continue;
            }

            $tests[] = array(
                $file->getRealPath(),
                __DIR__.'/Fixture/generated/'.basename($file, '.twig').'.js',
            );
        }

        return $tests;
    }
}
