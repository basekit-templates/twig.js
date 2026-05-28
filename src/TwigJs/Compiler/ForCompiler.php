<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TwigJs\Compiler;

use Twig\Node\ForNode;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class ForCompiler implements TypeCompilerInterface
{
    private $count = 0;

    public function getType()
    {
        return ForNode::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof ForNode) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    ForNode::class,
                    get_class($node)
                )
            );
        }

        $count = $this->count++;
        $suffix = $count > 0 ? $count : '';
        $seqName = 'seq'.$suffix;
        $keyName = 'k'.$suffix;
        $valueName = 'v'.$suffix;
        $iteratedName = 'iterated'.$suffix;
        $loopName = 'loop'.$suffix;

        if ($count > 0) {
            $compiler->enterScope();
        }

        $compiler
            ->setVar('_seq', $seqName)
            ->setVar('_iterated', $iteratedName)
            ->setVar('loop', $loopName)
            ->addDebugInfo($node)
        ;

        // keep parent reference as some might rely on this
        if (0 === $count) {
            $compiler->write("context['_parent'] = context;\n");
        }

        $compiler
            ->write("var $seqName = ")
            ->subcompile($node->getNode('seq'))
            ->raw(";\n")
        ;

        if ($node->hasNode('else') && null !== $node->getNode('else')) {
            $compiler->write("var $iteratedName = false;\n");
        }

        $loopData = [
            'index0' => 0,
            'index' => 1,
            'first' => true
        ];

        if ($count > 0) {
            $parentSuffix = ($count - 1 > 0) ? $count - 1 : '';
            $loopData['parent'] = "loop$parentSuffix";
        }

        $compiler
            ->write(sprintf('var %s = %s;', $loopName, json_encode($loopData, JSON_THROW_ON_ERROR)))
            ->write("\n");

        if (!$node->hasAttribute('ifexpr') || false === $node->getAttribute('ifexpr')) {
            $compiler
                ->write("if (twig.countable($seqName)) {\n")
                ->indent()
                ->write("var length = twig.count($seqName);\n")
                ->write("{$loopName}['revindex0'] = length - 1;\n")
                ->write("{$loopName}['revindex'] = length;\n")
                ->write("{$loopName}['length'] = length;\n")
                ->write("{$loopName}['last'] = 1 === length;\n")
                ->outdent()
                ->write("}\n");
        }

        $ref = new \ReflectionProperty($node, 'loop');
        $ref->setAccessible(true);
        $loop = $ref->getValue($node);
        $loop->setAttribute('else', $node->hasNode('else') && null !== $node->getNode('else'));
        $loop->setAttribute('with_loop', $node->getAttribute('with_loop'));
        $loop->setAttribute('ifexpr', $node->hasAttribute('ifexpr') && $node->getAttribute('ifexpr'));

        $compiler
            ->write("twig.forEach($seqName, function($valueName, $keyName) {\n")
            ->indent()
            ->write("")
            ->subcompile($node->getNode('key_target'))
            ->raw(" = $keyName;\n")
            ->write("")
            ->subcompile($node->getNode('value_target'))
            ->raw(" = $valueName;\n")
            ->subcompile($node->getNode('body'))
            ->outdent()
            ->write("}, this);\n")
        ;

        if ($node->hasNode('else') && null !== $node->getNode('else')) {
            $compiler
                ->write("if (!$iteratedName) {\n")
                ->indent()
                ->subcompile($node->getNode('else'))
                ->outdent()
                ->write("}\n")
            ;
        }

        if ($count > 0) {
            $compiler->leaveScope();
        }
        $this->count = $count;
    }
}
