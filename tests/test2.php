<?php

class Animal {
	private $species, $attributes;
	public function __construct($species, $attributes) {
		$this->species = $species;
		$this->attributes = $attributes;
	}
	public function getSpecies() { return $this->species; }
	public function getAttributes() { return $this->attributes; }
	public function jump() { return 'jumps'; }
	public function hop() { return 'hops'; }
};

class Fox extends Animal {
	public function __construct() {
		parent::__construct('fox', 'quick brown');
	}
};

class Dog extends Animal {
	public function __construct() {
		parent::__construct('dog', 'lazy');
	}
};
