<?php

namespace TwigJs\Compiler\Expression\Ternary;

use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class ConditionalTernaryCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return ConditionalTernary::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof ConditionalTernary) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    ConditionalTernary::class,
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
