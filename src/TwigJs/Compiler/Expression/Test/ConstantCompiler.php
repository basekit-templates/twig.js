<?php

namespace TwigJs\Compiler\Expression\Test;

use Twig\Node\Expression\Test\ConstantTest;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class ConstantCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return ConstantTest::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof ConstantTest) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    ConstantTest::class,
                    get_class($node)
                )
            );
        }

        $compiler->subcompile(
            new TestExpression(
                $node->getNode('node'),
                $node->getAttribute('name'),
                $node->hasNode('arguments') ? $node->getNode('arguments') : null,
                $node->getTemplateLine()
            )
        );
    }
}
