<?php

class Dog extends Animal {
	public function __construct() {
		parent::__construct('dog', 'lazy');
	}
};

require_once('ueberdog.php');
