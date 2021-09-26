<?php

$header = <<<'EOF'
The file is part of inhere/console

@author   https://github.com/inhere
@homepage https://github.com/inhere/php-console
@license  https://github.com/inhere/php-console/blob/master/LICENSE
EOF;

$rules = [
    '@PSR2' => true,
    'array_syntax' => [
        'syntax' => 'short'
    ],
    'list_syntax' => [
        'syntax' => 'short'
    ],
    'class_attributes_separation' => true,
    'declare_strict_types' => true,
    'global_namespace_import' => [
        'import_constants' => true,
        'import_functions' => true,
    ],
    'header_comment' => [
        'comment_type' => 'PHPDoc',
        'header'    => $header,
        'separate'  => 'bottom'
    ],
    'no_unused_imports' => true,
    'return_type_declaration' => [
        'space_before' => 'none',
    ],
    'single_quote' => true,
    'standardize_not_equals' => true,
    'void_return' => true, // add :void for method
];

$finder = PhpCsFixer\Finder::create()
    // ->exclude('test')
       ->exclude('docs')
       ->exclude('vendor')
       ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false);
