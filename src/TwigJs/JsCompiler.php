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

namespace TwigJs;

use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\InlinePrint;
use Twig\Profiler\Node\EnterProfileNode;
use Twig\Profiler\Node\LeaveProfileNode;
use TwigJs\Compiler\AutoEscapeCompiler;
use TwigJs\Compiler\BlockCompiler;
use TwigJs\Compiler\BlockReferenceCompiler;
use TwigJs\Compiler\BodyCompiler;
use TwigJs\Compiler\DoCompiler;
use TwigJs\Compiler\Expression\ArrayCompiler;
use TwigJs\Compiler\Expression\AssignNameCompiler;
use TwigJs\Compiler\Expression\Binary\AddCompiler;
use TwigJs\Compiler\Expression\Binary\AndCompiler;
use TwigJs\Compiler\Expression\Binary\BitwiseAndCompiler;
use TwigJs\Compiler\Expression\Binary\BitwiseOrCompiler;
use TwigJs\Compiler\Expression\Binary\BitwiseXorCompiler;
use TwigJs\Compiler\Expression\Binary\ConcatCompiler;
use TwigJs\Compiler\Expression\Binary\DivCompiler;
use TwigJs\Compiler\Expression\Binary\ElvisBinaryCompiler;
use TwigJs\Compiler\Expression\Binary\EqualCompiler;
use TwigJs\Compiler\Expression\Binary\FloorDivCompiler;
use TwigJs\Compiler\Expression\Binary\GreaterCompiler;
use TwigJs\Compiler\Expression\Binary\GreaterEqualCompiler;
use TwigJs\Compiler\Expression\Binary\InCompiler;
use TwigJs\Compiler\Expression\Binary\LessCompiler;
use TwigJs\Compiler\Expression\Binary\LessEqualCompiler;
use TwigJs\Compiler\Expression\Binary\ModCompiler;
use TwigJs\Compiler\Expression\Binary\MulCompiler;
use TwigJs\Compiler\Expression\Binary\NotEqualCompiler;
use TwigJs\Compiler\Expression\Binary\NotInCompiler;
use TwigJs\Compiler\Expression\Binary\NullCoalesceBinaryCompiler;
use TwigJs\Compiler\Expression\Binary\OrCompiler;
use TwigJs\Compiler\Expression\Binary\PowerCompiler;
use TwigJs\Compiler\Expression\Binary\RangeCompiler;
use TwigJs\Compiler\Expression\Binary\SubCompiler;
use TwigJs\Compiler\Expression\Ternary\ConditionalTernaryCompiler;
use TwigJs\Compiler\Expression\BlockReferenceCompiler as ExpressionBlockReferenceCompiler;
use TwigJs\Compiler\Expression\ConditionalCompiler;
use TwigJs\Compiler\Expression\ConstantCompiler;
use TwigJs\Compiler\Expression\DefaultFilterCompiler;
use TwigJs\Compiler\Expression\Filter\RawCompiler as FilterRawCompiler;
use TwigJs\Compiler\Expression\FilterCompiler;
use TwigJs\Compiler\Expression\FunctionCompiler;
use TwigJs\Compiler\Expression\MacroReferenceCompiler;
use TwigJs\Compiler\Expression\Variable\TemplateVariableCompiler;
use TwigJs\Compiler\Expression\Variable\AssignTemplateVariableCompiler;
use TwigJs\Compiler\Expression\GetAttrCompiler;
use TwigJs\Compiler\Expression\MethodCallCompiler;
use TwigJs\Compiler\Expression\NameCompiler;
use TwigJs\Compiler\Expression\NullCoalesceCompiler;
use TwigJs\Compiler\Expression\ParentCompiler;
use TwigJs\Compiler\Expression\TempNameCompiler;
use TwigJs\Compiler\Expression\TestCompiler;
use TwigJs\Compiler\Expression\Test\SameasCompiler as ExpressionTestSameasCompiler;
use TwigJs\Compiler\Expression\Test\TrueTestCompiler;
use TwigJs\Compiler\Expression\Unary\NegCompiler;
use TwigJs\Compiler\Expression\Unary\NotCompiler;
use TwigJs\Compiler\Expression\Unary\PosCompiler;
use TwigJs\Compiler\ForCompiler;
use TwigJs\Compiler\ForLoopCompiler;
use TwigJs\Compiler\IfCompiler;
use TwigJs\Compiler\ImportCompiler;
use TwigJs\Compiler\IncludeCompiler;
use TwigJs\Compiler\InlinePrintCompiler;
use TwigJs\Compiler\MacroCompiler;
use TwigJs\Compiler\ModuleCompiler;
use TwigJs\Compiler\NodeCompiler;
use TwigJs\Compiler\PrintCompiler;
use TwigJs\Compiler\SetCompiler;
use TwigJs\Compiler\SetTempCompiler;
use TwigJs\Compiler\Test\DefinedCompiler;
use TwigJs\Compiler\Test\DivisibleByCompiler;
use TwigJs\Compiler\Test\EmptyCompiler;
use TwigJs\Compiler\Test\EvenCompiler;
use TwigJs\Compiler\Test\NoneCompiler;
use TwigJs\Compiler\Test\NullCompiler;
use TwigJs\Compiler\Test\OddCompiler;
use TwigJs\Compiler\Test\SameAsCompiler;
use TwigJs\Compiler\TextCompiler;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\EmptyNode;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\BlockNode;
use Twig\Node\TextNode;
use Twig\Node\IfNode;
use Twig\Node\PrintNode;
use Twig\Node\ForNode;
use Twig\Node\ForLoopNode;
use Twig\Node\SetNode;
use Twig\Node\IncludeNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\AutoEscapeNode;
use Twig\Node\ImportNode;
use Twig\Node\MacroNode;
use Twig\Node\DoNode;
use Twig\Node\Expression\TempNameExpression;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\Node\Expression\Filter\RawFilter;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\MethodCallExpression;
use Twig\Node\Expression\Binary\AddBinary;
use Twig\Node\Expression\Binary\AndBinary;
use Twig\Node\Expression\Binary\BitwiseAndBinary;
use Twig\Node\Expression\Binary\BitwiseOrBinary;
use Twig\Node\Expression\Binary\BitwiseXorBinary;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\Binary\DivBinary;
use Twig\Node\Expression\Binary\EqualBinary;
use Twig\Node\Expression\Binary\FloorDivBinary;
use Twig\Node\Expression\Binary\GreaterBinary;
use Twig\Node\Expression\Binary\GreaterEqualBinary;
use Twig\Node\Expression\Binary\InBinary;
use Twig\Node\Expression\Binary\LessBinary;
use Twig\Node\Expression\Binary\LessEqualBinary;
use Twig\Node\Expression\Binary\ModBinary;
use Twig\Node\Expression\Binary\MulBinary;
use Twig\Node\Expression\Binary\NotEqualBinary;
use Twig\Node\Expression\Binary\NotInBinary;
use Twig\Node\Expression\Binary\OrBinary;
use Twig\Node\Expression\Binary\PowerBinary;
use Twig\Node\Expression\Binary\RangeBinary;
use Twig\Node\Expression\Binary\SubBinary;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\NotUnary;
use Twig\Node\Expression\Unary\PosUnary;
use Twig\Node\Expression\Test\ConstantTest;
use Twig\Node\Expression\Test\DefinedTest;
use Twig\Node\Expression\Test\DivisiblebyTest;
use Twig\Node\Expression\Test\EvenTest;
use Twig\Node\Expression\Test\NullTest;
use Twig\Node\Expression\Test\OddTest;
use Twig\Node\Expression\Test\SameasTest;
use Twig\Node\Expression\Test\TrueTest;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Node\Expression\Variable\AssignTemplateVariable;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\NullCoalesceExpression;
use Twig\Node\Expression\Binary\ElvisBinary;
use Twig\Node\Expression\Binary\NullCoalesceBinary;
use Twig\Node\Expression\Ternary\ConditionalTernary;

