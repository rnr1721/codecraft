<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * CSS file adapter for creating and editing CSS files
 */
class CssAdapter implements FileAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['css', 'scss', 'sass'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $type = $options['type'] ?? 'stylesheet'; // stylesheet, component, utilities

        switch ($type) {
            case 'component':
                return $this->createComponentStyles($options);
            case 'utilities':
                return $this->createUtilityClasses($options);
            case 'layout':
                return $this->createLayoutStyles($options);
            case 'stylesheet':
            default:
                return $this->createStylesheet($options);
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
            'type' => 'css',
            'selectors' => $this->extractSelectors($content),
            'variables' => $this->extractVariables($content),
            'imports' => $this->extractImports($content),
            'media_queries' => $this->extractMediaQueries($content),
            'keyframes' => $this->extractKeyframes($content)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        // Basic CSS validation
        $unclosedBraces = substr_count($content, '{') - substr_count($content, '}');
        $unclosedParens = substr_count($content, '(') - substr_count($content, ')');

        // Check for obvious syntax errors
        $hasInvalidSyntax = preg_match('/[{}]\s*[{}]/', $content); // Empty rules

        return $unclosedBraces === 0 && $unclosedParens === 0 && !$hasInvalidSyntax;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_component_styles' => true,
            'create_utilities' => true,
            'add_selector' => true,
            'add_variable' => true,
            'supports_scss' => true,
            'supports_sass' => true,
            'supports_media_queries' => true,
            'supports_keyframes' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'css';
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
            'description' => 'CSS/SCSS adapter for creating stylesheets, components, and utilities',
            'supported_types' => [
                'stylesheet' => 'Create basic CSS stylesheets',
                'component' => 'Create component styles using BEM methodology',
                'utilities' => 'Create utility classes (like Tailwind)',
                'layout' => 'Create layout systems (Grid, Flexbox)'
            ],
            'examples' => [
                'component' => [
                    'description' => 'Create BEM component styles',
                    'code' => '$codecraft->create("button.css", [
    "type" => "component",
    "name" => "button",
    "elements" => [
        "text" => [
            "color" => "white",
            "font-weight" => "bold"
        ],
        "icon" => [
            "margin-right" => "0.5rem"
        ]
    ],
    "states" => [
        "hover" => ["background-color" => "blue"],
        "disabled" => ["opacity" => "0.5"]
    ]
]);'
                ],
                'utilities' => [
                    'description' => 'Create utility classes',
                    'code' => '$codecraft->create("utilities.css", [
    "type" => "utilities",
    "prefix" => "u",
    "utilities" => [
        "spacing" => [
            "p-1" => ["padding" => "0.25rem"],
            "m-2" => ["margin" => "0.5rem"]
        ],
        "colors" => [
            "text-primary" => ["color" => "#007bff"],
            "bg-light" => ["background-color" => "#f8f9fa"]
        ]
    ]
]);'
                ],
                'layout' => [
                    'description' => 'Create layout system',
                    'code' => '$codecraft->create("grid.css", [
    "type" => "layout",
    "layout" => "grid",
    "columns" => 12,
    "gap" => "1rem",
    "responsive" => true
]);'
                ]
            ],
            'options' => [
                'type' => 'stylesheet, component, utilities, or layout',
                'name' => 'Component name (for BEM)',
                'selectors' => 'Array of CSS selectors',
                'variables' => 'CSS custom properties',
                'imports' => 'Array of @import statements',
                'reset' => 'Include CSS reset styles (boolean)'
            ],
            'component_options' => [
                'name' => 'Component name',
                'elements' => 'BEM elements (__element)',
                'states' => 'BEM modifiers (--state)',
                'responsive' => 'Include responsive styles (boolean)'
            ],
            'utilities_options' => [
                'utilities' => 'Object of utility categories and classes',
                'prefix' => 'Class prefix (optional)'
            ],
            'layout_options' => [
                'layout' => 'grid, flexbox, or basic',
                'columns' => 'Number of grid columns',
                'gap' => 'Grid/flex gap size',
                'responsive' => 'Include breakpoints (boolean)'
            ]
        ];
    }

    /**
     * Create a basic stylesheet
     */
    private function createStylesheet(array $options): string
    {
        $selectors = $options['selectors'] ?? [];
        $variables = $options['variables'] ?? [];
        $imports = $options['imports'] ?? [];
        $resetStyles = $options['reset'] ?? false;

        $css = '';

        // Imports
        foreach ($imports as $import) {
            $css .= "@import '{$import}';\n";
        }
        if (!empty($imports)) {
            $css .= "\n";
        }

        // Variables (CSS Custom Properties)
        if (!empty($variables)) {
            $css .= ":root {\n";
            foreach ($variables as $name => $value) {
                $css .= "  --{$name}: {$value};\n";
            }
            $css .= "}\n\n";
        }

        // Reset styles
        if ($resetStyles) {
            $css .= $this->getResetStyles() . "\n\n";
        }

        // Selectors
        foreach ($selectors as $selector) {
            $css .= $this->createSelector($selector) . "\n";
        }

        return $css;
    }

    /**
     * Create component-specific styles
     */
    private function createComponentStyles(array $options): string
    {
        $componentName = $options['name'] ?? 'component';
        $baseClass = ".{$componentName}";
        $elements = $options['elements'] ?? [];
        $states = $options['states'] ?? [];
        $responsive = $options['responsive'] ?? false;

        $css = "/* {$componentName} Component Styles */\n\n";

        // Base component styles
        $css .= "{$baseClass} {\n";
        $css .= "  /* Add base styles here */\n";
        $css .= "}\n\n";

        // Element styles
        foreach ($elements as $element => $styles) {
            $css .= "{$baseClass}__{$element} {\n";
            foreach ($styles as $property => $value) {
                $css .= "  {$property}: {$value};\n";
            }
            $css .= "}\n\n";
        }

        // State styles
        foreach ($states as $state => $styles) {
            $css .= "{$baseClass}--{$state} {\n";
            foreach ($styles as $property => $value) {
                $css .= "  {$property}: {$value};\n";
            }
            $css .= "}\n\n";
        }

        // Responsive styles
        if ($responsive) {
            $css .= "@media (max-width: 768px) {\n";
            $css .= "  {$baseClass} {\n";
            $css .= "    /* Mobile styles */\n";
            $css .= "  }\n";
            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Create utility classes
     */
    private function createUtilityClasses(array $options): string
    {
        $utilities = $options['utilities'] ?? [];
        $prefix = $options['prefix'] ?? '';

        $css = "/* Utility Classes */\n\n";

        foreach ($utilities as $category => $rules) {
            $css .= "/* {$category} utilities */\n";

            foreach ($rules as $name => $properties) {
                $className = $prefix ? "{$prefix}-{$name}" : $name;
                $css .= ".{$className} {\n";

                foreach ($properties as $property => $value) {
                    $css .= "  {$property}: {$value};\n";
                }

                $css .= "}\n\n";
            }
        }

        return $css;
    }

    /**
     * Create layout styles
     */
    private function createLayoutStyles(array $options): string
    {
        $layoutType = $options['layout'] ?? 'flexbox'; // flexbox, grid, float
        $responsive = $options['responsive'] ?? true;

        $css = "/* Layout Styles */\n\n";

        switch ($layoutType) {
            case 'grid':
                $css .= $this->createGridLayout($options);
                break;
            case 'flexbox':
                $css .= $this->createFlexboxLayout($options);
                break;
            default:
                $css .= $this->createBasicLayout($options);
        }

        if ($responsive) {
            $css .= "\n" . $this->createResponsiveBreakpoints();
        }

        return $css;
    }

    /**
     * Create CSS selector block
     */
    private function createSelector(array $selector): string
    {
        $name = $selector['selector'];
        $properties = $selector['properties'] ?? [];
        $nested = $selector['nested'] ?? [];

        $css = "{$name} {\n";

        foreach ($properties as $property => $value) {
            $css .= "  {$property}: {$value};\n";
        }

        // Nested selectors (for SCSS)
        foreach ($nested as $nestedSelector) {
            $css .= "\n  {$nestedSelector['selector']} {\n";
            foreach ($nestedSelector['properties'] as $property => $value) {
                $css .= "    {$property}: {$value};\n";
            }
            $css .= "  }\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Create Grid layout
     */
    private function createGridLayout(array $options): string
    {
        $columns = $options['columns'] ?? 12;
        $gap = $options['gap'] ?? '1rem';

        return ".grid {
  display: grid;
  grid-template-columns: repeat({$columns}, 1fr);
  gap: {$gap};
}

.grid-item {
  grid-column: span 1;
}";
    }

    /**
     * Create Flexbox layout
     */
    private function createFlexboxLayout(array $options): string
    {
        $direction = $options['direction'] ?? 'row';
        $justify = $options['justify'] ?? 'flex-start';
        $align = $options['align'] ?? 'stretch';

        return ".flex {
  display: flex;
  flex-direction: {$direction};
  justify-content: {$justify};
  align-items: {$align};
}

.flex-item {
  flex: 1;
}";
    }

    /**
     * Create basic layout
     */
    private function createBasicLayout(array $options): string
    {
        return ".container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.row {
  display: flex;
  flex-wrap: wrap;
}

.col {
  flex: 1;
  padding: 0 0.5rem;
}";
    }

    /**
     * Create responsive breakpoints
     */
    private function createResponsiveBreakpoints(): string
    {
        return "/* Responsive Breakpoints */
@media (max-width: 1199px) {
  /* Large desktops */
}

@media (max-width: 991px) {
  /* Tablets */
}

@media (max-width: 767px) {
  /* Mobile phones */
}

@media (max-width: 575px) {
  /* Small mobile phones */
}";
    }

    /**
     * Apply modification to CSS content
     */
    private function applyModification(string $content, array $modification): string
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'add_selector':
                return $content . "\n" . $this->createSelector($modification['selector']);
            case 'add_variable':
                return $this->addVariable($content, $modification['variable']);
            case 'add_media_query':
                return $content . "\n" . $this->createMediaQuery($modification['media_query']);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Add CSS variable to :root
     */
    private function addVariable(string $content, array $variable): string
    {
        $name = $variable['name'];
        $value = $variable['value'];
        $newVar = "  --{$name}: {$value};";

        // If :root exists, add to it
        if (preg_match('/:root\s*\{([^}]+)\}/', $content, $matches)) {
            $existingVars = $matches[1];
            $newVars = $existingVars . "\n" . $newVar;
            return str_replace($matches[1], $newVars, $content);
        } else {
            // Create new :root block
            $rootBlock = ":root {\n{$newVar}\n}\n\n";
            return $rootBlock . $content;
        }
    }

    /**
     * Create media query
     */
    private function createMediaQuery(array $mediaQuery): string
    {
        $condition = $mediaQuery['condition'];
        $selectors = $mediaQuery['selectors'] ?? [];

        $css = "@media {$condition} {\n";

        foreach ($selectors as $selector) {
            $css .= "  " . str_replace("\n", "\n  ", $this->createSelector($selector));
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Extract selectors from CSS content
     */
    private function extractSelectors(string $content): array
    {
        $selectors = [];

        preg_match_all('/([^{}]+)\s*\{[^}]*\}/', $content, $matches);
        foreach ($matches[1] as $selector) {
            $selectors[] = trim($selector);
        }

        return $selectors;
    }

    /**
     * Extract CSS variables
     */
    private function extractVariables(string $content): array
    {
        $variables = [];

        preg_match_all('/--([^:]+):\s*([^;]+);/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $variables[trim($matches[1][$i])] = trim($matches[2][$i]);
        }

        return $variables;
    }

    /**
     * Extract @import statements
     */
    private function extractImports(string $content): array
    {
        $imports = [];

        preg_match_all('/@import\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $import) {
            $imports[] = $import;
        }

        return $imports;
    }

    /**
     * Extract media queries
     */
    private function extractMediaQueries(string $content): array
    {
        $mediaQueries = [];

        preg_match_all('/@media\s+([^{]+)\s*\{/', $content, $matches);
        foreach ($matches[1] as $query) {
            $mediaQueries[] = trim($query);
        }

        return $mediaQueries;
    }

    /**
     * Extract keyframes
     */
    private function extractKeyframes(string $content): array
    {
        $keyframes = [];

        preg_match_all('/@keyframes\s+([^{]+)\s*\{/', $content, $matches);
        foreach ($matches[1] as $name) {
            $keyframes[] = trim($name);
        }

        return $keyframes;
    }

    /**
     * Get basic reset styles
     */
    private function getResetStyles(): string
    {
        return "/* Reset Styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  line-height: 1.6;
}

img {
  max-width: 100%;
  height: auto;
}

a {
  text-decoration: none;
  color: inherit;
}

ul, ol {
  list-style: none;
}";
    }
}
