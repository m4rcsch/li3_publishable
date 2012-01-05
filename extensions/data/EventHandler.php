<?php
/**
 * EventHandler: a lithium php "MagicMethod" Helper
 *
 * @copyright     Copyright 2012, M Schwering
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_publishable\extensions\data;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * The EventHandler is a Bridge between Controller and Models. The goal is to do less controller||
 * http request specific controller coding or writing to much code inside models.
 * Events are everything you do with models inside controllers which is more than storing some data
 * inside a member variable.
 * Example:
 *	Publishing an Article:
 *	First it starts with just a member variable: published, later you want more functionality:
 *	storing more data (a User), flag the article to be reviewed etc..
 * The Event Handler is a single filterable access point for this.
 * Models or behaviors register their possible "events". And controllers just call the EventHandler
 * with model  and possible options as parameters:
 *	EventHandler::<eventName>($model,$options);
 *
 * Example:
 * registering from inside the behavior
 *	EventHandler::register('publish',/*class name, static method and options?/*));
 *
 * inside the controller
 *	EventHandler::publish($article);
 *
 * will return boolean and will change model errors
 */
class EventHandler extends \lithium\core\StaticObject {

	/**
	 * Array of stored events and model relations
	 * @var array
	 */
	protected static $_registered_events = array();

	/**
	 * Magic method enabling gs from a locale.
	 *
	 * {{{
	 *     Locale::language('en_US'); // returns 'en'
	 *     Locale::territory('en_US'); // returns 'US'
	 * }}}
	 * @throws Exeption if Event is not existing
	 * @param string $event
	 * @param array $params
	 * @return boolean
	 */
	public static function __callStatic($event, $params = array()) {
		if (!isset(static::$_registered_events[$event])) {
			throw new BadMethodCallException("Invalid event called `{$event}`.");
		}

		$params += array(null, array());
		list($entity, $options) = $params;

		if (is_null($entity)) {
			throw new InvalidArgumentException("You have to pass at least one parameter.");
		}

		$event = static::$_registered_events[$event];
		//@todo: idea: call rules if there are some on this event!
		return $event($entity,$options);

	}

	/**
	 * @throws InvalidArgumentException if function is not callable
	 * @param string $event_name
	 * @param type $function
	 */
	public static function register($event_name,$function){
		if (!is_callable($function)) {
			throw new InvalidArgumentException("Function is not callable `{$function}`.");
		}
		if (isset(static::$_registered_events[$event_name])) {
			throw new BadMethodCallException("Event is already registered: `{$event_name}`.");
		}
		static::$_registered_events[$event_name] = $function;
	}
}

?>