<?php

namespace TwigJs\Compiler\Expression\Binary;

use Twig\Node\Expression\Binary\NullCoalesceBinary;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class NullCoalesceBinaryCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return NullCoalesceBinary::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof NullCoalesceBinary) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    NullCoalesceBinary::class,
                    get_class($node)
                )
            );
        }

        $compiler
            ->raw('((')
            ->subcompile($node->getNode('left'))
            ->raw(') ?? (')
            ->subcompile($node->getNode('right'))
            ->raw('))')
        ;
    }
}
