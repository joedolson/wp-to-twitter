<?php
/**
 * Scope vendor classes for XPoster.
 *
 * @category    Build
 * @package     XPoster
 * @author      @rob006
 * @copyright   2008-2023 Joe Dolson
 * @license     GPL-2.0+
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

return array(
	// The prefix configuration. If a non null value is be used, a random prefix
	// will be generated instead.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix.
	'prefix' => 'WpToTwitter_Vendor',

	// By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	// directory. You can however define which files should be scoped by defining a collection of Finders in the
	// following configuration key.
	//
	// This configuration entry is completely ignored when using Box.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths.
	'finders' => array(
		Finder::create()
			->files()
			->ignoreVCS( true )
			->notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/' )
			->exclude(
				array(
					'doc',
					'test',
					'test_old',
					'tests',
					'Tests',
					'vendor-bin',
				)
			)->in( __DIR__ . '/src/vendor' ),
		Finder::create()->append(
			array(
				'../composer.json',
			)
		),
	),

	// List of excluded files, i.e. files for which the content will be left untouched.
	// Paths are relative to the configuration file unless if they are already absolute
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers.
	'exclude-files' => array(),

	// When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	// original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	// support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	// heart contents.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers.
	'patchers' => array(
		static function ( string $filePath, string $prefix, string $contents ): string { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			// Change the contents here.

			return $contents;
		},
	),

	// List of symbols to consider internal i.e. to leave untouched.
	//
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols.
	'exclude-namespaces' => array(),
	'exclude-classes'    => array(),
	'exclude-functions'  => array(),
	'exclude-constants'  => array(),

	// List of symbols to expose.
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols.
	'expose-global-constants' => true,
	'expose-global-classes'   => true,
	'expose-global-functions' => true,
	'expose-namespaces'       => array(),
	'expose-classes'          => array(),
	'expose-functions'        => array(),
	'expose-constants'        => array(),
	];
