<?php
/**
 * li3_dateable: a lithium php behavior
 *
 * @copyright     Copyright 2011, weluse GmbH (http://weluse.de)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_publishable\tests\cases\extensions\data;

use li3_publishable\tests\mocks\extensions\data\MockEventHandler;

//use lithium\data\Connections;
//use lithium\data\model\Query;
//use lithium\data\entity\Record;
//use lithium\tests\mocks\data\model\MockDatabase;

class EventHandlerTest extends \lithium\test\Unit {

	protected $_configs = array();

	protected $_handler = null;

	public function setUp() {
		$this->_handler = new MockEventHandler();
//		$this->db = new MockDatabase();
//		$this->_configs = Connections::config();
//
//		Connections::reset();
//		Connections::config(array('mock-database-connection' => array(
//			'object' => &$this->db,
//			'adapter' => 'MockDatabase'
//		)));
	}

	public function tearDown() {
		$instance = $this->_handler;
		$instance::clearEvents();
//		Connections::reset();
//		Connections::config($this->_configs);
	}

	public function testHandlerArray(){
		$instance = $this->_handler;
		$this->assertIdentical(array(), $instance::getEvents());
	}

	public function testRegister(){
		$instance = $this->_handler;
		$func = function($param) {
			return $param;
		};
		$instance::register('foo',$func);
		$events = $instance::getEvents();
		$this->assertIdentical($func, $events['foo']);

		$instance::register('bar',$func);
		$events = $instance::getEvents();
		$this->assertIdentical($func, $events['bar']);
	}

	public function testAlreadyRegistered(){
		$instance = $this->_handler;
		$func = function($param) {
			return $param;
		};
		$instance::register('foo',$func);
		$events = $instance::getEvents();
		$this->assertIdentical($func, $events['foo']);
		$message = 'Event is already registered: `foo`.';
		$this->expectException($message);
		$instance::register('foo',$func);
	}

	public function testRegisterNotCallable(){
		$instance = $this->_handler;
		$func = 'baz';

		$message = 'Function is not callable `baz`.';
		$this->expectException($message);
		$instance::register('foo',$func);
	}

	public function testCallStaticNotExistand(){
		$instance = $this->_handler;

		$message = 'Invalid event called `foo`.';
		$this->expectException($message);
		$instance::foo(null);
	}

	public function testCallStaticWithNoParams(){
		$instance = $this->_handler;
		$func = function($param) {
			return $param;
		};

		$instance::register('foo',$func);

		$message = 'You have to pass at least one parameter.';
		$this->expectException($message);

		$result = $instance::foo();
	}

	public function testCallStaticWithParams(){
		$instance = $this->_handler;
		$func = function($param) {
			return $param = $param+1;
		};

		$instance::register('foo',$func);

		$result = $instance::foo(1);

		$this->assertIdentical(2, $result);
	}
}

?>