<?php
/**
 * Simple Google SpreadSheet CakePHP Datasource with base CRUD
 *
 * !!! Require Zend GData on http://framework.zend.com/download/gdata
 * For include it in bootstrap.php
 * set_include_path(get_include_path() . PATH_SEPARATOR . 'path\to\app\vendors\ZendGdata\library');
 *
 * Google spreadsheet as database and sheet as table
 *
 * @author Juraj Jancuska <jjancuska@gmail.com>
 * @copyright (c) 2010 Juraj Jancuska
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class GsheetSource extends DataSource {

        /**
         * Description of datasource
         *
         * @var string
         */
        public $description = 'Simple Google Spreadsheet Datasource';

        /**
         * Zend Gdata Spreadsheet service
         *
         * @var mixed
         */
        private $_service;

        /**
         * Load base config from database.php
         * Athentificate google user via Zend_Gdata_ClientLogin
         *
         * @param array $config
         */
        public function __construct($config) {

                if (!is_array($config)) {
                        $config = array();
                }

                // defaults, database config key required by parent::listSources()
                $defaults = array(
                    'user' => '',
                    'psw' => '',
                    'spreadsheet' => '',
                    'database' => $config['spreadsheet']
                );
                
                $config = array_merge($defaults, $config);

                // import Zend
                //App::import(array('type' => 'File', 'name' => 'Zend_Loader', 'file' => 'Zend'.DS.'Loader.php'));
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass('Zend_Gdata');
                Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
                Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');

                // login to google account and create service
                try {
                        $service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
                        $client = Zend_Gdata_ClientLogin::getHttpClient($config['user'], $config['psw'], $service);
                        $this->_service = new Zend_Gdata_Spreadsheets($client);
                } catch (Zend_Gdata_App_Exception $e) {
                        $this->_zendError($e);
                }

                parent::__construct($config);
                
        }

        /**
         * List all possible sheets in current spreadsheet
         *
         * @return array One dimensional array of all possible sheets
         */
        public function listSources() {

                $cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}

                $result = array();

                $query = new Zend_Gdata_Spreadsheets_DocumentQuery();
                $query->setSpreadsheetKey($this->_getSpreadSheetId($this->config['spreadsheet']));
                
                $feed = $this->_service->getWorksheetFeed($query);

                if ($feed) {
                        foreach($feed as $sheet) {
                                $result[] = $sheet->title->text;
                        }
                }
                
                parent::listSources($result);
                return $result;

        }

        /**
         * Return function name for count results, e.g in mysql COUNT()
         * Called by parent DataSource class with create and save method
         *
         * @param AppModel $model
         * @param string $fnc
         * @param array $params
         * @return string Count function name
         */
        public function calculate(&$model, $fnc = 'count', $params = array()) {

                return $fnc;

        }

        /**
         * "C" from CRUD
         * If id is not specified, create one
         * If row is created, setting $model->id with new row id
         *
         * @param AppModel $model
         * @param array $fields Field names
         * @param array $values Field values
         * @return boolean
         */
        public function create(&$model, $fields, $values) {

                $rowData = array_combine($fields, $values);
                
                // if primary key field not exists, create one
                if (!isset($rowData[$model->primaryKey])) {
                        $rowData[$model->primaryKey] = "id" . md5(microtime(true));
                }

                $createdRow = $this->_service->insertRow(
                        $rowData,
                        $this->_getSpreadSheetId($this->config['spreadsheet']),
                        $this->_getWorksheetId($model));

                if ($createdRow) {
                        $customEntry = $createdRow->getCustomByName($model->primaryKey);
                        $model->id = $customEntry->getText();
                        return TRUE;
                }

                return FALSE;

        }

        /**
         * "R" from CRUD
         * Return results by query, or count of results
         * Query in zend format, e.g. 'name = john and surname = smith'
         * Returned fieldnames are fields from sheet first row, lowercased and without spaces!
         *
         * @param AppModel $model
         * @param array $queryData All quey conditions
         * @return array Results
         */
        public function read(&$model, $queryData = array()) {

                $query = $this->_getZendListQueryObject($model);

                // Ugly fake for find called by save and delete method
                if (isset($queryData['conditions'][$model->name.'.'.$model->primaryKey])) {
                        $queryData['conditions']['query'] = $model->primaryKey.' = '.
                                $queryData['conditions'][$model->name.'.'.$model->primaryKey];
                }

                // setting spreadsheet query e.g. 'name > 1'
                if (isset($queryData['conditions']['query'])) {
                        $query->setSpreadsheetQuery($queryData['conditions']['query']);
                }

                $results = array();

                $listFeed = $this->_service->getListFeed($query);
                foreach($listFeed->entries as $entry) {
                        $rowData = $entry->getCustom();
                        foreach($rowData as $customEntry) {
                                $data[$customEntry->getColumnName()] = $customEntry->getText();
                        }
                        $results[][$model->name] = $data;
                }

                // if find('count',...) then return count of results
                if ($queryData['fields'] == 'count') {
                        $results = array(0 => array($model->alias => array('count' => count($results))));
                }

                return $results;

        }

        /**
         * "U" from CRUD
         * Update row in sheet, id field is required to update
         *
         * @param AppModel $model
         * @param array $fields Fields names
         * @param array $values Field values
         * @return boolean
         */
        public function update($model, $fields, $values) {

                $rowData = array_combine($fields, $values);

                $query = $this->_getZendListQueryObject($model);

                $query->setSpreadsheetQuery(
                        $model->primaryKey.' = '.$rowData[$model->primaryKey]);

                $listFeed = $this->_service->getListFeed($query);

                try {
                        $ret = $this->_service->updateRow($listFeed->entries[0], $rowData);
                } catch (Zend_Gdata_App_Exception $e) {
                        echo $e->getMessage();
                        return FALSE;
                }

                return TRUE;

        }

        /**
         * "D" from CRUD
         * delete row with id in $model->id
         *
         * @param AppModel $model
         * @return boolean
         */
        public function delete($model) {

                $query = $this->_getZendListQueryObject($model);
                $query->setSpreadsheetQuery($model->primaryKey.' = '.$model->id);
                $listFeed = $this->_service->getListFeed($query);

                if ($this->_service->deleteRow($listFeed->entries[0])) {
                        return TRUE;
                }
                return FALSE;

        }

        /**
         * Describe model fields
         * I don't find any zend function to fetch only field names,
         * so getting all rows and fetch field names from first result
         *
         * @param AppModel $model
         * @return array
         */
        public function describe(&$model) {

                $fullTableName = $model->tablePrefix . $model->table;

                $cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}

                $query = $this->_getZendListQueryObject($model);

                $listFeed = $this->_service->getListFeed($query);

                $rowData = $listFeed->entries[0]->getCustom();
                foreach($rowData as $customEntry) {
                        $fieldName = $customEntry->getColumnName();
                        if (($fieldName == 'id') || ($fieldName == $model->primaryKey)) {
                                $fields[$fieldName] = array(
                                    'type' => 'string',
                                    'null' => false,
                                    'key' => 'primary',
                                    'length' => 250
                                );
                        } else {
                                $fields[$fieldName] = array('type' => 'text');
                        }
                }
                $this->__cacheDescription($fullTableName, $fields);
                return $fields;

        }

        /**
         * Return Zend_Gdata_Spreadsheets_ListQuery object,
         * and set spreadheet key and worksheet id
         *
         * @param AppModel $model
         * @return object
         */
        function _getZendListQueryObject(&$model) {

                $query = new Zend_Gdata_Spreadsheets_ListQuery();
                $query->setSpreadsheetKey($this->_getSpreadSheetId($this->config['spreadsheet']));
                $query->setWorksheetId($this->_getWorksheetId($model));

                return $query;

        }
        
        /**
         * Get google docs spreadsheet id, if spreadsheet not extists,
         * handle cake missingDatabase error
         *
         * @param string $name Name of spreadsheet in google docs
         * @return string String with spreadsheet name
         */
        private function _getSpreadSheetId($name) {
                
                $sFeed = $this->_service->getSpreadsheetFeed();
                foreach($sFeed as $spreadsheet) {
                        if ($spreadsheet->title->text == $name) {
                                return array_pop(explode('/', $spreadsheet->id->text));
                        }
                }
                $this->cakeError('error', array(
                    'name' => __('Missing spreadsheet', true),
                    'message' => __('Check your docs.google.com for missing', true).' "'.$name.'" '.__('spreadsheet', true)
                ));
                
        }

        /**
         * Get google doc sheet id, if sheet not exists,
         * handle cake missingTable error
         *
         * @param AppModel $model
         * @return string Sheet id
         */
        private function _getWorksheetId(&$model) {

                $query = new Zend_Gdata_Spreadsheets_DocumentQuery();
                $query->setSpreadsheetKey($this->_getSpreadSheetId($this->config['spreadsheet']));

                // fetch all availale sheets
                $feed = $this->_service->getWorksheetFeed($query);

                foreach($feed as $sheet) {
                        if (($sheet->title->text == $model->name) || ($sheet->title->text == $model->useTable)) {
                                return array_pop(explode('/', $sheet->id->text));
                        }
                }

		$this->cakeError('error', array(
                    'message' => '"'.$model->useTable.'"'. __('missing in docs.google spreadsheet', true).
                        ' "'.$this->config['spreadsheet'].'"',
                    'name' => __('Missing sheet', true)
                ));
                
        }

        /**
         * Handle zend error
         *
         * @param object $e Zend exception
         * @return void
         */
        private function _zendError(&$e) {

                $this->cakeError('error', array(
                    'message' => $e->getMessage(),
                    'name' => __('Google API error', true)
                ));

        }

}
?>
