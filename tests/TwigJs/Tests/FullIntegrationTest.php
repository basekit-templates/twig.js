<?php

namespace TwigJs\Tests;

use Datto\JsonRpc\Http\Client;
use Datto\JsonRpc\Responses\ErrorResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Source;
use TwigJs\Twig\TwigJsExtension;
use TwigJs\JsCompiler;

class FullIntegrationTest extends TestCase
{
    /** @var Client */
    private static $rpc;

    /** @var ArrayLoader */
    private $arrayLoader;

    /** @var Environment */
    private $env;

    public static function setUpBeforeClass(): void
    {
        self::$rpc = new Client('http://0.0.0.0:7070');
    }

    public static function tearDownAfterClass(): void
    {
        self::$rpc->query( 'exit', [], $response);
        self::$rpc->send();
    }

    private function renderTemplate($name, $javascript, $parameters)
    {
        $output = '';
        self::$rpc->query( 'render', [$name, $javascript, $parameters], $output);
        self::$rpc->send();

        if ($output instanceof ErrorResponse) {
            throw new \ErrorException($output->getMessage());
        }

        return $output;
    }

    public function setUp(): void
    {
        $this->arrayLoader = new ArrayLoader(array());
        $this->env = new Environment($this->arrayLoader);
        $this->env->addExtension(new TwigJsExtension());
        $this->env->setLoader(
            new ChainLoader(
                array(
                    $this->arrayLoader,
                    new FilesystemLoader(__DIR__.'/Fixture/integration', getcwd())
                )
            )
        );
        $this->env->setCompiler(new JsCompiler($this->env));
    }

    /**
     * @test
     * @dataProvider getIntegrationTests
     */
    #[Test]
    #[DataProvider('getIntegrationTests')]
    public function integrationTest($file, $message, $data, $templates, $exception, $expectedOutput)
    {
        $javascript = '';

        foreach ($templates as $name => $twig) {
            $this->arrayLoader->setTemplate($name, $twig);
        }

        foreach ($templates as $name => $twig) {
            $javascript .= $this->compileTemplate($twig, $name);
        }

        $renderedOutput = $this->renderTemplate('index.twig', $javascript, $data);

        self::assertEquals($expectedOutput, $renderedOutput);
    }

    public static function getIntegrationTests()
    {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/Fixture/integration');
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/\.test/', RecursiveRegexIterator::GET_MATCH);

        foreach (array_keys(iterator_to_array($regex)) as $file) {
            yield $file => self::loadTest($file);
        }
    }

    public static function loadTest($file)
    {
        $fp = fopen($file, "rb");

        if (!feof($fp)) {
            $line = fgets($fp);

            if ($line === false) {
                throw new \InvalidArgumentException(sprintf('Cannot read test file "%s"', $file));
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Test "%s" file is empty', $file));
        }

        if (strncmp('--TEST--', $line, 8)) {
            throw new \InvalidArgumentException(sprintf('Test must start with --TEST-- [%s]', $file));
        }

        $section = 'TEST';
        $templateName = false;
        $sectionText = ['TEST' => ''];

        $sections = [
            'EXPECT', 'TEMPLATE', 'DATA'
        ];

        while (!feof($fp)) {
            $line = fgets($fp);

            if ($line === false) {
                break;
            }

            // Match the beginning of a section.
            if (preg_match('/^--([_A-Z]+)(?:\(([_a-z.]*)\))?--/', $line, $match)) {
                $section = (string) $match[1];
                $templateName = null;

                // check for unknown sections
                if (!in_array($section, $sections)) {
                    throw new \InvalidArgumentException(sprintf('Unknown section [%s] [%s]', $section, $file));
                }

                if ($section === 'TEMPLATE') {
                    $templateName = (string) ($match[2] ?? 'index.twig');
                }

                if ($templateName) {
                    $sectionText[$section][$templateName] = '';
                } else {
                    $sectionText[$section] = '';
                }
                continue;
            }

            if ($templateName) {
                $sectionText[$section][$templateName] .= $line;
            } else {
                $sectionText[$section] .= $line;
            }
        }

        return array(
            $file,
            $sectionText['TEST'],
            $sectionText['DATA'],
            $sectionText['TEMPLATE'],
            null, //$exception,
            $sectionText['EXPECT']
        );
    }

    private function compileTemplate($source, $name)
    {
        return $this->env->compileSource(new Source($source, $name));
    }
}
