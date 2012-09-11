<?php

# Include requirements if not testing:
$wd = getcwd();
chdir(dirname(__FILE__));
require('../vendor/autoload.php');
chdir($wd);

# Start logging:
LogMore::open('jugglecode');
LogMore::debug('Including JuggleCode');

/**
 * Class: JuggleCode
 */
class JuggleCode extends PHPParser_PrettyPrinter_Zend {

	/**
	 * Constants: Callhandling
	 *
	 * JC_OPPRESS - Oppress call to function or method
	 * JC_REPLACE - Replace call to function or method
	 */
	const JC_OPPRESS = 1;
	const JC_REPLACE = 2;

	/**
	 * Variable: $masterfile
	 *
	 * Absolute or relative path to the projects master-/mainfile.
	 */
	private $masterfile;


	/**
	 * Variable: $outfile
	 *
	 * Absolute or relative path to the projects outfile. If the 
	 * outfile is empty or null, the final script will get printed
	 * on stdout.
	 */
	private $outfile;


	/**
	 * Variable: $mergeScripts
	 *
	 * Flag to enable or disable the merging of script. Per default,
	 * merging is disabled.
	 */
	private $mergeScripts;


	/**
	 * Variable: $comments
	 *
	 * Flag to enable or disable comments in the output. Per default,
	 * comments are enabled.
	 */
	private $comments;


	/**
	 * Variable: $oppressedFunctionCalls
	 *
	 * Array containing the function calls to oppress.
	 *
	 * See Also:
	 *
	 * 	<oppressFunctionCall>
	 */
	private $oppressedFunctionCalls = array();


	/**
	 * Variable: $replacedFunctionCalls
	 *
	 * Table containing the function calls to replace and
	 * the code to replace the calls with (expression).
	 * Example:
	 *	$this->replacedFunctionCalls = array(
	 *		'function' => 'echo "Calling function"',
	 *		'function2' => 'foo()',
	 *	);
	 *
	 * See Also:
	 *
	 * 	<replaceFunctionCall>
	 */
	private $replacedFunctionCalls = array();


	/**
	 * Variable: $handleMethodCalls 
	 *
	 * Table containing the methods and info about how to handle them.
	 * Example:
	 *	$this->handleMethodCalls = array(
	 *		'$instance' => array(
	 *			'method' => array(
	 * 				'type' => JuggleCode::JC_OPPRESS
	 * 			),
	 *			'foo' => array(
	 * 				'type' => JuggleCode::JC_REPLACE,
	 * 				'expression' => 'foo_new(%args%)'
	 * 			)
	 * 		)
	 *	);
	 */
	private $handleMethodCalls = array();


	/**
	 * Variable: $includedFiles
	 *
	 * Table holding the filenames of the files that have been
	 * included while pretty-printing the masterfile as key.
	 * The value is the number of times the file has been included.
	 *
	 * Example of the contents of $includedFiles:
	 * > array(
	 * > 	'file1' => 1,
	 * > 	'file2' => 3
	 * > )
	 */
	private $includedFiles;


	/**
	 * Function: __construct
	 *
	 * The constructor
	 */
	public function __construct($masterfile=null, $outfile=null) {
		parent::__construct();

		# Initialize:
		if ($masterfile) {
			$this->setMasterfile($masterfile);
		}

		$this->outfile = $outfile;
		$this->comments = true;
		$this->mergeScripts = false;
		$this->includedFiles = array();
	}


	/**
	 * Function: setMasterfile
	 *
	 * Validates the passed filepath to the applications masterfile
	 * for existance and readability.
	 */
	public function setMasterfile($masterfile) {
		if (is_file($masterfile) && is_readable($masterfile)) {
			LogMore::info('Valid file passed');
			$this->masterfile = $masterfile;
		} else {
			LogMore::err('Invalid file passed: %s',
				$masterfile);
		}
	}


	/**
	 * Function: setOutfile
	 *
	 * Set the outfile for the PrettyPrinter. If left empty,
	 * output will be printed on the screen.
	 */
	public function setOutfile($outfile) {
		$this->outfile = $outfile;
	}


	/**
	 * Function: __set
	 *
	 * Magic method to handle setting of variables.
	 */
	public function __set($name, $value) {
		LogMore::debug('Calling __set');

		# Initialize masterfile:
		if ($name == 'masterfile') {
			$this->setMasterfile($value);
		} else {
			$this->$name = $value;
		}
	}


	/**
	 * Function: oppressFunctionCall
	 */
	public function oppressFunctionCall($function) {
		$this->oppressedFunctionCall[] = $function;
	}

	
	/**
	 * Function: oppressMethodCall
	 */
	public function oppressMethodCall($instanceOrClass, $method) {
		return $this->saveMethodHandling(
			self::JC_OPPRESS,
			$instanceOrClass,
			$method,
			$expression);
	}