class JsCompiler extends Compiler
{
    /** Whether the current expression is a template name */
    public $isTemplateName = false;

    /** The function name of the current template */
    public $templateFunctionName;

    /** Map for local variables */
    public $localVarMap = array();

    private $defines = array();

    private $scopes = array();
    private $scopeVariables = array();
    private $functionNamingStrategy;

    private $typeCompilers;
    private $filterCompilers;
    private $testCompilers;

    private $filterFunctions;
    private $functionMap;

    private $resolveConstantTemplates = true;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->typeCompilers = [
            Node::class => new NodeCompiler(),
            Nodes::class => new NodeCompiler(),
            EmptyNode::class => new NodeCompiler(),
            BodyNode::class => new BodyCompiler(),
            ModuleNode::class => new ModuleCompiler\GoogleCompiler(),
            BlockNode::class => new BlockCompiler(),
            TextNode::class => new TextCompiler(),
            IfNode::class => new IfCompiler(),
            PrintNode::class => new PrintCompiler(),
            ForNode::class => new ForCompiler(),
            ForLoopNode::class => new ForLoopCompiler(),
            SetNode::class => new SetCompiler(),
            IncludeNode::class => new IncludeCompiler(),
            BlockReferenceNode::class => new BlockReferenceCompiler(),
            AutoEscapeNode::class => new AutoEscapeCompiler(),
            ImportNode::class => new ImportCompiler(),
            MacroNode::class => new MacroCompiler(),
            DoNode::class => new DoCompiler(),

            InlinePrint::class => new InlinePrintCompiler(),
            TempNameExpression::class => new TempNameCompiler(),
            LocalVariable::class => new TempNameCompiler(),
            TemplateVariable::class => new TemplateVariableCompiler(),
            AssignTemplateVariable::class => new AssignTemplateVariableCompiler(),
            MacroReferenceExpression::class => new MacroReferenceCompiler(),
            ConditionalExpression::class => new ConditionalCompiler(),
            ConditionalTernary::class => new ConditionalTernaryCompiler(),
            ArrayExpression::class => new ArrayCompiler(),
            FunctionExpression::class => new FunctionCompiler(),
            ParentExpression::class         => new ParentCompiler(),
            BlockReferenceExpression::class => new ExpressionBlockReferenceCompiler(),
            AssignNameExpression::class     => new AssignNameCompiler(),
            AssignContextVariable::class    => new AssignNameCompiler(),
            TestExpression::class           => new TestCompiler(),
            TrueTest::class                 => new TrueTestCompiler(),
            NameExpression::class           => new NameCompiler(),
            ContextVariable::class          => new NameCompiler(),
            FilterExpression::class         => new FilterCompiler(),
            DefaultFilter::class            => new DefaultFilterCompiler(),
            RawFilter::class                => new FilterRawCompiler(),
            ConstantExpression::class       => new ConstantCompiler(),
            GetAttrExpression::class        => new GetAttrCompiler(),
            MethodCallExpression::class     => new MethodCallCompiler(),
            NullCoalesceExpression::class   => new NullCoalesceCompiler(),
            ElvisBinary::class              => new ElvisBinaryCompiler(),
            NullCoalesceBinary::class       => new NullCoalesceBinaryCompiler(),

            AddBinary::class => new AddCompiler(),
            AndBinary::class => new AndCompiler(),
            BitwiseAndBinary::class => new BitwiseAndCompiler(),
            BitwiseOrBinary::class => new BitwiseOrCompiler(),
            BitwiseXorBinary::class => new BitwiseXorCompiler(),
            ConcatBinary::class => new ConcatCompiler(),
            DivBinary::class => new DivCompiler(),
            EqualBinary::class => new EqualCompiler(),
            FloorDivBinary::class => new FloorDivCompiler(),
            GreaterBinary::class => new GreaterCompiler(),
            GreaterEqualBinary::class => new GreaterEqualCompiler(),
            InBinary::class => new InCompiler(),
            LessBinary::class => new LessCompiler(),
            LessEqualBinary::class => new LessEqualCompiler(),
            ModBinary::class => new ModCompiler(),
            MulBinary::class => new MulCompiler(),
            NotEqualBinary::class => new NotEqualCompiler(),
            NotInBinary::class => new NotInCompiler(),
            OrBinary::class => new OrCompiler(),
            PowerBinary::class => new PowerCompiler(),
            RangeBinary::class => new RangeCompiler(),
            SubBinary::class => new SubCompiler(),

            NegUnary::class => new NegCompiler(),
            NotUnary::class => new NotCompiler(),
            PosUnary::class => new PosCompiler(),

            ConstantTest::class => new ConstantCompiler(),
            DefinedTest::class => new DefinedCompiler(),
            DivisiblebyTest::class => new DivisiblebyCompiler(),
            EvenTest::class => new EvenCompiler(),
            NullTest::class => new NullCompiler(),
            OddTest::class => new OddCompiler(),
            SameasTest::class => new ExpressionTestSameasCompiler()
        ];

