<?php
/**
* Gsheet test model,
* used for testing purpose
*
* @author Juraj Jancuska <jjancuska@gmail.com>
* @copyright (c) 2010 Juraj Jancuska
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class GsheetTest extends GsheetAppModel {

        /**
        * Model name
        *
        * @var string $useDbConfig
        */
	public $name = 'GsheetTest';

        /**
         * Specify gsheet datasource
         *
         * @var string $useDbConfig
         */
        public $useDbConfig = 'Gsheet';

        /**
         * Name of the SHEET in google spreadsheet defined in database.php
         *
         * @var string $useTable
         */
        public $useTable = 'Sheet1';
        


}
?>