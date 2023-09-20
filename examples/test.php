<?php

// Copyleft Nurudin Imsirovic <github.com/oxou>
//
// This is test script for arg_opts()
//
// Created: 2023-09-20 12:28 AM
// Updated: 2023-09-20 12:31 AM

// Run the 'test' scripts to see the library in action.
// You can also run 'php args.php' with your arguments
// to see what gets output.

// Load library
require "../lib.php";

// Function flags
// ==============

// When a parameter with no assigned value is encountered,
// it's value defaults to 1 if $empty_params_become_true
// is true, else the value defaults to null.
//
// Default value: 1
// Possible values: 0, 1
$empty_params_become_true = 1;

// Instead of returning an array of parameters with values,
// when set to 1 return an array of opposite matches (non-
// parameters), when set to 2 return an array of parameters
// and opposite matches in an array:
// [
//   parameters => [...],
//   opposite   => [...]
// ]
//
// Default value: 0
// Possible values: 0, 1, 2
$get_non_opts = 0;

// When true all arguments after "--" will be discarded
// and the processed leftover is returned.
//
// Default value: 0
// Possible values: 0, 1
$signify_end = 0;

$parameters = arg_opts(
    $argv,
    $empty_params_become_true,
    $get_non_opts,
    $signify_end
);

print_r($parameters);
