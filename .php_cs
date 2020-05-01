<?php

$header = <<<'EOF'

@license  https://github.com/inhere/php-validate/blob/master/LICENSE
EOF;

return PhpCsFixer\Config::create()->setRiskyAllowed(true)->setRules([
    '@PSR2'                       => true,
    // 'header_comment' => [
    //     'comment_type' => 'PHPDoc',
    //      'header'    => $header,
    //     'separate'  => 'none'
    // ],
    'array_syntax'                => [
      'syntax' => 'short'
    ],
    'single_quote'                => true,
    'class_attributes_separation' => true,
    'no_unused_imports'           => true,
    'standardize_not_equals'      => true,
    'declare_strict_types'        => true,
  ])->setFinder(PhpCsFixer\Finder::create()
    // ->exclude('test')
                                 ->exclude('docs')->exclude('vendor')->in(__DIR__))->setUsingCache(false);
