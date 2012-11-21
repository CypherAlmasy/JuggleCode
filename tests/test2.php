<?php

class Animal {
	private $species, $attributes;
	public function __construct($species, $attributes) {
		$this->species = $species;
		$this->attributes = $attributes;
	}
	public function getSpecies() { return $this->species; }
	public function getAttributes() { return $this->attributes; }
	public static function jump() { return 'jumps'; }
	public static function hop() { return 'hops'; }
};
