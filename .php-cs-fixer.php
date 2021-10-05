<?php


/**
 * This file represents the configuration for Code Sniffing PSR-2-related
 * automatic checks of coding guidelines
 * Install @fabpot's great php-cs-fixer tool via
 *
 *  $ composer global require friendsofphp/php-cs-fixer
 *
 * And then simply run
 *
 *  $ php-cs-fixer fix
 *
 * For more information read:
 *  http://www.php-fig.org/psr/psr-2/
 *  http://cs.sensiolabs.org
 */

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
//        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => true,
        'braces' => ['allow_single_line_closure' => true],
        'cast_spaces' => ['space' => 'none'],
        'compact_nullable_typehint' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'none'],
        'dir_constant' => true,
//        'fully_qualified_strict_types' => true,
        'function_typehint_space' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author']
        ],
        'single_line_comment_style' => true,
        'lowercase_cast' => true,
//        'method_separation' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'modernize_types_casting' => true,
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_alias_functions' => true,
//        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
//        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
//        'no_multiline_whitespace_around_double_arrow' => true,
//        'no_multiline_whitespace_before_semicolons' => true,
        'no_null_property_initialization' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
//        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
//        'no_unreachable_default_argument_value' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
//        'no_useless_return' => true,
//        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
//        'normalize_index_brace' => true,
        'ordered_imports' => true,
        'php_unit_construct' =>
            ['assertions' =>
                ['assertEquals', 'assertSame', 'assertNotEquals', 'assertNotSame']
            ],
        'php_unit_mock_short_will_return' => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
//        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
//        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'return_type_declaration' => ['space_before' => 'none'],
//        'self_accessor' => true,
//        'short_scalar_cast' => true,
        'single_quote' => true,
        'single_trait_insert_per_statement' => true,
//        'standardize_not_equals' => true,
//        'ternary_operator_spaces' => true,
//        'trailing_comma_in_multiline_array' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude('.Build')
            ->exclude('node_modules')
    );
