<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * TypeScript file adapter for creating and editing TypeScript files
 */
class TypeScriptAdapter implements FileAdapterInterface
{
    /**
     * Create utility functions
     */
    private function createUtilityFunctions(array $options): string
    {
        $functions = $options['functions'] ?? [];
        $constants = $options['constants'] ?? [];
        $types = $options['types'] ?? [];

        $code = '';

        // Imports
        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        // Types first
        foreach ($types as $type) {
            $code .= $this->createType($type) . "\n";
        }

        if (!empty($types)) {
            $code .= "\n";
        }

        // Constants
        foreach ($constants as $constant) {
            $code .= "export const {$constant['name']}: {$constant['type']} = {$constant['value']};\n";
        }

        if (!empty($constants)) {
            $code .= "\n";
        }

        // Utility functions
        foreach ($functions as $func) {
            $code .= $this->generateFunction($func) . "\n\n";
        }

        return trim($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['ts', 'tsx', 'd.ts'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $type = $options['type'] ?? 'module'; // module, class, interface, type, component, service, hook

        switch ($type) {
            case 'class':
                return $this->createClass($options);
            case 'interface':
                return $this->createInterface($options);
            case 'type':
                return $this->createType($options);
            case 'component':
                return $this->createReactComponent($options);
            case 'service':
                return $this->createService($options);
            case 'hook':
                return $this->createCustomHook($options);
            case 'utility':
                return $this->createUtilityFunctions($options);
            case 'api':
                return $this->createApiClient($options);
            case 'store':
                return $this->createStore($options);
            case 'module':
            default:
                return $this->createModule($options);
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
            'type' => 'typescript',
            'imports' => $this->extractImports($content),
            'exports' => $this->extractExports($content),
            'interfaces' => $this->extractInterfaces($content),
            'types' => $this->extractTypes($content),
            'classes' => $this->extractClasses($content),
            'functions' => $this->extractFunctions($content),
            'components' => $this->extractComponents($content),
            'enums' => $this->extractEnums($content)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        // Basic TypeScript validation
        $unclosedBraces = substr_count($content, '{') - substr_count($content, '}');
        $unclosedParens = substr_count($content, '(') - substr_count($content, ')');
        $unclosedBrackets = substr_count($content, '[') - substr_count($content, ']');
        $unclosedAngles = substr_count($content, '<') - substr_count($content, '>');

        // Check for basic syntax issues
        $hasInvalidSyntax = preg_match('/[{}]\s*[{}]/', $content); // Empty blocks

        return $unclosedBraces === 0 &&
               $unclosedParens === 0 &&
               $unclosedBrackets === 0 &&
               $unclosedAngles === 0 &&
               !$hasInvalidSyntax;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_class' => true,
            'create_interface' => true,
            'create_type' => true,
            'create_enum' => true,
            'create_component' => true,
            'create_service' => true,
            'create_hook' => true,
            'create_api_client' => true,
            'create_store' => true,
            'add_method' => true,
            'add_property' => true,
            'supports_generics' => true,
            'supports_decorators' => true,
            'supports_jsx' => true,
            'supports_async' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'typescript';
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
            'description' => 'TypeScript adapter for creating classes, interfaces, React components, and services',
            'supported_types' => [
                'module' => 'Create TypeScript modules with imports/exports',
                'class' => 'Create TypeScript classes with generics and decorators',
                'interface' => 'Create TypeScript interfaces',
                'type' => 'Create type aliases and union types',
                'component' => 'Create React components with TypeScript',
                'service' => 'Create service classes for business logic',
                'hook' => 'Create custom React hooks',
                'utility' => 'Create utility functions with type safety',
                'api' => 'Create API client classes',
                'store' => 'Create state management stores'
            ],
            'examples' => [
                'interface' => [
                    'description' => 'Create TypeScript interface',
                    'code' => '$codecraft->create("User.ts", [
    "type" => "interface",
    "name" => "User",
    "properties" => [
        ["name" => "id", "type" => "number"],
        ["name" => "name", "type" => "string"],
        ["name" => "email", "type" => "string", "optional" => false],
        ["name" => "avatar", "type" => "string", "optional" => true]
    ],
    "extends" => ["BaseEntity"]
]);'
                ],
                'mapped_type' => [
                    'description' => 'Create mapped type for making properties optional',
                    'code' => '$codecraft->create("Optional.ts", [
    "type" => "type",
    "name" => "Optional",
    "generic_params" => ["T", "K extends keyof T"],
    "type_category" => "mapped",
    "key_type" => "P",
    "source_type" => "T",
    "modifier" => "optional",
    "value_type" => "T[P]"
]);'
                ],
                'conditional_type' => [
                    'description' => 'Create conditional type with inference',
                    'code' => '$codecraft->create("ExtractArrayType.ts", [
    "type" => "type",
    "name" => "ExtractArrayType",
    "generic_params" => ["T"],
    "type_category" => "conditional",
    "check_type" => "T",
    "extends_type" => "(infer U)[]",
    "true_type" => "U",
    "false_type" => "never",
    "inference" => ["name" => "U", "placeholder" => "infer U"]
]);'
                ],
                'utility_type' => [
                    'description' => 'Create utility type combinations',
                    'code' => '$codecraft->create("UserUpdate.ts", [
    "type" => "type", 
    "name" => "UserUpdate",
    "type_category" => "utility",
    "utility_type" => "Partial",
    "source_type" => "Pick<User, \"name\" | \"email\">"
]);'
                ],
                'template_literal' => [
                    'description' => 'Create template literal type',
                    'code' => '$codecraft->create("EventName.ts", [
    "type" => "type",
    "name" => "EventName",
    "generic_params" => ["T extends string"],
    "type_category" => "template_literal",
    "template" => "on{T}Change",
    "placeholders" => ["T" => "Capitalize<T>"]
]);'
                ],
                'react_component' => [
                    'description' => 'Create React TypeScript component',
                    'code' => '$codecraft->create("UserCard.tsx", [
    "type" => "component",
    "name" => "UserCard",
    "props_interface" => "UserCardProps",
    "props" => [
        ["name" => "user", "type" => "User"],
        ["name" => "onEdit", "type" => "(user: User) => void", "optional" => true],
        ["name" => "variant", "type" => "\"primary\" | \"secondary\"", "default" => "\"primary\""]
    ],
    "hooks" => ["useState", "useEffect"],
    "generic" => false
]);'
                ],
                'service_class' => [
                    'description' => 'Create service class',
                    'code' => '$codecraft->create("UserService.ts", [
    "type" => "service",
    "name" => "UserService",
    "methods" => [
        [
            "name" => "getUser",
            "params" => [["name" => "id", "type" => "number"]],
            "returnType" => "Promise<User>",
            "async" => true,
            "body" => "return this.apiClient.get(`/users/\${id}`);"
        ],
        [
            "name" => "createUser",
            "params" => [["name" => "userData", "type" => "CreateUserRequest"]],
            "returnType" => "Promise<User>",
            "async" => true,
            "body" => "return this.apiClient.post(\"/users\", userData);"
        ]
    ]
]);'
                ],
                'api_client' => [
                    'description' => 'Create API client with generics',
                    'code' => '$codecraft->create("ApiClient.ts", [
    "type" => "api",
    "name" => "ApiClient",
    "baseUrl" => "process.env.REACT_APP_API_URL",
    "endpoints" => [
        ["path" => "/users", "methods" => ["GET", "POST"]],
        ["path" => "/users/:id", "methods" => ["GET", "PUT", "DELETE"]]
    ],
    "responseTypes" => ["User", "CreateUserRequest", "UpdateUserRequest"]
]);'
                ]
            ],
            'options' => [
                'type' => 'module, class, interface, type, component, service, hook, utility, api, store',
                'name' => 'Name of the entity',
                'generic' => 'Use generics (boolean)',
                'abstract' => 'Make class abstract (boolean)',
                'export' => 'Export the entity (boolean)',
                'decorators' => 'Array of decorators'
            ],
            'interface_options' => [
                'name' => 'Interface name',
                'properties' => 'Array of interface properties',
                'extends' => 'Array of interfaces to extend',
                'generic_params' => 'Generic type parameters'
            ],
            'type_options' => [
                'name' => 'Type alias name',
                'type_category' => 'basic, mapped, conditional, utility, template_literal',
                'type_definition' => 'Type definition for basic types',
                'generic_params' => 'Generic type parameters'
            ],
            'mapped_type_options' => [
                'key_type' => 'Key variable name (default: K)',
                'value_type' => 'Value type expression',
                'source_type' => 'Source type to map over',
                'modifier' => 'readonly, optional, remove_readonly, remove_optional',
                'key_remapping' => 'Key remapping expression (as clause)'
            ],
            'conditional_type_options' => [
                'check_type' => 'Type to check',
                'extends_type' => 'Type to extend/match against',
                'true_type' => 'Type when condition is true',
                'false_type' => 'Type when condition is false',
                'inference' => 'Inference configuration for infer keyword'
            ],
            'utility_type_options' => [
                'utility_type' => 'Pick, Omit, Partial, Required, Record, etc.',
                'source_type' => 'Source type for utility',
                'keys' => 'Keys for Pick/Omit (array or string)',
                'value_type' => 'Value type for Record',
                'exclude_type' => 'Type to exclude for Exclude/Extract'
            ],
            'template_literal_options' => [
                'template' => 'Template string with placeholders',
                'placeholders' => 'Object mapping placeholders to types'
            ],
            'component_options' => [
                'name' => 'Component name',
                'props' => 'Component props with types',
                'props_interface' => 'Props interface name',
                'hooks' => 'React hooks to import',
                'generic' => 'Use generic props'
            ],
            'service_options' => [
                'name' => 'Service class name',
                'dependencies' => 'Constructor dependencies',
                'methods' => 'Service methods',
                'singleton' => 'Use singleton pattern'
            ]
        ];
    }

    /**
     * Create TypeScript class
     */
    private function createClass(array $options): string
    {
        $name = $options['name'];
        $extends = $options['extends'] ?? null;
        $implements = $options['implements'] ?? [];
        $abstract = $options['abstract'] ?? false;
        $generic = $options['generic_params'] ?? [];
        $decorators = $options['decorators'] ?? [];
        $properties = $options['properties'] ?? [];
        $methods = $options['methods'] ?? [];
        $constructor = $options['constructor'] ?? null;

        $code = '';

        // Imports
        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        // Decorators
        foreach ($decorators as $decorator) {
            $code .= "@{$decorator}\n";
        }

        // Class declaration
        $abstractKeyword = $abstract ? 'abstract ' : '';
        $genericParams = !empty($generic) ? '<' . implode(', ', $generic) . '>' : '';
        $code .= "{$abstractKeyword}export class {$name}{$genericParams}";

        if ($extends) {
            $code .= " extends {$extends}";
        }

        if (!empty($implements)) {
            $code .= " implements " . implode(', ', $implements);
        }

        $code .= " {\n";

        // Properties
        foreach ($properties as $property) {
            $code .= $this->generateProperty($property, '  ');
        }

        if (!empty($properties)) {
            $code .= "\n";
        }

        // Constructor
        if ($constructor) {
            $code .= $this->generateConstructor($constructor, '  ');
            $code .= "\n";
        }

        // Methods
        foreach ($methods as $method) {
            $code .= $this->generateMethod($method, '  ');
            $code .= "\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Create TypeScript interface
     */
    private function createInterface(array $options): string
    {
        $name = $options['name'];
        $extends = $options['extends'] ?? [];
        $properties = $options['properties'] ?? [];
        $genericParams = $options['generic_params'] ?? [];

        $code = '';

        // Imports
        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        // Interface declaration
        $genericParamsStr = !empty($genericParams) ? '<' . implode(', ', $genericParams) . '>' : '';
        $code .= "export interface {$name}{$genericParamsStr}";

        if (!empty($extends)) {
            $code .= " extends " . implode(', ', $extends);
        }

        $code .= " {\n";

        // Properties
        foreach ($properties as $property) {
            $optional = $property['optional'] ?? false ? '?' : '';
            $readonly = $property['readonly'] ?? false ? 'readonly ' : '';
            $code .= "  {$readonly}{$property['name']}{$optional}: {$property['type']};\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Create type alias
     */
    private function createType(array $options): string
    {
        $name = $options['name'];
        $typeCategory = $options['type_category'] ?? 'basic'; // basic, mapped, conditional, utility
        $genericParams = $options['generic_params'] ?? [];

        $code = '';

        // Imports
        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        $genericParamsStr = !empty($genericParams) ? '<' . implode(', ', $genericParams) . '>' : '';

        switch ($typeCategory) {
            case 'mapped':
                $typeDefinition = $this->generateMappedType($options);
                break;
            case 'conditional':
                $typeDefinition = $this->generateConditionalType($options);
                break;
            case 'utility':
                $typeDefinition = $this->generateUtilityType($options);
                break;
            case 'template_literal':
                $typeDefinition = $this->generateTemplateLiteralType($options);
                break;
            case 'basic':
            default:
                $typeDefinition = $options['type_definition'];
                break;
        }

        $code .= "export type {$name}{$genericParamsStr} = {$typeDefinition};\n";

        return $code;
    }

    /**
     * Generate mapped type
     */
    private function generateMappedType(array $options): string
    {
        $keyType = $options['key_type'] ?? 'K';
        $valueType = $options['value_type'] ?? 'T[K]';
        $sourceType = $options['source_type'] ?? 'T';
        $modifier = $options['modifier'] ?? null; // readonly, optional, remove_readonly, remove_optional
        $keyRemapping = $options['key_remapping'] ?? null;

        $modifierStr = '';
        switch ($modifier) {
            case 'readonly':
                $modifierStr = 'readonly ';
                break;
            case 'optional':
                $modifierStr = '';
                break;
            case 'remove_readonly':
                $modifierStr = '-readonly ';
                break;
            case 'remove_optional':
                $modifierStr = '';
                break;
        }

        $optionalStr = ($modifier === 'optional') ? '?' :
                      (($modifier === 'remove_optional') ? '-?' : '');

        $keyRemappingStr = $keyRemapping ? " as {$keyRemapping}" : '';

        return "{\n  {$modifierStr}[{$keyType} in keyof {$sourceType}]{$keyRemappingStr}{$optionalStr}: {$valueType};\n}";
    }

    /**
     * Generate conditional type
     */
    private function generateConditionalType(array $options): string
    {
        $checkType = $options['check_type'];
        $extendsType = $options['extends_type'];
        $trueType = $options['true_type'];
        $falseType = $options['false_type'];
        $inference = $options['inference'] ?? null; // for 'infer' keyword

        if ($inference) {
            $extendsType = str_replace($inference['placeholder'], "infer {$inference['name']}", $extendsType);
        }

        return "{$checkType} extends {$extendsType} ? {$trueType} : {$falseType}";
    }

    /**
     * Generate utility type
     */
    private function generateUtilityType(array $options): string
    {
        $utilityType = $options['utility_type']; // Pick, Omit, Partial, Required, etc.
        $sourceType = $options['source_type'];
        $keys = $options['keys'] ?? null;

        switch ($utilityType) {
            case 'Pick':
            case 'Omit':
                $keyUnion = is_array($keys) ? "'" . implode("' | '", $keys) . "'" : $keys;
                return "{$utilityType}<{$sourceType}, {$keyUnion}>";

            case 'Partial':
            case 'Required':
            case 'Readonly':
                return "{$utilityType}<{$sourceType}>";

            case 'Record':
                $valueType = $options['value_type'] ?? 'any';
                return "Record<{$sourceType}, {$valueType}>";

            case 'Exclude':
            case 'Extract':
                $excludeType = $options['exclude_type'];
                return "{$utilityType}<{$sourceType}, {$excludeType}>";

            case 'NonNullable':
                return "NonNullable<{$sourceType}>";

            case 'ReturnType':
            case 'Parameters':
                return "{$utilityType}<{$sourceType}>";

            default:
                return "{$utilityType}<{$sourceType}>";
        }
    }

    /**
     * Generate template literal type
     */
    private function generateTemplateLiteralType(array $options): string
    {
        $template = $options['template'];
        $placeholders = $options['placeholders'] ?? [];

        $templateStr = $template;
        foreach ($placeholders as $placeholder => $type) {
            // Replace {placeholder} with ${type} - escape the $ for PHP
            $templateStr = str_replace("{{$placeholder}}", "\${{$type}}", $templateStr);
        }

        return "`{$templateStr}`";
    }

    /**
     * Create React component
     */
    private function createReactComponent(array $options): string
    {
        $name = $options['name'];
        $props = $options['props'] ?? [];
        $propsInterface = $options['props_interface'] ?? "{$name}Props";
        $hooks = $options['hooks'] ?? [];
        $generic = $options['generic'] ?? false;

        $code = '';

        // Imports
        $imports = ['React'];
        if (!empty($hooks)) {
            $imports = array_merge($imports, $hooks);
        }
        $code .= "import { " . implode(', ', $imports) . " } from 'react';\n\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        // Props interface
        if (!empty($props)) {
            $code .= "interface {$propsInterface} {\n";
            foreach ($props as $prop) {
                $optional = $prop['optional'] ?? false ? '?' : '';
                $code .= "  {$prop['name']}{$optional}: {$prop['type']};\n";
            }
            $code .= "}\n\n";
        }

        // Component
        $propsParam = !empty($props) ? "{ " . implode(', ', array_column($props, 'name')) . " }: {$propsInterface}" : '';
        $genericParam = $generic ? '<T>' : '';

        $code .= "const {$name}{$genericParam}: React.FC<{$propsInterface}> = ({$propsParam}) => {\n";

        // Hooks
        foreach ($hooks as $hook) {
            if ($hook === 'useState') {
                $code .= "  const [state, setState] = useState();\n";
            } elseif ($hook === 'useEffect') {
                $code .= "  useEffect(() => {\n    // Effect logic here\n  }, []);\n";
            }
        }

        if (!empty($hooks)) {
            $code .= "\n";
        }

        $code .= "  return (\n";
        $code .= "    <div className=\"{$name}\">\n";
        $code .= "      <h1>{$name}</h1>\n";
        $code .= "    </div>\n";
        $code .= "  );\n";
        $code .= "};\n\n";
        $code .= "export default {$name};\n";

        return $code;
    }

    /**
     * Create service class
     */
    private function createService(array $options): string
    {
        $name = $options['name'];
        $dependencies = $options['dependencies'] ?? [];
        $methods = $options['methods'] ?? [];
        $singleton = $options['singleton'] ?? false;

        $code = '';

        // Imports
        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        $code .= "export class {$name} {\n";

        // Singleton instance
        if ($singleton) {
            $code .= "  private static instance: {$name};\n\n";
        }

        // Dependencies as properties
        foreach ($dependencies as $dependency) {
            $code .= "  private {$dependency['name']}: {$dependency['type']};\n";
        }

        if (!empty($dependencies)) {
            $code .= "\n";
        }

        // Constructor
        if (!empty($dependencies) || $singleton) {
            $constructorParams = [];
            foreach ($dependencies as $dependency) {
                $constructorParams[] = "{$dependency['name']}: {$dependency['type']}";
            }

            $visibility = $singleton ? 'private' : 'public';
            $code .= "  {$visibility} constructor(" . implode(', ', $constructorParams) . ") {\n";

            foreach ($dependencies as $dependency) {
                $code .= "    this.{$dependency['name']} = {$dependency['name']};\n";
            }

            $code .= "  }\n\n";
        }

        // Singleton getter
        if ($singleton) {
            $code .= "  public static getInstance(): {$name} {\n";
            $code .= "    if (!{$name}.instance) {\n";
            $code .= "      {$name}.instance = new {$name}();\n";
            $code .= "    }\n";
            $code .= "    return {$name}.instance;\n";
            $code .= "  }\n\n";
        }

        // Methods
        foreach ($methods as $method) {
            $code .= $this->generateMethod($method, '  ');
            $code .= "\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Create custom React hook
     */
    private function createCustomHook(array $options): string
    {
        $name = $options['name'];
        $params = $options['params'] ?? [];
        $returnType = $options['return_type'] ?? 'any';
        $dependencies = $options['dependencies'] ?? [];

        $code = '';

        // Imports
        $imports = ['useState', 'useEffect'];
        if (!empty($dependencies)) {
            $imports = array_merge($imports, $dependencies);
        }
        $code .= "import { " . implode(', ', array_unique($imports)) . " } from 'react';\n\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        // Hook function
        $paramsStr = [];
        foreach ($params as $param) {
            $optional = ($param['optional'] ?? false) ? '?' : '';
            $defaultValue = isset($param['default']) ? " = {$param['default']}" : '';
            $paramsStr[] = "{$param['name']}{$optional}: {$param['type']}{$defaultValue}";
        }

        $code .= "export const {$name} = (" . implode(', ', $paramsStr) . "): {$returnType} => {\n";
        $code .= "  const [state, setState] = useState();\n\n";
        $code .= "  useEffect(() => {\n";
        $code .= "    // Hook logic here\n";
        $code .= "  }, []);\n\n";
        $code .= "  return {\n";
        $code .= "    // Return hook interface\n";
        $code .= "  };\n";
        $code .= "};\n";

        return $code;
    }

    /**
     * Create API client
     */
    private function createApiClient(array $options): string
    {
        $name = $options['name'];
        $baseUrl = $options['base_url'] ?? 'process.env.REACT_APP_API_URL';
        $endpoints = $options['endpoints'] ?? [];

        $code = '';

        // Imports
        $code .= "import axios, { AxiosInstance, AxiosResponse } from 'axios';\n\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $this->generateImport($import) . "\n";
            }
            $code .= "\n";
        }

        $code .= "export class {$name} {\n";
        $code .= "  private api: AxiosInstance;\n\n";
        $code .= "  constructor(baseURL: string = {$baseUrl}) {\n";
        $code .= "    this.api = axios.create({\n";
        $code .= "      baseURL,\n";
        $code .= "      headers: {\n";
        $code .= "        'Content-Type': 'application/json',\n";
        $code .= "      },\n";
        $code .= "    });\n";
        $code .= "  }\n\n";

        // Generic methods
        foreach ($endpoints as $endpoint) {
            $path = $endpoint['path'];
            $methods = $endpoint['methods'];

            foreach ($methods as $method) {
                $methodName = strtolower($method);
                $funcName = $this->generateApiMethodName($methodName, $path);

                if ($method === 'GET') {
                    $code .= "  async {$funcName}<T = any>(): Promise<AxiosResponse<T>> {\n";
                    $code .= "    return this.api.get('{$path}');\n";
                    $code .= "  }\n\n";
                } elseif ($method === 'POST') {
                    $code .= "  async {$funcName}<T = any>(data: any): Promise<AxiosResponse<T>> {\n";
                    $code .= "    return this.api.post('{$path}', data);\n";
                    $code .= "  }\n\n";
                } elseif ($method === 'PUT') {
                    $code .= "  async {$funcName}<T = any>(data: any): Promise<AxiosResponse<T>> {\n";
                    $code .= "    return this.api.put('{$path}', data);\n";
                    $code .= "  }\n\n";
                } elseif ($method === 'DELETE') {
                    $code .= "  async {$funcName}<T = any>(): Promise<AxiosResponse<T>> {\n";
                    $code .= "    return this.api.delete('{$path}');\n";
                    $code .= "  }\n\n";
                }
            }
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Create state management store
     */
    private function createStore(array $options): string
    {
        $name = $options['name'];
        $storeType = $options['store_type'] ?? 'zustand'; // zustand, redux, custom
        $state = $options['state'] ?? [];
        $actions = $options['actions'] ?? [];

        $code = '';

        if ($storeType === 'zustand') {
            $code .= "import { create } from 'zustand';\n\n";

            // State interface
            $code .= "interface {$name}State {\n";
            foreach ($state as $prop) {
                $code .= "  {$prop['name']}: {$prop['type']};\n";
            }
            foreach ($actions as $action) {
                $code .= "  {$action['name']}: {$action['signature']};\n";
            }
            $code .= "}\n\n";

            // Store
            $code .= "export const use{$name} = create<{$name}State>((set, get) => ({\n";
            foreach ($state as $prop) {
                $defaultValue = $prop['default'] ?? 'null';
                $code .= "  {$prop['name']}: {$defaultValue},\n";
            }
            foreach ($actions as $action) {
                $code .= "  {$action['name']}: {$action['implementation']},\n";
            }
            $code .= "}));\n";
        }

        return $code;
    }

    /**
     * Create module
     */
    private function createModule(array $options): string
    {
        $imports = $options['imports'] ?? [];
        $exports = $options['exports'] ?? [];
        $functions = $options['functions'] ?? [];
        $classes = $options['classes'] ?? [];
        $interfaces = $options['interfaces'] ?? [];
        $types = $options['types'] ?? [];

        $code = '';

        // Imports
        foreach ($imports as $import) {
            $code .= $this->generateImport($import) . "\n";
        }

        if (!empty($imports)) {
            $code .= "\n";
        }

        // Types and interfaces first
        foreach ($types as $type) {
            $code .= $this->createType($type) . "\n";
        }

        foreach ($interfaces as $interface) {
            $code .= $this->createInterface($interface) . "\n";
        }

        // Functions
        foreach ($functions as $func) {
            $code .= $this->generateFunction($func) . "\n\n";
        }

        // Classes
        foreach ($classes as $class) {
            $code .= $this->createClass($class) . "\n";
        }

        // Named exports
        if (!empty($exports)) {
            $code .= "\nexport { " . implode(', ', $exports) . " };\n";
        }

        return $code;
    }

    /**
     * Generate import statement
     */
    private function generateImport(array $import): string
    {
        if (isset($import['default'])) {
            return "import {$import['default']} from '{$import['from']}';";
        }

        if (isset($import['namespace'])) {
            return "import * as {$import['namespace']} from '{$import['from']}';";
        }

        if (isset($import['named'])) {
            $named = is_array($import['named']) ? implode(', ', $import['named']) : $import['named'];
            return "import { {$named} } from '{$import['from']}';";
        }

        return "import '{$import['from']}';";
    }

    /**
     * Generate property
     */
    private function generateProperty(array $property, string $indent): string
    {
        $visibility = $property['visibility'] ?? 'private';
        $readonly = $property['readonly'] ?? false ? 'readonly ' : '';
        $static = $property['static'] ?? false ? 'static ' : '';
        $optional = $property['optional'] ?? false ? '?' : '';
        $name = $property['name'];
        $type = $property['type'];
        $defaultValue = isset($property['default']) ? " = {$property['default']}" : '';

        return "{$indent}{$visibility} {$static}{$readonly}{$name}{$optional}: {$type}{$defaultValue};\n";
    }

    /**
     * Generate constructor
     */
    private function generateConstructor(array $constructor, string $indent): string
    {
        $params = $constructor['params'] ?? [];
        $body = $constructor['body'] ?? '';

        $code = "{$indent}constructor(";

        $paramStrings = [];
        foreach ($params as $param) {
            $visibility = isset($param['visibility']) ? "{$param['visibility']} " : '';
            $readonly = $param['readonly'] ?? false ? 'readonly ' : '';
            $optional = $param['optional'] ?? false ? '?' : '';
            $defaultValue = isset($param['default']) ? " = {$param['default']}" : '';

            $paramStrings[] = "{$visibility}{$readonly}{$param['name']}{$optional}: {$param['type']}{$defaultValue}";
        }

        $code .= implode(', ', $paramStrings) . ") {\n";

        if ($body) {
            $code .= "{$indent}  " . str_replace("\n", "\n{$indent}  ", $body) . "\n";
        }

        $code .= "{$indent}}\n";

        return $code;
    }

    /**
     * Generate method
     */
    private function generateMethod(array $method, string $indent): string
    {
        $name = $method['name'];
        $visibility = $method['visibility'] ?? 'public';
        $static = $method['static'] ?? false ? 'static ' : '';
        $async = $method['async'] ?? false ? 'async ' : '';
        $abstract = $method['abstract'] ?? false ? 'abstract ' : '';
        $params = $method['params'] ?? [];
        $returnType = $method['returnType'] ?? 'void';
        $body = $method['body'] ?? '';
        $decorators = $method['decorators'] ?? [];

        $code = '';

        // Decorators
        foreach ($decorators as $decorator) {
            $code .= "{$indent}@{$decorator}\n";
        }

        // Method signature
        $paramStrings = [];
        foreach ($params as $param) {
            $optional = $param['optional'] ?? false ? '?' : '';
            $defaultValue = isset($param['default']) ? " = {$param['default']}" : '';
            $paramStrings[] = "{$param['name']}{$optional}: {$param['type']}{$defaultValue}";
        }

        $code .= "{$indent}{$visibility} {$static}{$async}{$abstract}{$name}(" . implode(', ', $paramStrings) . "): {$returnType}";

        if ($abstract) {
            $code .= ";\n";
        } else {
            $code .= " {\n";
            if ($body) {
                $code .= "{$indent}  " . str_replace("\n", "\n{$indent}  ", $body) . "\n";
            }
            $code .= "{$indent}}\n";
        }

        return $code;
    }

    /**
     * Generate function
     */
    private function generateFunction(array $func): string
    {
        $name = $func['name'];
        $async = $func['async'] ?? false ? 'async ' : '';
        $params = $func['params'] ?? [];
        $returnType = $func['returnType'] ?? 'void';
        $body = $func['body'] ?? '';
        $generic = $func['generic'] ?? [];

        $genericStr = !empty($generic) ? '<' . implode(', ', $generic) . '>' : '';

        $paramStrings = [];
        foreach ($params as $param) {
            $optional = $param['optional'] ?? false ? '?' : '';
            $defaultValue = isset($param['default']) ? " = {$param['default']}" : '';
            $paramStrings[] = "{$param['name']}{$optional}: {$param['type']}{$defaultValue}";
        }

        $code = "export {$async}function {$name}{$genericStr}(" . implode(', ', $paramStrings) . "): {$returnType} {\n";

        if ($body) {
            $code .= "  " . str_replace("\n", "\n  ", $body) . "\n";
        }

        $code .= "}";

        return $code;
    }

    /**
     * Generate API method name from HTTP method and path
     */
    private function generateApiMethodName(string $method, string $path): string
    {
        $cleanPath = str_replace(['/', ':', '-'], ['', '', ''], $path);
        $cleanPath = ucwords($cleanPath);
        $cleanPath = lcfirst($cleanPath);

        return $method . ucfirst($cleanPath);
    }

    /**
     * Apply modification to content
     */
    private function applyModification(string $content, array $modification): string
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'add_import':
                return $this->addImport($content, $modification['import']);
            case 'add_interface':
                return $this->addInterface($content, $modification['interface']);
            case 'add_method':
                return $this->addMethodToClass($content, $modification);
            case 'add_property':
                return $this->addPropertyToClass($content, $modification);
            case 'add_type':
                return $this->addType($content, $modification['type_def']);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Extract imports from content
     */
    private function extractImports(string $content): array
    {
        $imports = [];

        // Match import statements
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
     * Extract interfaces from content
     */
    private function extractInterfaces(string $content): array
    {
        $interfaces = [];

        preg_match_all('/interface\s+(\w+)(?:<([^>]+)>)?(?:\s+extends\s+([^{]+))?\s*{/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $interfaces[] = [
                'name' => $matches[1][$i],
                'generics' => $matches[2][$i] ? trim($matches[2][$i]) : null,
                'extends' => $matches[3][$i] ? array_map('trim', explode(',', $matches[3][$i])) : []
            ];
        }

        return $interfaces;
    }

    /**
     * Extract type aliases from content
     */
    private function extractTypes(string $content): array
    {
        $types = [];

        preg_match_all('/type\s+(\w+)(?:<([^>]+)>)?\s*=\s*([^;]+);/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $types[] = [
                'name' => $matches[1][$i],
                'generics' => $matches[2][$i] ? trim($matches[2][$i]) : null,
                'definition' => trim($matches[3][$i])
            ];
        }

        return $types;
    }

    /**
     * Extract classes from content
     */
    private function extractClasses(string $content): array
    {
        $classes = [];

        preg_match_all('/(?:export\s+)?(?:abstract\s+)?class\s+(\w+)(?:<([^>]+)>)?(?:\s+extends\s+(\w+))?(?:\s+implements\s+([^{]+))?\s*{/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $classes[] = [
                'name' => $matches[1][$i],
                'generics' => $matches[2][$i] ? trim($matches[2][$i]) : null,
                'extends' => $matches[3][$i] ? trim($matches[3][$i]) : null,
                'implements' => $matches[4][$i] ? array_map('trim', explode(',', $matches[4][$i])) : []
            ];
        }

        return $classes;
    }

    /**
     * Extract functions from content
     */
    private function extractFunctions(string $content): array
    {
        $functions = [];

        // Regular functions
        preg_match_all('/(?:export\s+)?(?:async\s+)?function\s+(\w+)(?:<([^>]+)>)?\s*\(([^)]*)\)(?:\s*:\s*([^{]+))?\s*{/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $functions[] = [
                'name' => $matches[1][$i],
                'type' => 'function',
                'generics' => $matches[2][$i] ? trim($matches[2][$i]) : null,
                'params' => trim($matches[3][$i]),
                'return_type' => $matches[4][$i] ? trim($matches[4][$i]) : 'void'
            ];
        }

        // Arrow functions
        preg_match_all('/const\s+(\w+)\s*=\s*(?:async\s*)?\(([^)]*)\)(?:\s*:\s*([^=]+))?\s*=>\s*{/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $functions[] = [
                'name' => $matches[1][$i],
                'type' => 'arrow',
                'params' => trim($matches[2][$i]),
                'return_type' => $matches[3][$i] ? trim($matches[3][$i]) : 'unknown'
            ];
        }

        return $functions;
    }

    /**
     * Extract React components from content
     */
    private function extractComponents(string $content): array
    {
        $components = [];

        // React.FC components
        preg_match_all('/const\s+(\w+):\s*React\.FC(?:<([^>]+)>)?\s*=/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $components[] = [
                'name' => $matches[1][$i],
                'type' => 'functional',
                'props_type' => $matches[2][$i] ? trim($matches[2][$i]) : null
            ];
        }

        return $components;
    }

    /**
     * Extract enums from content
     */
    private function extractEnums(string $content): array
    {
        $enums = [];

        preg_match_all('/enum\s+(\w+)\s*{/', $content, $matches);
        foreach ($matches[1] as $enumName) {
            $enums[] = ['name' => $enumName];
        }

        return $enums;
    }

    /**
     * Add import to file
     */
    private function addImport(string $content, array $import): string
    {
        $importStatement = $this->generateImport($import);
        $lines = explode("\n", $content);

        // Find where to insert import
        $insertAt = 0;
        foreach ($lines as $index => $line) {
            if (preg_match('/^import\s/', trim($line))) {
                $insertAt = $index + 1;
            } elseif (trim($line) === '') {
                continue;
            } else {
                break;
            }
        }

        array_splice($lines, $insertAt, 0, [$importStatement]);
        return implode("\n", $lines);
    }

    /**
     * Add interface to file
     */
    private function addInterface(string $content, array $interface): string
    {
        $interfaceCode = $this->createInterface($interface);
        return $content . "\n\n" . $interfaceCode;
    }

    /**
     * Add method to class
     */
    private function addMethodToClass(string $content, array $modification): string
    {
        $className = $modification['class_name'];
        $method = $modification['method'];

        $methodCode = $this->generateMethod($method, '  ');

        // Find class and insert method before closing brace
        $pattern = "/(class\s+{$className}[^{]*{[^}]*})/s";
        return preg_replace($pattern, "$1\n\n{$methodCode}", $content);
    }

    /**
     * Add property to class
     */
    private function addPropertyToClass(string $content, array $modification): string
    {
        $className = $modification['class_name'];
        $property = $modification['property'];

        $propertyCode = $this->generateProperty($property, '  ');

        // Find class and insert property after opening brace
        $pattern = "/(class\s+{$className}[^{]*{\s*)/";
        return preg_replace($pattern, "$1\n{$propertyCode}", $content);
    }

    /**
     * Add type alias to file
     */
    private function addType(string $content, array $typeDef): string
    {
        $typeCode = $this->createType($typeDef);
        return $content . "\n\n" . $typeCode;
    }
}
