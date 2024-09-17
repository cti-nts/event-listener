<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.65.0|configurator
 * you can change this configuration by importing this file.
 */

$config = new Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        // Sets
        '@PSR12' => true,
        '@PHP83Migration' => true,

        // Rules
        // Each line of multi-line DocComments must have an asterisk [PSR-5] and must be aligned with the first one.
        'align_multiline_comment' => true,
        // Each element of an array must be indented exactly once.
        'array_indentation' => true,
        // Binary operators should be surrounded by space as configured.
        'binary_operator_spaces' => true,
        // There MUST be one blank line after the namespace declaration.
        'blank_line_after_namespace' => true,
        // Ensure there is no code on the same line as the PHP open tag and it is followed by a blank line.
        'blank_line_after_opening_tag' => true,
        // Putting blank lines between `use` statement groups.
        'blank_line_between_import_groups' => true,
        // The body of each structure MUST be enclosed by braces. Braces should be properly placed. Body of braces should be properly indented.
        'braces' => true,
        // A single space or none should be between cast and variable.
        'cast_spaces' => ['space' => 'none'],
        // Class, trait and interface elements must be separated with one or none blank line.
        'class_attributes_separation' => true,
        // Whitespace around the keywords of a class, trait, enum or interfaces definition should be one space.
        'class_definition' => true,
        // When referencing an internal class it must be written using the correct casing.
        'class_reference_name_casing' => true,
        // Namespace must not contain spacing, comments or PHPDoc.
        'clean_namespace' => true,
        // Using `isset($var) &&` multiple times should be done in one call.
        'combine_consecutive_issets' => false,
        // Calling `unset` on multiple items should be done in one call.
        'combine_consecutive_unsets' => false,
        // Concatenation should be spaced according to configuration.
        'concat_space' => ['spacing' => 'one'],
        // Force strict types declaration in all files.
        'declare_strict_types' => true,
        // Replaces short-echo `<?=` with long format `<?php echo`/`<?php print` syntax, or vice-versa.
        'echo_tag_syntax' => true,
        // Removes the leading part of fully qualified symbol references if a given symbol is imported or belongs to the current namespace.
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true
        ],
        // Spaces should be properly placed in a function declaration.
        'function_declaration' => true,
        // Ensure single space between function's argument and its typehint.
        'function_typehint_space' => true,
        // Include/Require and file path should be divided with a single space. File path should not be placed within parentheses.
        'include' => true,
        // Lambda must not import variables it doesn't use.
        'lambda_not_used_import' => true,
        // Magic constants should be referred to using the correct casing.
        'magic_constant_casing' => true,
        // Magic method definitions and calls must be using the correct casing.
        'magic_method_casing' => true,
        // Method chaining MUST be properly indented. Method chaining with different levels of indentation is not supported.
        'method_chaining_indentation' => true,
        // DocBlocks must start with two asterisks, multiline comments must start with a single asterisk, after the opening slash. Both must end with a single asterisk before the closing slash.
        'multiline_comment_opening_closing' => true,
        // Forbid multi-line whitespace before the closing semicolon or move the semicolon to the new line for chained calls.
        'multiline_whitespace_before_semicolons' => true,
        // Function defined by PHP should be called using the correct casing.
        'native_function_casing' => true,
        // Native type declarations for functions should use the correct case.
        'native_function_type_declaration_casing' => true,
        // Replace control structure alternative syntax to use braces.
        'no_alternative_syntax' => true,
        // There should not be blank lines between docblock and the documented element.
        'no_blank_lines_after_phpdoc' => true,
        // There should not be any empty comments.
        'no_empty_comment' => true,
        // There should not be empty PHPDoc blocks.
        'no_empty_phpdoc' => true,
        // Remove useless (semicolon) statements.
        'no_empty_statement' => true,
        // Removes extra blank lines and/or blank lines following configuration.
        'no_extra_blank_lines' => true,
        // The namespace declaration line shouldn't contain leading whitespace.
        'no_leading_namespace_whitespace' => true,
        // Either language construct `print` or `echo` should be used.
        'no_mixed_echo_print' => true,
        // Operator `=>` should not be surrounded by multi-line whitespaces.
        'no_multiline_whitespace_around_double_arrow' => true,
        // Short cast `bool` using double exclamation mark should not be used.
        'no_short_bool_cast' => true,
        // Single-line whitespace before closing semicolon are prohibited.
        'no_singleline_whitespace_before_semicolons' => true,
        // There MUST NOT be spaces around offset braces.
        'no_spaces_around_offset' => true,
        // Replaces superfluous `elseif` with `if`.
        'no_superfluous_elseif' => true,
        // If a list of values separated by a comma is contained on a single line, then the last item MUST NOT have a trailing comma.
        'no_trailing_comma_in_singleline' => true,
        // Removes unneeded parentheses around control statements.
        'no_unneeded_control_parentheses' => true,
        // Removes unneeded curly braces that are superfluous and aren't part of a control structure's body.
        'no_unneeded_curly_braces' => true,
        // Imports should not be aliased as the same name.
        'no_unneeded_import_alias' => true,
        // Unused `use` statements must be removed.
        'no_unused_imports' => true,
        // There should not be useless concat operations.
        'no_useless_concat_operator' => true,
        // There should not be useless `else` cases.
        'no_useless_else' => true,
        // There should not be an empty `return` statement at the end of a function.
        'no_useless_return' => true,
        // There should not be space before or after object operators `->` and `?->`.
        'object_operator_without_whitespace' => true,
        // Operators - when multiline - must always be at the beginning or at the end of the line.
        'operator_linebreak' => false,
        // Ordering `use` statements.
        'ordered_imports' => true,
        // PHPDoc should contain `@param` for all params.
        'phpdoc_add_missing_param_annotation' => false,
        // Docblocks should have the same indentation as the documented subject.
        'phpdoc_indent' => true,
        // Annotations in PHPDoc should be ordered in defined sequence.
        'phpdoc_order' => true,
        // Scalar types should always be written in the same form. `int` not `integer`, `bool` not `boolean`, `float` not `real` or `double`.
        'phpdoc_scalar' => true,
        // Single line `@var` PHPDoc should have proper spacing.
        'phpdoc_single_line_var_spacing' => true,
        // PHPDoc should start and end with content, excluding the very first and last line of the docblocks.
        'phpdoc_trim' => true,
        // The correct case must be used for standard PHP types in PHPDoc.
        'phpdoc_types' => true,
        // `@var` and `@type` annotations must have type and name in the correct order.
        'phpdoc_var_annotation_correct_order' => true,
        // `@var` and `@type` annotations of classy properties should not contain the name.
        'phpdoc_var_without_name' => true,
        // Local, dynamic and directly referenced variables should not be assigned and directly returned by a function or method.
        'return_assignment' => true,
        // Convert double quotes to single quotes for simple strings.
        'single_quote' => false,
        // Ensures a single space after language constructs.
        'single_space_after_construct' => true,
        // Fix whitespace after a semicolon.
        'space_after_semicolon' => true,
        // Replace all `<>` with `!=`.
        'standardize_not_equals' => true,
        // Switch case must not be ended with `continue` but with `break`.
        'switch_continue_to_break' => true,
        // Arguments lists, array destructuring lists, arrays that are multi-line, `match`-lines and parameters lists must have a trailing comma.
        'trailing_comma_in_multiline' => false,
        // Arrays should be formatted like function/method arguments, without leading or trailing single line space.
        'trim_array_spaces' => true,
        // A single space or none should be around union type and intersection type operators.
        'types_spaces' => false,
        // Unary operators should be placed adjacent to their operands.
        'unary_operator_spaces' => true,
        // Write conditions in Yoda style (`true`), non-Yoda style (`['equal' => false, 'identical' => false, 'less_and_greater' => false]`) or ignore those conditions (`null`) based on configuration.
        'yoda_style' => [
            'always_move_variable' => false,
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false
        ],
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__)
            ->exclude(['vendor'])
    );
