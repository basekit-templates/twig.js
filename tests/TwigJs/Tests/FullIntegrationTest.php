<?php

namespace TwigJs\Tests;

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
    /** @var string|null */
    private static $rpcHost = null;

    /** @var ArrayLoader */
    private $arrayLoader;

    /** @var Environment */
    private $env;

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('curl_init')) {
            self::markTestSkipped('cURL extension is not available - skipping integration tests');
            return;
        }

        $host = getenv('JSON_RPC_HOST') ?: '0.0.0.0';
        $socket = @fsockopen($host, 7070, $errno, $errstr, 1);
        if (!$socket) {
            self::markTestSkipped('JSON-RPC server not available at ' . $host . ':7070 - skipping integration tests');
            return;
        }
        fclose($socket);
        self::$rpcHost = $host;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$rpcHost === null) {
            return;
        }
        try {
            self::callRpc('exit', []);
        } catch (\Exception $e) {
            // Server may have already exited
        }
    }

    /**
     * Send a JSON-RPC 2.0 request over HTTP using cURL.
     * cURL is used instead of file_get_contents so that tests work regardless
     * of the allow_url_fopen php.ini setting.
     *
     * @param string $method
     * @param array  $params
     * @return mixed The value of the "result" field in the response.
     * @throws \RuntimeException on transport or server-side error.
     */
    private static function callRpc(string $method, array $params)
    {
        $request = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => $method,
            'params'  => $params,
        ]);

        $ch = curl_init('http://' . self::$rpcHost . ':7070');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('JSON-RPC call failed: ' . $error);
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new \RuntimeException($decoded['error']['message'] ?? 'Unknown server error');
        }

        return $decoded['result'] ?? null;
    }

    private function renderTemplate($name, $javascript, $parameters)
    {
        return self::callRpc('render', [$name, $javascript, $parameters]);
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