        $this->testCompilers = [
            'defined' => new DefinedCompiler(),
            'divisibleby' => new DivisibleByCompiler(),
            'empty' => new EmptyCompiler(),
            'even' => new EvenCompiler(),
            'none' => new NoneCompiler(),
            'null' => new NullCompiler(),
            'odd' => new OddCompiler(),
            'same as' => new SameAsCompiler(),
        ];

        $this->filterCompilers = [];
        $this->filterFunctions = [
            '_default' => 'twig.filter.def',
            'abs' => 'twig.filter.abs',
            'batch' => 'twig.filter.batch',
            'capitalize' => 'twig.filter.capitalize',
            'default' => 'twig.filter.def',
            'e' => 'twig.filter.escape',
            'escape' => 'twig.filter.escape',
            'first' => 'twig.filter.first',
            'join' => 'twig.filter.join',
            'json_encode' => 'twig.filter.json_encode',
            'keys' => 'twig.filter.keys',
            'last' => 'twig.filter.last',
            'length' => 'twig.filter.length',
            'lower' => 'twig.filter.lower',
            'merge' => 'twig.filter.merge',
            'nl2br' => 'twig.filter.nl2br',
            'replace' => 'twig.filter.replace',
            'reverse' => 'twig.filter.reverse',
            'title' => 'twig.filter.title',
            'trim' => 'twig.filter.trim',
            'upper' => 'twig.filter.upper',
            'url_encode' => 'encodeURIComponent',
        ];

