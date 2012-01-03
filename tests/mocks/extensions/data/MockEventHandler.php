<?php

namespace li3_publishable\tests\mocks\extensions\data;

class MockEventHandler extends \li3_publishable\extensions\data\EventHandler {
	public static function getEvents(){
		return static::$_registered_events;
	}

	public static function clearEvents(){
		static::$_registered_events = array();
	}
}

?>