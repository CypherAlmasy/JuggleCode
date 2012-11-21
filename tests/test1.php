<?php

# A simple testfile for the JuggleCode package.
# The aim is to simply produce a sentence by using
# all features JuggleCode provides...

error_reporting(E_STRICT);

# Include some classes:
require('test2.php');
require('animals/dog.php');

# Dynamic include/require statements like the
# following should be kept untouched, even if
# mergeScripts is on:
$animal = 'fox';
require('animals/' . $animal . '.php');

$fox = new Fox;
$dog = new Dog;

function end_sentence($end_character) { return $end_character; }

# Collect words:
$words = array();
$words[] = 'The';
if ($fox->getAttributes()) {
	$words[] = $fox->getAttributes();
}
$words[] = $fox->getSpecies();
$words[] = Animal::jump();
$words[] = 'over';
$words[] = 'the';
if ($dog->getAttributes()) {
	$words[] = $dog->getAttributes();
}
$words[] = $dog->getSpecies();

# Concatenate words and output sentence:
$sentence = false;
include('build_sentence.php');
echo $sentence,PHP_EOL;
