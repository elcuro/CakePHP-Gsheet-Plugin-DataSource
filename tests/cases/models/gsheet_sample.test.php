<?php

/**
 * Gsheet test case without fixtures
 *
 * @author Duro
 */
App::import('Model', 'Gsheet.GsheetTest');
class  GsheetTestCase extends CakeTestCase{

        /**
         * Disable auto load fixtures
         *
         * @var $this GsheetTestCase
         */
        public $autoFixtures = false;

        /**
         * Tested model
         *
         */
        private $GsheetTest = null;
        
        /**
         * Testing connection to datasource
         *
         * @return void
         */
        public function testConnection() {
                
                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');
                $this->assertTrue(is_object($this->GsheetTest));

        }

        /**
         * Test find('all');
         *
         * @return void
         */
        public function testFindAll() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $result = $this->GsheetTest->find('all');
                $expected = array(
                    array('GsheetTest' => array('id' => 1, 'body' => 'firstbody')),
                    array('GsheetTest' => array('id' => 2, 'body' => 'secondbody')),
                    array('GsheetTest' => array('id' => 3, 'body' => 'thirdbody')),

                );
                $this->assertEqual($result, $expected);

        }

        /**
         * Find by query
         *
         * @return void
         */
        public function testFindByQuery() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $result = $this->GsheetTest->find('all', array('conditions' => array(
                    'query' => 'body = secondbody')));
                $expected = array(
                    array('GsheetTest' => array('id' => 2, 'body' => 'secondbody')),
                );
                $this->assertEqual($result, $expected);

                $result = $this->GsheetTest->find('all', array('conditions' => array(
                    'query' => 'id < 3')));
                $expected = array(
                    array('GsheetTest' => array('id' => 1, 'body' => 'firstbody')),
                    array('GsheetTest' => array('id' => 2, 'body' => 'secondbody'))
                );
                $this->assertEqual($result, $expected);

                $result = $this->GsheetTest->find('all', array('conditions' => array(
                    'query' => 'id > 4')));
                $expected = array();
                $this->assertEqual($result, $expected);

        }

        /**
         * Find first
         *
         * @return void
         */
        public function testFindFirst() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $result = $this->GsheetTest->find('first', array('conditions' => array(
                    'query' => 'body = secondbody')));
                $expected = array('GsheetTest' => array('id' => 2, 'body' => 'secondbody'));
                $this->assertEqual($result, $expected);

        }

        /**
         * Test findByCol_name ! NOT IMPLEMENTED YET
         *
         * @return void
         */
        /*public function testFindBy() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $result = $this->GsheetTest->findByBody('secondbody');
                $expected = array('GsheetTest' => array('id' => 2, 'body' => 'secondbody'));
                $this->assertEqual($result, $expected);

        }*/

        /**
         * Update records
         *
         * @return void
         */
        public function testUpdate() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $result = $this->GsheetTest->find('first', array('conditions' => array(
                    'query' => 'body = secondbody')));
                $expected = array('GsheetTest' => array('id' => 2, 'body' => 'secondbody'));
                if ($this->assertEqual($result, $expected)) {
                        $result['GsheetTest']['body'] = 'secondbodyupdated';
                        $this->GsheetTest->save($result);
                }

                $result = $this->GsheetTest->find('first', array('conditions' => array(
                    'query' => 'body = secondbodyupdated')));
                $expected = array('GsheetTest' => array('id' => 2, 'body' => 'secondbodyupdated'));
                if ($this->assertEqual($result, $expected)) {
                         $result['GsheetTest']['body'] = 'secondbody';
                         $this->GsheetTest->save($result);
                }

        }

        /**
         * Test delete
         *
         * @param array $var
         * @return array
         */
        public function testDelete() {

                ClassRegistry::config('Model', array('ds' => 'testgsheet'));
                $this->GsheetTest =& ClassRegistry::init('GsheetTest');

                $this->GsheetTest->delete(3);

                $result = $this->GsheetTest->find('first', array('conditions' => array(
                    'query' => 'body = thirdbody')));
                $expected = array();
                $this->assertEqual($result, $expected);

                $this->GsheetTest->save(array('id' => 3, 'body' => 'thirdbody'));

        }
        
}
?>