	/**
	 * Function: replaceFunctionCall
	 *
	 * Parameters:
	 *
	 * 	$function - Name of the function to replace
	 * 	$expression - Expression to replace the function-call with
	 */
	public function replaceFunctionCall($function, $expression) {
		$this->replacedFunctionCalls[$function] = $expression;
	}


	/**
	 * Function: replaceMethodCall
	 *
	 * Parameters:
	 *
	 * 	$instanceName - Name of the instance
	 * 	$method - Name of the method to replace
	 * 	$expression - Expression to replace the function-call with
	 */
	public function replaceMethodCall($instanceName, $method, $expression) {
		return $this->saveMethodHandling(
			self::JC_REPLACE,
			$instanceName,
			$method,
			$expression);
	}


	/**
	 * Function: saveMethodHandling
	 *
	 * Stores the data for handling method calls.
	 *
	 * Parameters:
	 *
	 * 	$type - How the call should get handled,
	 * 		see Callhandling-Constants
	 * 	$instanceOrClass - Name of the instance or the class
	 * 		in case of static methods
	 * 	$method - Name of the method
	 * 	$expression - Expression to replace the function-call with,
	 * 		if $type is JC_REPLACE
	 */
	private function saveMethodHandling(
		$type,
		$instanceOrClass,
		$method,
		$expression=null)
	{
		# Create table entry if not existant:
		if (!isset($this->handleMethodCalls[$instanceOrClass])) {
			$this->handleMethodCalls[$instanceOrClass] = array();
		}

		# Initialise handling of method call:
		$this->handleMethodCalls[$instanceOrClass][$method] = array(
			'type' => $type,
			'expression' => $expression
		);

		LogMore::debug('Method to handle (instance/method/type): %s/%s/%d',
			$instanceOrClass,
			$method,
			$type);
	}


	/**
	 * Function: getMethodHandlingType
	 */
	private function getMethodHandlingType($instance, $method) {
		return (isset($this->handleMethodCalls[$instance][$method]['type']))
			? $this->handleMethodCalls[$instance][$method]['type']
			: 0;
	}


	/**
	 * Function: getMethodHandlingExpression
	 */
	private function getMethodHandlingExpression($instance, $method) {
		return (isset($this->handleMethodCalls[$instance][$method]['expression']))
			? $this->handleMethodCalls[$instance][$method]['expression']
			: 0;
	}


	/**
	 * Function: pComments
	 *
	 * Handles comments
	 *
	 * Returns:
	 *
	 * 	If comments are enabled, the comments get returned.
	 * 	If comments are disabled, null is returned
	 */
	public function pComments(array $comments) {
		if ($this->comments) {
			$comments = parent::pComments($comments);
		} else {
			$comments = null;
		}

		return $comments;
	}


	/**
	 * Function: pExpr_Include
	 *
	 * Handles the inclusion of script-files.
	 */
	public function pExpr_Include(PHPParser_Node_Expr_Include $node) {
		$file_to_include = $node->expr->value;

		if ($file_to_include && $this->mergeScripts) {
			LogMore::debug('File to include: %s', $file_to_include);

			# If the file should be only included/required once
			if ( 	$node->type == PHPParser_Node_Expr_Include::TYPE_INCLUDE_ONCE ||
				$node->type == PHPParser_Node_Expr_Include::TYPE_REQUIRE_ONCE)
			{
				# If the file has already been included
				if (isset($this->includedFiles[$file_to_include])) {
					LogMore::debug('File has already been included once');

					# Leave function
					return null;
				}
			}

			$code = $this->parseFile($file_to_include);

			# Add file to array of included files and raise counter:
			if (isset($this->includedFiles[$file_to_include])) {
				$this->includedFiles[$file_to_include] += 1;
			} else {
				$this->includedFiles[$file_to_include] = 1;
			}

			return $code;
		} else {
			return parent::pExpr_Include($node);
		}
	}


	/**
	 * Function: pExpr_FuncCall
	 *
	 * Handles the printing of function calls.
	 */
	public function pExpr_FuncCall(PHPParser_Node_Expr_FuncCall $node) {
		$code = null;
		$function_name = $this->p($node->name);
		LogMore::debug('Name of function to call: %s', $function_name);

		# If function should get oppressed
		if (in_array($function_name, $this->oppressedFunctionCalls)) {
			$code = 'null';
		} elseif (isset($this->replacedFunctionCalls[$function_name])) {
			$code = $this->formatExpression(
				$this->replacedFunctionCalls[$function_name],
				$node);
		} else {
			$code = parent::pExpr_FuncCall($node);
		}

		return $code;
	}


