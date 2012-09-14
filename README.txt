Title: Description

JuggleCode is a tool to manipulate PHP statements in scriptfiles.


Title: Features

JuggleCode can:

- Join multiple PHP files into a single outfile
- Remove comments from PHP files
- Oppress or replace function and method calls in PHP scripts


Title: Usage

JuggleCode always expects a PHP script as input. The output is redirected to stdout per default, but can get captured to a file.

Example: the two files file1.php and file2.php form a PHP application and should get distributed in one file only (with the name app.php).

file1.php:

: 	<?php
: 	# file1.php:
: 	echo 'File 1',PHP_EOL;
: 	require('file2.php');

file2.php:

: 	<?php
: 	# file2.php:
: 	echo 'File 2',PHP_EOL;

To combine these two files into app.php, a short PHP scripts needs to be written:

: 	require('vendor/codeless/jugglecode/src/JuggleCode.php');
:
: 	$j = new JuggleCode();
: 	$j->masterfile = 'file1.php';
: 	$j->outfile = 'app.php';
: 	$j->mergeScripts = true;
: 	$j->run();

The first three lines of the above script can get combined to:

: 	$j = new JuggleCode('file1.php', 'app.php');

The result of the merging-process will look like this:

: 	<?php
: 	# file1.php:
: 	echo 'File 1',PHP_EOL;
: 	# file2.php:
: 	echo 'File 2',PHP_EOL;

To disable comments in the output, use:

: 	$j->comments = false;

Oppress function- and method-calls:

: 	$j->oppressFunctionCall('str_replace'); # Oppress all calls to str_replace
: 	$j->oppressMethodCall('$foo', 'foo'); # Oppress all calls to $foo->foo()
: 	$j->oppressMethodCall('Foo', 'foo'); # Oppress all calls to Foo::foo()

Replace function- and method-calls:

: 	# Replace all calls to str_replace with str_ireplace:
: 	$j->replaceFunctionCall('str_replace', 'str_ireplace(%args%)');
:
: 	# Replace all calls to $foo->foo() with foo():
: 	$j->replaceMethodCall('$foo', 'foo', 'foo(%args%)');


Title: Running JuggleCode directly from the commandline

: 	php -f /path/to/JuggleCode.php /path/to/project/mainfile.php 1> /path/to/outfile.php

Note the "1>", which redirects only the stdout to the outfile; JuggleCode will use the stderr-channel for logging.


Title: Installation

JuggleCode is easily installed using Packagist/Composer.


Title: Ideas for using JuggleCode

- Deploying PHP applications in a single file and in different versions: one version with included debugging features, the other version without


Title: Ideas for improving JuggleCode

- Allow the creation of single-file PHP patch-scripts that can overwrite PHP statements in one or multiple other PHP files
- Oppressing or replacing the body of function or method definitions (replaceMethodBody, replaceFunctionBody)
- Improve the code by seperating the JuggleCode class into multiple classes, e.g one for methods, one for functions, asf.
- Convert JuggleCode to a PHP extension (see PHP Preprocessors like <http://www.ohloh.net/p/pihipi> and <http://code.metala.org/p/ccpp>)


Title: Credits and Bugreports

JuggleCode was written by Codeless (<http://www.codeless.at/>). All bugreports can be directed to more@codeless.at. Even better, bugreports are posted on the github-repository of this package: <https://www.github.com/codeless/jugglecode>.
JuggleCode would not have been possible if there isn't nikic's PHP-Parser package: <https://www.github.com/nikic/php-parser>.
