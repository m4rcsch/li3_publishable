<?php
/**
 * li3_publishable: a lithium php behavior
 *
 * @copyright     Copyright 2012, M Schwering
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_publishable\tests\cases\extensions\data\behavior;


use lithium\data\Connections;
//use lithium\data\model\Query;
//use lithium\data\entity\Record;
use lithium\tests\mocks\data\model\MockDatabase;

//use li3_publishable\tests\mocks\extensions\data\behavior\MockPublishable;
use li3_publishable\tests\mocks\data\model\MockDatabaseCoffee;

//for integration tests?!
use li3_publishable\extensions\data\EventHandler;

class PublishableTest extends \lithium\test\Unit {

	protected $_configs = array();

	public function setUp() {
		$this->db = new MockDatabase();
		$this->_configs = Connections::config();

		Connections::reset();
		Connections::config(array('mock-database-connection' => array(
			'object' => &$this->db,
			'adapter' => 'MockDatabase'
		)));
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
	}

	public function testCreateAutoFilter(){
		$model = MockDatabaseCoffee::create(array('id'=>12));//,array("exists" => true)

		$data = $model->data();
		//die('tot');
		$this->assertTrue(isset($data['published']));
		$this->assertFalse($model->published);
	}

	public function testSaveAutoFilter(){
		$model = MockDatabaseCoffee::create(array(
			'id' => 12,
			'published' => false,
			'email' => 'foo@bar.de'
		));//,array("exists" => true)
		$this->assertTrue($model->save()); //null,array('validate' => false))
		$this->assertTrue($model->save(array('published' => true)));

		$options = array('validate' => array(
			'email' => array(
	          array('notEmpty', 'message' => 'Email is empty.'),
	          array('email', 'message' => 'Email is not valid.')
	      )
		));
		$this->assertTrue($model->save(null,$options));
		var_dump($model->errors());
	}

	public function testDeleteFilter(){
		$model = MockDatabaseCoffee::create(array(
			'id'=>12,
			'published' => true
		));//,array("exists" => true)

		$data = $model->data();
		//die('tot');
		$this->assertTrue(isset($data['published']));
		$this->assertFalse($model->delete());
		$model->published = 'foo';
		$this->assertFalse($model->delete());
		$model->published = false;
		$this->assertTrue($model->delete());
	}

	//integration test?
	//better via direct mocks?
	public function testPublish() {

		$model = MockDatabaseCoffee::create(array('id'=>13,'title' => 'foo'));

		//could be mocked but must be done inside a MockPublishable aswell
		$this->assertTrue(EventHandler::publish($model));
		$this->assertTrue($model->published);
	}

	public function testDepublish() {
		$model = MockDatabaseCoffee::create(array('id'=>13,'title' => 'foo','published' => true));

		//could be mocked but must be done inside a MockPublishable aswell
		$this->assertTrue(EventHandler::publish($model));
		$this->assertTrue($model->published);

		$this->assertTrue(EventHandler::depublish($model));
		$this->assertFalse($model->published);
	}
}

?>