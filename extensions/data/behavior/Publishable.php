<?php
/**
 * li3_publishable: a lithium php behavior
 *
 * @copyright     Copyright 2012, M Schwering
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_publishable\extensions\data\behavior;

use lithium\data\Connections;
use lithium\util\Set;

/**
 *
 */
class Publishable extends \lithium\core\StaticObject {

	/**
	 * An array of configurations indexed by model class name, for each model to which this class
	 * is bound.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	protected static $_defaults = array(
		'autoIndex' => true,
		'field' => 'published', //maybe later published_on etc... => own Object/model?
		'filters' => array(
			'create' => array(
				'auto' => true, //if true a published flag will be added on create
				'value' => false
			),
			'save' => array(
				'auto' => true,
				'rules' => array(), //if published is true, the rules will be applied on save
			),
			'delete' => array(
				'auto' => true, //@todo implement a regex?... until then we simpy test everytime!
//				'rules' => array(
//					'published' =>
//				)
			)
		)
	);

	protected static $_classes = array(
		'EventHandler' => 'li3_publishable\extensions\data\EventHandler',
		'Set' => 'lithium\util\Set'
	);

	/**
	 * Behavior init setup
	 *
	 * @param object $class
	 * @param array	$config
	 */
	public static function bind($class, array $config = array()) {
		$set = static::$_classes['Set'];
		$defaults = static::$_defaults;
		$config = $set::merge($defaults,$config);

		//If the date fields should be indexed
		if ($config['autoIndex']) {
			static::index(
				$class,
				array($config['field']),
				array()
			);
		}
		//set created filter
		if ($config['filters']['create']['auto']) {
			$class::applyFilter('create', function($self, $params, $chain) use ($class) {
				$params = Publishable::invokeMethod('_filterCreate', array($class, $params));
				return $chain->next($self, $params, $chain);
			});
		}

		//set save filter
		if ($config['filters']['save']['auto']) {
			$class::applyFilter('save', function($self, $params, $chain) use ($class) {
				$params = Publishable::invokeMethod('_filterSave', array($class, $params));
				if (!$params) {
					return false;
				}
				return $chain->next($self, $params, $chain);
			});
		}

		//set delete filter
		if ($config['filters']['delete']['auto']) {
			$class::applyFilter('delete', function($self, $params, $chain) use ($class) {
				if (!Publishable::invokeMethod('_filterDelete', array($class, $params))) {
					return false;
				}
				return $chain->next($self, $params, $chain);
			});
		}

		static::_registerHandlerMethods($class);


		static::$_configurations[$class] = $config;

		return static::$_configurations[$class];
	}

	/**
	 * Sets the EventHandler call
	 * @param type $class
	 * @return void
	 */
	protected static function _registerHandlerMethods($class){
		$handler = static::$_classes['EventHandler'];
		if (!$handler::isEventRegistered('publish')) {
			$handler::register('publish', function($entity,$options) {
				return Publishable::invokeMethod('publish',array($entity, $options));
			});
		}
		if (!$handler::isEventRegistered('depublish')) {
			$handler::register('depublish', function($entity,$options) {
				return Publishable::invokeMethod('depublish',array($entity, $options));
			});
		}
	}

	/**
	 * AutoIndex for MongoDB
	 *
	 * @todo not yet finished
	 * @see li3_geo\extensions\data\behavior\Locatable
	 * @param object $class
	 * @param array $keys
	 * @param array $options
	 */
	public static function index($class, array $keys, array $options = array()) {

		$defaults = array('include' => array(), 'background' => true);
		$options += $defaults;

		$meta = $class::meta();
		$database = Connections::get($meta['connection']);

		list($field) = $keys;

		$field = is_string($field) ? array($field => 1) : $updated;

		if (!$database || !$field) {
			return false;
		}

		if (is_a($database, 'lithium\data\source\MongoDb') && $database->connection) {
			$index = array('name' => 'publishable') + $options['include'] + $field;
			$collection = $meta['source'];
			unset($options['include']);
			$database->connection->{$collection}->ensureIndex($index, $options);
		}
	}