	/**
	 * Function: formatExpression
	 *
	 * Parameters:
	 *
	 * 	$expression - The expression is allowed to hold the keyword %args%
	 * 		which will get replaced with the arguments list
	 * 	$node - The node which holds the data for formating
	 */
	private function formatExpression($expression, $node) {
		# Compile list of possible argument-keywords:
		$args_keywords = $args = array();
		if ($node->args) {
			# All arguments at once:
			$args_keywords[] = '%args%';
			$args[] = $this->pCommaSeparated($node->args);

			# Single arguments:
			foreach ($node->args as $i => $arg) {
				$args_keywords[] = '%arg' . ($i+1) . '%';
				$args[] = $this->p($arg);
			}

			LogMore::debug('Possible keywords to use: %s', implode(',', $args_keywords));
		}

		return str_replace(
			$args_keywords,
			$args,
			$expression
		);
	}


	/**
	 * Function: pExpr_MethodCall
	 *
	 * Handles the printing of method calls.
	 */
	public function pExpr_MethodCall(PHPParser_Node_Expr_MethodCall $node) {
		$code = null;
		$instance = $this->pVarOrNewExpr($node->var);
		$method = $this->pObjectProperty($node->name);
		LogMore::debug('Name of method and instance to call: %s, %s',
			$method,
			$instance);

		if (isset($this->handleMethodCalls[$instance][$method])) {
			$code = $this->printMethodCall($instance, $method, $node);
		} else {
			$code = parent::pExpr_MethodCall($node);
		}
		return $code;
	}


	/**
	 * Function: pExpr_StaticCall
	 *
	 * Handles the printing of static method calls.
	 */
	public function pExpr_StaticCall(PHPParser_Node_Expr_StaticCall $node) {
		$code = null;

		# Get class and method name:
		$class = $this->p($node->class);
		$method = $node->name;
		LogMore::debug('Name of static method and class to call: %s, %s',
			$method,
			$class);

		if (isset($this->handleMethodCalls[$class][$method])) {
			$code = $this->printMethodCall($class, $method, $node);
		} else {
			$code = parent::pExpr_StaticCall($node);
		}

		return $code;
	}


	/**
	 * Function: printMethodCall
	 *
	 * Handles the printing of non-static and static method calls.
	 */
	private function printMethodCall($instanceOrClass, $method, $node) {
		$code = null;

		# Get type for handling call:
		$type = $this->getMethodHandlingType($instanceOrClass, $method);
		LogMore::debug('Type of method to call: %d', $type);

		if ($type == self::JC_OPPRESS) {
			LogMore::debug('Oppress method call');
			$code = 'null';
		} elseif ($type == self::JC_REPLACE) {
			LogMore::debug('Replace method call');
			$code = $this->formatExpression(
				$this->getMethodHandlingExpression($instanceOrClass, $method),
				$node);
		}

		return $code;
	}


	/**
	 * Function: run
	 *
	 * Parses and prints the masterfile either to stdout or
	 * to the outfile.
	 *
	 * Returns:
	 *
	 * 	true - When all statements got parsed and the outfile 
	 * 		was written
	 * 	false - When an error occured
	 */
	public function run() {
		$rc = false;

		if ($this->masterfile) {
			# Get projectfolder:
			$mainfolder = dirname($this->masterfile);

			# Get working directory:
			$workingdir = getcwd();

			# Switch to projectfolder
			chdir($mainfolder);

			# Process masterfile:
			$program = $this->parseFile(basename($this->masterfile));

			# Add PHP tags:
			$program = '<?php' . PHP_EOL . $program;

			# Switch back to workingdir:
			chdir($workingdir);

			# If program should get written to file
			if ($this->outfile) {
				file_put_contents($this->outfile, $program);
			} else {
				echo $program;
			}

			$rc = true;
		}

		return $rc;
	}


	/**
	 * Function: parseFile
	 *
	 * Returns:
	 *
	 * 	The pretty-printed PHP code
	 */
	private function parseFile($file) {
		LogMore::debug('parsing file %s', $file);
		$statements = file_get_contents($file);

		# Create Parser
		$parser = new PHPParser_Parser(new PHPParser_Lexer);

		# Create syntax tree
		$syntax_tree = $parser->parse($statements);
		LogMore::debug('Syntax tree parsed');

		# Pretty print syntax tree/convert syntax tree back to PHP statements:
		return $this->prettyPrint($syntax_tree);
	}

};


# To be able to use JuggleCode directly from the commandline, the
# first argument must be set to the current filename:
if (isset($argv) && isset($argv[0]) && basename($argv[0]) == 'JuggleCode.php') {
	# If a mainfile has been passed:
	if (isset($argv[1])) {
		LogMore::debug('Running JuggleCode from the commandline');
		$mainfile = $argv[1];

		# Run ScripJoiner:
		$s = new JuggleCode($mainfile);
		$s->run();
	}
}