        $this->functionMap = [
            'max' => 'twig.functions.max',
            'min' => 'twig.functions.min',
            'random' => 'twig.functions.random',
            'range' => 'twig.range',
        ];
    }

    public function setResolveConstanteTemplates(bool $value)
    {
        $this->resolveConstantTemplates = $value;
    }

    public function shouldResolveConstantTemplates(): bool
    {
        return $this->resolveConstantTemplates;
    }

    public function setDefines(array $defines)
    {
        $this->defines = $defines;
    }

    public function setDefine($key, $value)
    {
        $this->defines[$key] = $value;
    }

    public function getDefine($key)
    {
        return $this->defines[$key] ?? null;
    }

    public function setFunctionNamingStrategy(FunctionNamingStrategyInterface $strategy)
    {
        $this->functionNamingStrategy = $strategy;
    }

    /**
     * Returns the function name for the given template name.
     *
     * @param ModuleNode $module
     * @return string
     */
    final public function getFunctionName(ModuleNode $module)
    {
        if (null === $this->functionNamingStrategy) {
            $this->functionNamingStrategy = new DefaultFunctionNamingStrategy();
        }

        return $this->functionNamingStrategy->getFunctionName($module);
    }

    public function setTypeCompilers(array $compilers): void
    {
        $this->typeCompilers = $compilers;
    }

    public function addTypeCompiler(TypeCompilerInterface $compiler): void
    {
        $this->typeCompilers[$compiler->getType()] = $compiler;
    }

    public function getTestCompiler($name)
    {
        return $this->testCompilers[$name] ?? null;
    }

    public function addTestCompiler(TestCompilerInterface $compiler): void
    {
        $this->testCompilers[$compiler->getName()] = $compiler;
    }

    public function getFilterFunction($name)
    {
        return $this->filterFunctions[$name] ?? null;
    }

    public function setFilterFunction($filterName, $functionName): void
    {
        $this->filterFunctions[$filterName] = $functionName;
    }

    public function getFilterCompiler($name)
    {
        return $this->filterCompilers[$name] ?? null;
    }

    public function addFilterCompiler(FilterCompilerInterface $compiler): void
    {
        $this->filterCompilers[$compiler->getName()] = $compiler;
    }

    public function setJsFunction($twigFunctionName, $jsFunctionName): void
    {
        $this->functionMap[$twigFunctionName] = $jsFunctionName;
    }

    public function getJsFunction($twigFunctionName): ?string
    {
        return $this->functionMap[$twigFunctionName] ?? null;
    }

    public function compile(Node $node, $indentation = 0)
    {
        $this->setPrivatePropOfParent('lastLine', null);
        $this->setPrivatePropOfParent('source', '');
        $this->setPrivatePropOfParent('debugInfo', []);
        $this->setPrivatePropOfParent('sourceOffset', 0);
        // source code starts at 1 (as we then increment it when we encounter new lines)
        $this->setPrivatePropOfParent('sourceLine', 1);
        $this->setPrivatePropOfParent('indentation', $indentation);
        $this->setPrivatePropOfParent('varNameSalt', 0);

        $nodeClass = get_class($node);

        if (!isset($this->typeCompilers[$nodeClass])) {
            throw new \RuntimeException(sprintf('There is no compiler for node type "%s".', $nodeClass));
        }

        $this->typeCompilers[$nodeClass]->compile($this, $node);

        return $this;
    }

    private function setPrivatePropOfParent(string $prop, $value)
    {
        \Closure::bind(function () use ($prop, $value) {
            $this->$prop = $value;
        }, $this, Compiler::class)();
    }

    private function getPrivatePropOfParent(string $prop)
    {
        return \Closure::bind(function () use ($prop) {
            return $this->$prop;
        }, $this, Compiler::class)();
    }

    public function subcompile(Node $node, $raw = true)
    {
        if ($node instanceof EnterProfileNode || $node instanceof LeaveProfileNode) {
            return $this;
        }

        if (false === $raw) {
            $this->write();
        }

        $nodeClass = get_class($node);

        if (!isset($this->typeCompilers[$nodeClass])) {
            throw new \RuntimeException(sprintf('There is no compiler for node type "%s".', $nodeClass));
        }

        $this->typeCompilers[$nodeClass]->compile($this, $node);

        return $this;
    }

    public function enterScope(): JsCompiler
    {
        $this->scopes[] = $this->scopeVariables;
        $this->scopeVariables = [];

        return $this;
    }

    public function leaveScope(): JsCompiler
    {
        if (false === $lastScope = array_pop($this->scopes)) {
            throw new \RuntimeException('leaveScope() must be called only after enterScope.');
        }

        $this->localVarMap = array_diff_key($this->localVarMap, $this->scopeVariables);
        $this->scopeVariables = $lastScope;
        $this->localVarMap = array_merge($this->localVarMap, $this->scopeVariables);

        return $this;
    }

    public function setVar($var, $localName): JsCompiler
    {
        $this->localVarMap[$var] =
        $this->scopeVariables[$var] = $localName;

        return $this;
    }

    public function unsetVar($var): JsCompiler
    {
        unset($this->localVarMap[$var]);

        return $this;
    }

    public function setTemplateName($name): JsCompiler
    {
        $this->isTemplateName = (boolean)$name;

        return $this;
    }

    public function string($value): JsCompiler
    {
        return $this->repr($value);
    }

    public function repr($value)
    {
        $this->raw(json_encode($value, JSON_THROW_ON_ERROR));
        return $this;
    }

    public function addDebugInfo(Node $node)
    {
        if ($node->getTemplateLine() !== $this->getPrivatePropOfParent('lastLine')) {
            $this->write(sprintf("/* line %d */\n", $node->getTemplateLine()));

            $sourceLine = $this->getPrivatePropOfParent('sourceLine');
            $source = $this->getPrivatePropOfParent('source');
            $sourceOffset = $this->getPrivatePropOfParent('sourceOffset');
            $debugInfo = $this->getPrivatePropOfParent('debugInfo');

            $this->setPrivatePropOfParent('sourceLine', $sourceLine + substr_count($source, "\n", $sourceOffset));
            $this->setPrivatePropOfParent('sourceOffset', \strlen($source));
            $this->setPrivatePropOfParent('debugInfo', $debugInfo + [$sourceLine => $node->getTemplateLine()]);
            $this->setPrivatePropOfParent('lastLine', $node->getTemplateLine());
        }

        return $this;
    }
}
