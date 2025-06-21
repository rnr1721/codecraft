<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

/**
 * PHP file adapter for creating and editing PHP classes
 */
class PhpAdapter implements FileAdapterInterface
{
    private $parser;
    private $printer;
    private BuilderFactory $factory;

    public function __construct()
    {
        $parserFactory = new ParserFactory();
        $this->parser = ($this->parser = $parserFactory->createForNewestSupportedVersion());
        $this->printer = new PrettyPrinter\Standard();
        $this->factory = new BuilderFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['php'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $namespace = $options['namespace'] ?? null;
        $className = $options['name'] ?? basename($filePath, '.php');
        $extends = $options['extends'] ?? null;
        $implements = $options['implements'] ?? [];
        $methods = $options['methods'] ?? [];
        $properties = $options['properties'] ?? [];

        // Create class builder
        $class = $this->factory->class($className);

        if ($extends) {
            $class->extend($extends);
        }

        foreach ($implements as $interface) {
            $class->implement($interface);
        }

        // Add properties
        foreach ($properties as $property) {
            $prop = $this->factory->property($property['name']);

            if (isset($property['visibility'])) {
                $prop->{'make' . ucfirst($property['visibility'])}();
            }

            if (isset($property['type'])) {
                $prop->setType($property['type']);
            }

            if (isset($property['default'])) {
                $prop->setDefault($property['default']);
            }

            $class->addStmt($prop);
        }

        // Add methods
        foreach ($methods as $method) {
            $methodBuilder = $this->factory->method($method['name']);

            if (isset($method['visibility'])) {
                $methodBuilder->{'make' . ucfirst($method['visibility'])}();
            }

            if (isset($method['returnType'])) {
                $methodBuilder->setReturnType($method['returnType']);
            }

            if (isset($method['params'])) {
                foreach ($method['params'] as $param) {
                    $paramBuilder = $this->factory->param($param['name']);

                    if (isset($param['type'])) {
                        $paramBuilder->setType($param['type']);
                    }

                    // Only set default if explicitly provided
                    if (array_key_exists('default', $param)) {
                        $paramBuilder->setDefault($param['default']);
                    }

                    $methodBuilder->addParam($paramBuilder);
                }
            }

            if (isset($method['body'])) {
                // Parse method body if provided
                $body = "<?php " . $method['body'];
                try {
                    $bodyAst = $this->parser->parse($body);
                    if ($bodyAst && count($bodyAst) > 0) {
                        // Get all statements except the opening <?php
                        $statements = [];
                        foreach ($bodyAst as $node) {
                            if ($node instanceof Node\Stmt\InlineHTML) {
                                continue; // Skip HTML nodes
                            }
                            $statements[] = $node;
                        }
                        if (!empty($statements)) {
                            $methodBuilder->addStmts($statements);
                        }
                    }
                } catch (\Exception $e) {
                    // If parsing fails, add as comment
                    $methodBuilder->addStmt(
                        new Node\Stmt\Expression(
                            new Node\Expr\ConstFetch(new Node\Name('null'))
                        )
                    );
                }
            }

            $class->addStmt($methodBuilder);
        }

        $nodes = [];

        // Add namespace if specified
        if ($namespace) {
            $nodes[] = $this->factory->namespace($namespace)->addStmt($class)->getNode();
        } else {
            $nodes[] = $class->getNode();
        }

        return "<?php\n\ndeclare(strict_types=1);\n\n" . $this->printer->prettyPrint($nodes);
    }

    /**
     * {@inheritdoc}
     */
    public function edit(string $filePath, array $modifications): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $code = file_get_contents($filePath);
        $ast = $this->parser->parse($code);

        if (!$ast) {
            throw new \RuntimeException("Could not parse PHP file: {$filePath}");
        }

        foreach ($modifications as $modification) {
            $ast = $this->applyModification($ast, $modification);
        }

        return $this->printer->prettyPrintFile($ast);
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $code = file_get_contents($filePath);
        $ast = $this->parser->parse($code);

        if (!$ast) {
            return [];
        }

        $finder = new NodeFinder();
        $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);
        $interfaces = $finder->findInstanceOf($ast, Node\Stmt\Interface_::class);
        $namespaces = $finder->findInstanceOf($ast, Node\Stmt\Namespace_::class);

