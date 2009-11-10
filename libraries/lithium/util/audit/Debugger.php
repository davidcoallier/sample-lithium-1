<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\audit;

use \lithium\util\String;

class Debugger extends \lithium\core\Object {

	/**
	 * Outputs a stack trace based on the supplied options.
	 *
	 * @param array $options Format for outputting stack trace. Available options are:
	 *              - `'depth'`: The maximum depth of the trace.
	 *              - `'format'`: Either `null`, `'points'` or `'array'`.
	 *              - `'args'`: A boolean indicating if arguments should be included.
	 *              - `'start'`: The depth to start with.
	 *              - `'scope'`: Scope for items to include.
	 *              - `'trace'`: A trace to use instead of generating one.
	 *              - `'includeScope'`: A boolean indicating if items within scope
	 *                should be included.
	 * @return string|array Stack trace formatted according to `'format'` option.
	 */
	public static function trace($options = array()) {
		$defaults = array(
			'depth' => 999,
			'format' => null,
			'args' => false,
			'start' => 0,
			'scope' => array(),
			'trace' => array(),
			'includeScope' => true
		);
		$options += $defaults;

		$backtrace = $options['trace'] ?: debug_backtrace();
		$scope = $options['scope'];
		$count = count($backtrace);
		$back = array();
		$traceDefault = array(
			'line' => '??', 'file' => '[internal]', 'class' => null, 'function' => '[main]'
		);

		for ($i = $options['start']; $i < $count && $i < $options['depth']; $i++) {
			$trace = array_merge(array('file' => '[internal]', 'line' => '??'), $backtrace[$i]);
			$function = '[main]';

			if (isset($backtrace[$i + 1])) {
				$next = $backtrace[$i + 1] + $traceDefault;
				$function = $next['function'];

				if (!empty($next['class'])) {
					$function = $next['class'] . '::' . $function . '(';
					if ($options['args'] && isset($next['args'])) {
						$args = array_map(array('static', 'export'), $next['args']);
						$function .= join(', ', $args);
					}
					$function .= ')';
				}
			}

			if (in_array($function, array('call_user_func_array', 'trigger_error'))) {
				continue;
			}
			$trace['functionRef'] = $function;

			if ($options['format'] == 'points' && $trace['file'] != '[internal]') {
				$back[] = array('file' => $trace['file'], 'line' => $trace['line']);
			} elseif (is_string($options['format']) && $options['format'] != 'array') {
				$back[] = String::insert($options['format'], array_map(
					function($data) { return is_object($data) ? get_class($data) : $data; },
					$trace
				));
			} elseif (empty($options['format'])) {
				$back[] = $function . ' - ' . $trace['file'] . ', line ' . $trace['line'];
			} else {
				$back[] = $trace;
			}

			if (!empty($scope) && array_intersect_assoc($scope, $trace) == $scope) {
				if (!$options['includeScope']) {
					$back = array_slice($back, 0, count($back) - 1);
				}
				break;
			}
		}

		if ($options['format'] == 'array' || $options['format'] == 'points') {
			return $back;
		}
		return join("\n", $back);
	}

	/**
	 * Returns a parseable string represantation of a variable..
	 *
	 * @param mixed $var The variable to export.
	 * @return string The exported contents.
	 */
	public static function export($var) {
		return var_export($var, true);
	}
}

?>