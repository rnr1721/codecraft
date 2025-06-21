<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * JavaScript file adapter for creating and editing JS files
 */
class JavaScriptAdapter implements FileAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['js', 'jsx', 'mjs'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $type = $options['type'] ?? 'function'; // function, class, component, module

        switch ($type) {
            case 'class':
                return $this->createClass($options);
            case 'component':
                return $this->createReactComponent($options);
            case 'function':
                return $this->createFunction($options);
            case 'module':
                return $this->createModule($options);
            default:
                throw new \InvalidArgumentException("Unknown JavaScript type: {$type}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function edit(string $filePath, array $modifications): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);

        foreach ($modifications as $modification) {
            $content = $this->applyModification($content, $modification);
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);

        return [
            'type' => 'javascript',
            'functions' => $this->extractFunctions($content),
            'classes' => $this->extractClasses($content),
            'imports' => $this->extractImports($content),
            'exports' => $this->extractExports($content),
            'components' => $this->extractComponents($content)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        // Basic JS validation - check for syntax errors
        // In real implementation, you might use a JS parser

        // Check for basic syntax issues
        $unclosedBraces = substr_count($content, '{') - substr_count($content, '}');
        $unclosedParens = substr_count($content, '(') - substr_count($content, ')');
        $unclosedBrackets = substr_count($content, '[') - substr_count($content, ']');

        return $unclosedBraces === 0 && $unclosedParens === 0 && $unclosedBrackets === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_function' => true,
            'create_class' => true,
            'create_component' => true,
            'create_module' => true,
            'add_function' => true,
            'add_method' => true,
            'supports_es6' => true,
            'supports_jsx' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'javascript';
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
            'description' => 'JavaScript/JSX adapter for creating functions, classes, and React components',
            'supported_types' => [
                'function' => 'Create JavaScript functions (regular or arrow)',
                'class' => 'Create ES6 classes',
                'component' => 'Create React components (functional or class)',
                'module' => 'Create ES6 modules with imports/exports'
            ],
            'examples' => [
                'function' => [
                    'description' => 'Create a JavaScript function',
                    'code' => '$codecraft->create("utils.js", [
    "type" => "function",
    "name" => "formatDate",
    "params" => ["date", "format"],
    "body" => "return new Intl.DateTimeFormat(format).format(date);",
    "arrow" => true
]);'
                ],
                'react_component' => [
                    'description' => 'Create a React component',
                    'code' => '$codecraft->create("Button.jsx", [
    "type" => "component",
    "name" => "Button",
    "props" => ["text", "onClick", "variant"],
    "hooks" => ["useState"],
    "functional" => true
]);'
                ],
                'class' => [
                    'description' => 'Create an ES6 class',
                    'code' => '$codecraft->create("ApiClient.js", [
    "type" => "class",
    "name" => "ApiClient",
    "constructor" => [
        "params" => ["baseUrl"],
        "body" => "this.baseUrl = baseUrl;"
    ],
    "methods" => [
        [
            "name" => "get",
            "params" => ["endpoint"],
            "body" => "return fetch(\`\${this.baseUrl}/\${endpoint}\`);"
        ]
    ]
]);'
                ]
            ],
            'options' => [
                'type' => 'function, class, component, or module',
                'name' => 'Function/class/component name',
                'params' => 'Array of parameter names',
                'body' => 'Function/method body',
                'async' => 'Make function async (boolean)',
                'arrow' => 'Use arrow function syntax (boolean)',
                'export' => 'Export the function/class (boolean)'
            ],
            'component_options' => [
                'name' => 'Component name',
                'props' => 'Array of prop names',
                'hooks' => 'Array of React hooks to import',
                'functional' => 'Create functional component (boolean)'
            ],
            'class_options' => [
                'name' => 'Class name',
                'extends' => 'Parent class to extend',
                'constructor' => 'Constructor configuration',
                'methods' => 'Array of class methods'
            ]
        ];
    }

    /**
     * Create a JavaScript class
     */
    private function createClass(array $options): string
    {
        $className = $options['name'] ?? 'MyClass';
        $extends = $options['extends'] ?? null;
        $methods = $options['methods'] ?? [];
        $constructor = $options['constructor'] ?? null;

        $code = '';

        // Class declaration
        $code .= "class {$className}";
        if ($extends) {
            $code .= " extends {$extends}";
        }
        $code .= " {\n";

        // Constructor
        if ($constructor) {
            $code .= "  constructor(" . implode(', ', $constructor['params'] ?? []) . ") {\n";
            if ($extends) {
                $code .= "    super();\n";
            }
            if (isset($constructor['body'])) {
                $code .= "    " . str_replace("\n", "\n    ", $constructor['body']) . "\n";
            }
            $code .= "  }\n\n";
        }

        // Methods
        foreach ($methods as $method) {
            $static = $method['static'] ?? false ? 'static ' : '';
            $name = $method['name'];
            $params = implode(', ', $method['params'] ?? []);
            $body = $method['body'] ?? '// TODO: implement';

            $code .= "  {$static}{$name}({$params}) {\n";
            $code .= "    " . str_replace("\n", "\n    ", $body) . "\n";
            $code .= "  }\n\n";
        }

        $code .= "}\n\n";

        // Export
        if ($options['export'] ?? true) {
            $code .= "export default {$className};\n";
        }

        return $code;
    }

    /**
     * Create a React component
     */
    private function createReactComponent(array $options): string
    {
        $componentName = $options['name'] ?? 'MyComponent';
        $props = $options['props'] ?? [];
        $hooks = $options['hooks'] ?? [];
        $functional = $options['functional'] ?? true;

        $code = '';

        // Imports
        $imports = ['React'];
        if (!empty($hooks)) {
            $imports = array_merge($imports, $hooks);
        }
        $code .= "import { " . implode(', ', $imports) . " } from 'react';\n\n";

        if ($functional) {
            // Functional component
            $propsParam = empty($props) ? '' : '{ ' . implode(', ', $props) . ' }';
            $code .= "const {$componentName} = ({$propsParam}) => {\n";

            // Hooks
            foreach ($hooks as $hook) {
                if ($hook === 'useState') {
                    $code .= "  const [state, setState] = useState(null);\n";
                }
            }

            $code .= "\n  return (\n";
            $code .= "    <div className=\"{$componentName}\">\n";
            $code .= "      <h1>{$componentName}</h1>\n";
            $code .= "    </div>\n";
            $code .= "  );\n";
            $code .= "};\n\n";
        } else {
            // Class component
            $code .= "class {$componentName} extends React.Component {\n";
            $code .= "  render() {\n";
            $code .= "    return (\n";
            $code .= "      <div className=\"{$componentName}\">\n";
            $code .= "        <h1>{$componentName}</h1>\n";
            $code .= "      </div>\n";
            $code .= "    );\n";
            $code .= "  }\n";
            $code .= "}\n\n";
        }

        $code .= "export default {$componentName};\n";

        return $code;
    }

    /**
     * Create a JavaScript function
     */
    private function createFunction(array $options): string
    {
        $functionName = $options['name'] ?? 'myFunction';
        $params = $options['params'] ?? [];
        $body = $options['body'] ?? '// TODO: implement';
        $async = $options['async'] ?? false;
        $arrow = $options['arrow'] ?? false;

        $code = '';

        if ($arrow) {
            // Arrow function
            $asyncKeyword = $async ? 'async ' : '';
            $paramsStr = empty($params) ? '' : implode(', ', $params);
            $code .= "const {$functionName} = {$asyncKeyword}({$paramsStr}) => {\n";
            $code .= "  " . str_replace("\n", "\n  ", $body) . "\n";
            $code .= "};\n\n";
        } else {
            // Regular function
            $asyncKeyword = $async ? 'async ' : '';
            $paramsStr = implode(', ', $params);
            $code .= "{$asyncKeyword}function {$functionName}({$paramsStr}) {\n";
            $code .= "  " . str_replace("\n", "\n  ", $body) . "\n";
            $code .= "}\n\n";
        }

        if ($options['export'] ?? false) {
            $code .= "export { {$functionName} };\n";
        }

        return $code;
    }

    /**
     * Create a JavaScript module
     */
    private function createModule(array $options): string
    {
        $imports = $options['imports'] ?? [];
        $functions = $options['functions'] ?? [];
        $classes = $options['classes'] ?? [];
        $exports = $options['exports'] ?? [];

        $code = '';

        // Imports
        foreach ($imports as $import) {
            if (is_string($import)) {
                $code .= "import '{$import}';\n";
            } else {
                $what = $import['what'] ?? '*';
                $from = $import['from'];
                $as = isset($import['as']) ? " as {$import['as']}" : '';
                $code .= "import {$what}{$as} from '{$from}';\n";
            }
        }

        if (!empty($imports)) {
            $code .= "\n";
        }

        // Functions
        foreach ($functions as $function) {
            $code .= $this->createFunction($function) . "\n";
        }

        // Classes
        foreach ($classes as $class) {
            $code .= $this->createClass($class) . "\n";
        }

        // Exports
        if (!empty($exports)) {
            $code .= "export { " . implode(', ', $exports) . " };\n";
        }

        return $code;
    }

    /**
     * Apply modification to content
     */
    private function applyModification(string $content, array $modification): string
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'add_function':
                return $content . "\n" . $this->createFunction($modification['function']);
            case 'add_method':
                // Simple implementation - would need proper JS parser for production
                return $this->addMethodToClass($content, $modification);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Extract functions from content (basic implementation)
     */
    private function extractFunctions(string $content): array
    {
        $functions = [];

        // Match function declarations
        preg_match_all('/(?:async\s+)?function\s+(\w+)\s*\([^)]*\)/', $content, $matches);
        foreach ($matches[1] as $functionName) {
            $functions[] = ['name' => $functionName, 'type' => 'function'];
        }

        // Match arrow functions
        preg_match_all('/const\s+(\w+)\s*=\s*(?:async\s*)?\([^)]*\)\s*=>/', $content, $matches);
        foreach ($matches[1] as $functionName) {
            $functions[] = ['name' => $functionName, 'type' => 'arrow'];
        }

        return $functions;
    }

    /**
     * Extract classes from content
     */
    private function extractClasses(string $content): array
    {
        $classes = [];

        preg_match_all('/class\s+(\w+)(?:\s+extends\s+(\w+))?\s*{/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $classes[] = [
                'name' => $matches[1][$i],
                'extends' => $matches[2][$i] ?: null
            ];
        }

        return $classes;
    }

    /**
     * Extract imports from content
     */
    private function extractImports(string $content): array
    {
        $imports = [];

        preg_match_all('/import\s+(.+?)\s+from\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $imports[] = [
                'what' => trim($matches[1][$i]),
                'from' => $matches[2][$i]
            ];
        }

        return $imports;
    }

    /**
     * Extract exports from content
     */
    private function extractExports(string $content): array
    {
        $exports = [];

        preg_match_all('/export\s+(?:default\s+)?(.+?)(?:\s|;|$)/', $content, $matches);
        foreach ($matches[1] as $export) {
            $exports[] = trim($export);
        }

        return $exports;
    }

    /**
     * Extract React components
     */
    private function extractComponents(string $content): array
    {
        $components = [];

        // Functional components
        preg_match_all('/const\s+(\w+)\s*=\s*\([^)]*\)\s*=>\s*{[^}]*return\s*\([^}]*</', $content, $matches);
        foreach ($matches[1] as $componentName) {
            $components[] = ['name' => $componentName, 'type' => 'functional'];
        }

        // Class components
        preg_match_all('/class\s+(\w+)\s+extends\s+(?:React\.)?Component/', $content, $matches);
        foreach ($matches[1] as $componentName) {
            $components[] = ['name' => $componentName, 'type' => 'class'];
        }

        return $components;
    }

    /**
     * Add method to class (simple implementation)
     */
    private function addMethodToClass(string $content, array $modification): string
    {
        // This is a basic implementation - production would need proper JS parsing
        $className = $modification['class'] ?? null;
        $method = $modification['method'];

        if (!$className) {
            throw new \InvalidArgumentException("Class name required for add_method");
        }

        $methodCode = "  {$method['name']}({$method['params']}) {\n";
        $methodCode .= "    " . ($method['body'] ?? '// TODO: implement') . "\n";
        $methodCode .= "  }\n";

        // Find class and insert method before closing brace
        $pattern = "/(class\s+{$className}[^{]*{[^}]*})/s";
        return preg_replace($pattern, "$1\n{$methodCode}", $content);
    }
}
