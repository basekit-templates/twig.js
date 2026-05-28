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

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use TwigJs\JsCompiler;
use TwigJs\TypeCompilerInterface;

class IncludeCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return IncludeNode::class;
    }

    public function compile(JsCompiler $compiler, Node $node)
    {
        if (!$node instanceof IncludeNode) {
            throw new \RuntimeException(
                sprintf(
                    '$node must be an instanceof of %s, but got "%s".',
                    IncludeNode::class,
                    get_class($node)
                )
            );
        }

        $compiler->addDebugInfo($node);

        // Is there are use case for conditional includes at runtime?
//         if ($node->getAttribute('ignore_missing')) {
//             $compiler
//                 ->write("try {\n")
//                 ->indent()
//             ;
//         }

        $compiler->setTemplateName(true);
        if ($node->getNode('expr') instanceof ConstantExpression) {
            $compiler
                ->write("(new ")
                ->write('twig.templates[')
                ->subcompile($node->getNode('expr'))
                ->raw(']')
                ->raw("(this.env_)).render_(sb, ")
            ;
        } else {
            $compiler
                ->write("(new ")
                ->write('twig.templates[')
                ->subcompile($node->getNode('expr'))
                ->raw(']')
                ->raw("(this.env_)).render_(sb, ")
            ;
        }
        $compiler->setTemplateName(false);

        if (false === $node->getAttribute('only')) {
            if (!$node->hasNode('variables') || null === $node->getNode('variables')) {
                $compiler->raw('context');
            } else {
                $compiler
                    ->raw('twig.extend({}, context, ')
                    ->subcompile($node->getNode('variables'))
                    ->raw(')')
                ;
            }
        } else {
            if (!$node->hasNode('variables')) {
                $compiler->raw('{}');
            } else {
                $compiler->subcompile($node->getNode('variables'));
            }
        }

        $compiler->raw(");\n");

//         if ($node->getAttribute('ignore_missing')) {
//             $compiler
//                 ->outdent()
//                 ->write("} catch (Twig_Error_Loader \$e) {\n")
//                 ->indent()
//                 ->write("// ignore missing template\n")
//                 ->outdent()
//                 ->write("}\n\n")
//             ;
//         }
    }
}