	protected static function _filterCreate($class, $params) {
		$config = static::$_configurations[$class];
		$field = $config['field'];

		$defaults = array(
			$field => $config['filters']['create']['value']
		);
		$params['data'] = Set::merge(Set::expand($defaults), $params['data']);
		return $params;
	}

	protected static function _filterSave($class, $params) {
		$config = static::$_configurations[$class];
		$field = $config['field'];
		$validate = $params['options']['validate'];

		$entity = $params['entity'];
		$data = is_array($params['data']) ? $params['data'] : array();
		$entity->set($data);

		//in case of validation and isset true
		if ($validate && $entity->$field) {
			$params['options']['events'] = 'publish';
			$rules = (is_array($validate)) ? $validate : array();
			$rules += $config['filters']['save']['rules'];
			$params['options']['validate'] = $rules;
			if (empty($params['options']['validate'])) {
				$params['options']['validate'] = true;
			}
		}

		return $params;
	}

	/**
	 * Will check the classes field for delteable
	 * @todo maybe filter validates!
	 *
	 * @param string|object $class
	 * @param array $params
	 */
	protected static function _filterDelete($class, $params) {
		$config = static::$_configurations[$class];
		$field = $config['field'];

		$entity = $params['entity'];
		if ($entity->$field == false) {
			return true;
		}
		$entity->errors(array($field => 'must be unpublished first'));
		return false;
	}

	/**
	 * Will toggle the given `entity` publish field.
	 *
	 * @param Entity $entity
	 * @param array $params
	 * @return boolean
	 */
	public static function publish($entity, $params){
		//$entity = $params['entity'];
		$config = static::$_configurations[$entity->model()];
		$field = $config['field'];

		$defaults = array(
			'data' => null,
			'options' => array()
		);

		$params += $defaults;
		$params['options']['events'] = 'publish';

		$entity->set($params['data']);
		$entity->$field = true;
		$valid = $entity->validates($params['options']);

		return ($valid) ? $entity->save(null,$params['options']) : false;
	}

	/**
	 * Will toggle the given `entity` publish field.
	 *
	 * @param Entity $entity
	 * @param array $params
	 * @return boolean
	 */
	public static function depublish($entity, $params){
		//$entity = $params['entity'];
		$config = static::$_configurations[$entity->model()];
		$field = $config['field'];

		$defaults = array(
			'data' => null,
			'options' => array()
		);

		$params += $defaults;
		$params['options']['events'] = 'depublish';

		$entity->$field = false;
		$valid = $entity->validates($params['options']);
		if (!$valid) {
			$entity->$field = true;
		}

		return ($valid) ? $entity->save($params['data'],$params['options']) : false;
	}

//	/**
//	 *
//	 * @param string|object $class
//	 * @param array $options
//	 */
//	protected static function _formatUpdated($class, $params) {
//		$config = static::$_configurations[$class];
//		$config = $config['updated'];
//
//		$entity = $params['entity'];
//		//only if Entity exists
//		if ($entity->exists()) {
//			if (static::$_defaults_key == 'mongoDb') {
//				$datetime = new \MongoDate();
//			}
//			else {
//				$datetime = date($config['format']);
//			}
//			$params['data'][$config['field']] = $datetime;
//		}
//
//		return $params;
//	}
//
//	/**
//	 *
//	 * @param string|object $class
//	 * @param array $options
//	 */
//	protected static function _formatCreated($class, $options) {
//		$staticConfig = static::$_configurations[$class];
//		if (static::$_defaults_key == 'mongoDb') {
//			$datetime = new \MongoDate();
//			$config = $staticConfig['created'];
//			$options['data'][$config['field']] = $datetime;
//			$config = $staticConfig['updated'];
//			$options['data'][$config['field']] = $datetime;
//		} else {
//			$time = time();
//			$config = $staticConfig['created'];
//			$datetime = date($config['format'],$time);
//			$options['data'][$config['field']] = $datetime;
//			$config = $staticConfig['updated'];
//			$datetime = date($config['format'],$time);
//			$options['data'][$config['field']] = $datetime;
//		}
//		return $options;
//	}

}

?>