        $analysis = [
            'type' => 'php',
            'namespace' => null,
            'classes' => [],
            'interfaces' => [],
            'functions' => []
        ];

        // Get namespace
        if (!empty($namespaces)) {
            $namespace = $namespaces[0];
            $analysis['namespace'] = $namespace->name ? $namespace->name->toString() : null;
        }

        // Analyze classes
        foreach ($classes as $class) {
            $analysis['classes'][] = $this->analyzeClass($class);
        }

        // Analyze interfaces
        foreach ($interfaces as $interface) {
            $analysis['interfaces'][] = $this->analyzeInterface($interface);
        }

        return $analysis;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        try {
            $ast = $this->parser->parse($content);
            return $ast !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_class' => true,
            'create_interface' => true,
            'add_method' => true,
            'add_property' => true,
            'supports_namespace' => true,
            'supports_inheritance' => true,
            'supports_interfaces' => true,
            'supports_traits' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'php';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): array
    {
        return [
            'description' => 'PHP adapter for creating and editing PHP classes with full AST support',
            'supported_types' => [
                'class' => 'Create PHP classes with methods, properties, and inheritance',
                'interface' => 'Create PHP interfaces',
                'trait' => 'Create PHP traits'
            ],
            'examples' => [
                'basic_class' => [
                    'description' => 'Create a simple PHP class',
                    'code' => '$codecraft->create("User.php", [
    "namespace" => "App\\Models",
    "name" => "User",
    "methods" => [
        [
            "name" => "getName",
            "visibility" => "public",
            "returnType" => "string",
            "body" => "return \$this->name;"
        ]
    ]
]);'
                ],
                'full_class' => [
                    'description' => 'Create a class with properties, inheritance, and interfaces',
                    'code' => '$codecraft->create("User.php", [
    "namespace" => "App\\Models",
    "name" => "User",
    "extends" => "Model",
    "implements" => ["UserInterface"],
    "properties" => [
        [
            "name" => "email",
            "type" => "string",
            "visibility" => "protected"
        ]
    ],
    "methods" => [
        [
            "name" => "getEmail",
            "visibility" => "public",
            "returnType" => "string",
            "body" => "return \$this->email;"
        ]
    ]
]);'
                ],
                'editing' => [
                    'description' => 'Add method to existing class',
                    'code' => '$codecraft->edit("User.php", [
    [
        "type" => "add_method",
        "method" => [
            "name" => "setEmail",
            "visibility" => "public",
            "params" => [["name" => "email", "type" => "string"]],
            "body" => "\$this->email = \$email;"
        ]
    ]
]);'
                ]
            ],
            'options' => [
                'namespace' => 'PHP namespace for the class',
                'name' => 'Class name',
                'extends' => 'Parent class to extend',
                'implements' => 'Array of interfaces to implement',
                'properties' => 'Array of class properties',
                'methods' => 'Array of class methods'
            ],
            'property_options' => [
                'name' => 'Property name',
                'type' => 'Property type hint',
                'visibility' => 'public, protected, or private',
                'default' => 'Default value'
            ],
            'method_options' => [
                'name' => 'Method name',
                'visibility' => 'public, protected, or private',
                'static' => 'Boolean, make method static',
                'returnType' => 'Return type hint',
                'params' => 'Array of parameters',
                'body' => 'Method body code'
            ],
            'modification_types' => [
                'add_method' => 'Add new method to class',
                'add_property' => 'Add new property to class',
                'replace_method' => 'Replace existing method'
            ]
        ];
    }

    /**
     * Apply a single modification to the AST
     */
    private function applyModification(array $ast, array $modification): array
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'add_method':
                return $this->addMethodToClass($ast, $modification);
            case 'add_property':
                return $this->addPropertyToClass($ast, $modification);
            case 'replace_method':
                return $this->replaceMethodInClass($ast, $modification);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Analyze a class node
     */
    private function analyzeClass(Node\Stmt\Class_ $class): array
    {
        $methods = [];
        $properties = [];

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $methods[] = [
                    'name' => $stmt->name->name,
                    'visibility' => $this->getVisibility($stmt),
                    'static' => (bool)($stmt->flags & Node\Stmt\Class_::MODIFIER_STATIC),
                    'abstract' => (bool)($stmt->flags & Node\Stmt\Class_::MODIFIER_ABSTRACT),
                    'final' => (bool)($stmt->flags & Node\Stmt\Class_::MODIFIER_FINAL),
                    'return_type' => $stmt->returnType ? (string)$stmt->returnType : null
                ];
            } elseif ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    $properties[] = [
                        'name' => $prop->name->name,
                        'visibility' => $this->getVisibility($stmt),
                        'static' => (bool)($stmt->flags & Node\Stmt\Class_::MODIFIER_STATIC),
                        'type' => $stmt->type ? (string)$stmt->type : null
                    ];
                }
            }
        }

        return [
            'name' => $class->name->name,
            'extends' => $class->extends ? $class->extends->toString() : null,
            'implements' => array_map(fn ($i) => $i->toString(), $class->implements),
            'abstract' => $class->isAbstract(),
            'final' => $class->isFinal(),
            'methods' => $methods,
            'properties' => $properties
        ];
    }

    /**
     * Analyze an interface node
     */
    private function analyzeInterface(Node\Stmt\Interface_ $interface): array
    {
        $methods = [];

        foreach ($interface->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $methods[] = [
                    'name' => $stmt->name->name,
                    'return_type' => $stmt->returnType ? (string)$stmt->returnType : null
                ];
            }
        }

        return [
            'name' => $interface->name->name,
            'extends' => array_map(fn ($i) => $i->toString(), $interface->extends),
            'methods' => $methods
        ];
    }

    /**
     * Get visibility of a node
     */
    private function getVisibility(Node $node): string
    {
        if ($node->flags & Node\Stmt\Class_::MODIFIER_PRIVATE) {
            return 'private';
        }
        if ($node->flags & Node\Stmt\Class_::MODIFIER_PROTECTED) {
            return 'protected';
        }
        return 'public';
    }

    /**
     * Add method to class in AST
     */
    private function addMethodToClass(array $ast, array $modification): array
    {
        $methodConfig = $modification['method'];
        $finder = new NodeFinder();

        // Find the first class in AST
        $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

        if (empty($classes)) {
            throw new \RuntimeException("No class found in AST to add method to");
        }

        $class = $classes[0]; // Add to first class found

        // Create new method
        $methodBuilder = $this->factory->method($methodConfig['name']);

        if (isset($methodConfig['visibility'])) {
            $methodBuilder->{'make' . ucfirst($methodConfig['visibility'])}();
        }

        if (isset($methodConfig['static']) && $methodConfig['static']) {
            $methodBuilder->makeStatic();
        }

        if (isset($methodConfig['returnType'])) {
            $methodBuilder->setReturnType($methodConfig['returnType']);
        }

        if (isset($methodConfig['params'])) {
            foreach ($methodConfig['params'] as $param) {
                $paramBuilder = $this->factory->param($param['name']);

                if (isset($param['type'])) {
                    $paramBuilder->setType($param['type']);
                }

                // Only set default if explicitly provided
                if (array_key_exists('default', $param)) {
                    $paramBuilder->setDefault($param['default']);
                }

                $methodBuilder->addParam($paramBuilder);
            }
        }

        if (isset($methodConfig['body'])) {
            // Parse method body if provided
            $body = "<?php " . $methodConfig['body'];
            try {
                $bodyAst = $this->parser->parse($body);
                if ($bodyAst && count($bodyAst) > 0) {
                    // Get all statements except the opening <?php
                    $statements = [];
                    foreach ($bodyAst as $node) {
                        if ($node instanceof Node\Stmt\InlineHTML) {
                            continue; // Skip HTML nodes
                        }
                        $statements[] = $node;
                    }
                    if (!empty($statements)) {
                        $methodBuilder->addStmts($statements);
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, add as raw code comment
                $methodBuilder->addStmt(
                    new Node\Stmt\Expression(
                        new Node\Expr\ConstFetch(new Node\Name('null'))
                    )
                );
            }
        }

        // Add method to class
        $class->stmts[] = $methodBuilder->getNode();

        return $ast;
    }

    /**
     * Add property to class in AST
     */
    private function addPropertyToClass(array $ast, array $modification): array
    {
        $propertyConfig = $modification['property'];
        $finder = new NodeFinder();

        // Find the first class in AST
        $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

        if (empty($classes)) {
            throw new \RuntimeException("No class found in AST to add property to");
        }

        $class = $classes[0]; // Add to first class found

        // Create new property
        $prop = $this->factory->property($propertyConfig['name']);

        if (isset($propertyConfig['visibility'])) {
            $prop->{'make' . ucfirst($propertyConfig['visibility'])}();
        }

        if (isset($propertyConfig['static']) && $propertyConfig['static']) {
            $prop->makeStatic();
        }

        if (isset($propertyConfig['type'])) {
            $prop->setType($propertyConfig['type']);
        }

        if (isset($propertyConfig['default'])) {
            $prop->setDefault($propertyConfig['default']);
        }

        // Add property to class (insert at beginning, before methods)
        $insertAt = 0;
        foreach ($class->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $insertAt = $index;
                break;
            }
            $insertAt = $index + 1;
        }

        array_splice($class->stmts, $insertAt, 0, [$prop->getNode()]);

        return $ast;
    }

    /**
     * Replace method in class in AST
     */
    private function replaceMethodInClass(array $ast, array $modification): array
    {
        $methodName = $modification['method_name'];
        $methodConfig = $modification['method'];
        $finder = new NodeFinder();

        // Find the first class in AST
        $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

        if (empty($classes)) {
            throw new \RuntimeException("No class found in AST to replace method in");
        }

        $class = $classes[0];

        // Find and replace the method
        foreach ($class->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === $methodName) {
                // Create new method with same approach as addMethodToClass
                $methodBuilder = $this->factory->method($methodConfig['name'] ?? $methodName);

                if (isset($methodConfig['visibility'])) {
                    $methodBuilder->{'make' . ucfirst($methodConfig['visibility'])}();
                }

                if (isset($methodConfig['static']) && $methodConfig['static']) {
                    $methodBuilder->makeStatic();
                }

                if (isset($methodConfig['returnType'])) {
                    $methodBuilder->setReturnType($methodConfig['returnType']);
                }

                if (isset($methodConfig['params'])) {
                    foreach ($methodConfig['params'] as $param) {
                        $paramBuilder = $this->factory->param($param['name']);

                        if (isset($param['type'])) {
                            $paramBuilder->setType($param['type']);
                        }

                        // Only set default if explicitly provided
                        if (array_key_exists('default', $param)) {
                            $paramBuilder->setDefault($param['default']);
                        }

                        $methodBuilder->addParam($paramBuilder);
                    }
                }

                if (isset($methodConfig['body'])) {
                    $body = "<?php " . $methodConfig['body'];
                    try {
                        $bodyAst = $this->parser->parse($body);
                        if ($bodyAst && count($bodyAst) > 0) {
                            // Get all statements except the opening <?php
                            $statements = [];
                            foreach ($bodyAst as $node) {
                                if ($node instanceof Node\Stmt\InlineHTML) {
                                    continue; // Skip HTML nodes
                                }
                                $statements[] = $node;
                            }
                            if (!empty($statements)) {
                                $methodBuilder->addStmts($statements);
                            }
                        }
                    } catch (\Exception $e) {
                        $methodBuilder->addStmt(
                            new Node\Stmt\Expression(
                                new Node\Expr\ConstFetch(new Node\Name('null'))
                            )
                        );
                    }
                }

                // Replace the method
                $class->stmts[$index] = $methodBuilder->getNode();
                return $ast;
            }
        }

        throw new \RuntimeException("Method '{$methodName}' not found in class");
    }
}
