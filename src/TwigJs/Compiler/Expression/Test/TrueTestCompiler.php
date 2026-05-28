<?php

namespace TwigJs\Compiler\Expression\Test;

use Twig\Node\Expression\Test\TrueTest;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class TrueTestCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return TrueTest::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof TrueTest) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    TrueTest::class,
                    get_class($node)
                )
            );
        }

        $compiler->subcompile($node->getNode('node'));
    }
}
