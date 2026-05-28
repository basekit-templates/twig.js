<?php

namespace TwigJs\Compiler\Expression\Test;

use Twig\Node\Expression\Test\SameasTest;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class SameasCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return SameasTest::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof SameasTest) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    SameasTest::class,
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
