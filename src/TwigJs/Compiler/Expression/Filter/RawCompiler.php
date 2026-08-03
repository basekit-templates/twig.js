<?php

namespace TwigJs\Compiler\Expression\Filter;

use Twig\Node\Expression\Filter\RawFilter;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class RawCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return RawFilter::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof RawFilter) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    RawFilter::class,
                    get_class($node)
                )
            );
        }

        $compiler->subcompile($node->getNode('node'));
    }
}
