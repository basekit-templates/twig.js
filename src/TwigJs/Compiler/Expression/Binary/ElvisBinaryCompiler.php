<?php

namespace TwigJs\Compiler\Expression\Binary;

use Twig\Node\Expression\Binary\ElvisBinary;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class ElvisBinaryCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return ElvisBinary::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof ElvisBinary) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    ElvisBinary::class,
                    get_class($node)
                )
            );
        }

        $compiler
            ->raw('((')
            ->subcompile($node->getNode('test'))
            ->raw(') ? (')
            ->subcompile($node->getNode('left'))
            ->raw(') : (')
            ->subcompile($node->getNode('right'))
            ->raw('))')
        ;
    }
}
