<?php

// Copyleft Nurudin Imsirovic <github.com/oxou>
//
// Arguments Parameter Parser
//
// Created: 2023-09-20 12:30 AM
// Updated: 2023-09-24 07:43 AM

// returns an array with argument name
// indexes and argument specific values
//
// if $empty_params_become_true is true, all parameters
// without a value will automatically be assigned true.
//
// if $get_non_opts is 1|true, we return an array of
// options not matching the required criteria, else if
// it is 2, we return an array of parameters and also
// options not matching the required criteria with the
// respective indexes: parameters, opposite
//
// if $signify_end is true, we stop parsing parameters
// after we've reached '--' in the argument list, after
// which we return as usual without the extra data.
//
// if $same_params_become_array is true, we turn each
// reoccuring parameter into an array of multiple
// values given to that parameter.
function arg_opts(
    $arguments,
    $empty_params_become_true = true,
    $get_non_opts = false,
    $signify_end = false,
    $same_params_become_array = false
) {
    // Return an empty array if an empty array of arguments was given
    if (!is_array($arguments) || !sizeof($arguments)) {
        return array();
    }

    $parameters = array();
    $opposite = array();
    $skip_next = false;
    $signified_end = false;

    foreach ($arguments as $index => $value) {
        // We are told to skip
        if ($skip_next) {
            $skip_next = false;
            continue;
        }

        // Skip empty arguments
        if ($value == '')
            continue;

        // TODO: Add support for + parameters
        // Each argument must begin with a hyphen
        // (-) to be consideder a "parameter"
        if (strlen($value) > 0 && $value[0] != '-') {
            if ($get_non_opts)
                $opposite[] = $value;
            continue;
        }

        if ($signify_end && $value == "--") {
            $signified_end = true;
            break;
        }

        $next_arg = $arguments[$index + 1] ?? '';
        $has_equals = strpos($value, '=') !== false;
        $has_space  = strpos($value, ' ') !== false;

        // If this is an argument that begins with '-'
        // but has whitespace ' ', we won't treat it
        // as a parameter because parameters must not
        // contain whitespaces in their names.
        if ($has_space && !$has_equals) {
            if ($get_non_opts)
                $opposite[] = $value;
            continue;
        }

        // Handle parameter=value
        if ($has_equals) {
            $pairs = explode('=', $value);
            // NOTE: We don't pass $get_non_opts and $signify_end
            // as that will require more logic.
            $process = arg_opts(
                $pairs,
                $empty_params_become_true,
                false,
                false,
                $same_params_become_array
            );

            $pindex = ltrim($pairs[0], '-');
            $value = $process[$pindex];

            if ($same_params_become_array) {
                if (!isset($parameters[$pindex])) {
                    $parameters[$pindex] = $value;
                } else {
                    if (is_string($parameters[$pindex])) {
                        $parameters[$pindex] = [$parameters[$pindex], $value];
                    } else {
                        if (!is_array($parameters[$pindex])) {
                            $parameters[$pindex] = [];
                        }

                        $parameters[$pindex][] = $value;
                    }
                }
            } else {
                $parameters[$pindex] = $value;
            }

            continue;
        }

        $pindex = ltrim($value, '-');

        // NOTE: If we pass '--' while $signify_end is false, this
        // will result in a parameter that has no name (empty),
        // and this result will be written to the list, we don't
        // want that.
        if ($pindex == '')
            continue;

        // If the next argument is also an option, we'll set
        // the current argument to null|true and move on.
        if ($next_arg != '' && $next_arg[0] == '-') {
            $value = $empty_params_become_true ? 1 : null;

            arg_opts_auto_array(
                $same_params_become_array,
                $parameters[$pindex],
                $value
            );

            continue;
        }

        // If no value is given, set the current argument to
        // be null|true and move on.
        if ($next_arg == '') {
            $value = $empty_params_become_true ? 1 : null;

            arg_opts_auto_array(
                $same_params_become_array,
                $parameters[$pindex],
                $value
            );

            continue;
        }

        // Set the current argument to the next argument value
        // and skip checking the next argument for parameters
        arg_opts_auto_array(
            $same_params_become_array,
            $parameters[$pindex],
            $next_arg
        );

        $skip_next = true;
    }

    // Return non-parameters
    if ($get_non_opts === 1)
        return $opposite;

    // Return parameters and non-parameters
    if ($get_non_opts === 2)
        return array(
            "parameters" => $parameters,
            "opposite"   => $opposite
        );

    return $parameters;
}

// automatically converts a string value
// to an array depending on its state.
function arg_opts_auto_array(
    $become_array,
    &$parameter_index,
    $value
) {
    if ($become_array) {
        if (!isset($parameter_index)) {
            $parameter_index = $value;
        } else {
            if (is_string($parameter_index)) {
                $parameter_index = [$parameter_index, $value];
            } else {
                if (!is_array($parameter_index)) {
                    $parameter_index = [];
                }

                $parameter_index[] = $value;
            }
        }
    } else {
        $parameter_index = $value;
    }
}
