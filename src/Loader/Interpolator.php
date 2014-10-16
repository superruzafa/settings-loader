<?php

namespace Superruzafa\Settings\Loader;

class Interpolator
{
    /**
     * Interpolates string context values with values of other keys in the context.
     *
     * @param   array   $context   Context whose variables will be interpolated
     */
    public function interpolate(array $context)
    {
        $solvedContext = $stack = array();
        foreach ($context as $key => &$value) {
            $value = self::doInterpolation($key, $context, $solvedContext, $stack);
        }
        return $context;
    }

    /**
     * Auxiliary recursive method. Does the recursive interpolation.
     */
    public static function doInterpolation($key, array $context, array &$solvedContext, array &$stack)
    {
        if (isset($solvedContext[$key])) {
            return $solvedContext[$key];
        }

        if (in_array($key, $stack)) {
            trigger_error(sprintf('Cyclic recursion: %s -> %s', implode(' -> ', $stack), $key), E_USER_WARNING);
            return $solvedContext[$key] = '';
        }

        if (!isset($context[$key])) {
            trigger_error(sprintf('Undefined key: "%s"', $key), E_USER_WARNING);
            return $solvedContext[$key] = '';
        }

        if (!is_string($context[$key])) {
            return $solvedContext[$key] = $context[$key];
        }

        $callback = function ($matches) use ($context, &$solvedContext, &$stack) {
            list(, $subkey) = $matches;
            $value = Interpolator::doInterpolation($subkey, $context, $solvedContext, $stack);
            if (is_array($value)) {
                trigger_error(sprintf('Array interpolation: "%s"', $subkey), E_USER_WARNING);
                return '<array>';
            } elseif (is_object($value)) {
                trigger_error(sprintf('Object interpolation: "%s"', $subkey), E_USER_WARNING);
                return '<object>';
            }
            return $value;
        };

        // Matches things like {{whatever}}, {{ what}ever }}, {{what}e}v}e}r }}...
        // and replaces the string comprised between "{{" and "}}"
        array_push($stack, $key);
        $solvedContext[$key] = preg_replace_callback('/\{\{\s*((?:(?!}})\S)+)\s*}}/', $callback, $context[$key]);
        array_pop($stack);
        return $solvedContext[$key];
    }
}
