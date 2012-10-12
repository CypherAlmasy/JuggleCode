<?php

require('vendor/autoload.php');
require('src/JuggleCode.php');

class JuggleCodeTest extends PHPUnit_Framework_TestCase {

	/**
	 * Testcase provider for juggling code
	 */
	public function provideCases() {
		return array(
			array(
				// No function or method oppressed
				'The quick brown fox jumps over the lazy dog.',
				null
			),
			array(
				// Oppress function-call of end_sentence
				'The quick brown fox jumps over the lazy dog',
				array('oppressedFunctionCalls' => array('end_sentence'))
			),
			array(
				// Oppress method-calls
				'The fox jumps over the dog.',
				array('handleMethodCalls' => array(
					'$fox' => array(
						'getAttributes' => array(
							'type' => JuggleCode::JC_OPPRESS
							)),
					'$dog' => array(
						'getAttributes' => array(
							'type' => JuggleCode::JC_OPPRESS
							))
					)
				)
			),
			array(
				// Replace function call of end_sentence:
				'The quick brown fox jumps over the lazy dog!',
				array('replacedFunctionCalls' =>
					array('end_sentence' => '"!"'))
			),
			array(
				// Replace arguments of implode-function-call:
				'Lorem ipsum.',
				array('replacedFunctionCalls' =>
					array('implode' => 'implode(%arg1%, array("Lorem", "ipsum"))'))
			),
			array(
				// Replace function call of end_sentence, but obtain parameter-list:
				'The quick brown fox jumps over the lazy dog...',
				array('replacedFunctionCalls' =>
					array('end_sentence' => 'end_sentence(%args% . "..")'))
			),
			array(
				// Replace method-calls:
				'The lazy dog jumps over the quick brown fox.',
				array('handleMethodCalls' => array(
					'$fox' => array(
						'getAttributes' => array(
							'type' => JuggleCode::JC_REPLACE,
							'expression' => '$dog->getAttributes()'
						),
						'getSpecies' => array(
							'type' => JuggleCode::JC_REPLACE,
							'expression' => '$dog->getSpecies()'
						)
					),
					'$dog' => array(
						'getAttributes' => array(
							'type' => JuggleCode::JC_REPLACE,
							'expression' => '$fox->getAttributes()'
						),
						'getSpecies' => array(
							'type' => JuggleCode::JC_REPLACE,
							'expression' => '$fox->getSpecies()'
						)
					)
				))
			),
			array(
				// Replace static method-calls:
				'The quick brown fox hops over the lazy dog.',
				array('handleMethodCalls' => array(
					'Animal' => array(
						'jump' => array(
							'type' => JuggleCode::JC_REPLACE,
							'expression' => 'Animal::hop()'
						)
					)
				))
			)
		);
	}


	/**
	 * @dataProvider provideCases
	 */
	public function testJuggling($expected_result, $options) {
		# Create new object
		$j = new JuggleCode('tests/test1.php', 'tests/outfile.php');

		# Set default options for this test-script:
		$j->mergeScripts = true;

		# Set options...
		if ($options) {
			foreach ($options as $member => $value) {
				$j->$member = $value;
			}
		}

		# Juggle code
		$j->run();

		# Run juggled code
		$actual_result = shell_exec('php -f tests/outfile.php');

		# Compare results:
		$this->assertEquals($expected_result . PHP_EOL, $actual_result);
	}


	public function testFileMerging() {
		# Create new object
		$j = new JuggleCode('tests/test1.php', 'tests/outfile.php');

		# Tell JuggleCode to only merge test2.php:
		$j->mergeFile('test2.php');

		# Run:
		$j->run();

		# Check the include-table for correctness:
		$this->assertEquals(
			$j->getIncludedFiles(),
			array('test2.php' => 1));
	}

};
