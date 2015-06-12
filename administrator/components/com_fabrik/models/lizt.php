<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Storage\MySql as Storage;
use Joomla\String\String;
use \JText as JText;
use \FText as FText;
use \stdClass as stdClass;
use \JHTML as JHTML;
use Fabrik\Helpers\Worker;
use \JFactory as JFactory;
use Fabrik\Helpers\ArrayHelper;
use \RuntimeException as RuntimeException;
use \Joomla\Registry\Registry as JRegistry;
use \JEventDispatcher as JEventDispatcher;
use \JDatabaseQuery as JDatabaseQuery;

use Joomla\Registry\Registry;
use Fabrik\Helpers\LayoutFile;
use Fabrik\Helpers\Pagination;
use \JComponentHelper as JComponentHelper;
use \JFolder as JFolder;
use \FabrikHelperHTML as FabrikHelperHTML;
use \JFilterInput as JFilterInput;
use \JProfiler as JProfiler;
use \Exception as Exception;
use \FabrikString as FabrikString;
use \JUri as JUri;
use \JRoute as JRoute;
use \JSession as JSession;
use \JFile as JFile;
use \JApplication as JApplication;

interface ModelFormLiztInterface
{

}

/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Lizt extends View implements ModelFormLiztInterface
{
	protected $name = 'list';
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Currently loaded list row
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Plugin type
	 *
	 * @var string
	 * @deprecated ?
	 */
	protected $pluginType = 'List';

	/**
	 * List id
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * package id
	 *
	 * @var int
	 */
	public $packageId = null;

	/**
	 * Lists connection object
	 *
	 * @var object
	 */
	protected $connection = null;

	/**
	 * List item
	 *
	 * @var JTable
	 */
	protected $table = null;

	/**
	 * List's form model
	 *
	 * @var FabrikFEModelForm
	 */
	protected $formModel = null;

	/**
	 * Column calculations
	 *
	 * @var array
	 */
	protected $runCalculations = array();

	/**
	 * List output format - set to rss to collect correct element data within function getData()
	 *
	 * @var string
	 */
	protected $outputFormat = 'html';

	/**
	 * Is rendered as a J content plugin
	 *
	 * @var bool
	 */
	public $isMambot = false;

	/**
	 * Empty data message
	 *
	 * @var string
	 */
	protected $emptyMsg = '';
	/**
	 * Contain access rights
	 *
	 * @var object
	 */
	protected $access = null;

	/**
	 * Id of the last inserted record (or if updated the last record updated)
	 *
	 * @var int
	 */
	public $lastInsertId = null;

	/**
	 * Database fields
	 *
	 * @var array
	 */
	protected $dbFields = null;

	/**
	 * Used when making custom links to determine if we need to append the rowid to the url
	 *
	 * @var bool
	 */
	protected $rowIdentifierAdded = false;

	/**
	 * Is ajax used
	 *
	 * @var bool
	 */
	public $ajax = null;

	/**
	 * Join sql
	 *
	 * @var string
	 */
	protected $joinsSQL = null;

	/**
	 * Order by column names
	 *
	 * @var array
	 */
	protected $orderByFields = null;

	protected $joinsToThisKey = null;

	protected $linksToThisKey = null;

	/**
	 * Used to determine which filter action to use.
	 * If a filter is a range then override lists setting with onsubmit
	 *
	 * @var string
	 */
	protected $real_filter_action = null;

	/**
	 * Internally used when using parseMessageForRowHolder();
	 *
	 * @var array
	 */
	protected $aRow = null;

	/**
	 * Rows to delete
	 *
	 * @var array
	 */
	public $rowsToDelete = null;

	/**
	 * Original list data BEFORE form saved - used to ensure uneditable data is stored
	 *
	 * @var array
	 */
	protected $origData = null;

	/**
	 * Set to true to load records STARTING from a random id (used in the getPageNav func)
	 *
	 * @var bool
	 */
	public $randomRecords = false;

	/**
	 * List data
	 *
	 * @var array
	 */
	protected $data = null;

	/**
	 * Template name
	 *
	 * @var string
	 */
	protected $tmpl = null;

	/**
	 * Pagination
	 *
	 * @var Pagination
	 */
	protected $nav = null;

	/**
	 * List field names
	 *
	 * @var array
	 */
	protected $fields = null;

	/**
	 * Prefilters
	 *
	 * @var array
	 */
	protected $prefilters = null;

	/**
	 * Filters
	 *
	 * @var array
	 */
	public $filters = null;

	/**
	 * Can rows be selected
	 *
	 * @var bool
	 */
	protected $canSelectRows = null;

	/**
	 * As fields - used in query to build list data
	 *
	 * @var array
	 */
	public $asfields = null;

	/**
	 * Has an element, which is the db key, already been added to the list of fields to select
	 *
	 * @var bool
	 */
	public $temp_db_key_addded = false;

	/**
	 * Has the group by statement been added to the list query
	 *
	 * @var bool
	 */
	protected $group_by_added = false;

	/**
	 * List of where conditions added by list plugins
	 *
	 * @var array
	 */
	protected $pluginQueryWhere = array();

	/**
	 * List of group by statements added by list plugins
	 *
	 * @var array
	 */
	public $pluginQueryGroupBy = array();

	/**
	 * Used in views for rendering
	 *
	 * @var array
	 */
	public $groupTemplates = array();

	/**
	 * Is the list a view
	 *
	 * @var bool
	 */
	protected $isView = null;

	/**
	 * Index objects
	 *
	 * @var array
	 */
	protected $indexes = null;

	/**
	 * Previously submitted advanced search data
	 *
	 * @var array
	 */
	protected $advancedSearchRows = null;

	/**
	 * List action url
	 *
	 * @var string
	 */
	protected $tableAction = null;

	/**
	 * Doing CSV import
	 *
	 * @var bool
	 */
	public $importingCSV = false;

	/**
	 * CSV in overwrite mode
	 *
	 * @var bool
	 */
	public $csvOverwriting = false;

	/**
	 * Element names to encrypt
	 *
	 * @var array
	 */
	public $encrypt = array();

	/**
	 * Which record number to start showing from
	 *
	 * @var int
	 */
	public $limitStart = null;

	/**
	 * Number of records per page
	 *
	 * @var int
	 */
	public $limitLength = null;

	/**
	 * List rows
	 *
	 * @var array
	 */
	protected $rows = null;

	/**
	 * Should a heading be added for action buttons (returns true if at least one row has buttons)
	 *
	 * @deprecated (since 3.0.7)
	 *
	 * @var bool
	 */
	protected $actionHeading = false;

	/**
	 * List of column data - used for filters
	 *
	 * @var array
	 */
	protected $columnData = array();

	/**
	 * Render context used for defining custom css variable for tmpl rendering e.g. module_1
	 *
	 * @var string
	 */
	protected $renderContext = '';

	/**
	 * The max number of buttons that is shown in a row
	 *
	 * @var int
	 */
	protected $rowActionCount = 0;

	/**
	 * Do any of the elements have a required filter, only used through method of same name
	 *
	 * @var bool
	 */
	protected $hasRequiredElementFilters = null;

	/**
	 * Elements which have a required filter
	 *
	 * @var array
	 */
	protected $elementsWithRequiredFilters = array();

	/**
	 * Force formatData() to format all elements, uses formatAll() accessor method
	 *
	 * @var bool
	 */
	protected $format_all = false;

	/**
	 * Array of order by elements
	 *
	 * @var array
	 */
	public $orderEls = array();

	/**
	 * Cached order by statement
	 *
	 * @since 3.0.7
	 *
	 * @var JQueryBuilder
	 */
	public $orderBy = null;

	/**
	 * Tabs field to use
	 *
	 * @since 3.1
	 *
	 * @var string
	 */
	protected $tabsField = null;

	/**
	 * Tabs to display
	 *
	 * @since 3.1
	 *
	 * @var array
	 */
	protected $tabs = null;

	/**
	 * Filter js code
	 *
	 * @var string
	 */
	public $filterJs = null;

	protected $selectedOrderFields = null;
	protected $mainQuery = null;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry $state The model state.
	 *
	 * @since   12.1
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);

		$usersConfig     = JComponentHelper::getParams('com_fabrik');
		$input           = $this->app->input;
		$this->packageId = (int) $input->getInt('packageId', $usersConfig->get('packageId'));
		$this->access    = new stdClass;
	}

	/**
	 * Method to get the confirm list delete form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getConfirmDeleteForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.confirmdelete', 'confirmlistdelete', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function publish($ids = array())
	{
		return $this->_publish($ids, true);
	}

	public function unpublish($ids = array())
	{
		return $this->_publish($ids, false);
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array &$pks  A list of the primary keys to change.
	 * @param   int   $value The value of the published state.
	 *
	 * @return  boolean    True on success.
	 *
	 * @since    1.6
	 */
	protected function _publish(&$pks, $value = 1)
	{
		// Initialise variables.
		$dispatcher = JEventDispatcher::getInstance();
		$user       = JFactory::getUser();
		$table      = $this->getTable();
		$pks        = (array) $pks;

		// Include the content plugins for the change of state event.
		JPluginHelper::importPlugin('content');

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if (!$this->canEditState($table))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$this->app->enqueueMessage(FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'), 'warning');
				}
			}
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		$context = $this->option . '.' . $this->name;

		// Trigger the onContentChangeState event.
		$result = $dispatcher->trigger($this->event_change_state, array($context, $pks, $value));

		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		return true;
	}

	/**
	 * Build and/or dropdown list
	 *
	 * @param   bool   $addSlashes to reutrn data
	 * @param   string $name       input name
	 *
	 * @return string dropdown
	 */

	protected function getFilterJoinDd($addSlashes = true, $name = 'join')
	{
		$aConditions   = array();
		$aConditions[] = JHTML::_('select.option', 'AND');
		$aConditions[] = JHTML::_('select.option', 'OR');
		$attribs       = 'class="inputbox input-small" size="1"';
		$dd            = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, $attribs, 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Build prefilter dropdown
	 *
	 * @param   bool   $addSlashes add slashes to reutrn data
	 * @param   string $name       name of the drop down
	 * @param   int    $mode       states what values get put into drop down
	 *
	 * @return string dropdown
	 */

	protected function getFilterConditionDd($addSlashes = true, $name = 'conditions', $mode = 1)
	{
		$aConditions = array();

		switch ($mode)
		{
			case 1: /* used for search filter */
				$aConditions[] = JHTML::_('select.option', '<>', 'NOT EQUALS');
				$aConditions[] = JHTML::_('select.option', '=', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'like', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'like', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'like', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				break;
			case 2: /* used for prefilter */
				$aConditions[] = JHTML::_('select.option', 'equals', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'notequals', 'NOT EQUAL TO');
				$aConditions[] = JHTML::_('select.option', 'begins', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'contains', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'ends', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', 'IS NULL', 'IS NULL');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', 'in', 'IN');
				$aConditions[] = JHTML::_('select.option', 'not_in', 'NOT IN');
				$aConditions[] = JHTML::_('select.option', 'exists', 'EXISTS');
				$aConditions[] = JHTML::_('select.option', 'earlierthisyear', FText::_('COM_FABRIK_EARLIER_THIS_YEAR'));
				$aConditions[] = JHTML::_('select.option', 'laterthisyear', FText::_('COM_FABRIK_LATER_THIS_YEAR'));

				$aConditions[] = JHTML::_('select.option', 'yesterday', FText::_('COM_FABRIK_YESTERDAY'));
				$aConditions[] = JHTML::_('select.option', 'today', FText::_('COM_FABRIK_TODAY'));
				$aConditions[] = JHTML::_('select.option', 'tomorrow', FText::_('COM_FABRIK_TOMORROW'));
				$aConditions[] = JHTML::_('select.option', 'thismonth', FText::_('COM_FABRIK_THIS_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'lastmonth', FText::_('COM_FABRIK_LAST_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'nextmonth', FText::_('COM_FABRIK_NEXT_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'birthday', FText::_('COM_FABRIK_BIRTHDAY_TODAY'));

				break;
		}

		$dd = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, 'class="inputbox input-small"  size="1" ', 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Get connection model
	 *
	 * @return  object  connect model
	 */

	protected function getCnn()
	{
		$item      = $this->getItem();
		$connModel = new Connection;
		$connModel->set('id', $item->get('list.connection_id'));
		$connModel->getItem($item->get('list.connection_id'));

		return $connModel;
	}

	/**
	 * Create the js that manages the edit list view page
	 *
	 * @return  string  js
	 */

	public function getJs()
	{
		$item      = $this->getItem();
		$connModel = $this->getCnn();
		JText::script('COM_FABRIK_OPTIONS');
		JText::script('COM_FABRIK_JOIN');
		JText::script('COM_FABRIK_FIELD');
		JText::script('COM_FABRIK_CONDITION');
		JText::script('COM_FABRIK_VALUE');
		JText::script('COM_FABRIK_EVAL');
		JText::script('COM_FABRIK_APPLY_FILTER_TO');
		JText::script('COM_FABRIK_DELETE');
		JText::script('JYES');
		JText::script('JNO');
		JText::script('COM_FABRIK_QUERY');
		JTEXT::script('COM_FABRIK_NO_QUOTES');
		JText::script('COM_FABRIK_TEXT');
		JText::script('COM_FABRIK_TYPE');
		JText::script('COM_FABRIK_PLEASE_SELECT');
		JText::script('COM_FABRIK_GROUPED');
		JText::script('COM_FABRIK_TO');
		JText::script('COM_FABRIK_FROM');
		JText::script('COM_FABRIK_JOIN_TYPE');
		JText::script('COM_FABRIK_FROM_COLUMN');
		JText::script('COM_FABRIK_TO_COLUMN');
		JText::script('COM_FABRIK_REPEAT_GROUP_BUTTON_LABEL');
		JText::script('COM_FABRIK_PUBLISHED');

		$joinTypeOpts      = array();
		$joinTypeOpts[]    = array('inner', FText::_('INNER JOIN'));
		$joinTypeOpts[]    = array('left', FText::_('LEFT JOIN'));
		$joinTypeOpts[]    = array('right', FText::_('RIGHT JOIN'));
		$activeTableOpts[] = "";
		$activeTableOpts[] = $item->get('list.db_table_name');

		$joins = $this->getJoins();

		for ($i = 0; $i < count($joins); $i++)
		{
			$j                 = $joins[$i];
			$activeTableOpts[] = $j->table_join;
			$activeTableOpts[] = $j->join_from_table;
		}

		$activeTableOpts       = array_unique($activeTableOpts);
		$activeTableOpts       = array_values($activeTableOpts);
		$opts                  = new stdClass;
		$opts->joinOpts        = $joinTypeOpts;
		$opts->tableOpts       = $connModel->getThisTables(true);
		$opts->activetableOpts = $activeTableOpts;
		$opts                  = json_encode($opts);

		$filterOpts               = new stdClass;
		$filterOpts->filterJoinDd = $this->getFilterJoinDd(false, 'jform[params][filter-join][]');
		$filterOpts->filterCondDd = $this->getFilterConditionDd(false, 'jform[params][filter-conditions][]', 2);
		$filterOpts->filterAccess = JHtml::_('access.level', 'jform[params][filter-access][]', $item->get('list.access'), 'class="input-small"');
		$filterOpts->filterAccess = str_replace(array("\n", "\r"), '', $filterOpts->filterAccess);
		$filterOpts               = json_encode($filterOpts);

		$attribs      = 'class="inputbox input-small" size="1"';
		$filterFields = $this->getElementList('jform[params][filter-fields][]', '', false, false, true, 'name', $attribs);
		$filterFields = addslashes(str_replace(array("\n", "\r"), '', $filterFields));

		$plugins = json_encode($this->getPlugins());

		$js   = array();
		$js[] = "window.addEvent('domready', function () {";
		$js[] = "Fabrik.controller = new PluginManager($plugins, '" . $item->get('view') . "', 'list');";

		$js[] = "oAdminTable = new ListForm($opts);";
		$js[] = "oAdminTable.watchJoins();";

		for ($i = 0; $i < count($joins); $i++)
		{
			$joinGroupParams = json_decode($joins[$i]->params);
			$j               = $joins[$i];
			$joinFormFields  = json_encode($j->joinFormFields);
			$joinToFields    = json_encode($j->joinToFields);
			$repeat          = $joinGroupParams->repeat_group_button == 1 ? 1 : 0;
			$js[]            = "	oAdminTable.addJoin('{$j->group_id}','{$j->id}','{$j->join_type}','{$j->table_join}',"
				. "'{$j->table_key}','{$j->table_join_key}','{$j->join_from_table}', $joinFormFields, $joinToFields, $repeat);";
		}

		$js[]        = "oAdminFilters = new adminFilters('filterContainer', '$filterFields', $filterOpts);";
		$form        = $this->getForm();
		$filterJoins = $form->getValue('params.filter-join');

		// Force to arrays as single prefilters imported from 2.x will be stored as string values
		$filterFields     = (array) $form->getValue('params.filter-fields');
		$filterConditions = (array) $form->getValue('params.filter-conditions');
		$filterEvals      = (array) $form->getValue('params.filter-eval');
		$filterValues     = (array) $form->getValue('params.filter-value');
		$filterAccess     = (array) $form->getValue('params.filter-access');
		$aGrouped         = (array) $form->getValue('params.filter-grouped');

		for ($i = 0; $i < count($filterFields); $i++)
		{
			$selJoin = ArrayHelper::getValue($filterJoins, $i, 'and');

			// 2.0 upgraded sites had quoted filter names
			$selFilter    = str_replace('`', '', $filterFields[$i]);
			$grouped      = ArrayHelper::getValue($aGrouped, $i, 0);
			$selCondition = $filterConditions[$i];
			$filerEval    = (int) ArrayHelper::getValue($filterEvals, $i, '1');

			if ($selCondition == '&gt;')
			{
				$selCondition = '>';
			}

			if ($selCondition == '&lt;')
			{
				$selCondition = '<';
			}

			$selValue  = ArrayHelper::getValue($filterValues, $i, '');
			$selAccess = $filterAccess[$i];

			// Allow for multiline js variables ?
			$selValue = htmlspecialchars_decode($selValue, ENT_QUOTES);
			$selValue = json_encode($selValue);

			// No longer check for empty $selFilter as EXISTS prefilter condition doesn't require element to be selected
			$js[] = "\toAdminFilters.addFilterOption('$selJoin', '$selFilter', '$selCondition', $selValue, '$selAccess', $filerEval, '$grouped');\n";
		}

		$js[] = "});";

		return implode("\n", $js);
	}

	/**
	 * Set the form model
	 *
	 * @param   object $model form model
	 *
	 * @return  void
	 */

	public function setFormModel($model)
	{
		$this->formModel = $model;
	}

	/**
	 * Load up the front end list model so we can use some of its methods
	 *
	 * @throws Error
	 *
	 * @return  object  front end list model
	 */
	public function getFEModel()
	{
		throw new Error ('list admin model trying to load front end model - no no - its now all in admin model');
	}

	/**
	 * Validate the form
	 *
	 * @param   array $data The data to validate.
	 *
	 * @return mixed  false or data
	 */
	public function validate($data)
	{
		//$params = $data['params'];
		if (!parent::validate($data))
		{
			return false;
		}

		if (empty($data['_database_name']) && ArrayHelper::getValue($data, 'db_table_name') == '')
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_SELECT_DB_OR_ENTER_NAME'));

			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		//$data['params'] = $params;
		return $data;
	}

	/**
	 * Save the form
	 *
	 * @param   array $post The jform part of the request data pertaining to the list.
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function save($post)
	{
		$post = $this->prepareSave($post, 'list');
		parent::save($post);
	}

	/**
	 * Check to see if a table exists
	 *
	 * @param   string $tableName name of table (overwrites form_id val to test)
	 *
	 * @deprecated
	 *
	 * @return  bool    false if no table found true if table found
	 */

	public function databaseTableExists($tableName = null)
	{
		if (!is_null($tableName))
		{
			$this->getStorage()->table = $tableName;
		}

		return $this->getStorage()->tableExists($tableName);
	}

	/**
	 * New join make the group, group elements and formgroup entries for the join data
	 *
	 * @param   string $tableKey      table key
	 * @param   string $joinTableKey  join to table key
	 * @param   string $joinType      join type
	 * @param   string $joinTable     join to table
	 * @param   string $joinTableFrom join table
	 * @param   bool   $isRepeat      is the group a repeat
	 *
	 * @return  object  $join           returns new join object
	 */

	protected function makeNewJoin($tableKey, $joinTableKey, $joinType, $joinTable, $joinTableFrom, $isRepeat)
	{
		$groupData          = Worker::formDefaults('group');
		$groupData['name']  = $this->getTable()->label . '- [' . $joinTable . ']';
		$groupData['label'] = $joinTable;
		$groupId            = $this->createLinkedGroup($groupData, true, $isRepeat);

		$join                  = $this->getTable('Join');
		$join->id              = null;
		$join->list_id         = $this->get('list.id');
		$join->join_from_table = $joinTableFrom;
		$join->table_join      = $joinTable;
		$join->table_join_key  = $joinTableKey;
		$join->table_key       = str_replace('`', '', $tableKey);
		$join->join_type       = $joinType;
		$join->group_id        = $groupId;
		/**
		 * Create the 'pk' param.  Can't just call front end setJoinPk() for gory
		 * reasons, so do this by steam.
		 *
		 * Probably don't really need to create a registry object here, we could just
		 * JSON-up the pk param, but might as well make the point here that it's a
		 * params object, and it may come in useful for adding other params one day.
		 */
		$join->params = new JRegistry;
		/**
		 * This is kind of expensive, as getPrimaryKeyAndExtra() method does a table lookup,
		 * but I don't think we know what the PK of the joined table is any other
		 * way at this point.
		 */
		$pk = $this->getStorage()->getPrimaryKeyAndExtra($join->table_join);

		if ($pk !== false)
		{
			// If it didn't return false, getPrimaryKeyAndExtra will have created and array with at least one key
			$pk_col = ArrayHelper::getValue($pk[0], 'colname', '');

			if (!empty($pk_col))
			{
				$db     = Worker::getDbo(true);
				$pk_col = $join->table_join . '.' . $pk_col;
				$join->params->set('pk', $db->quoteName($pk_col));
				$join->params = (string) $join->params;
			}
		}

		$join->store();

		$this->createLinkedElements($groupId, $joinTable);

		return $join;
	}

	/**
	 * When copying a table we need to copy its joins as well
	 * note that the group and elements already exists - just the join needs to be saved
	 *
	 * @param   int   $fromid     table id to copy from
	 * @param   int   $toid       table id to copy to
	 * @param   array $groupidmap saying which groups got copied to which new group id (key = old id, value = new id)
	 *
	 * @return null
	 */
	protected function copyJoins($fromid, $toid, $groupidmap)
	{
	}

	/**
	 * Make a database table from  XML definition
	 *
	 * @param   string $key  primary key
	 * @param   string $name table name
	 * @param   string $xml  xml table definition
	 *
	 * @return bool
	 */
	public function dbTableFromXML($key, $name, $xml)
	{
		$row  = $xml[0];
		$data = array();

		// Get which field types to use
		foreach ($row->children() as $child)
		{
			$value = sprintf("%s", $child);
			$type  = $child->attributes()->type;

			if ($type == '')
			{
				$type = strtotime($value) == false ? "VARCHAR(255)" : "DATETIME";

				if (strstr($value, "\n"))
				{
					$type = 'TEXT';
				}
			}
			else
			{
				switch (String::strtolower($type))
				{
					case 'integer':
						$type = 'INT';
						break;
					case 'datetime':
						$type = "DATETIME";
						break;
					case 'float':
						$type = "DECIMAL(10,2)";
						break;
					default:
						$type = "VARCHAR(255)";
						break;
				}
			}

			$data[$child->getName()] = $type;
		}

		if (empty($data))
		{
			return false;
		}

		$db    = $this->_db;
		$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($name) . ' (';

		foreach ($data as $fname => $type)
		{
			$query .= $db->qn($fname) . " $type, \n";
		}

		$query .= ' primary key (' . $key . '))';
		$query .= ' ENGINE = MYISAM ';
		$db->setQuery($query);
		$db->execute();

		// Get a list of existing ids
		$query = $db->getQuery(true);
		$query->select($key)->from($name);
		$db->setQuery($query);
		$existingIds = $db->loadColumn();

		// Build the row object to insert/update
		foreach ($xml as $row)
		{
			$o = new stdClass;

			foreach ($row->children() as $child)
			{
				$k     = $child->getName();
				$o->$k = sprintf("%s", $child);
			}

			// Either update or add records
			if (in_array($o->$key, $existingIds))
			{
				$db->updateObject($name, $o, $key);
			}
			else
			{
				$db->insertObject($name, $o, $key);
			}
		}

		return true;
	}

	/**
	 * Load list from form id
	 *
	 * @param   int $formId form id
	 *
	 * @throws \Exception
	 *
	 * @return  object  JTable
	 */

	public function loadFromFormId($formId)
	{
		throw new \Exception ('Admin load from form id called but ids no longer used');
		$item = $this->getTable();

		/**
		 * Not sure why but we need to populate and manually __state_set
		 * Otherwise list.id reverts to the form's id and not the list id
		 */
		$this->populateState();
		$this->__state_set = true;
		$item->load(array('form_id' => $formId));
		$this->table = $item;
		$this->set('list.id', $item->id);

		return $item;
	}

	/**
	 * Create an element
	 *
	 * @param   string $name Element name
	 * @param   array  $data Properties
	 *
	 * @return mixed false if failed, otherwise element plugin public properties
	 */
	public function makeElement($name, $data)
	{
		$pluginManager = Worker::getPluginManager();
		$element       = $pluginManager->loadPlugIn($data['plugin'], 'element');
		$item          = $element->getDefaultProperties();
		$item->id      = null;
		$item->name    = $name;
		$item->label   = str_replace('_', ' ', $name);

		return $item;
	}

	/**
	 * Return the default set of attributes when creating a new
	 * fabrik list
	 *
	 * @return string json encoded Params
	 */

	public function getDefaultParams()
	{
		$a                      = array('advanced-filter' => 0, 'show-table-nav' => 1, 'show-table-filters' => 1, 'show-table-add' => 1, 'require-filter' => 0);
		$o                      = (object) $a;
		$o->admin_template      = 'admin';
		$o->detaillink          = 0;
		$o->empty_data_msg      = 'No data found';
		$o->pdf                 = '';
		$o->rss                 = 0;
		$o->feed_title          = '';
		$o->feed_date           = '';
		$o->rsslimit            = 150;
		$o->rsslimitmax         = 2500;
		$o->csv_import_frontend = 3;
		$o->csv_export_frontend = 3;
		$o->csvfullname         = 0;
		$o->access              = 1;
		$o->allow_view_details  = 1;
		$o->allow_edit_details  = 1;
		$o->allow_add           = 1;
		$o->allow_delete        = 1;
		$o->group_by_order      = '';
		$o->group_by_order_dir  = 'ASC';
		$o->prefilter_query     = '';

		return json_encode($o);
	}

	/**
	 * Get the element ids for list ordering
	 *
	 * @since  3.0.7
	 *
	 * @return  array  element ids
	 */

	public function getOrderBys()
	{
		$item     = $this->getItem();
		$orderBys = Worker::JSONtoData($item->get('list.order_by'), true);

		foreach ($orderBys as &$orderBy)
		{
			$elementModel = $this->getElement($orderBy, true);
			$orderBy      = $elementModel ? $elementModel->getId() : '';
		}

		return $orderBys;
	}

	/**
	 * Get the table object for the models _id
	 *
	 * @param   string $name    The table name. Optional.
	 * @param   string $prefix  The class prefix. Optional.
	 * @param   array  $options Configuration array for model. Optional.
	 *
	 * @return   Registry    table
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if ($name === true)
		{
			$this->clearTable();
		}

		if (!isset($this->table) || !is_object($this->table))
		{
			$id          = $this->get('id');
			$this->table = $this->getItem($id);

			if (trim($this->table->get('db_primary_key')) !== '')
			{
				$this->table->set('db_primary_key', FabrikString::safeColName($this->table->get('db_primary_key')));
			}
		}

		return $this->table;
	}

	/**
	 * Main query to build table
	 *
	 * @return  array  list data
	 */
	public function render()
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('onBeforeListRender', $this, 'list');
		FabrikHelperHTML::debug($_POST, 'render:post');
		$input              = $this->app->input;
		$profiler           = JProfiler::getInstance('Application');
		$id                 = $this->getId();
		$this->outputFormat = $input->get('format', 'html');

		if (is_null($id) || $id == '0')
		{
			throw new RuntimeException(FText::_('COM_FABRIK_INCORRECT_LIST_ID'), 500);
		}

		if ($this->outputFormat == 'fabrikfeed')
		{
			$this->outputFormat = 'feed';
		}

		$item = $this->getTable();

		if ($item->get('list.db_table_name') == '')
		{
			throw new RuntimeException(FText::_('COM_FABRIK_INCORRECT_LIST_ID'), 500);
		}

		// Cant set time limit in safe mode so suppress warning
		@set_time_limit(60);
		JDEBUG ? $profiler->mark('About to get table filter') : null;
		$filters = $this->getFilterArray();
		JDEBUG ? $profiler->mark('Got filters') : null;
		$this->setLimits();
		$this->setElementTmpl();
		$data = $this->getData();
		JDEBUG ? $profiler->mark('got data') : null;

		// Think we really have to do these as the calc isn't updated when the list is filtered
		$this->doCalculations();
		JDEBUG ? $profiler->mark('done calcs') : null;
		$this->getCalculations();
		JDEBUG ? $profiler->mark('got calcs') : null;

		return $data;
	}

	/**
	 * Set model sate
	 *
	 * @return  void
	 */
	protected function populateState()
	{
		$input = $this->app->input;

		if (!$this->app->isAdmin())
		{
			// Load the menu item / component parameters.
			$params = $this->app->getParams();
			$this->set('params', $params);

			// Load state from the request.
			$pk = $input->getString('listid', $params->get('listid'));
		}
		else
		{
			$pk = $input->getString('id');
		}

		$this->set('id', $pk);

		$offset = $input->getInt('limitstart');
		$this->set('list.offset', $offset);
	}

	/**
	 * Creates filter array (return existing if exists)
	 *
	 * @return  array    filters
	 */
	public function &getFilterArray()
	{
		if (isset($this->filters))
		{
			return $this->filters;
		}

		$filterModel   = $this->getFilterModel();
		$db            = Worker::getDbo();
		$this->filters = array();
		$request       = $this->getRequestData();
		$this->storeRequestData($request);
		FabrikHelperHTML::debug($request, 'filter:request');

		$elements = $this->getElements('id');

		/* $$$ rob prefilters loaded before anything to avoid issues where you filter on something and
		 * you have 2 prefilters with joined by an OR - this was incorrectly giving SQL of
		* WHERE normal filter = x OR ( prefilter1 = y OR prefilter2 = x)
		* this change changes the SQL to
		* WHERE ( prefilter1 = y OR prefilter2 = x) AND normal filter = x
		*/
		$this->getPrefilterArray($this->filters);

		// These are filters created from a search form or normal search
		$keys      = array_keys($request);
		$indexStep = count(ArrayHelper::getValue($this->filters, 'key', array()));
		FabrikHelperHTML::debug($keys, 'filter:request keys');

		foreach ($keys as $key)
		{
			if (is_array($request[$key]))
			{
				foreach ($request[$key] as $kk => $v)
				{
					if (!array_key_exists($key, $this->filters) || !is_array($this->filters[$key]))
					{
						$this->filters[$key] = array();
					}

					$this->filters[$key][$kk + $indexStep] = $v;
				}
			}
		}

		FabrikHelperHTML::debug($this->filters, 'listmodel::getFilterArray middle');
		$readOnlyValues = array();
		$w              = new Worker;
		$noFiltersSetup = ArrayHelper::getValue($this->filters, 'no-filter-setup', array());

		if (count($this->filters) == 0)
		{
			Worker::getPluginManager()->runPlugins('onFiltersGot', $this, 'list');

			return $this->filters;
		}

		// Get a list of plugins
		$pluginKeys = $filterModel->getPluginFilterKeys();
		$elementIds = ArrayHelper::getValue($this->filters, 'elementid', array());
		$sqlCond    = ArrayHelper::getValue($this->filters, 'sqlCond', array());
		$raws       = ArrayHelper::getValue($this->filters, 'raw', array());

		foreach ($this->filters['key'] as $i => $keyval)
		{
			$value      = $this->filters['value'][$i];
			$condition  = String::strtoupper($this->filters['condition'][$i]);
			$key        = $this->filters['key'][$i];
			$filterEval = $this->filters['eval'][$i];
			$elid       = ArrayHelper::getValue($elementIds, $i);
			$key2       = array_key_exists('key2', $this->filters) ? ArrayHelper::getValue($this->filters['key2'], $i, '') : '';

			/* $$$ rob see if the key is a raw filter
			 * 20/12/2010 - think $key is never with _raw now as it is unset in tablefilter::getQuerystringFilters() although may  be set elsewhere
			* - if it is make a note and remove the _raw from the name
			*/
			$raw = ArrayHelper::getValue($raws, $i, false);

			if (String::substr($key, -5, 5) == '_raw`')
			{
				$key = String::substr($key, 0, String::strlen($key) - 5) . '`';
				$raw = true;
			}

			if ($elid == -1)
			{
				// Bool match
				$this->filters['origvalue'][$i] = $value;
				$this->filters['sqlCond'][$i]   = $key . ' ' . $condition . ' (' . $db->q($value) . ' IN BOOLEAN MODE)';
				continue;
			}

			// List plug-in filter found - it should have set its own sql in onGetPostFilter();
			if (!empty($elid) && in_array($elid, $pluginKeys))
			{
				$this->filters['origvalue'][$i] = $value;
				$this->filters['sqlCond'][$i]   = $this->filters['sqlCond'][$i];
				continue;
			}

			$elementModel = ArrayHelper::getValue($elements, $elid);

			// $$$ rob key2 if set is in format  `countries_0`.`label` rather than  `countries`.`label`
			// used for search all filter on 2nd db join element pointing to the same table
			if (strval($key2) !== '')
			{
				$key = $key2;
			}

			$eval          = $this->filters['eval'][$i];
			$fullWordsOnly = $this->filters['full_words_only'][$i];

			// $$ hugh - testing allowing {QS} replacements in pre-filter values
			$w->replaceRequest($value);
			$value = $this->prefilterParse($value);
			$value = $w->parseMessageForPlaceHolder($value);

			if (!is_a($elementModel, 'PlgFabrik_Element'))
			{
				if ($this->filters['condition'][$i] == 'exists')
				{
					$this->filters['sqlCond'][$i] = 'EXISTS (' . $value . ')';
				}

				continue;
			}

			$elementModel->_rawFilter = $raw;

			if ($filterEval == '1')
			{
				// $$$ rob hehe if you set $i in the eval'd code all sorts of chaos ensues
				$origi = $i;
				$value = stripslashes(htmlspecialchars_decode($value, ENT_QUOTES));
				$value = @eval($value);
				Worker::logEval($value, 'Caught exception on eval of tableModel::getFilterArray() ' . $key . ': %s');
				$i = $origi;
			}

			if ($condition == 'LATERTHISYEAR' || $condition == 'EARLIERTHISYEAR')
			{
				$value = $db->q($value);
			}

			if ($fullWordsOnly == '1')
			{
				$condition = 'REGEXP';
			}

			$originalValue = $this->filters['value'][$i];

			if ($value == '' && $eval == FABRIKFILTER_QUERY)
			{
				throw new RuntimeException(FText::_('COM_FABRIK_QUERY_PREFILTER_WITH_NO_VALUE'), 500);
			}

			list($value, $condition) = $elementModel->getFilterValue($value, $condition, $eval);

			/*
			 *  $$$ hugh - this chunk got fugly, as we wound up with too many quotes with whole words on
			 *  and exact match off, like ...
			 *  LOWER('"[[:<:]]Brose[[:>:]]"')
			 *  ... so I fixed it the long handed way ... could prolly be done more elegantly, but this should work!
			 */
			if ($fullWordsOnly == '1')
			{
				if (is_array($value))
				{
					foreach ($value as &$v)
					{
						$v = "\"[[:<:]]" . $v . "[[:>:]]\"";
					}
					if (strtoupper($condition) === 'REGEXP')
					{
						// $$$ 15/11/2012 - moved from before getFilterValue() to after as otherwise date filters in querystrings created wonky query
						$v = 'LOWER(' . $v . ')';
					}
				}
				else
				{
					$value = "\"[[:<:]]" . $value . "[[:>:]]\"";
					if (strtoupper($condition) === 'REGEXP')
					{
						// $$$ 15/11/2012 - moved from before getFilterValue() to after as otherwise date filters in querystrings created wonky query
						$value = 'LOWER(' . $value . ')';
					}
				}
			}
			else
			{

				if (strtoupper($condition) === 'REGEXP')
				{
					// $$$ 15/11/2012 - moved from before getFilterValue() to after as otherwise date filters in querystrings created wonky query
					$value = 'LOWER(' . $db->q($value, false) . ')';
				}

			}

			if (!array_key_exists($i, $sqlCond) || $sqlCond[$i] == '')
			{
				// Will produce an SQL error - but is equivalant to 'show no records' so set to where 3 = -3
				if ($condition === 'IN' && $value === '()')
				{
					$query = '3 = -3';
				}
				else
				{
					$query = $elementModel->getFilterQuery($key, $condition, $value, $originalValue, $this->filters['search_type'][$i]);
				}

				$this->filters['sqlCond'][$i] = $query;
			}

			$this->filters['condition'][$i] = $condition;

			// Used when getting the selected dropdown filter value
			$this->filters['origvalue'][$i] = $originalValue;
			$this->filters['value'][$i]     = $value;

			if (!array_key_exists($i, $noFiltersSetup))
			{
				$this->filters['no-filter-setup'][$i] = 0;
			}

			if ($this->filters['no-filter-setup'][$i] == 1)
			{
				$tmpName = $elementModel->getFullName(true, false);
				$tmpData = array($tmpName => $originalValue, $tmpName . '_raw' => $originalValue);

				// Set defaults to null to ensure we get correct value for 2nd dropdown search value (mutli dropdown from search form)
				$elementModel->defaults = null;

				if (!array_key_exists($key, $readOnlyValues))
				{
					$readOnlyValues[$key] = array();
				}

				$readOnlyValues[$key][] = $elementModel->getFilterRO($tmpData);

				// Set it back to null again so that in form view we dont return this value.
				$elementModel->defaults = null;

				// Filter value assigned in readOnlyValues foreach loop towards end of this function
				$this->filters['filter'][$i] = '';
			}
			else
			{
				/*$$$rob not sure $value is the right var to put in here - or if its actually used
				 * but without this line you get warnings about missing variable in the filter array
				*/
				$this->filters['filter'][$i] = $value;
			}
		}

		FabrikHelperHTML::debug($this->filters, 'end filters');

		foreach ($readOnlyValues as $key => $val)
		{
			foreach ($this->filters['key'] as $i => $fkey)
			{
				if ($fkey === $key)
				{
					$this->filters['filter'][$i] = implode("<br>", $val);
				}
			}
		}

		Worker::getPluginManager()->runPlugins('onFiltersGot', $this, 'list');
		FabrikHelperHTML::debug($this->filters, 'after plugins:onFiltersGot');

		return $this->filters;
	}

	/**
	 * Process the lists plug-ins
	 *
	 * @return  array    of list plug-in result messages
	 */

	public function processPlugin()
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('process', $this, 'list');

		return $pluginManager->data;
	}

	/**
	 * Code to enable plugins to add a button to the top of the list
	 *
	 * @return  array    button html
	 */

	public function getPluginTopButtons()
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('topButton', $this, 'list');
		$buttons = $pluginManager->data;

		return $buttons;
	}

	/**
	 * Get the html that is outputted by list plug-in buttons
	 *
	 * @return  array  buttons
	 */

	public function getPluginButtons()
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$pluginManager->runPlugins('button', $this, 'list');
		$buttons = $pluginManager->data;

		return $buttons;
	}

	/**
	 * Get an array of plugin js classes to load
	 *
	 * @param   array &$r    Previously loaded classes
	 * @param   array &$shim Shim object to ini require.js
	 *
	 * @return  array
	 */

	public function getPluginJsClasses(&$r = array(), &$shim = array())
	{
		$r             = (array) $r;
		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$src = array();
		$pluginManager->runPlugins('loadJavascriptClass', $this, 'list', $src);

		foreach ($pluginManager->data as $f)
		{
			if (is_null($f) || $f == '')
			{
				continue;
			}

			if (is_array($f))
			{
				$r = array_merge($r, $f);
			}
			else
			{
				$r[] = $f;
			}
		}

		$pluginManager->runPlugins('requireJSShim', $this, 'list');

		foreach ($pluginManager->data as $ashim)
		{
			$shim = array_merge($shim, $ashim);
		}

		return $r;
	}

	/**
	 * Get plugin js objects
	 *
	 * @param   string $container list container HTML id
	 *
	 * @return  mixed
	 */

	public function getPluginJsObjects($container = null)
	{
		if (is_null($container))
		{
			$container = 'listform_' . $this->getId();
		}

		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$pluginManager->runPlugins('onLoadJavascriptInstance', $this, 'list', $container);

		return $pluginManager->data;
	}

	/**
	 * Set the navigation limit and limitstart
	 *
	 * @param   int $limitStart_override  Specific limitstart to use, if both start and length are specified
	 * @param   int $limitlength_override Specific limitlength to use, if both start and length are specified
	 *
	 * @return  void
	 */

	public function setLimits($limitStart_override = null, $limitlength_override = null)
	{
		$input = $this->app->input;

		// Plugins using setLimits - these limits would get overwritten by render() or getData() calls
		if (isset($this->limitLength) && isset($this->limitStart) && is_null($limitStart_override) && is_null($limitlength_override))
		{
			return;
		}
		/*
		 * $$$ hugh - added the overrides, so things like visualizations can just turn
		 * limits off, by passing 0's, without having to go round the houses setting
		 * the request array before calling this method.
		 */
		if (!is_null($limitStart_override) && !is_null($limitlength_override))
		{
			// Might want to set the request vars here?
			$limitStart  = $limitStart_override;
			$limitLength = $limitlength_override;
		}
		else
		{
			$package             = $this->app->getUserState('com_fabrik.package', 'fabrik');
			$item                = $this->getTable();
			$params              = $this->getParams();
			$id                  = $this->getId();
			$this->randomRecords = $input->get('fabrik_random', $this->randomRecords);

			// $$$ rob don't make the key list.X as the registry doesn't seem to like keys with just '1' a
			$context    = 'com_' . $package . '.list' . $this->getRenderContext() . '.';
			$limitStart = $this->randomRecords ? $this->getRandomLimitStart() : 0;

			// Deal with the fact that you can have more than one list on a page so limitstart has to be specific per table

			// If list is rendered as a content plugin don't set the limits in the session
			if ($this->app->scope == 'com_content')
			{
				$limitLength = $input->getInt('limit' . $id, $item->get('list.rows_per_page'));

				if (!$this->randomRecords)
				{
					$limitStart = $input->getInt('limitstart' . $id, $limitStart);
				}
			}
			else
			{
				// If a list (assoc with a menu item) loads a form, with db join & front end select - don't use the orig menu's rows_per_page value.
				$mambot      = $this->isMambot || ($input->get('tmpl') === 'component' && $input->getInt('ajax') === 1);
				$rowsPerPage = Worker::getMenuOrRequestVar('rows_per_page', $item->get('list.rows_per_page'), $mambot);
				$limitLength = $this->app->getUserStateFromRequest($context . 'limitlength', 'limit' . $id, $rowsPerPage);

				if (!$this->randomRecords)
				{
					$limitStart = $this->app->getUserStateFromRequest($context . 'limitstart', 'limitstart' . $id, $limitStart, 'int');
				}
			}

			if ($this->outputFormat == 'feed')
			{
				$limitLength = $input->getInt('limit', $params->get('rsslimit', 150));
				$maxLimit    = $params->get('rsslimitmax', 2500);

				if ($limitLength > $maxLimit)
				{
					$limitLength = $maxLimit;
				}
			}

			if ($limitStart < 0)
			{
				$limitStart = 0;
			}
		}

		$this->limitLength = $limitLength;
		$this->limitStart  = $limitStart;
	}

	/**
	 * This merges session data for the fromForm with any request data
	 * allowing us to filter data results from both search forms and filters
	 *
	 * @return  array
	 */

	public function getRequestData()
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('start get Request data') : null;
		$f = $this->getFilterModel()->getFilters();
		JDEBUG ? $profiler->mark('end get Request data') : null;

		return $f;
	}

	/**
	 * Get the table's filter model
	 *
	 * @return  model    filter model
	 */

	public function &getFilterModel()
	{
		if (!isset($this->filterModel))
		{
			$this->filterModel = new ListFilter;
			$this->filterModel->setListModel($this);
		}

		return $this->filterModel;
	}

	/**
	 * Once we have a few table joins, our select statements are
	 * getting big enough to hit default select length max in MySQL.
	 * Added per-list setting to enable_big_selects.
	 *
	 * 03/10/2012 - Should preserve any old list settings, but this is now set in the global config
	 * We set it on the main J db in the system plugin setBigSelects() but should do here as well as we
	 * may not be dealing with the same db.
	 *
	 * 2012-10-19 - $$$ hugh - trouble with preserving old list settings is there is no way to change them, without
	 * directly poking around in the params in the database.  Commenting out the per-list checking.
	 *
	 * @deprecated   now handled in FabrikHelper::getDbo(), as it needs to apply to all queries, including internal /
	 *               default connection ones.
	 * @since        3/16/2010
	 *
	 * @return  void
	 */

	public function setBigSelects()
	{
		$fbConfig   = JComponentHelper::getParams('com_fabrik');
		$bigSelects = $fbConfig->get('enable_big_selects', 0);
		/*
		 $fabrikDb = $this->getDb();
		$params = $this->getParams();
		if ($params->get('enable_big_selects', $bigSelects))
		 */
		if ($bigSelects)
		{
			$fabrikDb = $this->getDb();

			// $$$ hugh - added bumping up GROUP_CONCAT_MAX_LEN here, rather than adding YAFO for it
			//$fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			if (version_compare($fabrikDb->getVersion(), '5.1.0', '>='))
			{
				$fabrikDb->setQuery("SET SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			}
			else
			{
				$fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			}
			$fabrikDb->execute();
		}
	}

	/**
	 * Append to the list's data
	 *
	 * @param   string $groupRef Group by reference (0 for non-grouped)
	 * @param   object $row      Row to append to the list
	 *
	 * @return  array  $this->data
	 */

	public function appendData($groupRef, $row)
	{
		$data = $this->getData();

		if (array_key_exists($groupRef, $data))
		{
			$data[$groupRef][] = $row;
			$this->data        = $data;
		}

		return $this->data;
	}

	/**
	 * Get the table's data
	 *
	 * @return  array    of objects (rows)
	 */

	public function getData()
	{
		if (isset($this->data) && !is_null($this->data))
		{
			return $this->data;
		}

		$profiler      = JProfiler::getInstance('Application');
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('onPreLoadData', $this, 'list');

		// Needs to be off for FOUND_ROWS() to work
		ini_set('mysql.trace_mode', 'off');
		JDEBUG ? $profiler->mark('query build start') : null;

		// Ajax call needs to recall this - not sure why
		$this->setLimits();
		JDEBUG ? $profiler->mark('query build end') : null;

		try
		{
			$this->finesseData();
		} catch (Exception $e)
		{
			$item = $this->getTable();
			$msg  = 'Fabrik has generated an incorrect query for the list ' . $item->label . ':' . $e->getMessage();
			throw new RuntimeException($msg, 500);
		}

		$nav = $this->getPagination($this->totalRecords, $this->limitStart, $this->limitLength);

		// Pass the query as an object property so it can be updated via reference
		$args       = new stdClass;
		$args->data =& $this->data;

		$pluginManager->runPlugins('onLoadData', $this, 'list', $args);

		return $this->data;
	}

	/**
	 * Method to run the getData select query and do our Fabrik magikin'
	 *
	 * @return void
	 */

	public function finesseData()
	{
		$profiler   = JProfiler::getInstance('Application');
		$traceModel = ini_get('mysql.trace_mode');
		$fabrikDb   = $this->getDb();
		$this->setBigSelects();
		$query = $this->buildQuery();

		// $$$ rob - if merging joined data then we don't want to limit
		// the query as we have already done so in buildQuery()
		if ($this->mergeJoinedData() || $this->limitLength === -1)
		{
			$fabrikDb->setQuery($query);
		}
		else
		{
			$fabrikDb->setQuery($query, $this->limitStart, $this->limitLength);
		}

		FabrikHelperHTML::debug($fabrikDb->getQuery(), 'list GetData:' . $this->getItem()->get('list.label'));
		JDEBUG ? $profiler->mark('before query run') : null;

		/* set 2nd param to false in attempt to stop joomfish db adaptor from translating the original query
		 * fabrik3 - 2nd param in j16 is now used - guessing that joomfish now uses the third param for the false switch?
		* $$$ rob 26/09/2011 note Joomfish not currently released for J1.7
		*/
		$this->data = $fabrikDb->loadObjectList('', 'stdClass', false);

		// $$$ rob better way of getting total records
		if ($this->mergeJoinedData())
		{
			$this->totalRecords = $this->getTotalRecords();
		}
		else
		{
			$fabrikDb->setQuery("SELECT FOUND_ROWS()");
			$this->totalRecords = $fabrikDb->loadResult();
		}

		if ($this->randomRecords)
		{
			shuffle($this->data);
		}

		ini_set('mysql.trace_mode', $traceModel);
		JDEBUG ? $profiler->mark('query run and data loaded') : null;

		// Add labels before preformatting - otherwise calc elements on dropdown elements show raw data for {list___element}
		$this->addLabels($this->data);

		// Run calculations
		$this->preFormatFormJoins($this->data);
		JDEBUG ? $profiler->mark('start format for joins') : null;
		$this->formatForJoins($this->data);
		JDEBUG ? $profiler->mark('start format data') : null;
		$this->formatData($this->data);
		JDEBUG ? $profiler->mark('data formatted') : null;
	}

	/**
	 * Part of list model finesseData() replace list___element values with labels for
	 * things like dropdowns, needed so that calc elements in preFormatFormJoins() have access to the element
	 * label
	 *
	 * @param   array &$data List data
	 *
	 * @return  void
	 */
	protected function addLabels(&$data)
	{
		$form   = $this->getFormModel();
		$groups = $form->getGroupsHierarchy();
		$ec     = count($data);

		foreach ($groups as $groupModel)
		{
			$elementModels = $this->activeContextElements($groupModel);

			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $this);
				$col = $elementModel->getFullName(false, true, false);

				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					for ($i = 0; $i < $ec; $i++)
					{
						$thisRow        = $data[$i];
						$colData        = $thisRow->$col;
						$data[$i]->$col = $elementModel->getLabelForValue($colData, $colData);
					}
				}
			}
		}
	}

	/**
	 * $$$ rob pointless getting elements not shown in the table view?
	 * $$$ hugh - oops, they might be using elements in group-by template not shown in table
	 * http://fabrikar.com/forums/showthread.php?p=102600#post102600
	 * $$$ rob in that case lets test that rather than loading blindly
	 * $$$ rob 15/02/2011 or out put may be csv in which we want to format any fields not shown in the form
	 * $$$ hugh 06/05/2012 added formatAll() mechanism, so plugins can force formatting of all elements
	 *
	 * @param   JModel $groupModel Group model
	 *
	 * @return array element models
	 */
	private function activeContextElements($groupModel)
	{
		$tableParams = $this->getParams();

		if ($this->formatAll() || ($tableParams->get('group_by_template') !== '' && $this->getGroupBy() != '') || $this->outputFormat == 'csv'
			|| $this->outputFormat == 'feed'
		)
		{
			$elementModels = $groupModel->getPublishedElements();
		}
		else
		{
			/* $$$ hugh - added 'always render' option to elements, and methods to grab those.
			 * Could probably do this in getPublishedListElements(), but for now just grab a list
			 * of elements with 'always render' set to Yes, and "show in list" set to No,
			 * then merge that with the getPublishedListElements.  This is to work around issues
			 * where things like plugin bubble templates use placeholders for elements not shown in the list.
			 */
			$alwaysRenderElements = $this->getAlwaysRenderElements(true);
			$elementModels        = $groupModel->getPublishedListElements();
			$elementModels        = array_merge($elementModels, $alwaysRenderElements);
		}

		return $elementModels;
	}

	/**
	 * Run the list data through element filters
	 *
	 * @param   array &$data list data
	 *
	 * @return  void
	 */
	protected function formatData(&$data)
	{
		$profiler = JProfiler::getInstance('Application');
		$input    = $this->app->input;
		jimport('joomla.filesystem.file');
		$form                 = $this->getFormModel();
		$tableParams          = $this->getParams();
		$item                 = $this->getItem();
		$method               = 'renderListData_' . $this->outputFormat;
		$this->_aLinkElements = array();

		// $$$ hugh - temp foreach fix
		$groups = $form->getGroupsHierarchy();
		$ec     = count($data);

		foreach ($groups as $groupModel)
		{
			$elementModels = $this->activeContextElements($groupModel);

			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $this);
				$col = $elementModel->getFullName(true, false);

				// Check if there is  a custom out put handler for the tables format
				// Currently supports "renderListData_csv", "renderListData_rss", "renderListData_html", "renderListData_json"
				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					if (method_exists($elementModel, $method))
					{
						for ($i = 0; $i < count($data); $i++)
						{
							$thisRow        = $data[$i];
							$colData        = $thisRow->$col;
							$data[$i]->$col = $elementModel->$method($colData, $thisRow);
						}
					}
					else
					{
						JDEBUG ? $profiler->mark('elements renderListData: (' . $ec . ') id =' . $item->get('id') . ' ' . $col) : null;

						for ($i = 0; $i < $ec; $i++)
						{
							$thisRow        = $data[$i];
							$colData        = $thisRow->$col;
							$data[$i]->$col = $elementModel->renderListData($colData, $thisRow);
							$rawCol         = $col . '_raw';

							// Rendering of accented characters in DomPDF
							$data[$i]->$col = htmlspecialchars_decode(htmlentities($data[$i]->$col, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);

							/* Not sure if this works, as far as I can tell _raw will always exist, even if
							 * the element model hasn't explicitly done anything with it (except maybe unsetting it?)
							* For instance, the calc element needs to set _raw.  For now, I changed $thisRow above to
							* be a = reference to $data[$i], and in renderListData() the calc element modifies
							* the _raw entry in $thisRow.  I guess it could simply unset the _raw in $thisRow and
							* then implement a renderRawListData.  Anyway, just sayin'.
							*/
							if (!array_key_exists($rawCol, $thisRow))
							{
								$data[$i]->$rawCol = $elementModel->renderRawListData($colData, $thisRow);
								$data[$i]->$rawCol = htmlspecialchars_decode(htmlentities($data[$i]->$rawCol, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
							}
						}
					}
				}
			}
		}

		JDEBUG ? $profiler->mark('elements rendered for table data') : null;
		$this->groupTemplates = array();

		// Check if the data has a group by applied to it
		$groupBy = $this->getGroupBy();

		if ($groupBy != '' && $this->outputFormat != 'csv')
		{
			$w = new Worker;

			// 3.0 if not group by template spec'd by group but assigned in qs then use that as the group by tmpl
			$requestGroupBy = $input->get('group_by', '');

			if ($requestGroupBy == '')
			{
				$groupTemplate = $tableParams->get('group_by_template');

				if ($groupTemplate == '')
				{
					$groupTemplate = '{' . $groupBy . '}';
				}
			}
			else
			{
				$groupTemplate = '{' . $requestGroupBy . '}';
			}

			$groupedData = array();
			$groupBy     = FabrikString::safeColNameToArrayKey($groupBy);
			$groupBy .= '_raw';
			$groupTitle   = null;
			$aGroupTitles = array();

			for ($i = 0; $i < count($data); $i++)
			{
				$sdata = isset($data[$i]->$groupBy) ? $data[$i]->$groupBy : '';

				// Get rid of & as it blows up SimpleXMLElement, and don't want to use htmlspecialchars as don't want to mess with <, >, etc.
				$sdata = str_replace('&', '&amp;', str_replace('&amp;', '&', $sdata));

				// Test if its just an <a>*</a> tag - if so allow HTML (enables use of icons)
				$xml      = new SimpleXMLElement('<div>' . $sdata . '</div>');
				$children = $xml->children();

				// Not working in PHP5.2	if (!($xml->count() === 1 && $children[0]->getName() == 'a'))
				if (!(count($xml->children()) === 1 && $children[0]->getName() == 'a'))
				{
					$sdata = strip_tags($sdata);
				}

				if (!in_array($sdata, $aGroupTitles))
				{
					$aGroupTitles[]               = $sdata;
					$groupTemplate                = ($w->parseMessageForPlaceHolder($groupTemplate, ArrayHelper::fromObject($data[$i])));
					$this->groupTemplates[$sdata] = nl2br($groupTemplate);
					$groupedData[$sdata]          = array();
				}

				$data[$i]->_groupId = $sdata;
				$gKey               = $sdata;

				// If the group_by was added in in getAsFields remove it from the returned data set (to avoid mess in package view)

				if ($this->group_by_added)
				{
					unset($data[$i]->$groupBy);
				}

				if ($this->temp_db_key_addded)
				{
					$k = $item->get('list.db_primary_key');
				}

				$groupedData[$gKey][] = $data[$i];
			}

			$data = $groupedData;
		}
		else
		{
			for ($i = 0; $i < count($data); $i++)
			{
				if ($this->temp_db_key_addded)
				{
					$k = $item->get('list.db_primary_key');
				}
			}
			// Make sure that the none grouped data is in the same format
			$data = array($data);
		}

		JDEBUG ? $profiler->mark('table grouped-by applied') : null;

		if ($this->outputFormat != 'pdf' && $this->outputFormat != 'csv' && $this->outputFormat != 'feed')
		{
			$this->addSelectBoxAndLinks($data);
			FabrikHelperHTML::debug($data, 'table:data');
		}

		JDEBUG ? $profiler->mark('end format data') : null;
	}

	/**
	 * Add the select box and various links into the data array
	 *
	 * @param   array &$data list's row objects
	 *
	 * @return  void
	 */

	protected function addSelectBoxAndLinks(&$data)
	{
		$db           = Worker::getDbo(true);
		$params       = $this->getParams();
		$buttonAction = $this->actionMethod();
		$tmpKey       = '__pk_val';
		$faceted      = $params->get('facetedlinks');

		// Get a list of fabrik lists and ids for view list and form links
		$oldLinksToForms = $this->getLinksToThisKey();
		$linksToForms    = array();

		foreach ($oldLinksToForms as $join)
		{
			if ($join !== false)
			{
				$k                = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
				$linksToForms[$k] = $join;
			}
		}

		$query = $db->getQuery(true);
		// FIXME - not right!
		$query->select('id, label, db_table_name')->from('#__fabrik_lists');
		$db->setQuery($query);
		$aTableNames = $db->loadObjectList('label');

		// Get pk values
		$pks = array();

		foreach ($data as $groupKey => $group)
		{
			$cg = count($group);

			for ($i = 0; $i < $cg; $i++)
			{
				$pks[] = @$data[$groupKey][$i]->$tmpKey;
			}
		}

		$joins = $this->getJoins();

		foreach ($data as $groupKey => $group)
		{
			// $group = $data[$key]; //Messed up in php 5.1 group positioning in data became ambiguous
			$cg = count($group);

			for ($i = 0; $i < $cg; $i++)
			{
				$row           = $data[$groupKey][$i];
				$viewLinkAdded = false;

				// Done each row as its result can change
				$canEdit   = $this->canEdit($row);
				$canView   = $this->canView($row);
				$pKeyVal   = array_key_exists($tmpKey, $row) ? $row->$tmpKey : '';
				$pkCheck   = array();
				$pkCheck[] = '<div style="display:none">';

				foreach ($joins as $join)
				{
					if ($join->list_id !== '0')
					{
						// $$$ rob 22/02/2011 was not using _raw before which was inserting html into the value for image elements
						$fkey = $join->table_join_alias . '___' . $join->table_key . '_raw';

						if (isset($row->$fkey))
						{
							$fKeyVal = $row->$fkey;

							if (is_object($fKeyVal))
							{
								$fKeyVal = ArrayHelper::fromObject($fKeyVal);
							}

							if (is_array($fKeyVal))
							{
								$fKeyVal = array_shift($fKeyVal);
							}

							$pkCheck[] = '<input type="checkbox" class="fabrik_joinedkey" value="' . htmlspecialchars($fKeyVal, ENT_COMPAT, 'UTF-8')
								. '" name="' . $join->table_join_alias . '[' . $row->__pk_val . ']" />';
						}
					}
				}

				$pkCheck[]          = '</div>';
				$pkCheck            = implode("\n", $pkCheck);
				$row->fabrik_select = $this->canSelectRow($row)
					? '<input type="checkbox" id="id_' . $row->__pk_val . '" name="ids[' . $row->__pk_val . ']" value="'
					. htmlspecialchars($pKeyVal, ENT_COMPAT, 'UTF-8') . '" />' . $pkCheck : '';

				// Add in some default links if no element chosen to be a link
				$link                 = $this->viewDetailsLink($data[$groupKey][$i]);
				$edit_link            = $this->editLink($data[$groupKey][$i]);
				$row->fabrik_view_url = $link;
				$row->fabrik_edit_url = $edit_link;

				$editAttribs    = $this->getCustomLink('attribs', 'edit');
				$detailsAttribs = $this->getCustomLink('attribs', 'details');

				$row->fabrik_view = '';
				$row->fabrik_edit = '';

				$editLabel = $this->editLabel($data[$groupKey][$i]);
				$editText  = $buttonAction == 'dropdown' ? $editLabel : '<span class="hidden">' . $editLabel . '</span>';

				$btnClass   = ($buttonAction != 'dropdown') ? 'btn ' : '';
				$class      = $btnClass . 'fabrik_edit fabrik__rowlink';
				$dataList   = 'list_' . $this->getRenderContext();
				$loadMethod = $this->getLoadMethod('editurl');
				$img        = FabrikHelperHTML::image('edit.png', 'list', '', array('alt' => $editLabel));
				$editLink   = '<a data-loadmethod="' . $loadMethod . '" class="' . $class . '" ' . $editAttribs
					. 'data-list="' . $dataList . '" href="' . $edit_link . '" title="' . $editLabel . '">' . $img
					. ' ' . $editText . '</a>';

				$viewLabel = $this->viewLabel($data[$groupKey][$i]);
				$viewText  = $buttonAction == 'dropdown' ? $viewLabel : '<span class="hidden">' . $viewLabel . '</span>';
				$class     = $btnClass . 'fabrik_view fabrik__rowlink';

				$loadMethod = $this->getLoadMethod('detailurl');
				$img        = FabrikHelperHTML::image('search.png', 'list', '', array('alt' => $viewLabel));
				$viewLink   = '<a data-loadmethod="' . $loadMethod . '" class="' . $class . '" ' . $detailsAttribs
					. 'data-list="' . $dataList . '" href="' . $link . '" title="' . $viewLabel . '">' . $img
					. ' ' . $viewText . '</a>';

				// 3.0 actions now in list in one cell
				$row->fabrik_actions = array();
				$actionMethod        = $this->actionMethod();

				if ($canView || $canEdit)
				{
					if ($canEdit == 1)
					{
						if ($params->get('editlink') || ($actionMethod == 'floating'))
						{
							$row->fabrik_edit                   = $editLink;
							$row->fabrik_actions['fabrik_edit'] = $row->fabrik_edit;
						}

						$row->fabrik_edit_url = $edit_link;

						if ($this->canViewDetails() && $this->floatingDetailLink())
						{
							$row->fabrik_view                   = $viewLink;
							$row->fabrik_actions['fabrik_view'] = $row->fabrik_view;
						}
					}
					else
					{
						if ($this->canViewDetails() && $this->floatingDetailLink())
						{
							if (empty($this->_aLinkElements))
							{
								$viewLinkAdded                      = true;
								$row->fabrik_view                   = $viewLink;
								$row->fabrik_actions['fabrik_view'] = $row->fabrik_view;
							}
						}
						else
						{
							$row->fabrik_edit = '';
						}
					}
				}

				if ($this->canViewDetails() && !$viewLinkAdded && $this->floatingDetailLink())
				{
					$link                               = $this->viewDetailsLink($row, 'details');
					$row->fabrik_view_url               = $link;
					$row->fabrik_view                   = $viewLink;
					$row->fabrik_actions['fabrik_view'] = $row->fabrik_view;
				}

				if ($this->canDelete($row))
				{
					if ($buttonAction == 'dropdown')
					{
						$row->fabrik_actions['delete_divider'] = '';
					}

					$row->fabrik_actions['fabrik_delete'] = $this->deleteButton();
				}
				// Create columns containing links which point to tables associated with this table
				$oldJoinsToThisKey = $this->getJoinsToThisKey();
				$joinsToThisKey    = array();

				foreach ($oldJoinsToThisKey as $join)
				{
					$k                  = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
					$joinsToThisKey[$k] = $join;
				}

				foreach ($joinsToThisKey as $f => $join)
				{
					// $$$ hugh - for reasons I don't understand, $joinsToThisKey now contains entries
					// which aren't in $faceted->linkedlist, so added this sanity check.
					if (isset($faceted->linkedlist->$f))
					{
						$linkedTable = $faceted->linkedlist->$f;
						$popupLink   = $faceted->linkedlist_linktype->$f;

						if ($linkedTable != '0')
						{
							$recordKey    = $join->element_id . '___' . $linkedTable;
							$key          = $recordKey . "_list_heading";
							$val          = $pKeyVal;
							$recordCounts = $this->getRecordCounts($join, $pks);
							$count        = 0;
							$linkKey      = $recordCounts['linkKey'];

							if (is_array($recordCounts))
							{
								if (array_key_exists($val, $recordCounts))
								{
									$count   = $recordCounts[$val]->total;
									$linkKey = $recordCounts[$val]->linkKey;
								}
								else
								{
									if (array_key_exists((int) $val, $recordCounts) && (int) $val !== 0)
									{
										$count   = $recordCounts[(int) $val]->total;
										$linkKey = $recordCounts[$val]->linkKey;
									}
								}
							}

							$join->list_id   = array_key_exists($join->listlabel, $aTableNames) ? $aTableNames[$join->listlabel]->id : '';
							$group[$i]->$key = $this->viewDataLink($popupLink, $join, $row, $linkKey, $val, $count, $f);
						}
						// $$$ hugh - pretty sure we don't need to be doing this
						// $f++;
					}
				}

				// Create columns containing links which point to forms associated with this table
				foreach ($linksToForms as $f => $join)
				{
					$linkedForm = $faceted->linkedform->$f;
					$popupLink  = $faceted->linkedform_linktype->$f;
					/* $$$ hugh @TODO - rob, can you check this, I added this line,
					 * but the logic applied for $val in the linked table code above seems to be needed?
					* http://fabrikar.com/forums/showthread.php?t=9535
					*/
					$val = $pKeyVal;

					if ($linkedForm !== '0')
					{
						if (is_object($join))
						{
							// $$$rob moved these two lines here as there were giving warnings since Hugh commented out the if ($element != '') {
							$linkKey          = @$join->db_table_name . '___' . @$join->name;
							$gkey             = $linkKey . '_form_heading';
							$row2             = ArrayHelper::fromObject($row);
							$linkLabel        = $this->parseMessageForRowHolder($faceted->linkedformtext->$f, $row2);
							$group[$i]->$gkey = $this->viewFormLink($popupLink, $join, $row, $linkKey, $val, false, $f);
						}
					}
				}
			}
		}

		$args['data']  = &$data;
		$pluginButtons = $this->getPluginButtons();

		// Build layout for row buttons
		$tpl         = $this->getTmpl();
		$align       = $params->get('checkboxLocation', 'end') == 'end' ? 'right' : 'left';
		$displayData = array('align' => $align);
		$basePath    = COM_FABRIK_FRONTEND . '/components/com_fabrik/layouts';
		$layout      = new LayoutFile('listactions.' . $buttonAction, $basePath, array('debug' => false, 'component' => 'com_fabrik', 'client' => 'site'));
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts');
		$layout->addIncludePaths(COM_FABRIK_FRONTEND . '/views/list/tmpl/' . $tpl . '/layouts/');

		foreach ($data as $groupKey => $group)
		{
			$cg = count($group);

			for ($i = 0; $i < $cg; $i++)
			{
				$row = $data[$groupKey][$i];

				if (!empty($pluginButtons))
				{
					if ($buttonAction == 'dropdown')
					{
						$row->fabrik_actions[] = '';
					}
				}

				foreach ($pluginButtons as $b)
				{
					if (trim($b) !== '')
					{
						$row->fabrik_actions[] = $b;
					}
				}

				if (!empty($row->fabrik_actions))
				{
					if (count($row->fabrik_actions) > $this->rowActionCount)
					{
						$this->rowActionCount = count($row->fabrik_actions);
					}

					$displayData['items'] = $row->fabrik_actions;
					$row->fabrik_actions  = $layout->render($displayData);
				}
				else
				{
					$row->fabrik_actions = '';
				}
			}
		}
	}

	/**
	 * Should the list link load in an i-frame or a xhr
	 *
	 * @param   string $prop Parameter url to check
	 *
	 * @since  3.1.1
	 *
	 * @return  string  Load method xhr/iframe
	 */
	protected function getLoadMethod($prop)
	{
		$params = $this->getParams();
		$url    = $params->get($prop, '');

		return $url == '' || strstr($url, 'com_fabrik') ? 'xhr' : 'iframe';
	}

	/**
	 * Helper method to decide if a detail link should be added to the row.
	 *
	 * If in Fabrik 3.1 return true (just use the default acl to control the link)
	 *
	 * @return boolean
	 */
	protected function floatingDetailLink()
	{
		return true;
	}

	/**
	 * Get the way row buttons are rendered floating/inline
	 * Can be set either by global config or list options
	 *
	 * In Fabrik 3.1 we've deprecated the floating action code - should always return inline
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */

	public function actionMethod()
	{
		$params = $this->getParams();

		if ($params->get('actionMethod', 'default') == 'default')
		{
			// Use global
			$fbConfig      = JComponentHelper::getParams('com_fabrik');
			$globalDefault = $fbConfig->get('actionMethod', 'floating');

			// Floating deprecated in J3
			if ($globalDefault === 'floating')
			{
				return 'inline';
			}

			return $globalDefault;
		}
		else
		{
			$default = $params->get('actionMethod', 'floating');
		}
		// Floating deprecated in J3
		if ($default === 'floating')
		{
			return 'inline';
		}

		return $default;
	}

	/**
	 * Get delete button
	 *
	 * @param   string $tpl     Template
	 * @param   bool   $heading Is this the check all delete button
	 *
	 * @since 3.0
	 *
	 * @return    string    delete button wrapped in <li>
	 */

	protected function deleteButton($tpl = '', $heading = false)
	{
		$label        = FText::_('COM_FABRIK_DELETE');
		$buttonAction = $this->actionMethod();
		$tpl          = $this->getTmpl();
		$text         = $buttonAction == 'dropdown' ? $label : '<span class="hidden">' . $label . '</span>';
		$btnClass     = $buttonAction != 'dropdown' ? 'btn ' : '';
		$iconClass    = 'icon-remove';
		$label        = ' ' . FText::_('COM_FABRIK_DELETE');
		$btn          = '<a href="#" class="' . $btnClass . 'delete" data-listRef="list_' . $this->getRenderContext()
			. '" title="' . FText::_('COM_FABRIK_DELETE') . '">'
			. FabrikHelperHTML::image('delete.png', 'list', $tpl, array('alt' => $label, 'icon-class' => $iconClass)) . ' ' . $text . '</a>';

		return $btn;
	}

	/**
	 * Get a list of possible menus
	 * USED TO BUILD RELATED TABLE LINKS WITH CORRECT ITEM ID AND TEMPLATE
	 *
	 * @since   2.0.4
	 *
	 * @return  array  linked table menu items
	 */

	protected function getTableLinks()
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		if (isset($this->tableLinks))
		{
			return $this->tableLinks;
		}

		$db             = JFactory::getDbo();
		$joinsToThisKey = $this->getJoinsToThisKey();

		if (empty($joinsToThisKey))
		{
			$this->tableLinks = array();
		}
		else
		{
			$query = $db->getQuery(true);
			$query->select('*')->from('#__menu');

			foreach ($joinsToThisKey as $element)
			{
				$linkWhere[] = 'link LIKE "index.php?option=com_' . $package . '&view=list&listid=' . (int) $element->list_id . '%"';
			}

			$where = 'type = "component" AND (' . implode(' OR ', $linkWhere) . ')';
			$query->where($where);
			$db->setQuery($query);
			$this->tableLinks = $db->loadObjectList();
		}

		return $this->tableLinks;
	}

	/**
	 * For related table links get the record count for each of the table's rows
	 *
	 * @param   object &$element element
	 * @param   array  $pks      primary keys to count on
	 *
	 * @return  array  counts key'd on element primary key
	 */

	public function getRecordCounts(&$element, $pks = array())
	{
		if (!isset($this->recordCounts))
		{
			$this->recordCounts = array();
		}

		$input = $this->app->input;
		$k     = $element->element_id;

		if (array_key_exists($k, $this->recordCounts))
		{
			return $this->recordCounts[$k];
		}

		$listModel = new Lizt;
		$listModel->setId($element->list_id);
		$db           = $listModel->getDb();
		$elementModel = $listModel->getFormModel()->getElement($element->element_id, true);
		$key          = $elementModel->getFullName(false, false);
		$linkKey      = FabrikString::safeColName($key);
		$fparams      = $listModel->getParams();

		// Ensure that the faceted list's "require filters" option is set to false
		$fparams->set('require-filter', false);

		// Ignore faceted lists session filters
		$origIncSesssionFilters = $input->get('fabrik_incsessionfilters', true);
		$input->set('fabrik_incsessionfilters', false);
		$query = $db->getQuery(true);
		$query = $listModel->buildQueryWhere($input->getInt('incfilters', 0), $query);

		if (!ArrayHelper::emptyIsh($pks))
		{
			// Only load the current record sets record counts
			$query->where($linkKey . ' IN (' . implode(',', $pks) . ')');
		}
		// Force reload of join sql
		$listModel->set('joinsSQL', null);

		// Trigger load of joins without cdd elements - seems to mess up count otherwise
		$listModel->set('includeCddInJoin', false);
		$k2 = $db->q(FabrikString::safeColNameToArrayKey($key));

		// $$$ Jannus - see http://fabrikar.com/forums/showthread.php?t=20751
		$distinct = $listModel->mergeJoinedData() ? 'DISTINCT ' : '';
		$item     = $listModel->getTable();
		$query->select($k2 . ' AS linkKey, ' . $linkKey . ' AS id, COUNT(' . $distinct . $item->get('list.db_primary_key') . ') AS total')
			->from($item->get('list.db_table_name'));
		$query = $listModel->buildQueryJoin($query);
		$listModel->set('includeCddInJoin', true);
		$query->group($linkKey);
		$db->setQuery($query);
		$this->recordCounts[$k]            = $db->loadObjectList('id');
		$this->recordCounts[$k]['linkKey'] = FabrikString::safeColNameToArrayKey($key);
		FabrikHelperHTML::debug($query->dump(), 'getRecordCounts query: ' . $linkKey);
		FabrikHelperHTML::debug($this->recordCounts[$k], 'getRecordCounts data: ' . $linkKey);
		$input->set('fabrik_incsessionfilters', $origIncSesssionFilters);

		return $this->recordCounts[$k];
	}

	/**
	 * Creates the html <a> link allowing you to edit other forms from the list
	 * E.g. Faceted browsing: those specified in the list's "Form's whose primary keys link to this table"
	 *
	 * @param   bool   $popUp   is popup link
	 * @param   object $element 27/06/2011 - changed to passing in element
	 * @param   object $row     current list row
	 * @param   string $key     key
	 * @param   string $val     value
	 * @param   bool   $usekey  use the key
	 * @param   int    $f       repeat value 27/11/2011
	 *
	 * @return  string    <a> html part
	 */

	public function viewFormLink($popUp = false, $element = null, $row = null, $key = '', $val = '', $usekey = false, $f = 0)
	{
		$elKey          = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
		$params         = $this->getParams();
		$listId         = $element->list_id;
		$formId         = $element->form_id;
		$faceted        = $params->get('facetedlinks');
		$linkedFormText = ArrayHelper::fromObject($faceted->linkedformtext);
		$msg            = ArrayHelper::getValue($linkedFormText, $elKey);
		$row2           = ArrayHelper::fromObject($row);
		$label          = $this->parseMessageForRowHolder($msg, $row2);
		$package        = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$itemId         = Worker::itemId();

		if (is_null($listId))
		{
			$list   = $this->getTable();
			$listId = $list->id;
		}

		if (is_null($formId))
		{
			$form   = $this->getFormModel()->getForm();
			$formId = $form->id;
		}

		$facetTable = $this->facetedTable($listId);

		if (!$facetTable->canAdd())
		{
			return '<div style="text-align:center"><a title="' . FText::_('JERROR_ALERTNOAUTHOR')
			. '"><img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/login.png" alt="' . FText::_('JERROR_ALERTNOAUTHOR') . '" /></a></div>';
		}

		if ($this->app->isAdmin())
		{
			$bits[] = 'task=form.view';
			$bits[] = 'cid=' . $formId;
		}
		else
		{
			$bits[] = 'view=form';
			$bits[] = 'Itemid=' . $itemId;
		}

		$bits[] = 'formid=' . $formId;
		$bits[] = 'referring_table=' . $this->getTable()->id;

		// $$$ hugh - change in databasejoin getValue() means we have to append _raw to key name
		if ($key != '')
		{
			$bits[] = $key . '_raw=' . $val;
		}

		if ($usekey and $key != '' and !is_null($row))
		{
			$bits[] = 'usekey=' . FabrikString::shortColName($key);
			$bits[] = 'rowid=' . $row->slug;
		}

		$url = 'index.php?option=com_' . $package . '&' . implode('&', $bits);
		$url = JRoute::_($url);

		if (is_null($label) || $label == '')
		{
			$label = FText::_('COM_FABRIK_LINKED_FORM_ADD');
		}

		$icon    = '<i class="icon-plus"></i> ';
		$trigger = $popUp ? 'data-fabrik-view="form"' : '';
		$link    = '<a ' . $trigger . ' href="' . $url . '" title="' . $label . '">' . $icon . $label . '</a>';
		$url     = '<span class="addbutton">' . $link . '</span></a>';

		return $url;
	}

	/**
	 * Get one of the current tables facet tables
	 *(used in tables that link to this lists links)
	 *
	 * @param   int $id list id
	 *
	 * @return  object    table
	 */

	protected function facetedTable($id)
	{
		if (!isset($this->facettables))
		{
			$this->facettables = array();
		}

		if (!array_key_exists($id, $this->facettables))
		{
			$this->facettables[$id] = new Lizt;
			$this->facettables[$id]->setId($id);
		}

		return $this->facettables[$id];
	}

	/**
	 * Build the link (<a href..>) for viewing list data
	 *
	 * @param   bool   $popUp   is the link to generated a popup to show
	 * @param   object $element 27/06/2011
	 * @param   object $row     current list row data
	 * @param   string $key     28/06/2011 - do longer passed in with _raw appended (done in this method)
	 * @param   string $val     value
	 * @param   int    $count   number of related records
	 * @param   int    $f       ref to related data admin info 27/16/2011
	 *
	 * @return  string
	 */

	public function viewDataLink($popUp = false, $element = null, $row = null, $key = '', $val = '', $count = 0, $f = null)
	{
		$count   = (int) $count;
		$elKey   = $element->list_id . '-' . $element->form_id . '-' . $element->element_id;
		$listId  = $element->list_id;
		$html    = array();
		$params  = $this->getParams();
		$faceted = $params->get('facetedlinks');

		/* $$$ hugh - we are getting element keys that aren't in the linkedlisttext.
		 * not sure why, so added this defensive code.  Should probably find out
		* why though!  I just needed to make this error go away NAO!
		*/
		$linkedListText = isset($faceted->linkedlisttext->$elKey) ? $faceted->linkedlisttext->$elKey : '';
		$row2           = ArrayHelper::fromObject($row);
		$label          = $this->parseMessageForRowHolder($linkedListText, $row2);

		if (is_null($listId))
		{
			$list   = $this->getTable();
			$listId = $list->id;
		}

		$facetTable = $this->facetedTable($listId);

		if (!$facetTable->canView())
		{
			return '<div style="text-align:center"><a title="' . FText::_('COM_FABRIK_NO_ACCESS_PLEASE_LOGIN')
			. '"><img src="' . COM_FABRIK_LIVESITE . 'media/com_fabrik/images/login.png"
				alt="' . FText::_('COM_FABRIK_NO_ACCESS_PLEASE_LOGIN') . '" /></a></div>';
		}

		$showRelated         = (int) $params->get('show_related_info', 0);
		$emptyLabel          = $showRelated === 1 ? FText::_('COM_FABRIK_NO_RECORDS') : '';
		$tlabel              = ($count === 0) ? $emptyLabel : '(0) ' . $label;
		$showRelatedAdd      = (int) $params->get('show_related_add', 0);
		$existingLinkedForms = (array) $params->get('linkedform');
		$linkedForm          = ArrayHelper::getValue($existingLinkedForms, $f, false);
		$addLink             = $linkedForm == '0' ? $this->viewFormLink($popUp, $element, $row, $key, $val, false, $f) : '';

		if ($count === 0)
		{
			$html[] = '<div style="text-align:center" class="related_data_norecords">' . $tlabel . '</div>';
		}

		$key .= '_raw';

		if ($label === '')
		{
			$label = FText::_('COM_FABRIK_VIEW');
		}

		$title = $label;
		$label = '<span class="fabrik_related_data_count">(' . $count . ')</span> ' . $label;
		$icon  = '<i class="icon-list-view"></i> ';
		$url   = $this->relatedDataURL($key, $val, $listId);

		if ($showRelated == 0 || ($showRelated == 2 && $count))
		{
			$trigger = $popUp ? 'data-fabrik-view="list"' : '';
			$html[]  = '<a class="related_data" ' . $trigger . ' href="' . $url . '" title="' . $title . '">' . $icon . $label . '</a>';
		}

		if ($addLink != '' && ($showRelatedAdd === 1 || ($showRelatedAdd === 2 && $count === 0)))
		{
			$html[] = '<div>' . $addLink . '</div>';
		}

		return implode("\n", $html);
	}

	/**
	 * Make related data URL
	 *
	 * @param   string $key    Related link key
	 * @param   string $val    Related link value
	 * @param   int    $listId List id
	 *
	 * @since   3.0.8
	 *
	 * @return  string  URL
	 */

	protected function relatedDataURL($key, $val, $listId)
	{
		$itemId  = Worker::itemId();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$url     = 'index.php?option=com_' . $package . '&';

		if ($this->app->isAdmin())
		{
			$bits[] = 'task=list.view';
			$bits[] = 'cid=' . $listId;
		}
		else
		{
			$bits[]    = 'view=list';
			$bits[]    = 'listid=' . $listId;
			$listLinks = $this->getTableLinks();

			// $$$ rob 01/03/2011 find at matching itemid in another menu item for the related data link
			foreach ($listLinks as $listLink)
			{
				if (strstr($listLink->link, 'index.php?option=com_' . $package . '&view=list&listid=' . $listId))
				{
					$bits[] = 'Itemid=' . $listLink->id;
					$itemId = $listLink->id;
					break;
				}
			}

			$bits[] = 'Itemid=' . $itemId;
		}

		if ($key != '')
		{
			$bits[] = $key . '=' . $val;
		}

		$bits[] = 'limitstart' . $listId . '=0';
		$bits[] = 'resetfilters=1';

		// Nope stops url filter form working on related data :(
		// $bits[] = 'clearfilters=1';

		// Test for related data, filter once, go back o main list re-filter -
		$bits[] = 'fabrik_incsessionfilters=0';
		$url .= implode('&', $bits);
		$url = JRoute::_($url);

		return $url;
	}

	/**
	 * Add a normal/custom link to the element data
	 *
	 * @param   string $data          Element data
	 * @param   object &$elementModel Element model
	 * @param   object $row           All row data
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  string    element data with link added if specified
	 */

	public function _addLink($data, &$elementModel, $row, $repeatCounter = 0)
	{
		$element    = $elementModel->getElement();
		$params     = $elementModel->getParams();
		$customLink = trim($params->get('custom_link', ''));

		if ($this->outputFormat == 'csv' || ($element->get('ink_to_detail') == 0 && $customLink == ''))
		{
			return $data;
		}

		// $$$ rob if its a custom link then we aren't linking to the details view so we should
		// ignore the view details access settings
		if (!($this->canViewDetails($row) || $this->canEdit()) && $customLink == '')
		{
			return $data;
		}

		$link = $this->linkHref($elementModel, $row, $repeatCounter);

		if ($link == '')
		{
			return $data;
		}
		// Try to remove any previously entered links
		$data  = preg_replace('/<a(.*?)>|<\/a>/', '', $data);
		$class = '';

		if ($this->canViewDetails($row))
		{
			$class = ' fabrik_view';
		}

		if ($this->canEdit($row))
		{
			$class = ' fabrik_edit';
		}

		$loadMethod = $this->getLoadMethod('custom_link');
		$class      = 'fabrik___rowlink ' . $class;
		$dataList   = 'list_' . $this->getRenderContext();
		$data       = '<a data-loadmethod="' . $loadMethod . '" data-list="' . $dataList . '" class="' . $class . '" href="' . $link . '">' . $data
			. '</a>';

		return $data;
	}

	/**
	 * Get the href for the edit/details link
	 *
	 * @param   \Fabrik\Plugins\Element $elementModel  Element model
	 * @param   stdClass                $row           Lists current row data
	 * @param   int                     $repeatCounter Repeat group counter
	 *
	 * @since   2.0.4
	 *
	 * @return  string    link href
	 */
	public function linkHref($elementModel, $row, $repeatCounter = 0)
	{
		$element    = $elementModel->getElement();
		$params     = $elementModel->getParams();
		$customLink = $params->get('custom_link');
		$item       = $this->getItem();
		$link       = '';

		if ($customLink == '')
		{
			// $$$ rob only test canEdit and canView on standard edit links - if custom we should always use them,
			// 3.0 get either edit or view link - as viewDetailslInk now always returns the view details link
			if ($this->canEdit($row))
			{
				$this->_aLinkElements[] = $element->get('name');
				$link                   = $this->editLink($row);
			}
			elseif ($this->canViewDetails($row))
			{
				$this->_aLinkElements[] = $element->get('name');
				$link                   = $this->viewDetailsLink($row);
			}
		}
		else
		{
			$array = ArrayHelper::fromObject($row);

			foreach ($array as $k => &$v)
			{
				/* $$$ hugh - not everything is JSON, some stuff is just plain strings.
				 * So we need to see if JSON encoding failed, and only use result if it didn't.
				* $v = json_decode($v, true);
				*/
				if (is_array($v))
				{
					$v = ArrayHelper::getValue($v, $repeatCounter);
				}
				else
				{
					$v2 = json_decode($v, true);

					if ($v2 !== null)
					{
						if (is_array($v2))
						{
							$v = ArrayHelper::getValue($v2, $repeatCounter);
						}
						else
						{
							$v = $v2;
						}
					}
				}
			}

			$array['rowid']  = $this->getSlug($row);
			$array['listid'] = $item->get('id');
			$link            = JRoute::_($this->parseMessageForRowHolder($customLink, $array));
		}

		return $link;
	}

	/**
	 * get query to make records
	 *
	 * @return  string    sql
	 */

	public function buildQuery()
	{
		$profiler = JProfiler::getInstance('Application');
		$input    = $this->app->input;
		JDEBUG ? $profiler->mark('buildQuery: start') : null;
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$table = $this->getTable();

		if ($this->mergeJoinedData())
		{
			/* $$$ rob - get a list of the main table's ids limited on the navigation
			 * this will then be used to filter the main query,
			* by modifying the where part of the query
			*/
			$db    = $this->getDb();
			$table = $this->getTable();

			/* $$$ rob 23/05/2012 if the search data is in the joined records we want to get the id's for the joined records and not the master record
			 see http://fabrikar.com/forums/showthread.php?t=26400. This is a partial hack as I can't see how we know which joined record is really last
			$$$ rob 25/05/2012 - slight change so that we work our way up the pk/fk list until we find some ids.
			$$$ hugh, later in the day 25/05/2012 - big OOOOPS, see comment below about table_key vs table_join_key!
			erm no not a mistake!?! reverted as no example of what was wrong with original code
			*/
			$joins = $this->getJoins();

			// Default to the primary key as before this fix
			$tmpPks = array();

			foreach ($joins as $join)
			{
				// $$$ hugh - added repeatElement, as _makeJoinAliases() is going to set canUse to false for those,
				// so they won't get included in the query ... so will blow up if we reference them with __pk_calX selection
				if ($join->params->get('type') !== 'element' && $join->params->get('type') !== 'repeatElement')
				{
					// $$$ hugh - need to be $lookupC + 1, otherwise we end up with two 0's, 'cos we added main table above

					/**
					 * [non-merged data]
					 *
					 * country    town
					 * ------------------------------
					 * france    la rochelle
					 * france    paris
					 * france    bordeaux
					 *
					 * [merged data]
					 *
					 * country    town
					 * -------------------------------
					 * france    la rochelle
					 *            paris
					 *            bordeaux
					 *
					 * [now search on town = 'la rochelle']
					 *
					 * If we don't use this new code then the search results show all three towns.
					 * By getting the lowest set of complete primary keys (in this example the town ids) we set our query to be:
					 *
					 * where town_id IN (1)
					 *
					 * which gives a search result of
					 *
					 * country    town
					 * -------------------------------
					 * france    la rochelle
					 *
					 */
					$pk = $join->params->get('pk');

					if (!array_key_exists($pk, $tmpPks) || !is_array($tmpPks[$pk]))
					{
						$tmpPks[$pk] = array($pk);
					}
					else
					{
						if (count($tmpPks[$pk]) == 1)
						{
							$v              = str_replace('`', '', $tmpPks[$pk][0]);
							$v              = explode('.', $v);
							$v[0]           = $v[0] . '_0';
							$tmpPks[$pk][0] = $db->qn($v[0] . '.' . $v[1]);
						}

						$v             = str_replace('`', '', $pk);
						$v             = explode('.', $v);
						$v[0]          = $v[0] . '_' . count($tmpPks[$pk]);
						$tmpPks[$pk][] = $db->qn($v[0] . '.' . $v[1]);
					}
				}
			}
			// Check for duplicate pks if so we can presume that they are aliased with _X in from query
			$lookupC     = 0;
			$lookUps     = array('DISTINCT ' . $table->get('list.db_primary_key') . ' AS __pk_val' . $lookupC);
			$lookUpNames = array($table->get('list.db_primary_key'));

			foreach ($tmpPks as $pks)
			{
				foreach ($pks as $pk)
				{
					$lookUps[]     = $pk . ' AS __pk_val' . ($lookupC + 1);
					$lookUpNames[] = $pk;
					$lookupC++;
				}
			}

			// $$$ rob if no ordering applied i had results where main record (e.g. UK) was shown in 2 lines not next to each other
			// causing them not to be merged and a 6 rows shown when limit set to 5. So below, if no order by set then order by main pk asc
			$by = trim($table->order_by) === '' ? array() : (array) json_decode($table->order_by);

			if (empty($by))
			{
				$dir = (array) json_decode($table->order_dir);
				array_unshift($dir, 'ASC');
				$table->order_dir = json_encode($dir);

				$by = (array) json_decode($table->order_by);
				array_unshift($by, $table->get('list.db_primary_key'));
				$table->order_by = json_encode($by);
			}

			// $$$ rob build order first so that we know of any elemenets we need to include in the select statement
			$query                     = $this->buildQueryOrder($query);
			$this->selectedOrderFields = (array) $this->selectedOrderFields;
			$this->selectedOrderFields = array_unique(array_merge($lookUps, $this->selectedOrderFields));

			$query->select(implode(', ', $this->selectedOrderFields) . ' FROM ' . $db->qn($table->get('list.db_table_name')));
			$query = $this->buildQueryJoin($query);
			$query = $this->buildQueryWhere($input->get('incfilters', 1), $query);
			$query = $this->buildQueryGroupBy($query);

			// Can't limit the query here as this gives incorrect _data array.
			// $db->setQuery($query, $this->limitStart, $this->limitLength);
			$db->setQuery($query);
			FabrikHelperHTML::debug((string) $query, 'table:mergeJoinedData get ids');
			$ids            = array();
			$idRows         = $db->loadObjectList();
			$maxPossibleIds = count($idRows);

			// An array of the lists pk values
			$mainKeys = array();

			foreach ($idRows as $r)
			{
				$mainKeys[] = $db->q($r->__pk_val0);
			}

			// Chop up main keys for list limitstart, length to cull the data down to the correct length as defined by the page nav/ list settings
			$mainKeys = array_unique($mainKeys);

			if ($this->limitLength > 0)
			{
				$mainKeys = array_slice($mainKeys, $this->limitStart, $this->limitLength);
			}

			/**
			 * $$$ rob get an array containing the PRIMARY key values for each joined tables data.
			 * Stop as soon as we have a set of ids totaling the sum of records contained in $idRows
			 */

			while (count($ids) < $maxPossibleIds && $lookupC >= 0)
			{
				$ids = ArrayHelper::getColumn($idRows, '__pk_val' . $lookupC);

				for ($idx = count($ids) - 1; $idx >= 0; $idx--)
				{
					if ($ids[$idx] == '')
					{
						unset($ids[$idx]);
					}
					else
					{
						$ids[$idx] = $db->q($ids[$idx]);
					}
				}

				if (count($ids) < $maxPossibleIds)
				{
					$lookupC--;
				}
			}
		}

		// Now lets actually construct the query that will get the required records:
		$query->clear();
		unset($this->orderBy);
		$query = $this->buildQuerySelect('list', $query);
		JDEBUG ? $profiler->mark('queryselect: got') : null;
		$query = $this->buildQueryJoin($query);
		JDEBUG ? $profiler->mark('queryjoin: got') : null;

		if ($this->mergeJoinedData())
		{
			/* $$$ rob We've already used buildQueryWhere to get our list of main pk ids.
			 * so lets use that list of ids to create the where statement. This will return 5/10/20 etc
			* records from our main table, as per our page nav, even if a main record has 3 rows of joined
			* data. If no ids found then do where "2 = -2" to return no records (was "1 = -1", changed to make
			* it easier to know where this is coming form when debugging)
			*/
			if (!empty($ids))
			{
				if ($lookUpNames[$lookupC] !== $table->get('list.db_primary_key'))
				{
					$query->where($lookUpNames[$lookupC] . ' IN (' . implode(array_unique($ids), ',') . ')');
				}

				if (!empty($mainKeys))
				{
					// Limit to the current page
					$query->where($table->get('list.db_primary_key') . ' IN (' . implode($mainKeys, ',') . ')');
				}
				else
				{
					$query->where('2 = -2');
				}
			}
			else
			{
				$query->where('2 = -2');
			}
		}
		else
		{
			// $$$ rob we aren't merging joined records so lets just add the standard where query
			// Incfilters set when exporting as CSV
			$query = $this->buildQueryWhere($input->get('incfilters', 1), $query);
		}

		$query = $this->buildQueryGroupBy($query);

		$query           = $this->buildQueryOrder($query);
		$query           = $this->pluginQuery($query);
		$this->mainQuery = $query;

		/*
		$params = $this->getParams();

		if ($params->get('force_collate', '') !== '')
		{
			$query .= ' COLLATE ' . $params->get('force_collate', '') . ' ';
		}
		*/

		return (string) $query;
	}

	/**
	 * Pass an sql query through the table plug-ins
	 *
	 * @param   string $query sql query
	 *
	 * @return  string    altered query.
	 */

	public function pluginQuery($query)
	{
		// Pass the query as an object property so it can be updated via reference
		$args        = new stdClass;
		$args->query = $query;
		Worker::getPluginManager()->runPlugins('onQueryBuilt', $this, 'list', $args);
		$query = $args->query;

		return $query;
	}

	/**
	 * Add the slug field to the select fields, called from buildQuerySelect()
	 *
	 * @param   array &$fields fields
	 *
	 * @since 3.0.6
	 *
	 * @return  void
	 */

	private function selectSlug(&$fields)
	{
		$formModel = $this->getFormModel();
		$item      = $this->getTable();
		$pk        = FabrikString::safeColName($item->get('list.db_primary_key'));
		$params    = $this->getParams();

		if (in_array($this->outputFormat, array('raw', 'html', 'feed', 'pdf', 'phocapdf')))
		{
			$slug        = $params->get('sef-slug');
			$raw         = String::substr($slug, String::strlen($slug) - 4, 4) == '_raw' ? true : false;
			$slug        = FabrikString::rtrimword($slug, '_raw');
			$slugElement = $formModel->getElement($slug);

			if ($slugElement)
			{
				$slug     = $slugElement->getSlugName($raw);
				$slug     = FabrikString::safeColName($slug);
				$fields[] = "CONCAT_WS(':', $pk, $slug) AS slug";
			}
			else
			{
				if ($pk !== '``')
				{
					$fields[] = $pk . ' AS slug';
				}
			}
		}
	}

	/**
	 * Get the select part of the query
	 *
	 * @param   string         $mode  List/form - effects which elements are selected
	 * @param   JDatabaseQuery $query Joomla Query builder
	 *
	 * @return  mixed  string if $query = false, otherwise $query
	 */

	public function buildQuerySelect($mode = 'list', JDatabaseQuery $query)
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('queryselect: start') : null;
		$db    = $this->getDb();
		$form  = $this->getFormModel();
		$table = $this->getTable();
		$form->getGroupsHierarchy();
		JDEBUG ? $profiler->mark('queryselect: fields load start') : null;
		$fields = $this->getAsFields($mode);
		$pk     = FabrikString::safeColName($table->get('list.db_primary_key'));
		$params = $this->getParams();
		$this->selectSlug($fields);
		JDEBUG ? $profiler->mark('queryselect: fields loaded') : null;
		$sfields = (empty($fields)) ? '' : implode(", \n ", $fields) . "\n ";

		/**
		 * Testing potential fix for FOUND_ROWS performance issue on large tables.  If merging,
		 * we never do a SELECT FOUND_ROWS(), so no need to use SQL_CALC_FOUND_ROWS.
		 */
		$calc_found_rows = $this->mergeJoinedData() ? '' : 'SQL_CALC_FOUND_ROWS';

		/**
		 * Distinct creates a temporary table which may slow down queries.
		 * Added advanced option to toggle it on/off
		 * http://fabrikar.com/forums/index.php?threads/bug-distinct.39160/#post-196739
		 */
		$distinct = $params->get('distinct', true) ? 'DISTINCT' : '';

		// $$$rob added raw as an option to fix issue in saving calendar data
		$query->select($calc_found_rows . ' ' . $distinct . ' ' . $sfields);

		if (trim($table->get('list.db_primary_key')) != '' && (in_array($this->outputFormat, array('raw', 'html', 'feed', 'pdf', 'phocapdf', 'csv', 'word', 'yql'))))
		{
			$query->select($pk . ' AS ' . $db->qn('__pk_val'));
		}

		$query->from($db->qn($table->get('list.db_table_name')));
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('onBuildQuerySelect', $this, 'list', $query);

		return $query;
	}

	/**
	 * Get the part of the sql statement that orders the table data
	 * Since 3.0.7 caches the results as calling orderBy twice when using single ordering in admin module anules the
	 * user selected order by
	 *
	 * @param   JDatabaseQuery $query False or a query object
	 *
	 * @throws ErrorException
	 *
	 * @return  mixed  string or query object - Ordering part of sql statement
	 */
	public function buildQueryOrder(JDatabaseQuery $query)
	{
		$params                    = $this->getParams();
		$input                     = $this->app->input;
		$formModel                 = $this->getFormModel();
		$item                      = $this->getTable();
		$db                        = $this->getDb();
		$this->selectedOrderFields = array();

		if ($this->outputFormat == 'fabrikfeed' || $this->outputFormat == 'feed')
		{
			$dateColId      = (int) $params->get('feed_date', 0);
			$dateColElement = $formModel->getElement($dateColId, true);
			$dateCol        = $db->qn($dateColElement->getFullName(false, false, false));

			if ($dateColId !== 0)
			{
				$this->order_dir = 'DESC';
				$this->order_by  = $dateCol;
				$query->order($dateCol . ' DESC');

				return $query;
			}
		}

		$session = JFactory::getSession();

		/**
		 * When list reordered the controller runs order() and
		 * stores the order settings in the session by calling setOrderByAndDir()
		 * it then redirects to the list view and here all we need to do it get
		 * those order settings from the session
		 */

		$elements = $this->getElements();

		// Build the order by statement from the session
		$this->orderEls = $this->orderDirs = array();

		$clearOrdering  = (bool) $input->getInt('clearordering', false) && $input->get('task') !== 'order';
		$singleOrdering = $this->singleOrdering();

		foreach ($elements as $element)
		{
			$context = 'com_fabrik.list' . $this->getRenderContext() . '.order.' . $element->getElement()->get('id');

			if ($clearOrdering)
			{
				$session->set($context, null);
			}
			else
			{
				// $$$tom Added single-ordering option
				if (!$singleOrdering || ($singleOrdering && $element->getElement()->get('id') == $input->getInt('orderby')))
				{
					$dir = $session->get($context);

					if ($dir != '' && $dir != '-' && trim($dir) != 'Array')
					{
						$orderByName       = FabrikString::safeNameq($element->getOrderByName(), false);
						$this->orderEls[]  = $orderByName;
						$this->orderDirs[] = $dir;
						$element->getAsField_html($this->selectedOrderFields, $aAsFields);

						$query->order($orderByName . ' ' . $dir);
					}
				}
				else
				{
					$session->set($context, null);
				}
			}
		}

		// If nothing found in session use default ordering (or that set by querystring)
		if (empty($this->orderEls))
		{
			$orderBys = explode(',', $input->getString('order_by', $input->getString('orderby', '')));

			if ($orderBys[0] == '')
			{
				$orderBys = $item->get('list.order_by');
			}

			// $$$ not sure why, but sometimes $orderBys is NULL at this point.
			if (!isset($orderBys))
			{
				$orderBys = array();
			}
			// Covert ids to names (were stored as names but then stored as ids)
			foreach ($orderBys as &$orderBy)
			{
				if (is_numeric($orderBy))
				{
					$elementModel = $formModel->getElement($orderBy, true);
					$orderBy      = $elementModel ? $elementModel->getOrderByName() : $orderBy;
				}
			}

			$orderDirs = explode(',', $input->getString('order_dir', $input->getString('orderdir', '')));

			if ($orderDirs[0] == '')
			{
				$orderDirs = $item->get('list.order_dir');
			}

			$els = $this->getElements('filtername');

			if (!empty($orderBys))
			{
				$bits = array();
				$o    = 0;

				foreach ($orderBys as $orderByRaw)
				{
					$dir = ArrayHelper::getValue($orderDirs, $o, 'desc');

					// As we use getString() for query string, need to sanitize
					if (!in_array(strtolower($dir), array('asc', 'desc', '-')))
					{
						throw new ErrorException('invalid order direction: ' . $dir, 500);
					}

					if ($orderByRaw !== '' && $dir != '-')
					{
						// $$$ hugh - getOrderByName can return a CONCAT, ie join element ...

						/*
						 * $$$ hugh - OK, we need to test for this twice, because older elements
						 * which get converted form names to ids above have already been run through
						 * getOrderByName().  So first check here ...
						 */
						if (!String::stristr($orderByRaw, 'CONCAT(') && !String::stristr($orderByRaw, 'CONCAT_WS('))
						{
							$orderByRaw = FabrikString::safeColName($orderByRaw);

							if (array_key_exists($orderByRaw, $els))
							{
								$field = $els[$orderByRaw]->getOrderByName();
								/*
								 * $$$ hugh - ... second check for CONCAT, see comment above
								 * $$$ @TODO why don't we just embed this logic in safeColName(), so
								 * it recognizes a CONCAT and treats it accordingly?
								 */
								if (!String::stristr($field, 'CONCAT(') && !String::stristr($field, 'CONCAT_WS('))
								{
									$field = FabrikString::safeColName($field);
								}

								$bits[]            = " $field $dir";
								$this->orderEls[]  = $field;
								$this->orderDirs[] = $dir;
							}
							else
							{
								if (strstr($orderByRaw, '_raw`'))
								{
									$orderByRaw = FabrikString::safeColNameToArrayKey($orderByRaw);
								}

								$bits[]            = " $orderByRaw $dir";
								$this->orderEls[]  = $orderByRaw;
								$this->orderDirs[] = $dir;
							}
						}
						else
						{
							// If it was a CONCAT(), just add it with no other checks or processing
							$bits[]            = " $orderByRaw $dir";
							$this->orderEls[]  = $orderByRaw;
							$this->orderDirs[] = $dir;
						}
					}

					$o++;
				}

				if (!empty($bits))
				{
					$query->order(implode(',', $bits));
				}
			}
		}
		/* apply group ordering
		 * @TODO - explain something to hugh!  Why is this "group ordering"?  AFAICT, it's just a secondary
		* order by, isn't specific to the Group By feature in any way?  So why not just put this option in
		*/
		$groupOrderBy = $params->get('group_by_order');

		if ($groupOrderBy != '')
		{
			$groupOrderDir = $params->get('group_by_order_dir');
			$orderBy       = strstr($groupOrderBy, '_raw`') ? FabrikString::safeColNameToArrayKey($groupOrderBy) : FabrikString::safeColName($groupOrderBy);

			$query->order($orderBy . ' ' . $groupOrderDir);

			$this->orderEls[]  = $orderBy;
			$this->orderDirs[] = $groupOrderDir;
		}

		$this->orderBy = $query;

		return $this->orderBy;
	}

	/**
	 * Should we order on multiple elements or one
	 *
	 * @since   3.0.7 (refactored from buildQueryOrder())
	 *
	 * @return  bool
	 */

	public function singleOrdering()
	{
		$params = $this->getParams();

		if ($params->get('enable_single_sorting', 'default') == 'default')
		{
			// Use global
			$fbConfig       = JComponentHelper::getParams('com_fabrik');
			$singleOrdering = $fbConfig->get('enable_single_sorting', false);
		}
		else
		{
			$singleOrdering = $params->get('enable_single_sorting', false);
		}

		return $singleOrdering;
	}

	/**
	 * Called when the table column order by is clicked
	 * store order options in session
	 *
	 * @return  void
	 */

	public function setOrderByAndDir()
	{
		$package      = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$session      = JFactory::getSession();
		$input        = $this->app->input;
		$postOrderBy  = $input->getInt('orderby', '');
		$postOrderDir = $input->get('orderdir', '');
		$orders       = array('asc', 'desc', '-');
		$id           = $this->getRenderContext();

		if (in_array($postOrderDir, $orders))
		{
			$context = 'com_' . $package . '.list' . $id . '.order.' . $postOrderBy;
			$session->set($context, $postOrderDir);
		}
	}

	/**
	 * Get the part of the sql query that creates the joins
	 * used when building the table's data
	 *
	 * @param   JDatabaseQuery $query JQuery object or false
	 *
	 * @return  mixed  string or join query - join sql
	 */

	public function buildQueryJoin(JDatabaseQuery $query)
	{
		if (!is_null($this->joinsSQL))
		{
			return $this->joinsSQL;
		}

		$db               = Worker::getDbo();
		$statements       = array();
		$table            = $this->getTable();
		$selectedTables[] = $table->get('list.db_table_name');
		$return           = array();
		$joins            = ($this->get('includeCddInJoin', true) === false) ? $this->getJoinsNoCdd() : $this->getJoins();

		foreach ($joins as $join)
		{
			// Used to bypass user joins if the table connect is not the Joomla connection
			if ((int) $join->canUse === 0)
			{
				continue;
			}

			if ($join->join_type == '')
			{
				$join->join_type = 'LEFT';
			}

			$sql = String::strtoupper($join->join_type) . ' JOIN ' . $db->qn($join->table_join);
			$k   = FabrikString::safeColName($join->keytable . '.' . $join->table_key);

			// Check we only get the field name
			$join->table_join_key = explode('.', $join->table_join_key);
			$join->table_join_key = array_pop($join->table_join_key);

			if ($join->table_join_alias == '')
			{
				$on = FabrikString::safeColName($join->table_join . '.' . $join->table_join_key);
				$sql .= ' ON ' . $on . ' = ' . $k;
			}
			else
			{
				$on = FabrikString::safeColName($join->table_join_alias . '.' . $join->table_join_key);
				$sql .= ' AS ' . FabrikString::safeColName($join->table_join_alias) . ' ON ' . $on . ' = ' . $k . "\n";
			}

			/*
			 * @FIXME - need to work out where the COLLATE needs to go. Was at the end of builQuery, but that creates
			 * an invalid query if we had any grouping.  As it only applies to joined tables, tried putting it here,
			 * but still get a query error.  Needs to be fixed, but I have to commit to get some other changes done.
			 */
			/*
			$params = $this->getParams();

			if ($params->get('force_collate', '') !== '')
			{
				$sql .= ' COLLATE ' . $params->get('force_collate', '') . ' ';
			}
			*/

			/* Try to order join statements to ensure that you are selecting from tables that have
			 * already been included (either via a previous join statement or the table select statement)
			*/
			if (in_array($join->keytable, $selectedTables))
			{
				$return[]         = $sql;
				$selectedTables[] = $join->table_join;
			}
			else
			{
				// Didn't find anything so defer it till later

				/* $statements[$join->keytable] = $sql;
				 * $$$rob - sometimes the keytable is the same for 2 deferred joins
				* in this case the first join is incorrectly overwritten in the $statements array
				* keying on join->id should solve this
				*/
				$statements[$join->id] = array($join->keytable, $sql);
			}

			// Go through the deferred join statements and see if their table has now been selected
			foreach ($statements as $joinid => $ar)
			{
				$t = $ar[0];
				$s = $ar[1];

				if (in_array($t, $selectedTables))
				{
					if (!in_array($s, $return))
					{
						// $$$rob test to avoid duplicate join queries
						$return[] = $s;
						unset($statements[$t]);
					}
				}
			}
		}
		// $$$rob test for bug #376
		foreach ($statements as $joinid => $ar)
		{
			$s = $ar[1];

			if (!in_array($s, $return))
			{
				$return[] = $s;
			}
		}

		if ($query !== false)
		{
			foreach ($return as $r)
			{
				$words     = explode(' ', trim($r));
				$type      = array_shift($words);
				$statement = str_replace('JOIN', '', implode(' ', $words));
				$query->join($type, $statement);
			}

			return $query;
		}

		$this->joinsSQL = (string) $query;

		return $query;
	}

	/**
	 * Build query prefilter where part
	 *
	 * @param   object $element model
	 *
	 * @return  string
	 */

	public function buildQueryPrefilterWhere($element)
	{
		$elementName = FabrikString::safeColName($element->getFullName(false, false));
		$filters     = $this->getFilterArray();
		$keys        = array_keys($filters);
		$valueKeys   = array_keys(ArrayHelper::getValue($filters, 'value', array()));

		foreach ($valueKeys as $i)
		{
			if ($filters['search_type'][$i] != 'prefilter' || $filters['key'][$i] != $elementName)
			{
				foreach ($keys as $key)
				{
					unset($filters[$key][$i]);
				}
			}
		}

		list($sqlNoFilter, $sql) = $this->_filtersToSQL($filters);
		$where = str_replace('WHERE', '', $sql);

		if ($where != '')
		{
			$where = ' AND ' . $where;
		}

		return $where;
	}

	/**
	 * Get the part of the main query that provides a group by statement
	 * only added by 'count' element plug-in at the moment
	 *
	 * @param   JDatabaseQuery $query
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQueryGroupBy(JDatabaseQuery $query)
	{
		$groups        = $this->getFormModel()->getGroupsHierarchy();
		$pluginManager = Worker::getPluginManager();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$res = $elementModel->getGroupByQuery();

				if ($res != '')
				{
					$this->pluginQueryGroupBy[] = $res;
				}
			}
		}

		if (!empty($this->pluginQueryGroupBy))
		{
			$pluginManager->runPlugins('onBuildQueryGroupBy', $this, 'list', $query);
			$query->group($this->pluginQueryGroupBy);

			return $query;
		}

		$pluginManager->runPlugins('onBuildQueryGroupBy', $this, 'list', $query);

		return $query;
	}

	/**
	 * Get the part of the sql query that relates to the where statement
	 *
	 * @param   bool           $incFilters   if true the SQL contains any filters
	 *                                       if false only contains prefilter sql
	 * @param   JDatabaseQuery $query        if false return the where as a string
	 *                                       if a db query object, set the where clause
	 *                                       Paul 2013-07-20 Add join parameter to limit where clause to main table if
	 *                                       needed
	 * @param   bool           $doJoins      include where clauses for joins?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQueryWhere($incFilters = true, JDatabaseQuery $query, $doJoins = true)
	{
		$pluginManager = Worker::getPluginManager();
		$pluginManager->runPlugins('onBuildQueryWhere', $this, 'list');

		$sig = (int) $incFilters;

		if (isset($this->_whereSQL[$sig]))
		{
			return $this->_whereSQL[$sig][$incFilters];
		}

		$filters = $this->getFilterArray();

		/* $$$ hugh - added option to 'require filtering', so if no filters specified
		 * we return an empty table.  Only do this where $inFilters is set, so we're only doing this
		* on the main row count and data fetch, and things like
		* filter dropdowns still get built.
		*/

		if ($incFilters && !$this->gotAllRequiredFilters())
		{
			$query->where('1 = -1');

			return $query;
		}

		$groups = $this->getFormModel()->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			if ($doJoins || (!$doJoins && $groupModel->isJoin()))
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					$elementModel->appendTableWhere($this->pluginQueryWhere);
				}
			}
		}

		if (empty($filters))
		{
			// $$$ hugh - testing hack for plugins to add WHERE clauses
			if (!empty($this->pluginQueryWhere))
			{
				$query->where(implode(' AND ', $this->pluginQueryWhere));

				return $query;
			}
			else
			{
				return $query;
			}
		}

		list($sqlNoFilter, $sql) = $this->_filtersToSQL($filters);
		$this->_whereSQL[$sig] = array('0' => $sqlNoFilter, '1' => $sql);

		if (!empty($this->_whereSQL[$sig][$incFilters]))
		{
			$query->where($this->_whereSQL[$sig][$incFilters]);
		}

		return $query;
	}

	/**
	 * Used by buildQueryWhere and buildQueryPrefilterWhere
	 * takes a filter array and returns the SQL
	 *
	 * @param   array &$filters filters
	 *
	 * @return  array    nofilter, filter sql
	 */

	private function _filtersToSQL(&$filters)
	{
		$prefilters  = $this->groupFilterSQL($filters, 'prefilter');
		$postFilters = $this->groupFilterSQL($filters);

		if (!empty($prefilters) && !empty($postFilters))
		{
			array_unshift($postFilters, 'AND');
		}

		$sql              = array_merge($prefilters, $postFilters);
		$pluginQueryWhere = trim(implode(' AND ', $this->pluginQueryWhere));

		if ($pluginQueryWhere !== '')
		{
			$pluginQueryWhere = '(' . $pluginQueryWhere . ')';

			if (!empty($sql))
			{
				$sql[] = ' AND ';
			}

			if (!empty($prefilters))
			{
				$prefilters[] = ' AND ';
			}

			$sql[]        = $pluginQueryWhere;
			$prefilters[] = $pluginQueryWhere;
		}

		$sql        = implode($sql, ' ');
		$prefilters = implode($prefilters, ' ');

		return array($prefilters, $sql);
	}

	/**
	 * Parse the filter array and return an array of words that will make up part of the filter query
	 *
	 * @param   array  &$filters filters
	 * @param   string $type     * = filters, 'prefilter' = get prefilter only
	 *
	 * @return  array    words making up sql query.
	 */

	private function groupFilterSQL(&$filters, $type = '*')
	{
		$groupedCount = 0;
		$ingroup      = false;
		$sql          = array();

		// $$$ rob keys may no longer be in asc order as we may have filtered out some in buildQueryPrefilterWhere()
		$valueKeys             = array_keys(ArrayHelper::getValue($filters, 'key', array()));
		$last_i                = false;
		$nullElementConditions = array('IS NULL', 'IS NOT NULL');

		while (list($vkey, $i) = each($valueKeys))
		{
			// $$$rob - prefilter with element that is not published so ignore
			$condition = String::strtoupper(ArrayHelper::getValue($filters['condition'], $i, ''));

			if (ArrayHelper::getValue($filters['sqlCond'], $i, '') == '' && !in_array($condition, $nullElementConditions))
			{
				$last_i = $i;
				continue;
			}

			if ($filters['search_type'][$i] == 'prefilter' && $type == '*')
			{
				$last_i = $i;
				continue;
			}

			if ($filters['search_type'][$i] != 'prefilter' && $type == 'prefilter')
			{
				$last_i = $i;
				continue;
			}

			$n = current($valueKeys);

			if ($n === false)
			{
				// End of array
				$n = -1;
			}

			$gstart = '';
			$gend   = '';

			if (!in_array($condition, $nullElementConditions))
			{
				$filters['origvalue'][$i] = 'this is ignoerd i hope';
			}
			// $$$ rob added $filters['sqlCond'][$i] test so that you can test for an empty string
			if ($filters['origvalue'][$i] != '' || $filters['sqlCond'][$i] != '')
			{
				if (array_key_exists($n, $filters['grouped_to_previous']))
				{
					if ($filters['grouped_to_previous'][$n] == 1)
					{
						if (!$ingroup)
						{
							// Search all filter after a prefilter - alter 'join' value to 'AND'
							$gstart = '(';
							$groupedCount++;
						}

						$ingroup = true;
					}
					else
					{
						if ($ingroup)
						{
							$gend = ')';
							$groupedCount--;
							$ingroup = false;
						}
					}
				}
				else
				{
					if ($ingroup)
					{
						$gend = ')';
						$groupedCount--;
						$ingroup = false;
					}
				}

				$glue  = ArrayHelper::getValue($filters['join'], $i, 'AND');
				$sql[] = empty($sql) ? $gstart : $glue . ' ' . $gstart;
				$sql[] = $filters['sqlCond'][$i] . $gend;
			}

			$last_i = $i;
		}
		// $$$rob ensure opening and closing parathethis for prefilters are equal
		// Seems to occur if you have 3 prefilters with 2nd = grouped/AND and 3rd grouped/OR

		if ($groupedCount > 0)
		{
			$sql[] = str_pad('', (int) $groupedCount, ")");
		}
		// Wrap in brackets
		if (!empty($sql))
		{
			array_unshift($sql, '(');
			$sql[] = ')';
		}

		return $sql;
	}

	/**
	 * Get a list of the tables columns' order by field names
	 *
	 * @deprecated - dont think its used
	 *
	 * @return  array    order by names
	 */

	public function getOrderByFields()
	{
		if (is_null($this->orderByFields))
		{
			$this->orderByFields = array();
		}

		$form   = $this->getFormModel();
		$groups = $form->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$this->orderByFields[] = $elementModel->getOrderByName();
			}
		}

		return $this->orderByFields;
	}

	/**
	 * Get the elements that are included in the search all query
	 *
	 * @return  array  search all fields
	 */

	public function getSearchAllFields()
	{
		$profiler = JProfiler::getInstance('Application');

		if (isset($this->searchAllAsFields))
		{
			return $this->searchAllAsFields;
		}

		$searchAllFields         = array();
		$this->searchAllAsFields = array();
		$form                    = $this->getFormModel();
		$table                   = $this->getTable();
		$aJoinObjs               = $this->getJoins();
		$groups                  = $form->getGroupsHierarchy();
		$gkeys                   = array_keys($groups);
		$opts                    = array('inc_raw' => false);
		$mode                    = $this->getParams()->get('search-mode-advanced');

		foreach ($gkeys as $x)
		{
			$groupModel    = $groups[$x];
			$elementModels = $groupModel->getPublishedElements();

			for ($ek = 0; $ek < count($elementModels); $ek++)
			{
				$elementModel = $elementModels[$ek];

				if ($elementModel->includeInSearchAll($mode))
				{
					// Boolean search doesnt seem possible on encrypted fields.
					$p = $elementModel->getParams();
					$o = $p->get('encrypt');
					$p->set('encrypt', false);
					$elementModel->getAsField_html($this->searchAllAsFields, $searchAllFields, $opts);
					$p->set('encrypt', $o);
				}
			}
		}

		$db = Worker::getDbo();

		// If the group by element isnt in the fields (IE its not published) add it (otherwise group by wont work)
		$longGroupBy = $db->qn($this->getGroupBy());

		if (!in_array($longGroupBy, $searchAllFields) && trim($table->group_by) != '')
		{
			$this->searchAllAsFields[] = FabrikString::safeColName($this->getGroupBy()) . ' AS ' . $longGroupBy;
			$searchAllFields[]         = $longGroupBy;
		}

		for ($x = 0; $x < count($this->searchAllAsFields); $x++)
		{
			$match = ' AS ' . $searchAllFields[$x];

			if (array_key_exists($x, $this->searchAllAsFields))
			{
				$this->searchAllAsFields[$x] = trim(str_replace($match, '', $this->searchAllAsFields[$x]));
			}
		}

		$this->searchAllAsFields = array_unique($this->searchAllAsFields);

		return $this->searchAllAsFields;
	}

	/**
	 * Get the part of the table sql statement that selects which fields to load
	 *
	 * @param   string $mode list/form - effects which elements are selected
	 *
	 * @return  array    field names to select in getelement data sql query
	 */

	protected function &getAsFields($mode = 'list')
	{
		$profiler = JProfiler::getInstance('Application');

		if (isset($this->asfields))
		{
			return $this->asfields;
		}

		$this->fields             = array();
		$this->asfields           = array();
		$db                       = Worker::getDbo(true);
		$form                     = $this->getFormModel();
		$table                    = $this->getTable();
		$aJoinObjs                = $this->getJoins();
		$this->temp_db_key_addded = false;
		$groups                   = $form->getGroupsHierarchy();

		$gkeys = array_keys($groups);

		foreach ($gkeys as $x)
		{
			$groupModel = $groups[$x];

			if ($groupModel->canView($mode) !== false)
			{
				$elementModels = $mode === 'list' ? $groupModel->getListQueryElements() : $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					$method = 'getAsField_' . $this->outputFormat;

					if (!method_exists($elementModel, $method))
					{
						$method = 'getAsField_html';
					}

					$elementModel->$method($this->asfields, $this->fields);
				}
			}
		}

		/**
		 * temporaraily add in the db key so that the edit links work, must remove it before final return
		 * of getData();
		 */
		JDEBUG ? $profiler->mark('getAsFields: starting to test if a view') : null;

		if (!$this->isView())
		{
			if (!$this->temp_db_key_addded && $table->get('list.db_primary_key') != '')
			{
				$str            = FabrikString::safeColName($table->get('list.db_primary_key')) . ' AS ' . FabrikString::safeColNameToArrayKey($table->get('list.db_primary_key'));
				$this->fields[] = $db->qn(FabrikString::safeColNameToArrayKey($table->get('list.db_primary_key')));
			}
		}

		JDEBUG ? $profiler->mark('getAsFields: end of view test') : null;

		// For raw data in packages
		if ($this->outputFormat == 'raw')
		{
			$str            = FabrikString::safeColName($table->get('list.db_primary_key')) . ' AS __pk_val';
			$this->fields[] = $str;
		}

		$this->group_by_added = false;

		// If the group by element isnt in the fields (IE its not published) add it (otherwise group by wont work)
		$longGroupBy = $this->getGroupByName();

		if (!in_array($longGroupBy, $this->fields) && trim($longGroupBy) != '')
		{
			$this->asfields[]     = FabrikString::safeColName($longGroupBy) . ' AS ' . $longGroupBy;
			$this->fields         = $longGroupBy;
			$this->group_by_added = true;
		}

		return $this->asfields;
	}

	/**
	 * Get the group by element regardless of wheter it was stored as id or string
	 *
	 * @since 3.0.7
	 *
	 * @return  plgFabrik_Element
	 */
	protected function getGroupByElement()
	{
		$item      = $this->getTable();
		$formModel = $this->getFormModel();
		$groupBy   = $this->app->input->get('group_by', $item->get('list.group_by'), 'string');

		return $formModel->getElement($groupBy, true);
	}

	/**
	 * Get group by field name
	 *
	 * @since 3.0.7
	 *
	 * @return mixed false or name
	 */

	protected function getGroupByName()
	{
		$db           = $this->getDb();
		$elementModel = $this->getGroupByElement();

		if (!$elementModel)
		{
			return false;
		}

		$groupBy = $elementModel->getFullName(true, false);

		return $db->qn(FabrikString::safeColNameToArrayKey($groupBy));
	}

	/**
	 * Checks if the params object has been created and if not creates and returns it
	 *
	 * @return  object    params
	 */

	public function getParams()
	{
		$item = $this->getTable();

		if (!isset($this->params))
		{
			$this->params = new JRegistry($item->get('list.params'));
		}

		return $this->params;
	}

	/**
	 * Method to set the list id
	 *
	 * @param   string $id list ID
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		parent::setId($id);
		$this->renderContext = '';

		// $$$ rob not sure why but we need this getState() here when assigning id from admin view
		$this->setRenderContext($id);
		$this->getState();
	}

	/**
	 * Set the table object
	 *
	 * @param   object $table db row
	 *
	 * @return   void
	 */

	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * unset the table object
	 *
	 * @return void
	 */

	public function clearTable()
	{
		unset($this->table);
	}

	/**
	 * Load the database object associated with the list
	 *
	 * @return  \JDatabaseDriver    database
	 */

	public function getDb()
	{
		// @todo - getTable needs to be converted to just getting the json object
		$row = $this->getTable();
		$cnn = Worker::getConnection($row);

		return $cnn->getDb();
	}

	/**
	 * Get the lists connection object
	 * sets $this->connection to the lists connection
	 *
	 * @deprecated since 3.0b use Worker::getConnection() instead
	 *
	 * @return  object    connection
	 */

	public function &getConnection()
	{
		$this->connection = Worker::getConnection($this->getTable());

		return $this->connection;
	}

	/**
	 *Is the table published
	 * Dates are stored as UTC so we can compare them against a date with no offset applied
	 *
	 * @return  bool    published state
	 */

	public function canPublish()
	{
		$item        = $this->getTable();
		$db          = Worker::getDbo();
		$nullDate    = $db->getNullDate();
		$up          = $item->get('list.publish_up');
		$down        = $item->get('list.publish_down');
		$publishUp   = JFactory::getDate($up);
		$publishUp   = $publishUp->toUnix();
		$publishDown = JFactory::getDate($down);
		$publishDown = $publishDown->toUnix();
		$now         = JFactory::getDate()->toUnix();

		if ($item->get('list.published') == '1')
		{
			if ($now >= $publishUp || $up == '' || $up == $nullDate)
			{
				if ($now <= $publishDown || $down == '' || $down == $nullDate)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Access control to determine if the current user has rights to drop data
	 * from the table
	 *
	 * @return  bool    yes/no
	 */

	public function canEmpty()
	{
		if (!array_key_exists('allow_drop', $this->access))
		{
			$groups                   = $this->user->getAuthorisedViewLevels();
			$this->access->allow_drop = in_array($this->getParams()->get('allow_drop'), $groups);
		}

		return $this->access->allow_drop;
	}

	/**
	 * Check if the user can view the detailed records
	 *
	 * @return  bool
	 */

	public function canViewDetails()
	{
		if (!array_key_exists('viewdetails', $this->access))
		{
			$groups                    = $this->user->getAuthorisedViewLevels();
			$this->access->viewdetails = in_array($this->getParams()->get('allow_view_details'), $groups);
		}

		return $this->access->viewdetails;
	}

	/**
	 * Checks user access for editing records
	 *
	 * @param   object $row of data currently active
	 *
	 * @return  bool    access allowed
	 */

	public function canEdit($row = null)
	{
		/**
		 * $$$ hugh - FIXME - we really need to split out a onCanEditRow method, rather than overloading
		 * onCanEdit for both table and per-row contexts.  At the moment, we calling per-row plugins with
		 * null $row when canEdit() is called in a table context.
		 */

		/**
		 * Find out what any plugins have to say
		 */
		$pluginCanEdit = Worker::getPluginManager()->runPlugins('onCanEdit', $this, 'list', $row);

		// At least one plugin run, so plugin results take precedence over anything else.
		if (!empty($pluginCanEdit))
		{
			// If one plugin returns false then return false.
			return in_array(false, $pluginCanEdit) ? false : true;
		}

		$canUserDo = $this->canUserDo($row, 'allow_edit_details2');

		if ($canUserDo !== -1)
		{
			// $canUserDo expressed a boolean preference, so use that
			return $canUserDo;
		}

		if (!array_key_exists('edit', $this->access))
		{
			$groups             = $this->user->getAuthorisedViewLevels();
			$this->access->edit = in_array($this->getParams()->get('allow_edit_details'), $groups);
		}

		// Plugins didn't override, canuserDo() didn't express a preference, so return standard ACL
		return $this->access->edit;
	}

	/**
	 * Checks if any one row is editable = used to get the correct headings
	 *
	 * @return  bool
	 */

	protected function canEditARow()
	{
		$data = $this->getData();

		foreach ($data as $rows)
		{
			foreach ($rows as $row)
			{
				if ($this->canEdit($row))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Access control function for determining if the user can perform
	 * a designated function on a specific row
	 *
	 * @param   object $row data
	 * @param   string $col access control setting to compare against
	 *
	 * @return  mixed    - if ACL setting defined here return bool, otherwise return -1 to contiune with default acl
	 *                   setting
	 */

	protected function canUserDo($row, $col)
	{
		$params = $this->getParams();

		return Worker::canUserDo($params, $row, $col);
	}

	/**
	 * Checks user access for deleting records.
	 *
	 * @param   object $row of data currently active
	 *
	 * @return  bool    access allowed
	 */

	public function canDelete($row = null)
	{
		/**
		 * Find out if any plugins deny delete.  We then allow a plugin to override with 'false' if
		 * if useDo or group ACL allows edit.  But we don't allow plugin to allow, if userDo or group ACL
		 * deny access.
		 */
		$pluginCanDelete = Worker::getPluginManager()->runPlugins('onCanDelete', $this, 'list', $row);
		$pluginCanDelete = !in_array(false, $pluginCanDelete);
		$canUserDo       = $this->canUserDo($row, 'allow_delete2');

		if ($canUserDo !== -1)
		{
			// If userDo allows delete, let plugin override
			return $canUserDo ? $pluginCanDelete : $canUserDo;
		}

		if (!array_key_exists('delete', $this->access))
		{
			$groups               = $this->user->getAuthorisedViewLevels();
			$this->access->delete = in_array($this->getParams()->get('allow_delete'), $groups);
		}

		// If group access allows delete, then let plugin override
		return $this->access->delete ? $pluginCanDelete : $this->access->delete;
	}

	/**
	 * Determine if any record can be deleted - used to see if we include the
	 * delete button in the list view
	 *
	 * @return  bool
	 */

	public function deletePossible()
	{
		$data = $this->getData();

		foreach ($data as $rows)
		{
			foreach ($rows as $row)
			{
				if ($this->canDelete($row))
				{
					return true;
				}
			}
		}

		if ($this->listRequiresFiltering() && $this->isAjax() && $this->canDelete())
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks user access for importing csv
	 *
	 * @return  bool  access allowed
	 */

	public function canCSVImport()
	{
		if (!array_key_exists('csvimport', $this->access))
		{
			$groups                  = $this->user->getAuthorisedViewLevels();
			$this->access->csvimport = in_array($this->getParams()->get('csv_import_frontend'), $groups);
		}

		return $this->access->csvimport;
	}

	/**
	 * Checks user access for exporting csv
	 *
	 * @return  bool  access allowed
	 */

	public function canCSVExport()
	{
		if (!array_key_exists('csvexport', $this->access))
		{
			$groups                  = $this->user->getAuthorisedViewLevels();
			$this->access->csvexport = in_array($this->getParams()->get('csv_export_frontend'), $groups);
		}

		return $this->access->csvexport;
	}

	/**
	 * Checks user access for front end group by
	 *
	 * @return  bool  access allowed
	 */

	public function canGroupBy()
	{
		if (!array_key_exists('groupby', $this->access))
		{
			$groups                = $this->user->getAuthorisedViewLevels();
			$this->access->groupby = in_array($this->getParams()->get('group_by_access'), $groups);
		}

		return $this->access->groupby;
	}

	/**
	 * Checks user access for adding records
	 *
	 * @return  bool  access allowed
	 */
	public function canAdd()
	{
		if (!array_key_exists('add', $this->access))
		{
			$input             = $this->app->input;
			$groups            = $this->user->getAuthorisedViewLevels();
			$this->access->add = in_array($this->getParams()->get('allow_add'), $groups);
			$hideAdd           = $input->getBool('hide-add', false);

			if ($hideAdd)
			{
				$this->access->add = false;
			}
		}

		return $this->access->add;
	}

	/**
	 * Check use can view the list
	 *
	 * @return  bool  can view or not
	 */

	public function canView()
	{
		if (!array_key_exists('view', $this->access))
		{
			$groups             = $this->user->getAuthorisedViewLevels();
			$this->access->view = in_array($this->getItem()->get('list.access'), $groups);
		}

		return $this->access->view;
	}

	/**
	 * Like getJoins() but exclude cascading dropdown joins
	 * seems to be needed when calculating related table's record counts.
	 * This is called from within buildQueryJoin()
	 * and fired if this is done:
	 * $listModel->set('includeCddInJoin', false);
	 * as in tableModel::getRecordCounts()
	 *
	 * @return  array  join objects (table rows - not table objects or models)
	 */

	protected function getJoinsNoCdd()
	{
		if (!isset($this->_joinsNoCdd))
		{
			$form = $this->getFormModel();
			$form->getGroupsHierarchy();
			$ignore = array('PlgFabrik_ElementCascadingdropdown');
			$ids    = $form->getElementIds($ignore);
			$db     = Worker::getDbo(true);
			$id     = (int) $this->getId();
			$query  = $db->getQuery(true);
			$query->select('*')->from('#__fabrik_joins')->where('list_id = ' . $id, 'OR');

			if (!empty($ids))
			{
				$query->where('element_id IN (' . implode(", ", $ids) . ')');
			}
			/* maybe we will have to order by element_id asc to ensure that table joins are loaded
			 * before element joins (if an element join is in a table join then its 'join_from_table' key needs to be updated
			 		*/
			$query->order('id');
			$db->setQuery($query);
			$this->_joinsNoCdd = $db->loadObjectList();
			$this->_makeJoinAliases($this->_joinsNoCdd);
		}

		return $this->_joinsNoCdd;
	}

	/**
	 * Merged data queries need to know the joined tables primary key value
	 *
	 * @param   object &$join join
	 *
	 * @since    3.0.6
	 *
	 * @return  void
	 */

	protected function setJoinPk(&$join)
	{
		// FIXME
		throw new Exception('not done for 3.5!');
		$pk = $join->params->get('pk');

		if (!isset($pk))
		{
			$fabrikDb = $this->getDb();
			$db       = Worker::getDbo(true);
			$query    = $db->getQuery(true);
			$pk       = $this->getPrimaryKeyAndExtra($join->table_join);
			$pks      = $join->table_join;
			$pks .= '.' . $pk[0]['colname'];
			$join->params->set('pk', $fabrikDb->qn($pks));
			$query->update('#__fabrik_joins')->set('params = ' . $db->q((string) $join->params))->where('id = ' . (int) $join->id);
			$db->setQuery($query);

			try
			{
				$db->execute();
			} catch (RuntimeException $e)
			{
			}

			$join->params = new JRegistry($join->params);
		}
	}

	/**
	 * Gets the field names for the given table
	 * $$$ hugh - copies this to backend model, so remember to modify that as well, if
	 * you make changes to this one.  Better yet, make it a Helper func that requires
	 * the $tbl arg, as that's the only thing that makes it list model specific.
	 *
	 * @param   string $tbl table name
	 * @param   string $key field to key return array on
	 *
	 * @return  array    table fields
	 */

	public function getDBFields($tbl = null, $key = null)
	{
		if (is_null($tbl))
		{
			$table = $this->getTable();
			$tbl   = $table->get('list.db_table_name');
		}

		if ($tbl == '')
		{
			return array();
		}

		$sig = $tbl . $key;
		$tbl = FabrikString::safeColName($tbl);

		if (!isset($this->dbFields[$sig]))
		{
			$db  = $this->getDb();
			$tbl = FabrikString::safeColName($tbl);
			$db->setQuery("DESCRIBE " . $tbl);

			try
			{
				$this->dbFields[$sig] = $db->loadObjectList($key);
			} catch (RuntimeException $e)
			{
				// List may be in second connection but we might try to get #__user fields for join
				$this->dbFields[$sig] = array();
			}

			foreach ($this->dbFields[$sig] as &$row)
			{
				/**
				 * Boil the type down to just the base type, so "INT(11) UNSIGNED" becomes just "INT"
				 * I'm sure there's other cases than just UNSIGNED I need to deal with, but for now that's
				 * what I most care about, as this stuff is being written handle being more specific about
				 * the elements the list PK can be selected from.
				 */
				$row->BaseType = strtoupper(preg_replace('#(\(\d+\))$#', '', $row->Type));
				$row->BaseType = preg_replace('#(\s+SIGNED|\s+UNSIGNED)#', '', $row->BaseType);
			}
		}

		return $this->dbFields[$sig];
	}

	/**
	 * Called at the end of saving an element
	 * if a new element it will run the sql to add to field,
	 * if existing element and name changed will create query to be used later
	 *
	 * @param   object &$elementModel element model
	 * @param   string $origColName   original column name
	 *
	 * @return  array($update, $q, $oldName, $newdesc, $origDesc, $dropKey)
	 */

	public function shouldUpdateElement(&$elementModel, $origColName = null)
	{
		$return        = array(false, '', '', '', '', false);
		$element       = $elementModel->getElement();
		$pluginManager = Worker::getPluginManager();
		$basePlugIn    = $pluginManager->getPlugIn($element->get('plugin'), 'element');
		$fabrikDb      = $this->getDb();
		$group         = $elementModel->getGroup();
		$dropKey       = false;
		/*$$$ rob - replaced this with getting the table from the group as if we moved the element
		 *from one group to another $this->getTable gives you the old group's table, where as we want
		* the new group's table
		*/
		$table = $group->getlistModel()->getTable();

		// $$$ hugh - if this is a table-less form ... not much point going any
		// further 'cos things will go BANG
		if (empty($table->id))
		{
			return $return;
		}

		if ($this->isView())
		{
			return $return;
		}

		if ($group->isJoin())
		{
			$tableName  = $group->getJoinModel()->getJoin()->table_join;
			$keydata    = $this->getPrimaryKeyAndExtra($tableName);
			$primaryKey = $keydata[0]['colname'];
		}
		else
		{
			$keydata    = $this->getPrimaryKeyAndExtra();
			$tableName  = $table->get('list.db_table_name');
			$primaryKey = $table->get('list.db_primary_key');
		}

		// $$$ rob base plugin needs to know group info for date fields in non-join repeat groups
		$basePlugIn->setGroupModel($elementModel->getGroupModel());

		// The element type AFTER saving
		$type         = $elementModel->getFieldDescription();
		$descriptions = $this->getStorage()->getDBFields($tableName, 'Field');

		if (!$this->canAlterFields() && !$this->canAddFields())
		{
			$type = $descriptions[$origColName]->Type;
		}

		if (is_null($type))
		{
			return $return;
		}

		$existingFields = array_keys($descriptions);
		$lastField      = $existingFields[count($existingFields) - 1];
		$tableName      = FabrikString::safeColName($tableName);
		$lastField      = FabrikString::safeColName($lastField);
		$altered        = false;

		if (!array_key_exists($element->get('name'), $descriptions))
		{
			if ($origColName == '')
			{
				if ($this->canAddFields())
				{
					$fabrikDb
						->setQuery("ALTER TABLE $tableName ADD COLUMN " . FabrikString::safeColName($element->get('name')) . " $type AFTER $lastField");

					try
					{
						$fabrikDb->execute();
						$altered = true;
					} catch (Exception $e)
					{
						throw new ErrorException('alter structure: ' . $fabrikDb->getErrorMsg(), 500);
						$altered = false;
					}
				}
			}
			// Commented out as it stops the update when changing an element name
			// return $return;
		}

		$thisFieldDesc = ArrayHelper::getValue($descriptions, $origColName, new stdClass);

		/* $$$ rob the Default property for timestamps when they are set to CURRENT_TIMESTAMP
		 * doesn't show up from getDBFields()  - so presuming a timestamp field will always default
		* to the current timestamp (update of the field's data controller in the Extra property (on update CURRENT_TIMESTAMP)
				*/
		$existingDef = '';

		if (isset($thisFieldDesc->Type))
		{
			$existingDef = $thisFieldDesc->Type;

			if ($thisFieldDesc->Type == 'timestamp')
			{
				$existingDef .= $thisFieldDesc->Null = 'YES' ? ' NULL' : ' NOT NULL';
				$existingDef .= ' DEFAULT CURRENT_TIMESTAMP';
				$existingDef .= ' ' . $thisFieldDesc->Extra;
			}
		}

		// If its the primary 3.0
		for ($k = 0; $k < count($keydata); $k++)
		{
			if ($keydata[$k]['colname'] == $origColName)
			{
				$existingDef .= ' ' . $keydata[$k]['extra'];
			}
		}
		/* $$$ hugh 2012/05/13 - tweaking things a little so we don't care about certain differences in type.
		 * Initially, just integer types and signed vs unsigned.  So if the existing column is TINYINT(3) UNSIGNED
		* and we think it's INT(3), i.e. that's what getFieldDescription() returns, let's treat those as functionally
		* the same, and not change anything.  Ideally we should turn this into some kind of element model method, so
		* we would do something like $base_existingDef = $elementModel->baseFieldDescription($existingDef), and (say) the
		* field element, if passed "TINYINT(3) UNSIGNED" would return "INT(3)".  But for now, just tweak it here.
		*/
		$typeUpper   = ' ' . String::strtoupper(trim($type)) . ' ';
		$typeUpper   = str_replace(' NOT NULL ', ' ', $typeUpper);
		$typeUpper   = str_replace(' UNSIGNED ', ' ', $typeUpper);
		$typeUpper   = str_replace(array(' INTEGER', ' TINYINT', ' SMALLINT', ' MEDIUMINT', ' BIGINT'), ' INT', $typeUpper);
		$typeUpper   = trim($typeUpper);
		$existingDef = ' ' . String::strtoupper(trim($existingDef)) . ' ';
		$existingDef = str_replace(' UNSIGNED ', ' ', $existingDef);
		$existingDef = str_replace(array(' INTEGER', ' TINYINT', ' SMALLINT', ' MEDIUMINT', ' BIGINT'), ' INT', $existingDef);
		$existingDef = trim($existingDef);

		if ($element->name == $origColName && $existingDef == $typeUpper)
		{
			// No changes to the element name or field type
			return $return;
		}
		elseif ($this->canAlterFields() === false)
		{
			// Give a notice if the user cant alter the field type but selections he has made would normally do so:
			$this->app->enqueueMessage(FText::_('COM_FABRIK_NOTICE_ELEMENT_SAVED_BUT_STRUCTUAL_CHANGES_NOT_APPLIED'), 'notice');

			return $return;
		}

		$return[4]      = $existingDef;
		$existingFields = array_keys($descriptions);
		$lastField      = $existingFields[count($existingFields) - 1];
		$tableName      = FabrikString::safeColName($tableName);
		$lastField      = FabrikString::safeColName($lastField);

		if (empty($origColName) || !in_array($origColName, $existingFields) || ($this->app->input->get('task') === 'save2copy' && $this->canAddFields()))
		{
			if (!$altered)
			{
				if (!in_array($element->get('name'), $existingFields))
				{
					$fabrikDb->setQuery("ALTER TABLE $tableName ADD COLUMN " . FabrikString::safeColName($element->get('name')) . " $type AFTER $lastField");

					try
					{
						$fabrikDb->execute();
					} catch (RuntimeException $e)
					{
						// Don't throw error for attempting to re-add an existing db column
						if (!array_key_exists($element->get('name'), $descriptions))
						{
							throw new ErrorException('alter structure: ' . $fabrikDb->getErrorMsg(), 500);
						}
					}
				}
			}
		}
		else
		{
			// $$$ rob don't alter it yet - lets defer this and give the user the choice if they
			// really want to do this
			if ($this->canAlterFields())
			{
				$origColName = $origColName == null ? $fabrikDb->qn($element->get('name')) : $fabrikDb->qn($origColName);

				if (String::strtolower($type) == 'blob')
				{
					$dropKey = true;
				}

				$q           = 'ALTER TABLE ' . $tableName . ' CHANGE ' . $origColName . ' ' . FabrikString::safeColName($element->get('name')) . ' ' . $type . ' ';
				$testColName = $tableName . '.' . FabrikString::safeColName($element->get('name'));

				if (FabrikString::safeColName($primaryKey) == $tableName . '.' . FabrikString::safeColName($element->get('name')) && $table->auto_inc)
				{
					if (!stripos($q, ' NOT NULL'))
					{
						$q .= ' NOT NULL';
					}

					if (!stripos($q, ' AUTO_INCREMENT'))
					{
						$q .= ' AUTO_INCREMENT';
					}
				}

				$origColName = FabrikString::safeColName($origColName);
				$return[0]   = true;
				$return[1]   = $q;
				$return[2]   = $origColName;
				$return[3]   = $typeUpper;
				$return[5]   = $dropKey;

				return $return;
			}
		}

		return $return;
	}

	/**
	 * Add or update a database column via sql
	 *
	 * @param   object &$elementModel element plugin
	 * @param   string $origColName   origional field name
	 *
	 * @return  bool
	 */

	public function alterStructure(&$elementModel, $origColName = null)
	{
		$element       = $elementModel->getElement();
		$pluginManager = Worker::getPluginManager();
		$basePlugIn    = $pluginManager->getPlugIn($element->get('plugin'), 'element');
		$fabrikDb      = $this->getDb();
		$table         = $this->getTable();
		$tableName     = $table->get('list.db_table_name');

		// $$$ rob base plugin needs to know group info for date fields in non-join repeat groups
		$basePlugIn->setGroupModel($elementModel->getGroupModel());
		$type         = $elementModel->getFieldDescription();
		$descriptions = $this->getStorage()->getDBFields($tableName);

		if (!$this->canAlterFields())
		{
			foreach ($descriptions as $f)
			{
				if ($f->Field == $origColName)
				{
					$type = $f->Type;
				}
			}
		}

		if (!is_null($type))
		{
			foreach ($descriptions as $description)
			{
				$fieldName = String::strtolower($description->Field);

				if (String::strtolower($element->get('name')) == $fieldName && String::strtolower($description->Type) == String::strtolower($type))
				{
					return;
				}

				$existingFields[] = $fieldName;
			}

			$lastField = $fieldName;
			$element->set('name', FabrikString::safeColName($element->get('name')));
			$tableName = FabrikString::safeColName($tableName);
			$lastField = FabrikString::safeColName($lastField);

			if (empty($origColName) || !in_array(String::strtolower($origColName), $existingFields))
			{
				$fabrikDb->setQuery("ALTER TABLE $tableName ADD COLUMN " . $element->get('name') . " $type AFTER $lastField");

				try
				{
					$fabrikDb->execute();
				} catch (Exception $e)
				{
					throw new RuntimeException('alter structure: ' . $e->getMessage());
				}
			}
			else
			{
				if ($this->canAlterFields())
				{
					if ($origColName == null)
					{
						$origColName = $element->get('name');
					}

					$origColName = FabrikString::safeColName($origColName);
					$fabrikDb->setQuery("ALTER TABLE $tableName CHANGE $origColName " . $element->get('name') . ' ' . $type);

					try
					{
						$fabrikDb->execute();
					} catch (Exception $e)
					{
						throw new RuntimeException('alter structure: ' . $e->getMessage());
					}
				}
			}
		}

		return true;
	}

	/**
	 * Can we alter this tables fields structure?
	 *
	 * @return  bool
	 */

	public function canAlterFields()
	{
		$listId = $this->getId();

		if (empty($listId))
		{
			return false;
		}

		$state = $this->alterExisting();

		return $state == 1;
	}

	/**
	 * Get the alter fields setting
	 *
	 * @since    3.0.6
	 *
	 * @return  string    alter fields setting
	 */

	private function alterExisting()
	{
		$params   = $this->getParams();
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$alter    = $params->get('alter_existing_db_cols', 'default');

		if ($alter === 'default')
		{
			$alter = $fbConfig->get('fbConf_alter_existing_db_cols', true);
		}

		return $alter;
	}

	/**
	 * Can we add fields to the list?
	 *
	 * @since    3.0.6
	 *
	 * @return  bool
	 */

	public function canAddFields()
	{
		$state = $this->alterExisting();

		return ($state == 1 || $state == 'addonly');
	}

	/**
	 * If not loaded this loads in the table's form model
	 * also binds a reference of the table to the form.
	 *
	 * @return  object    form model with form table loaded
	 */

	/*public function &getFormModel()
	{
		if (!isset($this->formModel))
		{
			$this->formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
			$table = $this->getTable();
			$this->formModel->setId($table->form_id);
			$this->formModel->getForm();
			$this->formModel->setListModel($this);
		}

		return $this->formModel;
	}*/

	/**
	 * Tests if the table is in fact a view
	 *
	 * @deprecated use storage isView();
	 *
	 * @return  bool    true if table is a view
	 */

	public function isView()
	{
		return $this->getStorage()->isView();
	}

	/**
	 * Store filters in the registry
	 *
	 * @param   array $request filters to store
	 *
	 * @return  void
	 */

	public function storeRequestData($request)
	{
		$package  = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$input    = $this->app->input;
		$session  = JFactory::getSession();
		$registry = $session->get('registry');
		$option   = 'com_' . $package;
		$tid      = 'list' . $this->getRenderContext();

		// Make sure that we only store data thats been entered from this page first test we aren't in a plugin
		if ($input->get('option') == $option && is_object($registry))
		{
			// Don't do this when you are viewing a form or details page as it wipes out the table filters
			$reg = $registry->get('_registry');

			if (isset($reg[$option]) && !in_array($input->get('view'), array('form', 'details')))
			{
				unset($reg[$option]['data']->$tid->filter);
			}
		}

		$context = $option . '.' . $tid . '.filter';

		// @TODO test for _clear_ in values and if so delete session data
		foreach ($request as $key => $val)
		{
			if (is_array($val))
			{
				$key = $context . '.' . $key;
				$this->app->setUserState($key, array_values($val));
			}
		}
	}

	/**
	 * Get the elements to show in the list view
	 *
	 * @since 3.1b2
	 *
	 * @return array
	 */
	private function showInList()
	{
		$input      = $this->app->input;
		$showInList = array();
		$listels    = json_decode(Worker::getMenuOrRequestVar('list_elements', '', $this->isMambot, 'menu'));

		if (isset($listels->show_in_list))
		{
			$showInList = $listels->show_in_list;
		}

		$showInList = (array) $input->get('fabrik_show_in_list', $showInList, 'array');

		// Set it for use by groupModel->getPublishedListElements()
		$input->set('fabrik_show_in_list', $showInList);

		return $showInList;
	}

	/**
	 * Get the prefilter settings from list/module/menu options
	 * Use in listModel::getPrefilterArray() and formModel::getElementIds()
	 *
	 * @return multitype:array
	 */
	public function prefilterSetting()
	{
		$input      = $this->app->input;
		$package    = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$params     = $this->getParams();
		$showInList = $this->showInList();

		// Are we coming from a post request via a module?
		$moduleid   = 0;
		$requestRef = $input->get('listref', '', 'string');

		if ($requestRef !== '' && !strstr($requestRef, 'com_' . $package))
		{
			// If so we need to load in the modules parameters
			$ref = explode('_', $requestRef);

			if (count($ref) > 1)
			{
				$moduleid = (int) array_pop($ref);
				$db       = JFactory::getDbo();
				$query    = $db->getQuery(true);

				if ($moduleid !== 0)
				{
					$this->setRenderContext($moduleid);
					$query->select('params')->from('#__modules')->where('id = ' . $moduleid);
					$db->setQuery($query);
					$obj = json_decode($db->loadResult());

					if (is_object($obj) && isset($obj->prefilters))
					{
						$properties = $obj->prefilters;
					}
				}
			}
		}

		// List prefilter properties
		$elements          = $this->getElements('filtername');
		$filterFields      = (array) $params->get('filter-fields');
		$afilterConditions = (array) $params->get('filter-conditions');
		$filterValues      = (array) $params->get('filter-value');
		$afilterAccess     = (array) $params->get('filter-access');
		$afilterEval       = (array) $params->get('filter-eval');
		$filterJoins       = (array) $params->get('filter-join');
		$filterGrouped     = (array) $params->get('filter-grouped');

		/* If we are rendering as a module dont pick up the menu item options (parmas already set in list module)
		 * so first statement when rendenering a module, 2nd when posting to the component from a module.
		*/
		if (!strstr($this->getRenderContext(), 'mod_fabrik_list') && $moduleid === 0)
		{
			$spoof_check = array(
				'view' => 'list',
				'listid' => $this->getId()
			);
			$properties  = Worker::getMenuOrRequestVar('prefilters', '', $this->isMambot, 'menu', $spoof_check);
		}

		if (isset($properties))
		{
			$prefilters = ArrayHelper::fromObject(json_decode($properties));
			$conditions = (array) $prefilters['filter-conditions'];

			if (!empty($conditions))
			{
				$filterFields      = ArrayHelper::getValue($prefilters, 'filter-fields', array());
				$afilterConditions = ArrayHelper::getValue($prefilters, 'filter-conditions', array());
				$filterValues      = ArrayHelper::getValue($prefilters, 'filter-value', array());
				$afilterAccess     = ArrayHelper::getValue($prefilters, 'filter-access', array());
				$afilterEval       = ArrayHelper::getValue($prefilters, 'filter-eval', array());
				$filterJoins       = ArrayHelper::getValue($prefilters, 'filter-join', array());
			}
		}

		return array($filterFields, $afilterConditions, $filterValues, $afilterAccess, $afilterEval, $filterJoins, $filterGrouped);
	}

	/**
	 * Creates array of prefilters
	 * Set to public 15/04/2013
	 *
	 * @param   array &$filters filters
	 *
	 * @return  array    prefilters combinde with filters
	 */

	public function getPrefilterArray(&$filters)
	{
		if (!isset($this->prefilters))
		{
			$elements = $this->getElements('filtername', false, false);
			list($filterFields, $afilterConditions, $filterValues, $afilterAccess, $afilterEval, $filterJoins, $filterGrouped) = $this->prefilterSetting();

			for ($i = 0; $i < count($filterFields); $i++)
			{
				if (!array_key_exists(0, $filterJoins) || $filterJoins[0] == '')
				{
					$filterJoins[0] = 'AND';
				}

				$join = ArrayHelper::getValue($filterJoins, $i, 'AND');

				if (trim(String::strtolower($join)) == 'where')
				{
					$join = 'AND';
				}

				$filter        = $filterFields[$i];
				$condition     = $afilterConditions[$i];
				$selValue      = ArrayHelper::getValue($filterValues, $i, '');
				$filterEval    = ArrayHelper::getValue($afilterEval, $i, false);
				$filterGrouped = ArrayHelper::getValue($filterGrouped, $i, false);
				$selAccess     = $afilterAccess[$i];

				if (!$this->mustApplyFilter($selAccess))
				{
					continue;
				}

				$raw          = preg_match("/_raw$/", $filter) > 0;
				$tmpFilter    = $raw ? FabrikString::rtrimword($filter, '_raw') : $filter;
				$elementModel = ArrayHelper::getValue($elements, FabrikString::safeColName($tmpFilter), false);

				if ($elementModel === false)
				{
					/*
					 * HACK!
					 * http://fabrikar.com/forums/index.php?threads/a-prefilter-has-been-set-up-on-an-unpublished-element.37385/#post-189201
					 * Complex set up of joined group which has a user element which is linked to a parent one. Raw AS field has _0 applied to its name
					 * For this we'll just remove that to find the correct element.
					 */
					$tmpFilter    = str_replace('_0.', '.', $tmpFilter);
					$elementModel = ArrayHelper::getValue($elements, FabrikString::safeColName($tmpFilter), false);
				}

				if ($elementModel && $elementModel->getElement()->get('published') == 0)
				{
					// Include the JLog class.
					jimport('joomla.log.log');

					// Add the logger.
					JLog::addLogger(array('text_file' => 'fabrik.log.php'));

					// Start logging...
					$msg = JText::sprintf('COM_FABRIK_ERR_PREFILTER_NOT_APPLIED', FabrikString::safeColName($tmpFilter));
					JLog::add($msg, JLog::NOTICE, 'com_fabrik');

					$this->app->enqueueMessage($msg, 'notice');
					continue;
				}

				$filters['join'][]                = $join;
				$filters['search_type'][]         = 'prefilter';
				$filters['key'][]                 = $tmpFilter;
				$filters['value'][]               = $selValue;
				$filters['origvalue'][]           = $selValue;
				$filters['sqlCond'][]             = '';
				$filters['no-filter-setup'][]     = null;
				$filters['condition'][]           = $condition;
				$filters['grouped_to_previous'][] = $filterGrouped;
				$filters['eval'][]                = $filterEval;
				$filters['match'][]               = ($condition == 'equals') ? 1 : 0;
				$filters['full_words_only'][]     = 0;
				$filters['label'][]               = '';
				$filters['access'][]              = '';
				$filters['key2'][]                = '';
				$filters['required'][]            = 0;
				$filters['hidden'][]              = false;
				$filters['elementid'][]           = $elementModel !== false ? $elementModel->getElement()->get('id') : 0;
				$filters['raw'][]                 = $raw;
				$this->prefilters                 = true;
			}
		}

		FabrikHelperHTML::debug($filters, 'prefilters');
	}

	/**
	 * Get the total number of records in the table
	 *
	 * @return  int        total number of records
	 */

	public function getTotalRecords()
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		// $$$ rob ensure that the limits are set - otherwise can create monster query
		$this->setLimits();
		$session = JFactory::getSession();
		$context = 'com_' . $package . '.list' . $this->getRenderContext() . '.total';

		if (isset($this->totalRecords))
		{
			$session->set($context, $this->totalRecords);

			return $this->totalRecords;
		}
		// $$$ rob getData() should always be run first
		if (!isset($this->data) || is_null($this->data))
		{
			$this->getData();

			return $this->totalRecords;
		}

		if ($this->mergeJoinedData())
		{
			$this->totalRecords = $this->getJoinMergeTotalRecords();
			$session->set($context, $this->totalRecords);

			return $this->totalRecords;
		}
	}

	/**
	 * Modified version of getTotalRecords() for use when the table join data
	 * is to be merged on the main table's primary key
	 *
	 * @return int total records
	 */

	protected function getJoinMergeTotalRecords()
	{
		$db    = $this->getDb();
		$table = $this->getTable();
		$query = $db->getQuery(true);
		$count = 'DISTINCT ' . $table->get('list.db_primary_key');
		$query->select('COUNT(' . $count . ') AS t ')->from($table->get('list.db_table_name'));
		$query    = $this->buildQueryJoin($query);
		$query    = $this->buildQueryWhere($this->app->input->get('incfilters', 1), $query);
		$query    = $this->buildQueryGroupBy($query);
		$totalSql = (string) $query;
		$totalSql = $this->pluginQuery($totalSql);
		$db->setQuery($totalSql);
		FabrikHelperHTML::debug($totalSql, 'table getJoinMergeTotalRecords');
		$total = $db->loadResult();

		return $total;
	}

	/**
	 * Require the correct pagenav class based on template
	 *
	 * @param   int $total      total
	 * @param   int $limitStart start
	 * @param   int $limit      length of records to return
	 *
	 * @return  object    pageNav
	 */

	public function &getPagination($total = 0, $limitStart = 0, $limit = 0)
	{
		if (!isset($this->nav))
		{
			if ($this->randomRecords)
			{
				$limitStart = $this->getRandomLimitStart();
			}

			$params = $this->getParams();

			$this->nav = new Pagination($total, $limitStart, $limit);

			if ($limit == -1)
			{
				$this->nav->viewAll = true;
			}

			// $$$ rob set the nav link urls to the table action to avoid messed up url links when  doing ranged filters via the querystring
			$this->nav->url           = $this->getTableAction();
			$this->nav->showAllOption = $params->get('showall-records', false);
			$this->nav->setId($this->getId());
			$this->nav->showTotal      = $params->get('show-total', false);
			$item                      = $this->getTable();
			$this->nav->startLimit     = Worker::getMenuOrRequestVar('rows_per_page', $item->get('list.rows_per_page'), $this->isMambot);
			$this->nav->showDisplayNum = $params->get('show_displaynum', true);
		}

		return $this->nav;
	}

	/**
	 * Get the random lmit start val
	 *
	 * @return  int     Limit start
	 */

	protected function getRandomLimitStart()
	{
		if (isset($this->randomLimitStart))
		{
			return $this->randomLimitStart;
		}

		$db   = $this->getDb();
		$item = $this->getTable();
		/* $$$ rob @todo - do we need to add the join in here as well?
		 * added + 1 as with 4 records to show 3 4th was not shown
		*/
		$query = $db->getQuery(true);
		$query->select('FLOOR(RAND() * COUNT(*) + 1) AS ' . $db->qn('offset'))
			->from($db->qn($item->get('list.db_table_name')));
		$query = $this->buildQueryWhere(true, $query);
		$db->setQuery($query);
		$limitStart = $db->loadResult();

		/*$$$ rob 11/01/2011 cant do this as we dont know what the total is yet
		 $$$ rob ensure that the limitstart + limit isn't greater than the total
		if ($limitStart + $limit > $total) {
		$limitStart = $total - $limit;
		}
		$$$ rob 25/02/2011 if you only have say 3 reocrds then above random will show 1 2 or 3 records
		so decrease the random start num by the table row dispaly num
		going to favour records at the beginning of the table though
		*/
		$limitStart -= $item->get('list.rows_per_page');

		if ($limitStart < 0)
		{
			$limitStart = 0;
		}

		$this->randomLimitStart = $limitStart;

		return $limitStart;
	}

	/**
	 * Used to determine which filter action to use.
	 * If a filter is a range then override lists setting with onsubmit
	 *
	 * @return  string
	 */

	public function getFilterAction()
	{
		if (!isset($this->real_filter_action))
		{
			// First, grab the list's setting as the default
			$item                     = $this->getTable();
			$this->real_filter_action = $item->get('list.filter_action');

			// Check to see if any list filter plugins require a Go button, like radius search
			$pluginManager = Worker::getPluginManager();
			$pluginManager->getPlugInGroup('list');

			$pluginManager->runPlugins('requireFilterSubmit', $this, 'list');
			$res = $pluginManager->data;

			if (!empty($res))
			{
				if (in_array(1, $res))
				{
					// We've got at least one plugin which needs the button, so set action and bail
					$this->real_filter_action = 'submitform';

					return $this->real_filter_action;
				}
			}

			// No list plugins expressed a preference, so check for range filters
			$form   = $this->getFormModel();
			$groups = $form->getGroupsHierarchy();

			foreach ($groups as $groupModel)
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					$element    = $elementModel->getElement();
					$filterType = $element->get('filter_type', '');

					if ($element->get('filter_type') <> '')
					{
						if ($elementModel->canView() && $elementModel->canUseFilter() && $element->get('show_in_list_summary') == '1')
						{
							// $$$ rob does need to check auto-compelte otherwise submission occurs without the value selected.
							if ($filterType == 'range' || $filterType == 'auto-complete')
							{
								$this->real_filter_action = 'submitform';

								return $this->real_filter_action;
							}
						}
					}
				}
			}
		}

		return $this->real_filter_action;
	}

	/**
	 * Gets the part of a url to describe the key that the link links to
	 * if a table this is rowid=x
	 * if a view this is view_primary_key={where statement}
	 *
	 * @param   object $data current list row
	 *
	 * @return  string
	 */

	protected function getKeyIdentifier($data)
	{
		return '&rowid=' . $this->getSlug($data);
	}

	/**
	 * Format the row id slug
	 *
	 * @param   object $row current list row data
	 *
	 * @return  string    formatted slug
	 */

	protected function getSlug($row)
	{
		if (!isset($row->slug))
		{
			return '';
		}

		$row->slug = str_replace(':', '-', $row->slug);
		$row->slug = JApplication::stringURLSafe($row->slug);

		return $row->slug;
	}

	/**
	 * Get other lists who have joins to the list db tables pk
	 *
	 * @return array of element objects that are database joins and that
	 * use this table's key as their foregin key
	 */

	public function getJoinsToThisKey()
	{
		if (is_null($this->joinsToThisKey))
		{
			$this->joinsToThisKey = array();
			$db                   = Worker::getDbo(true);
			$item                 = $this->getTable();

			if ($item->get('id') == '')
			{
				$this->joinsToThisKey = array();
			}
			else
			{
				// FIXME - not ok for 3.5
				$this->joinsToThisKey = array();
				/*$usersConfig = JComponentHelper::getParams('com_fabrik');
				$query = $db->getQuery(true);

				// Select the required fields from the table.
				$query
				->select(
						"l.db_table_name,
						el.name, el.plugin, l.label AS listlabel, l.id as list_id, \n
						el.id AS element_id, el.label AS element_label, f.id AS form_id,
						el.params AS element_params");
				$query->from('#__fabrik_elements AS el');
				$query->join('LEFT', '#__fabrik_formgroup AS fg ON fg.group_id = el.group_id');
				$query->join('LEFT', '#__fabrik_forms AS f ON f.id = fg.form_id');
				$query->join('LEFT', '#__fabrik_lists AS l ON l.form_id = f.id');
				$query->join('LEFT', '#__fabrik_groups AS g ON g.id = fg.group_id');
				$query->where('el.published = 1 AND g.published = 1');
				$query
				->where(
						"(plugin = 'databasejoin' AND el.params like '%\"join_db_name\":\"" . $table->get('list.db_table_name')
						. "\"%'
						AND el.params like  '%\"join_conn_id\":\"" . $table->connection_id . "%') OR (plugin = 'cascadingdropdown' AND \n"
						. " el.params like '\"%cascadingdropdown_table\":\"" . $table->id . "\"%' \n"
						. "AND el.params like '\"%cascadingdropdown_connection\":\"" . $table->connection_id . "\"%') ", "OR");

				// Load in user element links as well
				// $$$rob - not convinced this is a good idea
				if ($usersConfig->get('user_elements_as_related_data', false) == true)
				{
					$query->where("(plugin = 'user' AND
							el.params like '%\"join_conn_id\":\"" . $table->connection_id . "%\"' )", "OR");
				}

				$db->setQuery($query);

				try
				{
					$this->joinsToThisKey = $db->loadObjectList();

					foreach ($this->joinsToThisKey as $join)
					{
						$element_params = json_decode($join->element_params);
						$join->join_key_column = $element_params->join_key_column;
					}
				}
				catch (RuntimeException $e)
				{
					throw new ErrorException('getJoinsToThisKey: ' . $e->getMessage(), 500);
				}*/
			}
		}

		return $this->joinsToThisKey;
	}

	/**
	 * Get an array of elements that point to a form where their data will be filtered
	 *
	 * @return  array
	 */

	public function getLinksToThisKey()
	{
		if (!is_null($this->linksToThisKey))
		{
			return $this->linksToThisKey;
		}

		$params               = $this->getParams();
		$this->linksToThisKey = array();
		$faceted              = $params->get('facetedlinks', new stdClass);

		if (!isset($faceted->linkedform))
		{
			return $this->linksToThisKey;
		}

		$linkedForms        = $faceted->linkedform;
		$aAllJoinsToThisKey = $this->getJoinsToThisKey();

		foreach ($aAllJoinsToThisKey as $join)
		{
			$key = "{$join->list_id}-{$join->form_id}-{$join->element_id}";

			// $$$ rob required for related form links. otherwise links for forms not listed first in the admin options wherent being rendered
			$this->linksToThisKey[] = isset($linkedForms->$key) ? $join : false;
		}

		return $this->linksToThisKey;
	}

	/**
	 * Get empty data message
	 *
	 * @return string
	 */

	public function getEmptyDataMsg()
	{
		if (isset($this->emptyMsg))
		{
			return $this->emptyMsg;
		}

		$params = $this->getParams();

		return FText::_($params->get('empty_data_msg', 'COM_FABRIK_LIST_NO_DATA_MSG'));
	}

	/**
	 * Get the message telling the user that all required filters must be selected
	 *
	 * @return  string
	 */

	public function getRequiredMsg()
	{
		if (isset($this->emptyMsg))
		{
			return $this->emptyMsg;
		}

		return '';
	}

	/**
	 * Do we have all required filters, by both list level and element level settings.
	 *
	 * @return  bool
	 */

	public function gotAllRequiredFilters()
	{
		if ($this->listRequiresFiltering() && !$this->gotOptionalFilters())
		{
			$this->emptyMsg = FText::_('COM_FABRIK_SELECT_AT_LEAST_ONE_FILTER');

			return false;
		}

		if ($this->hasRequiredElementFilters() && !$this->getRequiredFiltersFound())
		{
			$this->emptyMsg = FText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');

			return false;
		}

		return true;
	}

	/**
	 * Does a filter have to be applied before we show any list data
	 *
	 * @return bool
	 */

	protected function listRequiresFiltering()
	{
		$params = $this->getParams();
		/*
		 if (!$this->getRequiredFiltersFound()) {
		return true;
		}
		*/
		switch ($params->get('require-filter', 0))
		{
			case 0:
			default:
				return false;
				break;
			case 1:
				return true;
				break;
			case 2:
				return $this->app->isAdmin() ? false : true;
				break;
		}
	}

	/**
	 * Have all the required filters been met?
	 *
	 * @return  bool  true if they have if false we shouldnt show the table data
	 */

	protected function hasRequiredElementFilters()
	{
		if (isset($this->hasRequiredElementFilters))
		{
			return $this->hasRequiredElementFilters;
		}

		$this->getFilterArray();
		$elements                        = $this->getElements();
		$this->hasRequiredElementFilters = false;

		foreach ($elements as $kk => $val2)
		{
			// Don't do with = as this foobars up the last elementModel
			$elementModel = $elements[$kk];
			$element      = $elementModel->getElement();
			$filterType   = $element->get('filter_type', '');

			if ($filterType <> '')
			{
				if ($elementModel->canView() && $elementModel->canUseFilter())
				{
					if ($elementModel->getParams()->get('filter_required') == 1)
					{
						$this->elementsWithRequiredFilters[] = $elementModel;
						$this->hasRequiredElementFilters     = true;
					}
				}
			}
		}

		return $this->hasRequiredElementFilters;
	}

	/**
	 * Do we have any filters that aren't pre-filters
	 *
	 * @return  bool
	 */

	protected function gotOptionalFilters()
	{
		$filters = $this->getFilterArray();
		$types   = ArrayHelper::getValue($filters, 'search_type', array());

		foreach ($types as $i => $type)
		{
			if ($type != 'prefilter')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Have all the required filters been met?
	 *
	 * @return  bool  true if they have if false we shouldnt show the table data
	 */

	public function getRequiredFiltersFound()
	{
		if (isset($this->requiredFilterFound))
		{
			return $this->requiredFilterFound;
		}

		$filters = $this->getFilterArray();
		$this->getElements();

		// If no required filters, then by definition we have them all
		if (!$this->hasRequiredElementFilters())
		{
			return true;
		}
		// If no filter keys, by definition we don't have required ones
		if (!array_key_exists('key', $filters) || !is_array($filters['key']))
		{
			$this->emptyMsg = FText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');

			return false;
		}

		foreach ($this->elementsWithRequiredFilters as $elementModel)
		{
			if ($elementModel->getParams()->get('filter_required') == 1)
			{
				$name = FabrikString::safeColName($elementModel->getFullName(false, false));
				reset($filters['key']);
				$found = false;

				while (list($key, $val) = each($filters['key']))
				{
					if ($val == $name)
					{
						$found = true;
						break;
					}
				}

				if (!$found || $filters['origvalue'][$key] == '')
				{
					$this->emptyMsg = FText::_('COM_FABRIK_PLEASE_SELECT_ALL_REQUIRED_FILTERS');

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get filters for display in html view
	 *
	 * @param   string $container List container
	 * @param   string $type      Type
	 * @param   string $id        Html id, only used if called from viz plugin
	 * @param   string $ref       Js ref used when filters set for visualizations
	 *
	 * @return array filters
	 */

	public function getFilters($container = 'listform_1', $type = 'list', $id = '', $ref = '')
	{
		if (!isset($this->viewfilters))
		{
			$profiler          = JProfiler::getInstance('Application');
			$this->viewfilters = array();
			JDEBUG ? $profiler->mark('fabrik makeFilters start') : null;
			$modelFilters = $this->makeFilters($container, $type, $id, $ref);
			JDEBUG ? $profiler->mark('fabrik makeFilters end') : null;

			if (!$this->app->input->get('showfilters', 1))
			{
				$this->viewfilters = array();
			}
			else
			{
				foreach ($modelFilters as $name => $filter)
				{
					$f                                = new stdClass;
					$f->label                         = $filter->label;
					$f->element                       = $filter->filter;
					$f->required                      = array_key_exists('required', $filter) ? $filter->required : '';
					$this->viewfilters[$filter->name] = $f;
				}
			}

			Worker::getPluginManager()->runPlugins('onMakeFilters', $this, 'list');
		}

		return $this->viewfilters;
	}

	/**
	 * Creates an array of HTML code for each filter
	 * Also adds in JS code to manage filters
	 *
	 * @param   string $container container
	 * @param   string $type      type listviz
	 * @param   int    $id        html id, only used if called from viz plugin
	 * @param   string $ref       js filter ref, used when rendering filters for visualizations
	 *
	 * @return  array    of html code for each filter
	 */

	protected function &makeFilters($container = 'listform_1', $type = 'list', $id = '', $ref = '')
	{
		$filter                           = JFilterInput::getInstance();
		$container                        = $filter->clean($container, 'WORD');
		$aFilters                         = array();
		$opts                             = new stdClass;
		$opts->container                  = $container;
		$opts->type                       = $type;
		$opts->id                         = $type === 'list' ? $this->getId() : $id;
		$opts->ref                        = $this->getRenderContext();
		$opts->advancedSearch             = $this->getAdvancedSearchOpts();
		$opts->advancedSearch->controller = $type;
		$opts                             = json_encode($opts);
		$filterScript                     = "\tFabrik.filter_{$container} = new FbListFilter($opts);\n";
		$package                          = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$filters                          = $this->getFilterArray();
		$params                           = $this->getParams();

		// Paul Switch to 0/1 for NO/YES from AND/OR so that bootstrap classes work but support legacy values
		if (($params->get('search-mode', '0') == '1')
			|| ($params->get('search-mode', '0') == 'OR')
		)
		{
			// One field to search them all (and in the darkness bind them)
			$requestKey  = $this->getFilterModel()->getSearchAllRequestKey();
			$v           = $this->getFilterModel()->getSearchAllValue('html');
			$o           = new stdClass;
			$searchLabel = $params->get('search-all-label', FText::_('COM_FABRIK_SEARCH'));
			$class       = 'fabrik_filter search-query input-medium';
			$o->filter   = '<input type="search" size="20" placeholder="' . $searchLabel . '" value="' . $v
				. '" class="' . $class . '" name="' . $requestKey . '" />';

			if ($params->get('search-mode-advanced') == 1)
			{
				$opts   = array();
				$opts[] = JHTML::_('select.option', 'all', FText::_('COM_FABRIK_ALL_OF_THESE_TERMS'));
				$opts[] = JHTML::_('select.option', 'any', FText::_('COM_FABRIK_ANY_OF_THESE_TERMS'));
				$opts[] = JHTML::_('select.option', 'exact', FText::_('COM_FABRIK_EXACT_TERMS'));
				$opts[] = JHTML::_('select.option', 'none', FText::_('COM_FABRIK_NONE_OF_THESE_TERMS'));
				$mode   = $this->app->getUserStateFromRequest('com_' . $package . '.list' . $this->getRenderContext() . '.searchallmode', 'search-mode-advanced');
				$o->filter .= '&nbsp;'
					. JHTML::_('select.genericList', $opts, 'search-mode-advanced', "class='fabrik_filter'", 'value', 'text', $mode);
			}

			$o->name    = 'all';
			$o->label   = $searchLabel;
			$aFilters[] = $o;
		}

		$counter = 0;
		/* $$$ hugh - another one of those weird ones where if we use = the foreach loop
		 * will sometimes skip a group
		* $groups = $this->getFormGroupElementData();
		*/
		$groups = $this->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$g             = $groupModel->getGroup();
			$elementModels = null;
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				$filterType = $element->get('filter_type', '');

				if ($filterType <> '')
				{
					if ($elementModel->canView('list') && $elementModel->canUseFilter())
					{
						$formId = $element->get('form_id', '');
						/* $$$ rob in faceted browsing somehow (not sure how!) some elements from the faceted table get inserted into elementModels
						 * with their form id set - so test if its been set and if its not the same as the current form id
						* if so then ignore
						*/
						if ($formId !== '' && $formId !== $this->getFormModel()->getId())
						{
							continue;
						}
						// Force the correct group model into the element model to ensure no wierdness in getting the element name
						$elementModel->setGroupModel($groupModel);
						$o         = new stdClass;
						$o->name   = $elementModel->getFullName(true, false);
						$o->filter = $elementModel->getFilter($counter, true);
						$filterScript .= $elementModel->filterJS(true, $container);
						$o->required = $elementModel->getParams()->get('filter_required');
						$o->label    = $elementModel->getListHeading();
						$aFilters[]  = $o;
						$counter++;
					}
				}
			}
		}

		$filterScript .= 'Fabrik.filter_' . $container . ".update();\n";
		$this->filterJs = $filterScript;

		// Check for search form filters - if they exists create hidden elements for them
		$keys = ArrayHelper::getValue($filters, 'key', array());

		foreach ($keys as $i => $key)
		{
			if ($filters['no-filter-setup'][$i] == '1' && !in_array($filters['search_type'][$i], array('searchall', 'advanced', 'jpluginfilters')))
			{
				$o = new stdClass;
				/* $$$ rob - we are now setting read only filters 'filter' var to the elements read only
				 * label for the passed in filter value
				*$o->filter = $value;
				*/
				$elementModel = $this->getFormModel()->getElement(str_replace('`', '', $key));
				$o->filter    = ArrayHelper::getValue($filters['filter'], $i);

				if ($elementModel)
				{
					$elementModel->getElement()->set('filter_type', 'hidden');
					$o->filter .= $elementModel->getFilter($counter, true);
				}

				$o->name    = FabrikString::safeColNameToArrayKey($filters['key'][$i]);
				$o->label   = $filters['label'][$i];
				$aFilters[] = $o;
				$counter++;
			}
		}

		return $aFilters;
	}

	/**
	 * Build the advanced search link
	 *
	 * @return  string  <a href...> link
	 */

	public function getAdvancedSearchLink()
	{
		$params = $this->getParams();

		if ($params->get('advanced-filter', '0'))
		{
			$tmpl  = $this->getTmpl();
			$url   = $this->getAdvancedSearchURL();
			$title = '<span>' . FText::_('COM_FABRIK_ADVANCED_SEARCH') . '</span>';
			$opts  = array('alt' => FText::_('COM_FABRIK_ADVANCED_SEARCH'), 'class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => $title);
			$img   = FabrikHelperHTML::image('find.png', 'list', $tmpl, $opts);

			return '<a href="' . $url . '" class="advanced-search-link">' . $img . '</a>';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the URL used to open the advanced search window
	 *
	 * @return  string
	 */

	public function getAdvancedSearchURL()
	{
		$item    = $this->getTable();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$url     = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=list&amp;layout=_advancedsearch&amp;tmpl=component&amp;listid='
			. $item->get('id') . '&amp;nextview=' . $this->app->input->get('view', 'list');

		// Defines if we are in a module or in the component.
		$url .= '&amp;scope=' . $this->app->scope;
		$url .= '&amp;tkn=' . JSession::getFormToken();

		return $url;
	}

	/**
	 * Called from index.php?option=com_fabrik&view=list&layout=_advancedsearch&tmpl=component&listid=4
	 * advanced serach popup view
	 *
	 * @return  object    advanced search options
	 */

	public function getAdvancedSearchOpts()
	{
		$params = $this->getParams();
		$opts   = new stdClass;

		// $$$ rob - 20/208/2012 if list advanced search off return nothing
		if ($params->get('advanced-filter') == 0)
		{
			return $opts;
		}

		$list                = $this->getTable();
		$listRef             = $this->getRenderContext();
		$opts->conditionList = FabrikHelperHTML::conditionList($listRef, '');
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$statements          = $this->getStatementsOpts();
		$opts->elementList   = JHTML::_('select.genericlist', $fieldNames, 'fabrik___filter[list_' . $listRef . '][key][]',
			'class="inputbox key" size="1" ', 'value', 'text');
		$opts->statementList = JHTML::_('select.genericlist', $statements, 'fabrik___filter[list_' . $listRef . '][condition][]',
			'class="inputbox" size="1" ', 'value', 'text');
		$opts->listid        = $list->id;
		$opts->listref       = $listRef;
		$opts->ajax          = $this->isAjax();
		$opts->counter       = count($this->getadvancedSearchRows()) - 1;
		$elements            = $this->getElements();
		$arr                 = array();

		foreach ($elements as $e)
		{
			$key       = $e->getFilterFullName();
			$arr[$key] = array('id' => $e->getId(), 'plugin' => $e->getElement()->get('plugin'));
		}

		$opts->elementMap = $arr;

		return $opts;
	}

	/**
	 * Get a list of elements that are included in the advacned search dropdown list
	 *
	 * @return  array  list of fields names and which is the first filter
	 */

	private function getAdvancedSearchElementList()
	{
		$first         = false;
		$firstFilter   = false;
		$fieldNames[]  = JHTML::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT'));
		$elementModels = $this->getElements();

		foreach ($elementModels as $elementModel)
		{
			if (!$elementModel->canView('list'))
			{
				continue;
			}

			$element  = $elementModel->getElement();
			$elParams = $elementModel->getParams();

			if ($elParams->get('inc_in_adv_search', 1))
			{
				$elName = $elementModel->getFilterFullName();

				if (!$first)
				{
					$first       = true;
					$firstFilter = $elementModel->getFilter(0, false);
				}

				$fieldNames[] = JHTML::_('select.option', $elName, strip_tags(FText::_($element->get('label'))));
			}
		}

		return array($fieldNames, $firstFilter);
	}

	/**
	 * Get a list of advanced search options
	 *
	 * @return array of JHTML options
	 */

	private function getStatementsOpts()
	{
		$statements   = array();
		$statements[] = JHTML::_('select.option', '=', FText::_('COM_FABRIK_EQUALS'));
		$statements[] = JHTML::_('select.option', '<>', FText::_('COM_FABRIK_NOT_EQUALS'));
		$statements[] = JHTML::_('select.option', 'BEGINS WITH', FText::_('COM_FABRIK_BEGINS_WITH'));
		$statements[] = JHTML::_('select.option', 'CONTAINS', FText::_('COM_FABRIK_CONTAINS'));
		$statements[] = JHTML::_('select.option', 'ENDS WITH', FText::_('COM_FABRIK_ENDS_WITH'));
		$statements[] = JHTML::_('select.option', '>', FText::_('COM_FABRIK_GREATER_THAN'));
		$statements[] = JHTML::_('select.option', '<', FText::_('COM_FABRIK_LESS_THAN'));
		$statements[] = JHTML::_('select.option', 'EMPTY', FText::_('COM_FABRIK_IS_EMPTY'));

		return $statements;
	}

	/**
	 * Get a list of submitted advanced filters
	 *
	 * @return array advanced filter values
	 */

	public function getAdvancedFilterValues()
	{
		$filters  = $this->getFilterArray();
		$advanced = array();
		$iKeys    = array_keys(ArrayHelper::getValue($filters, 'key', array()));

		foreach ($iKeys as $i)
		{
			$searchType = ArrayHelper::getValue($filters['search_type'], $i);

			if (!is_null($searchType) && $searchType == 'advanced')
			{
				$tmp = array();

				foreach (array_keys($filters) as $k)
				{
					if (array_key_exists($k, $advanced))
					{
						$advanced[$k][] = ArrayHelper::getValue($filters[$k], $i, '');
					}
					else
					{
						$advanced[$k] = array_key_exists($i, $filters[$k]) ? array(($filters[$k][$i])) : '';
					}
				}
			}
		}

		return $advanced;
	}

	/**
	 * Build an array of html data that gets inserted into the advanced search popup view
	 *
	 * @return  array    html lists/fields
	 */

	public function getAdvancedSearchRows()
	{
		if (isset($this->advancedSearchRows))
		{
			return $this->advancedSearchRows;
		}

		$statements    = $this->getStatementsOpts();
		$input         = $this->app->input;
		$rows          = array();
		$first         = false;
		$elementModels = $this->getElements();
		list($fieldNames, $firstFilter) = $this->getAdvancedSearchElementList();
		$prefix  = 'fabrik___filter[list_' . $this->getRenderContext() . '][';
		$type    = '<input type="hidden" name="' . $prefix . 'search_type][]" value="advanced" />';
		$grouped = '<input type="hidden" name="' . $prefix . 'grouped_to_previous][]" value="0" />';
		$filters = $this->getAdvancedFilterValues();
		$counter = 0;

		if (array_key_exists('key', $filters))
		{
			foreach ($filters['key'] as $key)
			{
				foreach ($elementModels as $elementModel)
				{
					$testkey = FabrikString::safeColName($elementModel->getFullName(false, false));

					if ($testkey == $key)
					{
						break;
					}
				}

				$join      = $filters['join'][$counter];
				$condition = $filters['condition'][$counter];
				$value     = $filters['origvalue'][$counter];
				$v2        = $filters['value'][$counter];
				$jsSel     = '=';

				switch ($condition)
				{
					case 'EMPTY':
						$jsSel = 'EMPTY';
						break;
					case "<>":
						$jsSel = '<>';
						break;
					case "=":
						$jsSel = 'EQUALS';
						break;
					case "<":
						$jsSel = '<';
						break;
					case ">":
						$jsSel = '>';
						break;
					default:
						$firstChar = String::substr($v2, 1, 1);
						$lastChar  = String::substr($v2, -2, 1);

						switch ($firstChar)
						{
							case "%":
								$jsSel = ($lastChar == "%") ? 'CONTAINS' : $jsSel = 'ENDS WITH';
								break;
							default:
								if ($lastChar == "%")
								{
									$jsSel = 'BEGINS WITH';
								}
								break;
						}
						break;
				}

				if (is_string($value))
				{
					$value = trim(trim($value, '"'), "%");
				}

				if ($counter == 0)
				{
					$join = FText::_('COM_FABRIK_WHERE') . '<input type="hidden" value="WHERE" name="' . $prefix . 'join][]" />';
				}
				else
				{
					$join = FabrikHelperHTML::conditionList($this->getRenderContext(), $join);
				}

				$lineElname = FabrikString::safeColName($elementModel->getFullName(true, false));
				$orig       = $input->get($lineElname);
				$input->set($lineElname, array('value' => $value));
				$filter = $elementModel->getFilter($counter, false);
				$input->set($lineElname, $orig);
				$key    = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key input-small" size="1" ', 'value', 'text', $key);
				$jsSel  = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox input-small" size="1" ', 'value', 'text', $jsSel);
				$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $filter, 'type' => $type,
					'grouped' => $grouped);
				$counter++;
			}
		}

		if ($counter == 0)
		{
			$join   = FText::_('COM_FABRIK_WHERE') . '<input type="hidden" name="' . $prefix . 'join][]" value="WHERE" />';
			$key    = JHTML::_('select.genericlist', $fieldNames, $prefix . 'key][]', 'class="inputbox key" size="1" ', 'value', 'text', '');
			$jsSel  = JHTML::_('select.genericlist', $statements, $prefix . 'condition][]', 'class="inputbox" size="1" ', 'value', 'text', '');
			$rows[] = array('join' => $join, 'element' => $key, 'condition' => $jsSel, 'filter' => $firstFilter, 'type' => $type,
				'grouped' => $grouped);
		}

		$this->advancedSearchRows = $rows;

		return $rows;
	}

	/**
	 * Fet the headings that should be shown in the csv export file
	 *
	 * @param   array $headings to use (key is element name value must be 1 for it to be added)
	 *
	 * @return  void
	 */

	public function setHeadingsForCSV($headings)
	{
		$asfields                 = $this->getAsFields();
		$newfields                = array();
		$db                       = $this->getDb();
		$this->temp_db_key_addded = false;
		/* $$$ rob if no fields specified presume we are requesting CSV file from URL and return
		 * all fields otherwise set the fields to be those selected in fabrik window
		* or defined in the lists csv export settings
		*/
		if (!empty($headings))
		{
			foreach ($headings as $name => $val)
			{
				if ($val != 1)
				{
					continue;
				}

				$elModel = $this->getFormModel()->getElement($name);

				if (is_object($elModel))
				{
					$name  = $elModel->getFullName(true, false);
					$pName = $elModel->isJoin() ? $db->qn($elModel->getJoinModel()->getJoin()->table_join . '___params') : '';

					foreach ($asfields as $f)
					{
						if ((strstr($f, $db->qn($name)) || strstr($f, $db->qn($name . '_raw'))
							|| ($elModel->isJoin() && strstr($f, $pName)))
						)
						{
							$newfields[] = $f;
						}
					}
				}
			}

			$this->asfields = $newfields;
		}
	}

	/**
	 * returns the table headings, separated from writetable function as
	 * when group_by is selected multiple tables are written
	 * 09/07/2011 moved headingClass into array rather than string
	 *
	 * @return  array  (table headings, array columns, $aLinkElements)
	 */

	public function getHeadings()
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$item    = $this->getTable();
		//$item->order_dir = String::strtolower($item->get('list.order_dir'));
		$aTableHeadings  = array();
		$headingClass    = array();
		$cellClass       = array();
		$params          = $this->getParams();
		$w               = new Worker;
		$session         = JFactory::getSession();
		$formModel       = $this->getFormModel();
		$oldLinksToForms = $this->getLinksToThisKey();
		$linksToForms    = array();

		foreach ($oldLinksToForms as $join)
		{
			// $$$ hugh - another issue with getLinksToThisKey() now returning false for some joins.
			if ($join)
			{
				$k                = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;
				$linksToForms[$k] = $join;
			}
		}

		$groups        = $formModel->getGroupsHierarchy();
		$groupHeadings = array();

		$orderBys = (array) $item->get('list.order_by');

		if (!isset($orderBys))
		{
			$orderBys = array();
		}

		// Responsive element classes
		$listClasses = json_decode($params->get('list_responsive_elements'));

		if (!isset($listClasses->responsive_elements))
		{
			$listClasses                      = new stdClass;
			$listClasses->responsive_elements = array();
		}

		$showInList = $this->showInList();

		if (!in_array($this->outputFormat, array('pdf', 'csv')))
		{
			if ($this->canSelectRows() && $params->get('checkboxLocation', 'end') !== 'end')
			{
				$this->addCheckBox($aTableHeadings, $headingClass, $cellClass);
			}

			if ($params->get('checkboxLocation', 'end') !== 'end')
			{
				$this->actionHeading($aTableHeadings, $headingClass, $cellClass);
			}
		}

		foreach ($groups as $groupModel)
		{
			$groupHeadingKey                 = $w->parseMessageForPlaceHolder($groupModel->getGroup()->get('label'), array(), false);
			$groupHeadings[$groupHeadingKey] = 0;
			$elementModels                   = $groupModel->getPublishedListElements();

			if ($groupModel->canView('list') === false)
			{
				continue;
			}

			foreach ($elementModels as $key => $elementModel)
			{
				$element = $elementModel->getElement();

				// If we define the elements to show in the list - e.g in admin list module then only show those elements
				if (!empty($showInList) && !in_array($element->get('id'), $showInList))
				{
					continue;
				}

				$groupHeadings[$groupHeadingKey]++;
				$key           = $elementModel->getFullName(true, false);
				$compsitKey    = !empty($showInList) ? array_search($element->get('id'), $showInList) . ':' . $key : $key;
				$orderKey      = $elementModel->getOrderbyFullName(false);
				$elementParams = $elementModel->getParams();
				$label         = $elementModel->getListHeading();
				$label         = $w->parseMessageForPlaceHolder($label, array());
				$elementId     = $elementModel->getId();

				if ($elementParams->get('can_order') == '1' && $this->outputFormat != 'csv')
				{
					$context  = 'com_' . $package . '.list' . $this->getRenderContext() . '.order.' . $elementId;
					$orderDir = $session->get($context);

					//  No user set order so get it from the list properties
					if (is_null($orderDir))
					{
						$orderDirs = (array) json_decode($item->order_dir);
						$orderEls  = (array) json_decode($item->order_by);
						$ix        = array_search($elementId, $orderEls);

						if ($ix !== false)
						{
							$orderDir = ArrayHelper::getValue($orderDirs, $ix, '');
						}
					}

					$class = '';
					$tmpl  = $this->getTmpl();

					switch ($orderDir)
					{
						case 'desc':
							$orderDir = '-';
							$class    = 'class="fabrikorder-desc"';
							$img      = FabrikHelperHTML::image('arrow-up.png', 'list', $tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
							break;
						case 'asc':
							$orderDir = 'desc';
							$class    = 'class="fabrikorder-asc"';
							$img      = FabrikHelperHTML::image('arrow-down.png', 'list', $tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
							break;
						case '':
						case '-':
							$orderDir = 'asc';
							$class    = 'class="fabrikorder"';
							$img      = FabrikHelperHTML::image('menu-2.png', 'list', $tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
							break;
					}

					if ($class === '')
					{
						if (in_array($key, $orderBys))
						{
							if ($item->order_dir === 'desc')
							{
								$class = 'class="fabrikorder-desc"';
								$img   = FabrikHelperHTML::image('orderdesc.png', 'list', $tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
							}
						}
					}

					if ($elementParams->get('can_order', false))
					{
						$heading = '<a ' . $class . ' href="#">' . $img . $label . '</a>';
					}
					else
					{
						$img     = $orderDir === 'asc' ? '' : $img;
						$heading = $img . $label;
					}
				}
				else
				{
					$heading = $label;
				}

				$aTableHeadings[$compsitKey] = $heading;

				// Check responsive class
				$responsiveKey   = array_search($element->get('id'), $listClasses->responsive_elements);
				$responsiveClass = $responsiveKey !== false ? ArrayHelper::getValue($listClasses->responsive_class, $responsiveKey, '') : '';

				if ($responsiveClass !== '')
				{
					$responsiveClass .= ' ';
				}

				$headingClass[$compsitKey] = array('class' => $responsiveClass . $elementModel->getHeadingClass(),
					'style' => $elementParams->get('tablecss_header'));
				$cellClass[$compsitKey]    = array('class' => $responsiveClass . $elementModel->getCellClass(), 'style' => $elementParams->get('tablecss_cell'));

				// Add in classes for repeat/merge data
				if ($groupModel->canRepeat())
				{
					$cellClass[$compsitKey]['class'] .= ' repeat';
					$dis = $params->get('join-display');

					if ($dis != 'default')
					{
						$cellClass[$compsitKey]['class'] .= '-' . $dis;
					}
				}
			}

			if ($groupHeadings[$groupHeadingKey] == 0)
			{
				unset($groupHeadings[$groupHeadingKey]);
			}
		}

		if (!empty($showInList))
		{
			$aTableHeadings = $this->removeHeadingCompositKey($aTableHeadings);
			$headingClass   = $this->removeHeadingCompositKey($headingClass);
			$cellClass      = $this->removeHeadingCompositKey($cellClass);
		}

		if (!in_array($this->outputFormat, array('pdf', 'csv')))
		{
			// @TODO check if any plugins need to use the selector as well!
			if ($this->canSelectRows() && $params->get('checkboxLocation', 'end') === 'end')
			{
				$this->addCheckBox($aTableHeadings, $headingClass, $cellClass);
			}

			// If no elements linking to the edit form add in a edit column (only if we have the right to edit/view of course!)
			if ($params->get('checkboxLocation', 'end') === 'end')
			{
				$this->actionHeading($aTableHeadings, $headingClass, $cellClass);
			}
			// Create columns containing links which point to lists associated with this list
			$faceted        = $params->get('facetedlinks');
			$joinsToThisKey = $this->getJoinsToThisKey();
			$listOrder      = json_decode($params->get('faceted_list_order'));
			$formOrder      = json_decode($params->get('faceted_form_order'));

			if (is_null($listOrder))
			{
				// Not yet saved with order
				$listOrder = is_object($faceted) && is_object($faceted->linkedlist) ? array_keys(ArrayHelper::fromObject($faceted->linkedlist)) : array();
			}

			if (is_null($formOrder))
			{
				// Not yet saved with order
				$formOrder = is_object($faceted) && is_object($faceted->linkedform) ? array_keys(ArrayHelper::fromObject($faceted->linkedform)) : array();
			}

			foreach ($listOrder as $key)
			{
				$join = $this->facetedJoin($key);

				if ($join === false)
				{
					continue;
				}

				if (is_object($join) && isset($faceted->linkedlist->$key))
				{
					$linkedTable = $faceted->linkedlist->$key;
					$heading     = $faceted->linkedlistheader->$key;

					if ($linkedTable != '0')
					{
						$prefix                  = $join->element_id . '___' . $linkedTable . '_list_heading';
						$aTableHeadings[$prefix] = empty($heading) ? $join->listlabel . ' ' . FText::_('COM_FABRIK_LIST') : $heading;
						$headingClass[$prefix]   = array('class' => 'fabrik_ordercell related ' . $prefix,
							'style' => '');
						$cellClass[$prefix]      = array('class' => $prefix . ' fabrik_element related');
					}
				}
			}

			foreach ($formOrder as $key)
			{
				$join = $this->facetedJoin($key);

				if ($join === false || !isset($faceted->linkedform->$key))
				{
					continue;
				}

				$linkedForm = $faceted->linkedform->$key;

				if ($linkedForm != '0')
				{
					$heading                 = $faceted->linkedformheader->$key;
					$prefix                  = $join->db_table_name . '___' . $join->name . '_form_heading';
					$aTableHeadings[$prefix] = empty($heading) ? $join->listlabel . ' ' . FText::_('COM_FABRIK_FORM') : $heading;
					$headingClass[$prefix]   = array('class' => 'fabrik_ordercell related ' . $prefix,
						'style' => '');
					$cellClass[$prefix]      = array('class' => $prefix . ' fabrik_element related');
				}
			}
		}

		if ($this->canSelectRows())
		{
			$groupHeadings[''] = '';
		}

		$args['tableHeadings'] =& $aTableHeadings;
		$args['groupHeadings'] =& $groupHeadings;
		$args['headingClass']  =& $headingClass;
		$args['cellClass']     =& $cellClass;
		$args['data']          = $this->data;

		Worker::getPluginManager()->runPlugins('onGetPluginRowHeadings', $this, 'list', $args);

		return array($aTableHeadings, $groupHeadings, $headingClass, $cellClass);
	}

	/**
	 * Find a faceted join based on composite key
	 *
	 * @param   string $searchKey Key
	 *
	 * @return  mixed   False if not found, join object if found
	 */

	protected function facetedJoin($searchKey)
	{
		$facetedJoins = $this->getJoinsToThisKey();

		foreach ($facetedJoins as $join)
		{
			$key = $join->list_id . '-' . $join->form_id . '-' . $join->element_id;

			if ($searchKey === $key)
			{
				return $join;
			}
		}

		return false;
	}

	/**
	 * Put the actions in the headings array - separated to here to enable it to be added at the end or beginning
	 *
	 * @param   array &$aTableHeadings Table headings
	 * @param   array &$headingClass   Heading classes
	 * @param   array &$cellClass      Cell classes
	 *
	 * @return  void
	 */

	protected function actionHeading(&$aTableHeadings, &$headingClass, &$cellClass)
	{
		$params                    = $this->getParams();
		$filterMethod              = $params->get('show-table-filters');
		$filters                   = $this->getFilters('listform_' . $this->getRenderContext(), 'list');
		$filtersUnderHeadingsAndGo = ($this->getFilterAction() === 'submitform' && !empty($filters) && $filterMethod > 2) ? true : false;

		// Check for conditions in https://github.com/Fabrik/fabrik/issues/621
		$details = $this->canViewDetails();

		if ($params->get('detaillink', 1) == 0)
		{
			$details = false;
		}

		$edit = $this->canEdit();

		if ($params->get('editlink', 1) == 0)
		{
			$edit = false;
		}

		if ($this->canSelectRows() || $this->canEditARow() || $details || $edit || $filtersUnderHeadingsAndGo)
		{
			// 3.0 actions now go in one column
			$pluginManager  = Worker::getPluginManager();
			$headingButtons = array();

			if ($this->deletePossible())
			{
				$headingButtons[] = $this->deleteButton('', true);
			}

			$return         = $pluginManager->runPlugins('button', $this, 'list', array('heading' => true));
			$res            = $pluginManager->data;
			$headingButtons = array_merge($headingButtons, $res);

			if (empty($headingButtons))
			{
				$aTableHeadings['fabrik_actions'] = '';
			}
			else
			{
				if ($this->actionMethod() == 'dropdown')
				{
					$aTableHeadings['fabrik_actions'] = FabrikHelperHTML::bootStrapDropDown($headingButtons);
				}
				else
				{
					if ($this->actionMethod() == 'dropdown')
					{
						$align                            = $params->get('checkboxLocation', 'end') == 'end' ? 'right' : 'left';
						$aTableHeadings['fabrik_actions'] = FabrikHelperHTML::bootStrapDropDown($headingButtons, $align);
					}
					else
					{
						$aTableHeadings['fabrik_actions'] = FabrikHelperHTML::bootStrapButtonGroup($headingButtons);
					}
				}
			}

			$headingClass['fabrik_actions'] = array('class' => 'fabrik_ordercell fabrik_actions', 'style' => '');

			// Needed for ajax filter/nav
			$cellClass['fabrik_actions'] = array('class' => 'fabrik_actions fabrik_element');
		}
	}

	/**
	 * Put the checkbox in the headings array - separated to here to enable it to be added at the end or beginning
	 *
	 * @param   array &$aTableHeadings table headings
	 * @param   array &$headingClass   heading classes
	 * @param   array &$cellClass      cell classes
	 *
	 * @return  void
	 */

	protected function addCheckBox(&$aTableHeadings, &$headingClass, &$cellClass)
	{
		$id                              = 'list_' . $this->getId() . '_checkAll';
		$select                          = '<input type="checkbox" name="checkAll" class="' . $id . '" id="' . $id . '" />';
		$aTableHeadings['fabrik_select'] = $select;
		$headingClass['fabrik_select']   = array('class' => 'fabrik_ordercell fabrik_select', 'style' => '');

		// Needed for ajax filter/nav
		$cellClass['fabrik_select'] = array('class' => 'fabrik_select fabrik_element');
	}

	/**
	 * Enter description here ...
	 *
	 * @param   array $arr array
	 *
	 * @return  array
	 */

	protected function removeHeadingCompositKey($arr)
	{
		/* $$$ hugh - horrible hack, but if we just ksort as-is, once we have more than 9 elements,
		 * it'll start sort 0,1,10,11,2,3 etc.  There's no doubt a cleaner way to do this,
		* but for now ... rekey with a 0 padded prefix before we ksort
		*/
		$others = array();

		// These fields shouldn't be re-ordered
		$locations['fabrik_select']  = array_search('fabrik_select', array_keys($arr));
		$locations['fabrik_actions'] = array_search('fabrik_actions', array_keys($arr));

		if (array_key_exists('fabrik_select', $arr))
		{
			$others['fabrik_select'] = $arr['fabrik_select'];
		}
		if (array_key_exists('fabrik_actions', $arr))
		{
			$others['fabrik_actions'] = $arr['fabrik_actions'];
		}

		asort($locations);

		foreach ($arr as $key => $val)
		{
			if (strstr($key, ':'))
			{
				list($part1, $part2) = explode(':', $key);
				$part1        = sprintf('%03d', $part1);
				$newkey       = $part1 . ':' . $part2;
				$arr[$newkey] = $arr[$key];
			}

			//unset($arr[$key]);
		}

		ksort($arr);

		// Add the unsorted fields to the beginning or end
		$arr = array_sum($locations) <= 1 ? $others + $arr : $arr + $others;

		foreach ($arr as $key => $val)
		{
			if (strstr($key, ':'))
			{
				$bits         = explode(':', $key);
				$newkey       = array_pop($bits);
				$arr[$newkey] = $arr[$key];
				unset($arr[$key]);
			}
		}

		return $arr;
	}

	/**
	 * Can the user select the specified row
	 *
	 * Needs to return true to insert a checkbox in the row.
	 *
	 * @param   object $row row of list data
	 *
	 * @return  bool
	 */

	public function canSelectRow($row)
	{
		$canSelect = Worker::getPluginManager()->runPlugins('onCanSelectRow', $this, 'list', $row);

		if (in_array(false, $canSelect))
		{
			return false;
		}

		if ($this->canDelete($row))
		{
			$this->canSelectRows = true;

			return true;
		}

		$params      = $this->getParams();
		$usedPlugins = (array) $params->get('plugins');

		if (empty($usedPlugins))
		{
			return false;
		}

		$pluginManager = Worker::getPluginManager();
		$listplugins   = $pluginManager->getPlugInGroup('list');
		$v             = in_array(true, $pluginManager->runPlugins('canSelectRows', $this, 'list'));

		if ($v)
		{
			$this->canSelectRows = true;
		}

		return $v;
	}

	/**
	 * Can the user select ANY row?
	 *
	 * Should the checkbox be shown in the list
	 * If you can delete then true returned, if not then check
	 * available list plugins to see if they allow for row selection
	 * if so a checkbox column appears in the table
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		if (!is_null($this->canSelectRows))
		{
			return $this->canSelectRows;
		}

		if ($this->canDelete() || $this->deletePossible())
		{
			$this->canSelectRows = true;

			return $this->canSelectRows;
		}

		$params      = $this->getParams();
		$usedPlugins = (array) $params->get('plugins');

		if (empty($usedPlugins))
		{
			$this->canSelectRows = false;

			return $this->canSelectRows;
		}

		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('list');
		$this->canSelectRows = in_array(true, $pluginManager->runPlugins('canSelectRows', $this, 'list'));

		return $this->canSelectRows;
	}

	/**
	 * Clear the calculations
	 *
	 * @return  void
	 */

	public function clearCalculations()
	{
		unset($this->runCalculations);
	}

	/**
	 * return mathematical column calculations (run at doCalculations() on for submission)
	 *
	 * @return  array  calculations
	 */

	public function getCalculations()
	{
		if (!empty($this->runCalculations))
		{
			return $this->runCalculations;
		}

		$aclGroups     = $this->user->getAuthorisedViewLevels();
		$aCalculations = array();
		$formModel     = $this->getFormModel();
		$aAvgs         = array();
		$aSums         = array();
		$aMedians      = array();
		$aCounts       = array();
		$aCustoms      = array();
		$groups        = $formModel->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$params       = $elementModel->getParams();
				$elName       = $elementModel->getFullName(true, false);
				$sumOn        = $params->get('sum_on', '0');
				$avgOn        = $params->get('avg_on', '0');
				$medianOn     = $params->get('median_on', '0');
				$countOn      = $params->get('count_on', '0');
				$customOn     = $params->get('custom_calc_on', '0');
				$sumAccess    = $params->get('sum_access', 0);
				$avgAccess    = $params->get('avg_access', 0);
				$medianAccess = $params->get('median_access', 0);
				$countAccess  = $params->get('count_access', 0);
				$customAccess = $params->get('custom_calc_access', 0);

				if ($sumOn && in_array($sumAccess, $aclGroups) && $params->get('sum_value', '') != '')
				{
					$aSums[$elName] = $params->get('sum_value', '');
					$ser            = $params->get('sum_value_serialized');

					if (is_string($ser))
					{
						// If group gone from repeat to none repeat could be array
						$aSums[$elName . '_obj'] = unserialize($ser);
					}
				}

				if ($avgOn && in_array($avgAccess, $aclGroups) && $params->get('avg_value', '') != '')
				{
					$aAvgs[$elName] = $params->get('avg_value', '');
					$ser            = $params->get('avg_value_serialized');

					if (is_string($ser))
					{
						$aAvgs[$elName . '_obj'] = unserialize($ser);
					}
				}

				if ($medianOn && in_array($medianAccess, $aclGroups) && $params->get('median_value', '') != '')
				{
					$aMedians[$elName] = $params->get('median_value', '');
					$ser               = $params->get('median_value_serialized', '');

					if (is_string($ser))
					{
						$aMedians[$elName . '_obj'] = unserialize($ser);
					}
				}

				if ($countOn && in_array($countAccess, $aclGroups) && $params->get('count_value', '') != '')
				{
					$aCounts[$elName] = $params->get('count_value', '');
					$ser              = $params->get('count_value_serialized');

					if (is_string($ser))
					{
						$aCounts[$elName . '_obj'] = unserialize($ser);
					}
				}

				if ($customOn && in_array($customAccess, $aclGroups) && $params->get('custom_calc_value', '') != '')
				{
					$aCustoms[$elName] = $params->get('custom_calc_value', '');
					$ser               = $params->get('custom_calc_value_serialized');

					if (is_string($ser))
					{
						$aCounts[$elName . '_obj'] = unserialize($ser);
					}
				}
			}
		}

		$aCalculations['sums']        = $aSums;
		$aCalculations['avgs']        = $aAvgs;
		$aCalculations['medians']     = $aMedians;
		$aCalculations['count']       = $aCounts;
		$aCalculations['custom_calc'] = $aCustoms;
		$this->runCalculations        = $aCalculations;

		return $aCalculations;
	}

	/**
	 * Get list headings to pass into list js oject
	 *
	 * @return  string    headings tablename___name
	 */

	public function jsonHeadings()
	{
		$aHeadings = array();
		$table     = $this->getTable();
		$formModel = $this->getFormModel();
		$groups    = $formModel->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->get('show_in_list_summary'))
				{
					$aHeadings[] = $table->get('list.db_table_name') . '___' . $element->get('name');
				}
			}
		}

		return "['" . implode("','", $aHeadings) . "']";
	}

	/**
	 * Strip the table names from the front of the key
	 *
	 * @param   array  $data  data to strip
	 * @param   string $split string splitter ___ or .
	 *
	 * @return  array stripped data
	 */

	public function removeTableNameFromSaveData($data, $split = '___')
	{
		foreach ($data as $key => $val)
		{
			$akey = explode($split, $key);

			if (count($akey) > 1)
			{
				$newKey = $akey[1];
				unset($data[$key]);
			}
			else
			{
				$newKey = $akey[0];
			}

			$data[$newKey] = $val;
		}

		return $data;
	}

	/**
	 * Saves posted form data into a table
	 * data should be keyed on short name
	 *
	 * @param   array  $data           To save
	 * @param   int    $rowId          Row id to edit/updated
	 * @param   bool   $isJoin         Is the data being saved into a join table
	 * @param   JTable $joinGroupTable Joined group table
	 *
	 * @return  bool    int  Insert id
	 */

	public function storeRow($data, $rowId, $isJoin = false, $joinGroupTable = null)
	{
		/**
		 * REMEMBER - if we've arrived here from the group model process(), saving joined rows,
		 * $this list model will be for the parent list, and only $this-table->db_primary_key and
		 * table_name will have been set to that of the joined table being saved to.  Bit of a hack,
		 * and we need to find a better way to do this, but right now ... it is what it is.  Just be careful!
		 */

		if (is_array($rowId))
		{
			$rowId = array_shift($rowId);
		}

		$package   = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$origRowId = $rowId;

		// Don't save a record if no data collected
		if ($isJoin && empty($data))
		{
			return;
		}

		$input     = $this->app->input;
		$fabrikDb  = $this->getDb();
		$table     = $this->getTable();
		$formModel = $this->getFormModel();

		if ($isJoin)
		{
			$this->getGroupsHierarchy();
		}

		$oRecord        = new stdClass;
		$aBindData      = array();
		$noRepeatFields = array();
		$c              = 0;
		$groups         = $this->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$group = $groupModel->getGroup();
			/*
			 * $$$rob this following if statement avoids this scenario from happening:
			* you have a form with joins to two other tables
			* each joined group has a field called 'password'
			* first group's password is set to password plugin, second to field
			* on update if no password entered for first field data should not be updated as recordInDatabase() return false
			* however, as we were iterating over all groups, the 2nd password field's data is used instead!
			* this if statement ensures we only look at the correct group
			*/
			if ($isJoin == false || $group->id == $joinGroupTable->id)
			{
				if (($isJoin && $groupModel->isJoin()) || (!$isJoin && !$groupModel->isJoin()))
				{
					$elementModels = $groupModel->getPublishedElements();

					/*
					 * If the group is un-editable - then the form won't contain the group data, thus we don't want to add blank data into $oRecord
					 * @see http://fabrikar.com/forums/index.php?threads/changing-access-level-for-a-group-corrupts-data.34067/
					 */
					if (!$groupModel->canView('form'))
					{
						continue;
					}

					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->getElement();
						$key     = $element->get('name');
						$fullKey = $elementModel->getFullName(true, false);

						// For radio buttons and dropdowns otherwise nothing is stored for them??
						$postkey = array_key_exists($key . '_raw', $data) ? $key . '_raw' : $key;

						if ($elementModel->recordInDatabase($data))
						{
							if (array_key_exists($key, $data) && !in_array($key, $noRepeatFields))
							{
								$noRepeatFields[] = $key;
								$lastKey          = $key;
								$val              = $elementModel->storeDatabaseFormat($data[$postkey], $data);
								$elementModel->updateRowId($rowId);

								if (array_key_exists('fabrik_copy_from_table', $data))
								{
									$val = $elementModel->onCopyRow($val);
								}

								if (array_key_exists('Copy', $data))
								{
									$val = $elementModel->onSaveAsCopy($val);
								}

								// Test for backslashed quotes
								if (get_magic_quotes_gpc())
								{
									if (!$elementModel->isUpload())
									{
										$val = stripslashes($val);
									}
								}

								if ($elementModel->dataIsNull($data, $val))
								{
									$val = null;
								}

								$oRecord->$key   = $val;
								$aBindData[$key] = $val;

								if ($elementModel->isJoin() && $isJoin && array_key_exists('params', $data))
								{
									// Add in params object set by element plugin - eg fileupload element rotation/scale
									$oRecord->params = ArrayHelper::getValue($data, 'params');
									$aBindData[$key] = $oRecord->params;
								}

								$c++;
							}
						}
					}
				}
			}
		}

		$primaryKey = FabrikString::shortColName($this->getTable()->get('list.db_primary_key'));

		if ($rowId != '' && $c == 1 && $lastKey == $primaryKey)
		{
			return;
		}
		/*
		 * $$$ rob - correct rowid is now inserted into the form's rowid hidden field
		* even when useing usekey and -1, we just need to check if we are adding a new record and if so set rowid to 0
		*/
		if ($input->get('usekey_newrecord', false))
		{
			$rowId     = 0;
			$origRowId = 0;
		}

		$primaryKey = str_replace("`", "", $primaryKey);

		// $$$ hugh - if we do this, CSV importing can't maintain existing keys
		if (!$this->importingCSV)
		{
			// If its a repeat group which is also the primary group $primaryKey was not set.
			if ($primaryKey)
			{
				if (isset($oRecord->$primaryKey) && is_numeric($oRecord->$primaryKey))
				{
					$oRecord->$primaryKey = $rowId;
				}
			}
		}

		if ($origRowId == '')
		{
			/**
			 * $$$ rob added test for auto_inc as sugarid key is set from storeDatabaseFormat() and needs to be maintained
			 * $$$ rob don't do this when importing via CSV as we want to maintain existing keys (hence check on task var
			 * $$$ hugh - except ... if we are importing CSV and NOT overwriting, we need to remove the PK, as the CSV code
			 * will have set the PK to empty.  So we have to remove it from the record, otherwise it'll get written out as 0.
			 * The importingCSV and csvOverwriting vars are set in insertData() in the csv import model
			 *
			 * NOTE - for the new csvOverwriting test, I'm also using $this->importingCSV, rather than checking the task input,
			 * as I'm not sure if the latter gets set on front and backend, whereas the importingCSV variable definitely gets
			 * set in the CSV import model.
			 */
			$task = strtolower($input->get('task'));
			if (
				(
					$this->importingCSV
					&&
					!$this->csvOverwriting
				)
				||
				(
					(
						$primaryKey !== ''
						&&
						$this->getTable()->auto_inc == true
					)
					&&
					$task !== 'doimport'
				)
			)
			{
				unset($oRecord->$primaryKey);
			}

			$ok = $this->insertObject($table->get('list.db_table_name'), $oRecord, $primaryKey, false);
		}
		else
		{
			$ok = $this->updateObject($table->get('list.db_table_name'), $oRecord, $primaryKey, true);
		}

		$this->_tmpSQL = $fabrikDb->getQuery();

		if (!$ok)
		{
			$q = JDEBUG ? $fabrikDb->getQuery() : '';
			throw new ErrorException('Store row failed: ' . $q . "<br>" . $fabrikDb->getErrorMsg(), 500);
		}
		else
		{
			// Clean the cache.
			JFactory::getCache('com_' . $package)->clean();

			// $$$ rob new as if you update a record the insertid() returns 0
			$this->lastInsertId = ($rowId == '') ? $fabrikDb->insertid() : $rowId;

			return $this->lastInsertId;
		}
	}

	/**
	 * hack! copied from mysqli db driver to enable AES_ENCRYPT calls
	 *
	 * @param   string $table       table name
	 * @param   object &$object     update object
	 * @param   string $keyName     name of pk field
	 * @param   bool   $updateNulls update null values
	 *
	 * @return  mixed  query result
	 */

	public function updateObject($table, &$object, $keyName, $updateNulls = true)
	{
		$db     = $this->getDb();
		$secret = $this->config->get('secret');
		$fmtsql = 'UPDATE ' . $db->qn($table) . ' SET %s WHERE %s';
		$tmp    = array();

		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $k[0] == '_')
			{
				// Internal or NA field
				continue;
			}

			if ($k == $keyName)
			{
				// PK not to be updated
				$where = $keyName . '=' . $db->q($v);
				continue;
			}

			if ($v === null)
			{
				if ($updateNulls)
				{
					$val = 'NULL';
				}
				else
				{
					continue;
				}
			}
			else
			{
				$val = $db->q($v);
			}

			if (in_array($k, $this->encrypt))
			{
				$val = "AES_ENCRYPT($val, '$secret')";
			}

			$tmp[] = $db->qn($k) . '=' . $val;
		}

		$db->setQuery(sprintf($fmtsql, implode(",", $tmp), $where));

		if (!$db->execute())
		{
			throw new Exception($db->getErrorMsg());

			return false;
		}

		FabrikHelperHTML::debug($db->getQuery(), 'list model updateObject:');

		return true;
	}

	/**
	 * Hack! copied from mysqli db driver to enable AES_ENCRYPT calls
	 * Inserts a row into a table based on an objects properties
	 *
	 * @param   string $table   The name of the table
	 * @param   object &$object An object whose properties match table fields
	 * @param   string $keyName The name of the primary key. If provided the object property is updated.
	 *
	 * @return  bool
	 */

	public function insertObject($table, &$object, $keyName = null)
	{
		$db     = $this->getDb();
		$secret = $this->config->get('secret');
		$fmtsql = 'INSERT INTO ' . $db->qn($table) . ' ( %s ) VALUES ( %s ) ';
		$fields = array();
		$values = array();

		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $v === null)
			{
				continue;
			}

			if ($k[0] == '_')
			{
				// Internal field
				continue;
			}

			$fields[] = $db->qn($k);
			$val      = $db->q($v);

			if (in_array($k, $this->encrypt))
			{
				$val = "AES_ENCRYPT($val, '$secret')";
			}

			$values[] = $val;
		}

		$db->setQuery(sprintf($fmtsql, implode(",", $fields), implode(",", $values)));

		if (!$db->execute())
		{
			throw new Exception($db->getErrorMsg());

			return false;
		}

		$id = $db->insertid();

		if ($keyName && $id)
		{
			$object->$keyName = $id;
		}

		FabrikHelperHTML::debug($db->getQuery(), 'list model insertObject:');

		return true;
	}

	/**
	 * If an element is set to readonly, and has a default value selected then insert this
	 * data into the array that is to be bound to the table record
	 *
	 * @param   array  &$data          List data
	 * @param   object &$oRecord       To bind to table row
	 * @param   int    $isJoin         Is record join record
	 * @param   int    $rowId          Row id
	 * @param   JTable $joinGroupTable Join group table
	 *
	 * @since       1.0.6
	 *
	 * @deprecated  since 3.0.7 - we should be using formmodel addEncrytedVarsToArray() only
	 *
	 * @return  void
	 */

	protected function addDefaultDataFromRO(&$data, &$oRecord, $isJoin, $rowId, $joinGroupTable)
	{
		// $$$ rob since 1.0.6 : 10 June 08
		// Get the current record - not that which was posted
		$formModel = $this->getFormModel();
		$table     = $this->getTable();
		$input     = $this->app->input;

		if (is_null($this->origData))
		{
			/* $$$ hugh FIXME - doesn't work for rowid=-1 / usekey submissions,
			 * ends up querying "WHERE foo.userid = '<rowid>'" instead of <userid>
			* OK for now, as we should catch RO data from the encrypted vars check
			* later in this method.
			*/
			if (empty($rowId))
			{
				$this->origData = $origData = array();
			}
			else
			{
				$sql = $formModel->buildQuery();
				$db  = $this->getDb();
				$db->setQuery($sql);
				$origData       = $db->loadObject();
				$origData       = ArrayHelper::fromObject($origData);
				$origData       = is_array($origData) ? $origData : array();
				$this->origData = $origData;
			}
		}
		else
		{
			$origData = $this->origData;
		}

		$form   = $formModel->getForm();
		$groups = $formModel->getGroupsHierarchy();

		/* $$$ hugh - seems like there's no point in doing this chunk if there is no
		 $origData to work with?  Not sure if there's ever a valid reason for doing so,
		but it certainly breaks things like onCopyRow(), where (for instance) user
		elements will get reset to 0 by this code.
		*/
		$repeatGroupCounts = $input->get('fabrik_repeat_group', array(), 'array');

		if (!empty($origData))
		{
			$gCounter = 0;

			foreach ($groups as $groupModel)
			{
				if (($isJoin && $groupModel->isJoin()) || (!$isJoin && !$groupModel->isJoin()))
				{
					$elementModels = $groupModel->getPublishedElements();

					foreach ($elementModels as $elementModel)
					{
						// $$$ rob 25/02/2011 unviewable elements are now also being encrypted
						// if (!$elementModel->canUse() && $elementModel->canView()) {
						if (!$elementModel->canUse())
						{
							$element = $elementModel->getElement();
							$fullKey = $elementModel->getFullName(true, false);

							// $$$ rob 24/01/2012 if a previous joined data set had a ro element then if we werent checkign that group is the
							// same as the join group then the insert failed as data from other joins added into the current join
							if ($isJoin && ($groupModel->getId() != $joinGroupTable->id))
							{
								continue;
							}

							$key = $element->get('name');

							// $$$ hugh - allow submission plugins to override RO data
							// TODO - test this for joined data
							if ($formModel->updatedByPlugin($fullKey))
							{
								continue;
							}
							// Force a reload of the default value with $origData
							unset($elementModel->defaults);
							$default          = array();
							$repeatGroupCount = ArrayHelper::getValue($repeatGroupCounts, $groupModel->getGroup()->id);

							for ($repeatCount = 0; $repeatCount < $repeatGroupCount; $repeatCount++)
							{
								$def = $elementModel->getValue($origData, $repeatCount);

								if (is_array($def))
								{
									// Radio buttons getValue() returns an array already so don't array the array.
									$default = $def;
								}
								else
								{
									$default[] = $def;
								}
							}

							$default       = count($default) == 1 ? $default[0] : json_encode($default);
							$data[$key]    = $default;
							$oRecord->$key = $default;
						}
					}
				}

				$gCounter++;
			}
		}

		$copy = $input->getBool('Copy');

		// Check encrypted querystring vars (encrypted in form/view.html.php ) _cryptQueryString
		if (array_key_exists('fabrik_vars', $_REQUEST) && array_key_exists('querystring', $_REQUEST['fabrik_vars']))
		{
			$crypt = Worker::getCrypt();

			foreach ($_REQUEST['fabrik_vars']['querystring'] as $key => $encrypted)
			{
				// $$$ hugh - allow submission plugins to override RO data
				// TODO - test this for joined data
				if ($formModel->updatedByPlugin($key))
				{
					continue;
				}

				$key = FabrikString::shortColName($key);

				/* $$$ hugh - trying to fix issue where encrypted elements from a main group end up being added to
				 * a joined group's field list for the update/insert on the joined row(s).
				*/
				/*
				 * $$$ rob - commenting it out as this was stopping data that was not viewable or editable from being included
				* in $data. New test added inside foreach loop below
				**/
				/* if (!array_key_exists($key, $data))
				 {
				continue;
				} */
				foreach ($groups as $groupModel)
				{
					// New test to replace if (!array_key_exists($key, $data))
					// $$$ hugh - this stops elements from joined groups being added to main row, but see 'else'
					if ($isJoin)
					{
						if ($groupModel->getGroup()->id != $joinGroupTable->id)
						{
							continue;
						}
					}
					else
					{
						// $$$ hugh - need test here if not $isJoin, to stop keys from joined groups being added to main row!
						if ($groupModel->isJoin())
						{
							continue;
						}
					}

					$elementModels = $groupModel->getPublishedElements();

					foreach ($elementModels as $elementModel)
					{
						$element = $elementModel->getElement();

						/*
						 * $$$ hugh - I have a feeling this test is a Bad Thing <tm> as it is using short keys,
						 * so if two joined groups share the same element name(s) ...
						 */
						if ($element->get('name') == $key)
						{
							// Don't overwrite if something has been entered

							// $$$ rob 25/02/2011 unviewable elements are now also being encrypted
							// if (!$elementModel->canUse() && $elementModel->canView()) {
							if (!$elementModel->canUse())
							{
								// Repeat groups
								$repeatGroupCount = ArrayHelper::getValue($repeatGroupCounts, $groupModel->getGroup()->id);

								for ($repeatCount = 0; $repeatCount < $repeatGroupCount; $repeatCount++)
								{
									$enc = ArrayHelper::getValue($encrypted, $repeatCount);

									if (is_array($enc))
									{
										$v = array();

										foreach ($enc as $e)
										{
											$e   = urldecode($e);
											$v[] = empty($e) ? '' : $crypt->decrypt($e);
										}

										$v = json_encode($v);
									}
									else
									{
										$enc = urldecode($enc);
										$v   = !empty($enc) ? $crypt->decrypt($enc) : '';
									}
								}

								/* $$$ hugh - also gets called in storeRow(), not sure if we really need to
								 * call it here?  And if we do, then we should probably be calling onStoreRow
								* as well, if $data['fabrik_copy_from_table'] is set?  Can't remember why,
								* but we differentiate between the two, with onCopyRow being when a row is copied
								* using the list plugin, and onSaveAsCopy when the form plugin is used.
								*/
								if ($copy)
								{
									$v = $elementModel->onSaveAsCopy($v);
								}

								$data[$key]    = $v;
								$oRecord->$key = $v;
							}

							break 2;
						}
					}
				}
			}
		}
	}

	/**
	 * Called when the form is submitted to perform calculations
	 *
	 * @return  void
	 */
	public function doCalculations()
	{
		$cache = Worker::getCache($this);
		$cache->call(array(get_class($this), 'cacheDoCalculations'), $this->getId());
	}

	/**
	 * Cache do calculations
	 *
	 * @param   int $listId List id
	 *
	 * @return  void
	 */

	public static function cacheDoCalculations($listId)
	{
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('cacheDoCalculations: start') : null;

		$listModel = new Lizt;
		$listModel->setId($listId);
		$formModel = $listModel->getFormModel();

		JDEBUG ? $profiler->mark('cacheDoCalculations, getGroupsHierarchy: start') : null;
		$groups = $formModel->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$params = $elementModel->getParams();
				$update = false;

				if ($params->get('sum_on', 0) == 1)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, sum_on: start') : null;
					$aSumCals = $elementModel->sum($listModel);
					$params->set('sum_value_serialized', serialize($aSumCals[1]));
					$params->set('sum_value', $aSumCals[0]);
					$update = true;
				}

				if ($params->get('avg_on', 0) == 1)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, avg_on: start') : null;
					$aAvgCals = $elementModel->avg($listModel);
					$params->set('avg_value_serialized', serialize($aAvgCals[1]));
					$params->set('avg_value', $aAvgCals[0]);
					$update = true;
				}

				if ($params->get('median_on', 0) == 1)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, median_on: start') : null;
					$medians = $elementModel->median($listModel);
					$params->set('median_value_serialized', serialize($medians[1]));
					$params->set('median_value', $medians[0]);
					$update = true;
				}

				if ($params->get('count_on', 0) == 1)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, count_on: start') : null;
					$aCountCals = $elementModel->count($listModel);
					$params->set('count_value_serialized', serialize($aCountCals[1]));
					$params->set('count_value', $aCountCals[0]);
					$update = true;
				}

				if ($params->get('custom_calc_on', 0) == 1)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, custom_calc_on: start') : null;
					$aCustomCalcCals = $elementModel->custom_calc($listModel);
					$params->set('custom_calc_value_serialized', serialize($aCustomCalcCals[1]));
					$params->set('custom_calc_value', $aCustomCalcCals[0]);
					$update = true;
				}

				if ($update)
				{
					JDEBUG ? $profiler->mark('cacheDoCalculations, storeAttribs: start') : null;
					$elementModel->storeAttribs();
				}
			}
		}
	}

	/**
	 * Check to see if pre-filter should be applied
	 *
	 * @param   int $gid view access level to check against
	 *
	 * @return  bool  Must apply filter
	 */
	protected function mustApplyFilter($gid)
	{
		return in_array($gid, $this->user->getAuthorisedViewLevels());
	}

	/**
	 * Set the connection id - used when creating a new table
	 *
	 * @param   int $id connection id
	 *
	 * @return  void
	 */

	public function setConnectionId($id)
	{
		$this->getTable()->connection_id = $id;
	}

	/**
	 * Get group by (can be set via qs group_by var)
	 *
	 * @return  string
	 */

	public function getGroupBy()
	{
		$elementModel = $this->getGroupByElement();

		if (!$elementModel)
		{
			return '';
		}

		return $elementModel->getFullName(true, false);
	}

	/**
	 * Test if the main J user can create mySQL tables
	 *
	 * @return  bool
	 */

	public function canCreateDbTable()
	{
		return true;
	}

	/**
	 * Make id element
	 *
	 * @param   int $groupId element group id
	 *
	 * @since Fabrik 3.0
	 *
	 * @return  void
	 */

	public function makeIdElement($groupId)
	{
		$pluginManager  = Worker::getPluginManager();
		$element        = $pluginManager->getPlugIn('internalid', 'element');
		$item           = $element->getDefaultProperties();
		$item->name     = $item->label = 'id';
		$item->group_id = $groupId;

		if (!$item->store())
		{
			JError::raiseWarning(500, $item->getError());

			return false;
		}

		return true;
	}

	/**
	 * Make foreign key element
	 *
	 * @param   int $groupId element group id
	 *
	 * @since   Fabrik 3.0
	 *
	 * @return void
	 */

	public function makeFkElement($groupId)
	{
		$pluginManager  = Worker::getPluginManager();
		$element        = $pluginManager->getPlugIn('field', 'element');
		$item           = $element->getDefaultProperties();
		$item->name     = $item->label = 'parent_id';
		$item->hidden   = 1;
		$item->group_id = $groupId;

		if (!$item->store())
		{
			JError::raiseWarning(500, $item->getError());

			return false;
		}

		return true;
	}

	/**
	 * Updates the table record to point to the newly created form
	 *
	 * @param   int $formId form id
	 *
	 * @deprecated - not used
	 *
	 * @return  mixed  null/error
	 */

	protected function _updateFormId($formId)
	{
		$item          = $this->getTable();
		$item->form_id = $formId;

		if (!$item->store())
		{
			return JError::raiseWarning(500, $item->getError());
		}
	}

	/**
	 * Get the tables primary key and if the primary key is auto increment
	 *
	 * @param   string $table Optional table name (used when getting pk to joined tables)
	 *
	 * @deprecated
	 *
	 * @return  mixed    If ok returns array(key, extra, type, name) otherwise
	 */

	public function getPrimaryKeyAndExtra($table = null)
	{
		return $this->getStorage()->setTable($table)->getPrimarykeyAndDefault();
	}

	/**
	 * Run the prefilter sql and replace any placeholders in the subsequent prefilter
	 *
	 * @param   mixed $selValue string/array prefilter value
	 *
	 * @return  mixed  string/array prefilter value
	 */

	protected function prefilterParse($selValue)
	{
		$isstring = false;

		if (is_string($selValue))
		{
			$isstring = true;
			$selValue = array($selValue);
		}

		$preSQL = htmlspecialchars_decode($this->getParams()->get('prefilter_query'), ENT_QUOTES);

		if (trim($preSQL) != '')
		{
			$db = Worker::getDbo();
			$w  = new Worker;
			$w->replaceRequest($preSQL);
			$preSQL = $w->parseMessageForPlaceHolder($preSQL);
			$db->setQuery($preSQL);
			$q = $db->loadObjectList();

			if (!$q)
			{
				// Try the table's connection db for the query
				$thisDb = $this->getDb();
				$thisDb->setQuery($preSQL);
				$q = $thisDb->loadObjectList();
			}

			if (!empty($q))
			{
				$q = $q[0];
			}
		}

		if (isset($q))
		{
			foreach ($q as $key => $val)
			{
				if (substr($key, 0, 1) != '_')
				{
					$found = false;

					for ($i = 0; $i < count($selValue); $i++)
					{
						if (strstr($selValue[$i], '{$q-&gt;' . $key))
						{
							$found   = true;
							$pattern = '{$q-&gt;' . $key . "}";
						}

						if (strstr($selValue[$i], '{$q->' . $key))
						{
							$found   = true;
							$pattern = '{$q->' . $key . "}";
						}

						if ($found)
						{
							$selValue[$i] = str_replace($pattern, $val, $selValue[$i]);
						}
					}
				}
			}
		}
		else
		{
			/* Parse for default values only
			 * $$$ hugh - this pattern is being greedy, so for example ...
			 * foo {$my->id} bar {$my->id} gaprly
			 * ... matches everyting from first to last brace, like ...
			 * {$my->id} bar {$my->id}
			 *$pattern = "/({[^}]+}).*}?/s";
			 */
			$pattern = "/({[^}]+})/";

			for ($i = 0; $i < count($selValue); $i++)
			{
				$ok = preg_match($pattern, $selValue[$i], $matches);

				foreach ($matches as $match)
				{
					$matchx = String::substr($match, 1, String::strlen($match) - 2);

					// A default option was set so lets use that
					if (strstr($matchx, '|'))
					{
						$bits         = explode('|', $matchx);
						$selValue[$i] = str_replace($match, $bits[1], $selValue[$i]);
					}
				}
			}
		}

		$selValue = $isstring ? $selValue[0] : $selValue;

		// Replace {authorisedViewLevels} with array of view levels the user can access
		if (is_array($selValue))
		{
			foreach ($selValue as &$v)
			{
				if (strstr($v, '{authorisedViewLevels}'))
				{
					$v = $this->user->getAuthorisedViewLevels();
				}
			}
		}
		else
		{
			if (strstr($selValue, '{authorisedViewLevels}'))
			{
				$selValue = $this->user->getAuthorisedViewLevels();
			}
		}

		return $selValue;
	}

	/**
	 * Get the lists db table's indexes
	 *
	 * @param   string $table table name, only needed if join
	 *
	 * @deprecated use storage admin model instead
	 *
	 * @return array  list indexes
	 */

	protected function getIndexes($table = '')
	{
		if (empty($table))
		{
			$table = $this->getTable()->get('list.db_table_name');
		}
		if (!isset($this->indexes))
		{
			$this->indexes = array();
		}
		if (!array_key_exists($table, $this->indexes))
		{
			$db = $this->getDb();
			$db->setQuery('SHOW INDEXES FROM ' . $db->qn($table));
			$this->indexes[$table] = $db->loadObjectList();
		}

		return $this->indexes[$table];
	}

	/**
	 * Add an index to the table
	 *
	 * @param   string $field  field name
	 * @param   string $prefix index name prefix (allows you to differentiate between indexes created in
	 *                         different parts of fabrik)
	 * @param   string $type   index type
	 * @param   int    $size   index length
	 *
	 * @deprecated use Storage admin model instead.
	 *
	 * @return void
	 */

	public function addIndex($field, $prefix = '', $type = 'INDEX', $size = '')
	{

		if (is_numeric($field))
		{
			$el    = $this->getFormModel()->getElement($field, true);
			$field = $el->getFullName(true, false);
		}

		/* $$$ hugh - @TODO $field is in 'table.element' format but $indexes
		 * has Column_name as just 'element' ... so we're always rebuilding indexes!
		* I'm in the middle of fixing something else, must come back and fix this!!
		* OK, moved these two lines from below to here
		*/
		$field = str_replace('_raw', '', $field);

		// $$$ rob 29/03/2011 ensure its in tablename___elementname format
		$field = str_replace('.', '___', $field);

		// $$$ rob 28/02/2011 if index in joined table we need to use that the make the key on
		if (!strstr($field, '___'))
		{
			$table = $this->getTable()->get('list.db_table_name');
		}
		else
		{
			$fieldParts = explode('___', $field);
			$table      = array_shift($fieldParts);
		}

		$field = FabrikString::shortColName($field);

		$indexes = $this->getIndexes($table);

		ArrayHelper::filter($indexes, 'Column_name', $field);

		if (!empty($indexes))
		{
			// An index already exists on that column name no need to add
			return;
		}

		$db = $this->getDb();

		if ($field == '')
		{
			return;
		}

		if ($size != '')
		{
			$size = '( ' . $size . ' )';
		}

		$this->dropIndex($field, $prefix, $type, $table);
		$query = ' ALTER TABLE ' . $db->qn($table) . ' ADD INDEX ' . $db->qn("fb_{$prefix}_{$field}_{$type}") . ' ('
			. $db->qn($field) . ' ' . $size . ')';
		$db->setQuery($query);

		try
		{
			$db->execute();
		} catch (RuntimeException $e)
		{
			// Try to suppress error
			$this->setError($e->getMessage());
		}
	}

	/**
	 * Drop an index
	 *
	 * @param   string $field  field name
	 * @param   stirng $prefix index name prefix (allows you to differentiate between indexes created in
	 *                         different parts of fabrik)
	 * @param   string $type   table name @since 29/03/2011
	 * @param   string $table  db table name
	 *
	 * @deprecated used admin Storage
	 *
	 * @return  string  index type
	 */

	public function dropIndex($field, $prefix = '', $type = 'INDEX', $table = '')
	{
		$db    = $this->getDb();
		$table = $table == '' ? $this->getTable()->get('list.db_table_name') : $table;
		$field = FabrikString::shortColName($field);

		if ($field == '')
		{
			return;
		}

		$db->setQuery("SHOW INDEX FROM " . $db->qn($table));
		$dbIndexes = $db->loadObjectList();

		if (is_array($dbIndexes))
		{
			foreach ($dbIndexes as $index)
			{
				if ($index->Key_name == "fb_{$prefix}_{$field}_{$type}")
				{
					$db->setQuery("ALTER TABLE " . $db->qn($table) . " DROP INDEX " . $db->qn("fb_{$prefix}_{$field}_{$type}"));

					try
					{
						$db->execute();
					} catch (Exception $e)
					{
						$this->setError($e->getMessage());
					}
					break;
				}
			}
		}
	}

	/**
	 * Drop all indexes for a give element name
	 * required when encrypting text fileds whcih have a key on them , as blobs cant have keys
	 *
	 * @param   string $field field name to drop
	 * @param   string $table table to drop from
	 *
	 * @return  void
	 */

	public function dropColumnNameIndex($field, $table = '')
	{
		$db    = $this->getDb();
		$table = $table == '' ? $this->getTable()->get('list.db_table_name') : $table;
		$field = FabrikString::shortColName($field);

		if ($field == '')
		{
			return;
		}

		$db->setQuery("SHOW INDEX FROM " . $db->qn($table) . ' WHERE Column_name = ' . $db->q($field));
		$dbIndexes = $db->loadObjectList();

		foreach ($dbIndexes as $index)
		{
			$db->setQuery(" ALTER TABLE " . $db->qn($table) . " DROP INDEX " . $db->qn($index->Key_name));
			$db->execute();
		}
	}

	/**
	 * Delete joined records when deleting the main row
	 *
	 * @param   string $val quoted primary key values from the main table's rows that are to be deleted
	 *
	 * @return  void
	 */

	protected function deleteJoinedRows($val)
	{
		$db     = $this->getDb();
		$query  = $db->getQuery(true);
		$params = $this->getParams();

		if ($params->get('delete-joined-rows', false))
		{
			$joins = $this->getJoins();

			for ($i = 0; $i < count($joins); $i++)
			{
				$join = $joins[$i];

				if ((int) $join->list_id !== 0)
				{
					$query->clear();
					$query->delete($db->qn($join->table_join))->where($db->qn($join->table_join_key) . ' IN (' . $val . ')');
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}

	/**
	 * Deletes records from a table
	 *
	 * @param   mixed  &$ids Key values to delete (string or array)
	 * @param   string $key  Key to use (leave empty to default to the list's key)
	 *
	 * @throws  Exception  If no key found or main delete row fails (perhaps due to INNODB foreign constraints)
	 *
	 * @return  bool
	 */

	public function deleteRows(&$ids, $key = '')
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		$val   = $ids;
		$table = $this->getTable();
		$db    = $this->getDb();

		if ($key == '')
		{
			$key = $table->get('list.db_primary_key');

			if ($key == '')
			{
				throw new Exception(FText::_("COM_FABRIK_NO_KEY_FOUND_FOR_THIS_TABLE"));
			}
		}

		$c = count($val);

		foreach ($val as &$v)
		{
			$v = $db->q($v);
		}

		$val = implode(',', $val);

		// $$$ rob - if we are not deleting joined rows then onloy load in the first row
		// otherwise load in all rows so we can apply onDeleteRows() to all the data
		if ($this->getParams()->get('delete-joined-rows', false) == false)
		{
			$nav = $this->getPagination($c, 0, $c);
		}

		$this->setPluginQueryWhere('list.deleteRows', $key . ' IN (' . $val . ')');

		/* $$$ hugh - need to clear cached data, 'cos we called getTotalRecords from the controller, which now
		 * calls getData(), and will have cached all rows on this page, not just the ones being deleted, which means
		* things like form and element onDelete plugins will get handed a whole page of rows, not just the ones
		* selected for delete!  Ooops.
		*/
		$this->reset();
		$rows = $this->getData();

		/* $$$ hugh - we need to check delete perms, see:
		 * http://fabrikar.com/forums/showthread.php?p=102670#post102670
		* Short version, if user has access for a table plugin, they get a checkbox on the row, but may not have
		* delete access on that row.
		*/
		$removed_id = false;

		foreach ($rows as &$group)
		{
			foreach ($group as $group_key => $row)
			{
				if (!$this->canDelete($row))
				{
					// Can't delete, so remove row data from $rows, and the id from $ids, and queue a message
					foreach ($ids as $id_key => $id)
					{
						if ($id == $row->__pk_val)
						{
							unset($ids[$id_key]);
							continue;
						}
					}

					unset($group[$group_key]);
					$this->app->enqueueMessage('NO PERMISSION TO DELETE ROW');
					$removed_id = true;
				}
			}
		}

		// See if we have any rows left to delete after checking perms
		if (empty($ids))
		{
			return;
		}
		// Redo $val list of ids in case we zapped any on canDelete check
		if ($removed_id)
		{
			$val = $ids;
			$c   = count($val);

			foreach ($val as &$v)
			{
				$v = $db->q($v);
			}

			$val = implode(',', $val);
		}

		$this->rowsToDelete = $rows;
		$groupModels        = $this->getGroupsHierarchy();

		foreach ($groupModels as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->onDeleteRows($rows);
			}
		}

		$pluginManager = Worker::getPluginManager();

		/* $$$ hugh - added onDeleteRowsForm plugin (needed it so fabrikjuser form plugin can delete users)
		 * NOTE - had to call it onDeleteRowsForm rather than onDeleteRows, otherwise runPlugins() automagically
		* runs the element onDeleteRows(), which we already do above.  And with the code as-is, that won't work
		* from runPlugins() 'cos it won't pass it the $rows it needs.  So i have to sidestep the issue by using
		* a different trigger name.  Added a default onDeleteRowsForm() to plugin-form.php, and implemented
		* (and tested) user deletion in fabrikjuser.php using this trigger.  All seems to work.  7/28/2009
		*/

		if (in_array(false, $pluginManager->runPlugins('onDeleteRowsForm', $this->getFormModel(), 'form', $rows)))
		{
			return false;
		}

		$pluginManager->getPlugInGroup('list');

		if (in_array(false, $pluginManager->runPlugins('onDeleteRows', $this, 'list')))
		{
			return false;
		}

		$query = $db->getQuery(true);
		$query->delete($db->qn($table->get('list.db_table_name')))->where($key . ' IN (' . $val . ')');
		$db->setQuery($query);

		if (!$db->execute())
		{
			throw new Exception($db->getErrorMsg());
		}

		$this->deleteJoinedRows($val);

		// Clean the cache.
		$cache = JFactory::getCache($this->app->input->get('option'));
		$cache->clean();

		$this->unsetPluginQueryWhere('list.deleteRows');

		if (in_array(false, $pluginManager->runPlugins('onAfterDeleteRowsForm', $this->getFormModel(), 'form', $rows)))
		{
			return false;
		}

		if (in_array(false, $pluginManager->runPlugins('onAfterDeleteRows', $this, 'list')))
		{
			return false;
		}

		return true;
	}

	/**
	 * Remove all records from the table
	 *
	 * @return  mixed
	 */

	public function dropData()
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$table = $this->getTable();
		$query->delete($db->qn($table->get('list.db_table_name')));
		$db->setQuery($query);

		if (!$db->execute())
		{
			return JError::raiseWarning(FText::_($db->getErrorMsg()));
		}
		// Clean the cache.
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		JFactory::getCache('com_' . $package)->clean();

		return true;
	}

	/**
	 * Truncate the main db table and any internal joined groups
	 *
	 * @return  void
	 */

	public function truncate()
	{
		$db   = $this->getDb();
		$item = $this->getTable();

		$pluginManager = Worker::getPluginManager();

		$formModel = $this->getFormModel();
		$pluginManager->runPlugins('onBeforeTruncate', $this, 'list');
		$pluginManager->runPlugins('onBeforeTruncate', $formModel, 'form');

		// Remove any groups that were set to be repeating and hence were storing in their own db table.
		$joinModels = $this->getInternalRepeatJoins();

		foreach ($joinModels as $joinModel)
		{
			$db->setQuery("TRUNCATE " . $db->qn($joinModel->getJoin()->table_join));
			$db->execute();
		}

		$db->setQuery("TRUNCATE " . $db->qn($item->get('list.db_table_name')));
		$db->execute();

		// 3.0 clear filters (resets limitstart so that subsequently added records are shown)
		$this->getFilterModel()->clearFilters();

		// Clean the cache.
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		JFactory::getCache('com_' . $package)->clean();
	}

	/**
	 * Test if a field already exists in the database
	 *
	 * @param   string $field  field to test
	 * @param   array  $ignore id's to ignore
	 *
	 * @return  bool
	 */

	public function fieldExists($field, $ignore = array())
	{
		$field       = String::strtolower($field);
		$groupModels = $this->getGroupsHierarchy();

		foreach ($groupModels as $groupModel)
		{
			if (!$groupModel->isJoin())
			{
				// Don't check groups that aren't in this table
				$elementModels = $groupModel->getMyElements();

				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();

					if (String::strtolower($element->get('name')) == $field && !in_array($element->get('id'), $ignore))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Build a dropdown list of fileds
	 *
	 * @param   int    $cnnId          Connection id to use
	 * @param   string $tbl            Table to load fields for
	 * @param   string $incSelect      Show "please select" top option
	 * @param   bool   $incTableName   Append field name values with table name
	 * @param   string $selectListName Name of drop down
	 * @param   string $selected       Selected option
	 * @param   string $className      Class name
	 *
	 * @return  string    html to be added to DOM
	 */

	public function getFieldsDropDown($cnnId, $tbl, $incSelect, $incTableName = false, $selectListName = 'order_by', $selected = null,
		$className = "inputbox")
	{
		$this->setConnectionId($cnnId);
		$aFields    = $this->getStorage()->getDBFields($tbl);
		$fieldNames = array();

		if ($incSelect != '')
		{
			$fieldNames[] = JHTML::_('select.option', '', $incSelect);
		}

		if (is_array($aFields))
		{
			foreach ($aFields as $oField)
			{
				if ($incTableName)
				{
					$fieldNames[] = JHTML::_('select.option', $tbl . '___' . $oField->Field, $oField->Field);
				}
				else
				{
					$fieldNames[] = JHTML::_('select.option', $oField->Field);
				}
			}
		}

		$opts          = 'class="' . $className . '" size="1" ';
		$fieldDropDown = JHTML::_('select.genericlist', $fieldNames, $selectListName, $opts, 'value', 'text', $selected);

		return str_replace("\n", "", $fieldDropDown);
	}

	/**
	 * Create the RSS href link to go in the table template
	 *
	 * @return  string    RSS link
	 */

	public function getRSSFeedLink()
	{
		$link    = '';
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		if ($this->getParams()->get('rss') == '1')
		{
			// $$$ rob test fabriks own feed renderer
			$link = 'index.php?option=com_' . $package . '&view=list&listid=' . $this->getId() . "&format=fabrikfeed";

			if (!$this->app->isAdmin())
			{
				$link = JRoute::_($link);
			}
		}

		return $link;
	}

	/**
	 * Iterates through string to replace every
	 * {placeholder} with row data
	 * (added by hugh, does the same thing as parseMessageForPlaceHolder in parent
	 * class, but for rows instead of forms)
	 *
	 * NOTE - I can't remember why I addded this way back when in 2.x, instead of using the helper function,
	 * I just know there was a good reason, to do with the helper func making assumptions about something
	 * (I think to do with how form data is formatted) which weren't true when rendering list data.  I have
	 * a suspicion that in the intervening years, the helper func and the way we format data may now be
	 * copacetic, and we could do away with this separation, and just use the normal helper func.  Might be
	 * worth testing, as this code looks like it has suffered bitrot, and doesn't do a number of things the main
	 * helper func now does.
	 *
	 * @param   string $msg        text to parse
	 * @param   array  &$row       of row data
	 * @param   bool   $addslashes add slashes to the replaced data (default = false) set to true in fabrikcalc element
	 *
	 * @return  string  parsed message
	 */

	public function parseMessageForRowHolder($msg, &$row, $addslashes = false)
	{
		$this->aRow = $row;

		if (!strstr($msg, '{'))
		{
			return $msg;
		}

		$this->parseAddSlases     = $addslashes;
		$msg                      = Worker::replaceWithUserData($msg);
		$msg                      = Worker::replaceWithGlobals($msg);
		$msg                      = preg_replace("/{}/", "", $msg);
		$this->rowIdentifierAdded = false;
		/* replace {element name} with form data */
		/* $$$ hugh - testing changing the regex so we don't blow away PHP structures!  Added the \s so
		 * we only match non-space chars in {}'s.  So unless you have some code like "if (blah) {foo;}", PHP
		* block level {}'s should remain unmolested.
		*/
		$msg = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceWithRowData'), $msg);

		$lang = JFactory::getLanguage()->getTag();
		$lang = str_replace('-', '_', $lang);
		$msg  = str_replace('{lang}', $lang, $msg);

		return $msg;
	}

	/**
	 * Called from parseMessageForRowHolder to iterate through string to replace
	 * {placeholder} with row data
	 *
	 * @param   array $matches found in parseMessageForRowHolder
	 *
	 * @return  string    posted data that corresponds with placeholder
	 */

	private function replaceWithRowData($matches)
	{
		$match = $matches[0];

		// $$$ felixkat - J! plugin closings, i.e  {/foo} were getting caught here.
		if (preg_match('[{/]', $match))
		{
			return $match;
		}

		/* strip the {} */
		$match = String::substr($match, 1, String::strlen($match) - 2);

		// $$$ hugh - in case any {$my->foo} or {$_SERVER->FOO} paterns are left over, avoid 'undefined index' warnings
		if (preg_match('#^\$#', $match))
		{
			return '';
		}

		$match = str_replace('.', '___', $match);

		// $$$ hugh - allow use of {$rowpk} or {rowpk} to mean the rowid of the row within a table
		if ($match == 'rowpk' || $match == '$rowpk' || $match == 'rowid')
		{
			$this->rowIdentifierAdded = true;
			$match                    = '__pk_val';
		}

		$match = preg_replace("/ /", "_", $match);

		if ($match == 'formid')
		{
			return $this->getFormModel()->getId();
		}

		$return = ArrayHelper::getValue($this->aRow, $match);

		if (is_array($return))
		{
			$this->parseAddSlases = true;
			$return               = json_encode($return);
		}

		if ($this->parseAddSlases)
		{
			$return = htmlspecialchars($return, ENT_QUOTES, 'UTF-8');
		}

		return $return;
	}

	/**
	 * This is just way too confuins - view details link now always returns a view details link and not an edit link
	 * ?!!! get the link to view the records details
	 *
	 * @param   object &$row active list row
	 * @param   string $view 3.0 depreciated
	 *
	 * @return  string    url of view details link
	 *
	 * @since  3.0
	 *
	 * @retun  string  link
	 */

	protected function viewDetailsLink(&$row, $view = null)
	{
		// FIXME links incorrect
		$package       = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$itemId        = Worker::itemId();
		$keyIdentifier = $this->getKeyIdentifier($row);
		$item          = $this->getItem();
		$view          = 'details';
		$customLink    = $this->getCustomLink('url', 'details');

		if (trim($customLink) === '')
		{
			$link = '';

			// $$$ hugh - if we don't do this on feeds, links with subfolders in root get screwed up because no BASE_HREF is set
			if ($this->app->input->get('format', '') == 'fabrikfeed')
			{
				$link .= COM_FABRIK_LIVESITE;
			}

			$link .= 'index.php?option=com_' . $package . '&view=' . $view . '&id=' . $item->get('id') . $keyIdentifier . '&Itemid=' . $itemId;
			$link = JRoute::_($link);
		}
		else
		{
			// Custom link
			$link = $this->makeCustomLink($customLink, $row);
		}

		return $link;
	}

	/**
	 * Create a custom edit/view details link
	 *
	 * @param   string $link link
	 * @param   object $row  row's data
	 *
	 * @return  string  custom link
	 */

	protected function makeCustomLink($link, $row)
	{
		$link          = htmlspecialchars($link);
		$keyIdentifier = $this->getKeyIdentifier($row);
		$row           = ArrayHelper::fromObject($row);
		$link          = $this->parseMessageForRowHolder($link, $row);

		if (preg_match('/([\?&]rowid=)/', htmlspecialchars_decode($link)))
		{
			$this->rowIdentifierAdded = true;
		}

		if ($this->rowIdentifierAdded === false)
		{
			if (strstr($link, '?'))
			{
				$link .= $keyIdentifier;
			}
			else
			{
				$link .= '?' . str_replace('&', '', $keyIdentifier);
			}
		}

		$link = JRoute::_($link);

		return $link;
	}

	/**
	 * Get a custom link
	 *
	 * @param   string $type link type
	 * @param   string $mode edit/details link
	 *
	 * @return  string  link
	 */

	protected function getCustomLink($type = 'url', $mode = 'edit')
	{
		$params = $this->getParams();

		if ($type === 'url')
		{
			$str = ($mode == 'edit') ? $params->get('editurl') : $params->get('detailurl');
		}
		else
		{
			$str = ($mode == 'edit') ? $params->get('editurl_attribs') : $params->get('detailurl_attribs');
		}

		$w = new Worker;

		return $w->parseMessageForPlaceHolder($str);
	}

	/**
	 * Get the link to edit the records details
	 *
	 * @param   object &$row active table row
	 *
	 * @return  string  url of view details link
	 */

	protected function editLink(&$row)
	{
		$package       = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$itemId        = Worker::itemId();
		$keyIdentifier = $this->getKeyIdentifier($row);
		$table         = $this->getTable();
		$customLink    = $this->getCustomLink('url', 'edit');

		if ($customLink == '')
		{
			if ($this->app->isAdmin())
			{
				$url = 'index.php?option=com_' . $package . '&task=form.view&id=' . $this->getId() . $keyIdentifier;
			}
			else
			{
				$url = 'index.php?option=com_' . $package . '&view=form&Itemid=' . $itemId . '&formid=' . $table->get('form_id') . $keyIdentifier . '&listid='
					. $this->getId();
			}

			$link = JRoute::_($url);
		}
		else
		{
			$link = $this->makeCustomLink($customLink, $row);
		}

		return $link;
	}

	/**
	 * Make the drop sql statement for the table
	 *
	 * @return  string  drop table sql
	 */

	public function getDropTableSQL()
	{
		$db       = Worker::getDbo();
		$genTable = $this->getGenericTableName();
		$sql      = "DROP TABLE IF EXISTS " . $db->qn($genTable);

		return $sql;
	}

	/**
	 * Convert a prefix__tablename to #__tablename
	 *
	 * @return  string  table name
	 */

	public function getGenericTableName()
	{
		$table = $this->getTable();

		return str_replace($this->app->get('dbprefix'), '#__', $table->get('list.db_table_name'));
	}

	/**
	 * Make the create sql statement for the table
	 *
	 * @param   bool   $addIfNotExists add 'if not exists' to query
	 * @param   string $table          table to get sql for(leave out to use models table)
	 *
	 * @return  string    sql to drop & or create table
	 */

	public function getCreateTableSQL($addIfNotExists = false, $table = null)
	{
		$addIfNotExists = $addIfNotExists ? 'IF NOT EXISTS ' : '';

		if (is_null($table))
		{
			$table = $this->getGenericTableName();
		}

		$fields     = $this->getStorage()->getDBFields($table);
		$primaryKey = "";
		$sql        = "";
		$table      = FabrikString::safeColName($table);

		if (is_array($fields))
		{
			$sql .= "CREATE TABLE $addIfNotExists" . $table . " (\n";

			foreach ($fields as $field)
			{
				$field->Field = FabrikString::safeColName($field->Field);

				if ($field->Key == 'PRI' && $field->Extra == 'auto_increment')
				{
					$primaryKey = "PRIMARY KEY ($field->Field)";
				}

				$sql .= "$field->Field ";
				$sql .= ' ' . $field->Type . ' ';

				if ($field->Null == '')
				{
					$sql .= " NOT NULL ";
				}

				if ($field->Default != '' && $field->Key != 'PRI')
				{
					if ($field->Default == 'CURRENT_TIMESTAMP')
					{
						$sql .= "DEFAULT $field->Default";
					}
					else
					{
						$sql .= "DEFAULT '$field->Default'";
					}
				}

				$sql .= $field->Extra . ",\n";
			}

			if ($primaryKey == '')
			{
				$sql = rtrim($sql, ",\n");
			}

			$sql .= $primaryKey . ");";
		}

		return $sql;
	}

	/**
	 * Make the create sql statement for inserting the table data
	 * used in package export
	 *
	 * @param   object $oExporter exporter
	 *
	 * @deprecated - not used?
	 *
	 * @return  string    sql to drop & or create table
	 */

	public function getInsertRowsSQL($oExporter)
	{
		@set_time_limit(300);
		$table       = $this->getTable();
		$memoryLimit = ini_get('memory_limit');
		$db          = $this->getDb();
		/*
		 * dont load in all the table data as on large tables this gives a memory error
		* in fact this wasnt the problem, but rather the $sql var becomes too large to hold in memory
		* going to try saving to a file on the server and then compressing that and sending it as a header for download
		*/
		$tableName = $table->get('list.db_table_name');
		$query     = $db->getQuery(true);
		$query->select($table->get('list.db_primary_key'))->from($tableName);
		$db->setQuery($query);
		$keys            = $db->loadColumn();
		$sql             = "";
		$query           = $db->getQuery(true);
		$dump_buffer_len = 0;

		if (is_array($keys))
		{
			foreach ($keys as $id)
			{
				$query->clear();
				$query->select('*')->from($tableName)->where($tableName . ' = ' . $id);
				$db->setQuery($query);
				$row    = $db->loadObject();
				$fmtsql = "\t<query>INSERT INTO " . $tableName . " ( %s ) VALUES ( %s )</query>";
				$values = array();
				$fields = array();

				foreach ($row as $k => $v)
				{
					$fields[] = $db->qn($k);
					$values[] = $db->q($v);
				}

				$sql .= sprintf($fmtsql, implode(",", $fields), implode(",", $values));
				$sql .= "\n";
				$dump_buffer_len += String::strlen($sql);

				if ($dump_buffer_len > $memoryLimit)
				{
					$oExporter->writeExportBuffer($sql);
					$sql             = "";
					$dump_buffer_len = 0;
				}

				unset($values);
				unset($fmtsql);
			}
		}

		$oExporter->writeExportBuffer($sql);
	}

	/**
	 * Get a row of data from the table
	 *
	 * @param   int  $id       Id
	 * @param   bool $format   The data
	 * @param   bool $loadJoin Load the rows joined data @since 2.0.5 (used in J Content plugin)
	 *
	 * @return  object    Row
	 */

	public function getRow($id, $format = false, $loadJoin = false)
	{
		if (is_null($this->rows))
		{
			$this->rows = array();
		}

		$sig = $id . '.' . $format . '.' . $loadJoin;

		if (array_key_exists($sig, $this->rows))
		{
			return $this->rows[$sig];
		}

		$fabrikDb  = $this->getDb();
		$formModel = $this->getFormModel();
		$formModel->reset();
		$this->reset();
		$formModel->rowId = $id;

		$sql = $formModel->buildQuery();
		$fabrikDb->setQuery($sql);

		if (!$loadJoin)
		{
			if ($format == true)
			{
				$row = $fabrikDb->loadObject();
				$row = array($row);
				$this->formatData($row);
				/* $$$ hugh - if table is grouped, formatData will have turned $row into an
				 * assoc array, so can't assume 0 is first key.
				* $this->rows[$sig] = $row[0][0];
				*/
				$row              = ArrayHelper::getValue($row, ArrayHelper::firstKey($row), array());
				$this->rows[$sig] = ArrayHelper::getValue($row, 0, new stdClass);
			}
			else
			{
				$this->rows[$sig] = $fabrikDb->loadObject();
			}
		}
		else
		{
			$rows = $fabrikDb->loadObjectList();
			$formModel->setJoinData($rows);

			if ($format == true)
			{
				$rows = array(ArrayHelper::toObject($rows));
				$this->formatData($rows);
				$rows = $rows[0];
				/* $$$ hugh - if list is grouped, formatData will have re-index as assoc array,
				 /* so can't assume 0 is first key.
				*/
				$this->rows[$sig] = ArrayHelper::getValue($rows, ArrayHelper::firstKey($rows), array());
			}
			else
			{
				// Content plugin - rows is 1 dimensional array
				$this->rows[$sig] = $rows;
			}
		}

		if (is_array($this->rows[$sig]))
		{
			$this->rows[$sig] = ArrayHelper::toObject($this->rows[$sig]);
		}

		return $this->rows[$sig];
	}

	/**
	 * Find a row in the table that matches " key LIKE '%val' "
	 *
	 * @param   string $key    key
	 * @param   string $val    value
	 * @param   bool   $format format the row
	 *
	 * @return  object    row
	 */

	public function findRow($key, $val, $format = false)
	{
		$input             = $this->app->input;
		$usekey            = $input->get('usekey');
		$usekey_comparison = $input->get('usekey_comparison');
		$input->set('usekey', $key);
		$input->set('usekey_comparison', 'like');
		$row = $this->getRow($val, $format);
		$input->set('usekey', $usekey);
		$input->set('usekey_comparison', $usekey_comparison);

		return $row;
	}

	/**
	 * Ajax get record specified by row id
	 *
	 * @param   string $mode mode
	 *
	 * @return  string  json encoded row
	 */

	public function xRecord($mode = 'table')
	{
		$input    = $this->app->input;
		$package  = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$fabrikDb = $this->getDb();
		$cursor   = $input->getInt('cursor', 1);
		$db       = $this->getDb();
		$this->getConnection();
		$this->outputFormat = 'json';
		$nav                = $this->getPagination(1, $cursor, 1);

		if ($mode == 'table')
		{
			$query = $this->buildQuery();
			$this->setBigSelects();
			$fabrikDb->setQuery($query, $this->limitStart, $this->limitLength);
			$data = $fabrikDb->loadObjectList();
		}
		else
		{
			// Get the row id
			$table = $this->getTable();
			$query = $db->getQuery(true);
			$query->select($table->get('list.db_primary_key'))->from($table->get('list.db_table_name'));
			$query = $this->buildQueryJoin($query);
			$query = $this->buildQueryOrder($query);
			$fabrikDb->setQuery($query, $nav->limitstart, $nav->limit);
			$rowId = $fabrikDb->loadResult();
			$input->set('rowid', $rowId);
			$formId = $input->getString('formid');
			$this->app->redirect('index.php?option=' . $package . '&view=form&formid=' . $formId . '&rowid=' . $rowId . '&format=raw');
		}

		return json_encode($data);
	}

	/**
	 * Ajax get next record
	 *
	 * @return  string  json object representing record/row
	 */

	public function nextRecord()
	{
		$cursor = $this->app->input->getInt('cursor', 1);
		$this->getConnection();
		$this->outputFormat = 'json';
		$nav                = $this->getPagination(1, $cursor, 1);
		$data               = $this->getData();
		echo json_encode($data);
	}

	/**
	 * Ajax get previous record
	 *
	 * @return  string json  object representing record/row
	 */

	public function previousRecord()
	{
		$cursor = $this->app->input->getInt('cursor', 1);
		$this->getConnection();
		$this->outputFormat = 'json';
		$nav                = $this->getPagination(1, $cursor - 2, 1);
		$data               = $this->getData();

		return json_encode($data);
	}

	/**
	 * Ajax get first record
	 *
	 * @return  string  json object representing record/row
	 */

	public function firstRecord()
	{
		$input  = $this->app->input;
		$cursor = $input->getInt('cursor', 1);
		$this->getConnection();
		$this->outputFormat = 'json';
		$nav                = $this->getPagination(1, 0, 1);
		$data               = $this->getData();

		return json_encode($data);
	}

	/**
	 * Ajax get last record
	 *
	 * @return  string  json object representing record/row
	 */

	public function lastRecord()
	{
		$total = $this->app->input->getInt('total', 0);
		$this->getConnection();
		$this->outputFormat = 'json';
		$nav                = $this->getPagination(1, $total - 1, 1);
		$data               = $this->getData();

		return json_encode($data);
	}

	/**
	 * Get a single column of data from the table, test for element filters
	 *
	 * @param   mixed $col                 Column to grab. Element full name or id
	 * @param   bool  $distinct            Select distinct values only
	 * @param   array $opts                Options: filterLimit bool - should limit to filter_list_max global param
	 *                                     (default true) where - additional where filter to apply to query (@since
	 *                                     3.0.8)
	 *
	 * @return  array  Values for the column - empty array if no results found
	 */

	public function getColumnData($col, $distinct = true, $opts = array())
	{
		if (!array_key_exists($col, $this->columnData))
		{
			$fbConfig        = JComponentHelper::getParams('com_fabrik');
			$cache           = Worker::getCache($this);
			$opts['filters'] = $this->filters;
			$res             = $cache->call(array(get_class($this), 'columnData'), $this->getId(), $col, $distinct, $opts);

			if (is_null($res))
			{
				$this->app->enqueueMessage('list model getColumn Data for ' . $col . ' failed', 'notice');
				$res = array();
			}

			if ((int) $fbConfig->get('filter_list_max', 100) == count($res))
			{
				$this->app->enqueueMessage(JText::sprintf('COM_FABRIK_FILTER_LIST_MAX_REACHED', $col), 'notice');
			}

			$this->columnData[$col] = $res;
		}

		return $this->columnData[$col];
	}

	/**
	 * Cached method to grab a colums' data, called from getColumnData()
	 *
	 * @param   int   $listId              List id
	 * @param   mixed $col                 Column to grab. Element full name or id
	 * @param   bool  $distinct            Select distinct values only
	 * @param   array $opts                Options: filterLimit bool - should limit to filter_list_max global param
	 *                                     (default true) where - additional where filter to apply to query (@since
	 *                                     3.0.8)
	 *
	 * @since   3.0.7
	 *
	 * @return  array  Column's values
	 */

	public static function columnData($listId, $col, $distinct = true, $opts = array())
	{
		$listModel = new Lizt;
		$listModel->setId($listId);
		$listModel->filters = ArrayHelper::getValue($opts, 'filters');
		$table              = $listModel->getTable();
		$fbConfig           = JComponentHelper::getParams('com_fabrik');
		$db                 = $listModel->getDb();
		$el                 = $listModel->getFormModel()->getElement($col);
		$col                = $db->qn($col);
		$el->encryptFieldName($col);
		$tableName = $table->get('list.db_table_name');
		$tableName = FabrikString::safeColName($tableName);
		$query     = $db->getQuery(true);
		$query->select('DISTINCT(' . $col . ')')->from($tableName);
		$query       = $listModel->buildQueryJoin($query);
		$query       = $listModel->buildQueryWhere(false, $query);
		$query       = $listModel->pluginQuery($query);
		$filterLimit = ArrayHelper::getValue($opts, 'filterLimit', true);
		$where       = ArrayHelper::getValue($opts, 'where', '');

		if ($where != '')
		{
			$query->where($where);
		}

		if ($filterLimit)
		{
			$db->setQuery($query, 0, $fbConfig->get('filter_list_max', 100));
		}
		else
		{
			$db->setQuery($query);
		}

		$res = $db->loadColumn(0);

		return $res;
	}

	/**
	 * Determine how the model does filtering and navigation
	 *
	 * @return  bool  ajax true /post false; default post
	 */

	public function isAjax()
	{
		$params = $this->getParams();

		if (is_null($this->ajax))
		{
			// $$$ rob 11/07/2011 if post method set to ajax in request use that over the list_nav option
			$input = $this->app->input;

			if ($input->get('ajax', false) == '1')
			{
				$this->ajax = true;
			}
			else
			{
				$this->ajax = $params->get('list_ajax', $input->Bool('ajax', false));
			}
		}

		return (bool) $this->ajax;
	}

	/**
	 * Model edit/add links can be set separately to the ajax option
	 *
	 * @return  bool
	 */

	protected function isAjaxLinks()
	{
		$params = $this->getParams();
		$ajax   = $this->isAjax();

		return (bool) $params->get('list_ajax_links', $ajax);
	}

	/**
	 * Get an array of the table's elements that match a certain plugin type
	 *
	 * @param   string $plugin name
	 *
	 * @return  array    matched element models
	 */

	public function getElementsOfType($plugin)
	{
		$found  = array();
		$groups = $this->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->get('plugin') == $plugin)
				{
					$found[] = $elementModel;
				}
			}
		}

		return $found;
	}

	/**
	 * Get all the elements in the list
	 *
	 * @param   string $key           key to key returned array on, currently accepts null, '', 'id', or 'filtername'
	 * @param   bool   $showInTable   show in table default true
	 * @param   bool   $onlyPublished return only published elements
	 *
	 * @return  array    table element models
	 */

	public function getElements($key = 0, $showInTable = true, $onlyPublished = true)
	{
		if (!isset($this->elements))
		{
			$this->elements = array();
		}

		$sig = $key . '.' . (int) $showInTable;

		if (!array_key_exists($sig, $this->elements))
		{
			$this->elements[$sig] = array();
			$found                = array();
			$groups               = $this->getGroupsHierarchy();

			foreach (array_keys($groups) as $gid)
			{
				$groupModel    = $groups[$gid];
				$elementModels = $groupModel->getMyElements();

				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();

					if ($element->get('published') == 0 && $onlyPublished)
					{
						continue;
					}

					$dbkey = $key == 'filtername' ? trim($elementModel->getFilterFullName()) : trim($elementModel->getFullName(true, false));

					switch ($key)
					{
						case 'safecolname':
							// Deprecated (except for querystring filters and inline edit)
						case 'filtername':
							// $$$ rob hack to ensure that querystring filters dont use the concat string when getting the
							// Dbkey for the element, otherwise related data doesn't work
							$origconcat = $elementModel->getParams()->get('join_val_column_concat');
							$elementModel->getParams()->set('join_val_column_concat', '');

							// $$$ rob if prefilter was using _raw field then we need to assign the model twice to both possible keys
							if (is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
							{
								$dbkey2                        = FabrikString::safeColName($elementModel->getFullName(false, false));
								$this->elements[$sig][$dbkey2] = $elementModel;
							}

							$elementModel->getParams()->set('join_val_column_concat', $origconcat);
							$this->elements[$sig][$dbkey] = $elementModel;
							break;
						case 'id':
							$this->elements[$sig][$element->get('id')] = $elementModel;
							break;
						default:
							$this->elements[$sig][] = $elementModel;
							break;
					}
				}
			}
		}

		return $this->elements[$sig];
	}

	/**
	 * Does the list need to include the slimbox js code
	 *
	 * @return  bool
	 */

	public function requiresSlimbox()
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');

		if ($fbConfig->get('include_lightbox_js', 1) == 2)
		{
			return true;
		}

		$form   = $this->getFormModel();
		$groups = $form->getGroupsHierarchy();

		foreach ($groups as $group)
		{
			$elements = $group->getPublishedElements();

			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->get('show_in_list_summary') && $elementModel->requiresLightBox())
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Does the list need to include the slimbox js code
	 *
	 * @return  bool
	 */

	public function requiresSlideshow()
	{
		$form   = $this->getFormModel();
		$groups = $form->getGroupsHierarchy();

		foreach ($groups as $group)
		{
			$elements = $group->getPublishedElements();

			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($element->get('show_in_list_summary') && $elementModel->requiresSlideshow())
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get pluginmanager (get reference to form's plugin manager
	 *
	 * @deprecated - use Worker::getPluginManager() instead since 3.0b
	 *
	 * @return  object  plugin manager model
	 */

	public function getPluginManager()
	{
		return Worker::getPluginManager();
	}

	/**
	 * Called via advanced search to load in a given element filter
	 *
	 * @return string html for filter
	 */

	public function getAdvancedElementFilter()
	{
		$input         = $this->app->input;
		$elementId     = $input->getId('elid');
		$pluginManager = Worker::getPluginManager();
		$className     = $input->get('plugin');
		$plugin        = $pluginManager->getPlugIn($className, 'element');
		$plugin->setId($elementId);
		$el = $plugin->getElement();

		if ($this->app->input->get('context') == 'visualization')
		{
			$container = $this->app->input->get('parentView');
		}
		else
		{
			$container = 'listform_' . $this->getRenderContext();
		}

		$script = $plugin->filterJS(false, $container);
		FabrikHelperHTML::addScriptDeclaration($script);

		echo $plugin->getFilter($input->getInt('counter', 0), false);
	}

	/**
	 * Get add button label
	 *
	 * @since   3.1rc1
	 *
	 * @return  string
	 */

	public function addLabel()
	{
		$params = $this->getParams();

		return FText::_($params->get('addlabel', FText::_('COM_FABRIK_ADD')));
	}

	/**
	 * Get view details button label
	 *
	 * @since   3.1rc1
	 *
	 * @param   object &$row active table row
	 *
	 * @return  string
	 */

	public function viewLabel($row)
	{
		$params = $this->getParams();
		$row    = ArrayHelper::fromObject($row);

		return FText::_($this->parseMessageForRowHolder($params->get('detaillabel', FText::_('COM_FABRIK_VIEW')), $row));
	}

	/**
	 * Get edit row button label
	 *
	 * @param   object $row active table row
	 *
	 * @since   3.1rc1
	 *
	 * @return  string
	 */

	public function editLabel($row)
	{
		$params = $this->getParams();
		$row    = ArrayHelper::fromObject($row);

		return FText::_($this->parseMessageForRowHolder($params->get('editlabel', FText::_('COM_FABRIK_EDIT')), $row));
	}

	/**
	 * Build the list's add record link
	 * if a querystring filter has been passed in to the table then apply this to the link
	 * this means that table->faceted table->add will auto select the data you browsed on
	 *
	 * @return string  url
	 */

	public function getAddRecordLink()
	{
		$qs         = array();
		$w          = new Worker;
		$package    = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$input      = $this->app->input;
		$itemId     = Worker::itemId();
		$params     = $this->getParams();
		$addurl_url = Worker::getMenuOrRequestVar('addurl', $params->get('addurl', ''), $this->isMambot);
		$filters    = $this->getRequestData();
		$keys       = ArrayHelper::getValue($filters, 'key', array());
		$vals       = ArrayHelper::getValue($filters, 'value', array());
		$types      = ArrayHelper::getValue($filters, 'search_type', array());

		for ($i = 0; $i < count($keys); $i++)
		{
			$ftype = ArrayHelper::getValue($types, $i, '');

			// Append content plugin filters or querystring filters
			if (in_array($ftype, array('jpluginfilters', 'querystring')))
			{
				$qs[FabrikString::safeColNameToArrayKey($keys[$i]) . '_raw'] = $vals[$i];
			}
		}

		$addurl_qs = array();

		if (!empty($addurl_url))
		{
			$addurl_parts = explode('?', $addurl_url);

			if (count($addurl_parts) > 1)
			{
				$addurl_url = $addurl_parts[0];

				foreach (explode('&', $addurl_parts[1]) as $urlvar)
				{
					$key_value                = explode('=', $urlvar);
					$addurl_qs[$key_value[0]] = $key_value[1];
				}
			}
		}
		// $$$ rob needs the item id for when sef urls are turned on
		if ($input->get('option') !== 'com_' . $package)
		{
			if (!array_key_exists('Itemid', $addurl_qs))
			{
				$qs['Itemid'] = $itemId;
			}
		}

		if (empty($addurl_url))
		{
			$qs['option'] = 'com_' . $package;

			/*if ($this->app->isAdmin())
			{
				$qs['task'] = 'form.view';
			}
			else
			{*/
			$qs['view'] = 'form';
			//}

			$qs['id']    = $this->getItem()->get('id');
			$qs['rowid'] = '';

			/* $$$ hugh - testing social profile session hash, which may get set by things like
			 * the CB or JomSocial plugin.  Needed so things like the 'user' element can derive the
			* user ID of the profile being viewed, to which a record is being added.
			*/
			if ($input->get('fabrik_social_profile_hash', '') != '')
			{
				$qs['fabrik_social_profile_hash'] = $input->get('fabrik_social_profile_hash', '');
			}
		}

		$qs      = array_merge($qs, $addurl_qs);
		$qs_args = array();

		foreach ($qs as $key => $val)
		{
			if (!is_array($val))
			{
				$qs_args[] = $key . '=' . $val;
			}
			else
			{
				// Rob says do nothing if a multi-value array
			}
		}

		$qs = implode('&', $qs_args);
		$qs = $w->parseMessageForPlaceHolder($qs, $_REQUEST);

		return !empty($addurl_url) ? JRoute::_($addurl_url . '?' . $qs) : JRoute::_('index.php?' . $qs);
	}

	/**
	 * Create the JS to load element list JS
	 *
	 * @param   array &$srcs JS scripts to load
	 *
	 * @return  string  script
	 */

	public function getElementJs(&$srcs)
	{
		$form   = $this->getFormModel();
		$script = '';
		$groups = $form->getGroupsHierarchy();
		$run    = array();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();

				if (!in_array($element->get('plugin'), $run))
				{
					$run[] = $element->get('plugin');
					$elementModel->tableJavascriptClass($srcs);
				}

				$script .= $elementModel->elementListJavascript();
			}
		}

		return $script;
	}

	/**
	 * Return the url for the list form - this url is used when submitting searches, and ordering
	 *
	 * @return  string  action url
	 */

	public function getTableAction()
	{
		if (isset($this->tableAction))
		{
			return $this->tableAction;
		}

		$input  = $this->app->input;
		$option = $input->get('option');

		// Get the router
		$router  = $this->app->getRouter();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		$uri = clone (JURI::getInstance());
		/* $$$ rob force these to be 0 once the menu item has been loaded for the first time
		 * subsequent loads of the link should have this set to 0. When the menu item is re-clicked
		* rest filters is set to 1 again
		*/
		$router->setVar('resetfilters', 0);

		if ($option !== $package)
		{
			// $$$ rob these can't be set by the menu item, but can be set in {fabrik....}
			$router->setVar('clearordering', 0);
			$router->setVar('clearfilters', 0);
		}

		$queryvars = $router->getVars();
		$form      = $this->getFormModel();
		$page      = 'index.php?';

		foreach ($queryvars as $k => $v)
		{
			$rawK = FabrikString::rtrimword($k, '_raw');
			$el   = $form->getElement($k);

			if ($el === false)
			{
				$el = $form->getElement($rawK);
			}

			if (is_array($v))
			{
				/* $$$ rob if you were using URL filters such as
				 *
				* &jos_fabble_activity___create_date[value][]=now
				* &jos_fabble_activity___create_date[value][]=%2B2%20week&jos_fabble_activity___create_date[condition]=BETWEEN
				*
				* then we don't want to re-add them to the table action.
				* Instead they are aded to the filter sessions and reapplied that way
				* otherwise we ended up with elementname=Array in the query string
				*/

				/*
				 * $$$ hugh ... yeah, but $v is still an array, so we need to deal with it
				 * if it isn't an element ... like (say) ids[]=1&ids[]=2 in a table plugin, like
				 * email list
				 */
				if ($el === false && $k !== 'fabrik___filter')
				{
					foreach ($v as $v1)
					{
						$qs[] = $k . '[]=' . $v1;
					}
				}
			}
			else
			{
				if ($el === false)
				{
					$qs[] = $k . '=' . $v;
				}
				else
				{
					/* $$$ e-kinst
					 * let's keep &id for com_content - in other case in Content Plugin
					* we have incorrect action in form and as a result bad pagination URLS.
					* In any case this will not be excessive (I suppose)
					*/
					if ($k == 'id' && $option == 'com_content')
					{
						// At least. May be even  $option != 'com_fabrik'
						$qs[] = $k . '=' . $v;
					}
					// Check if its a tag element if it is we want to clear that when we clear the form
					// (if the filter was set via the url we generally want to keep it though

					/* 28/12/2011 $$$ rob testing never keeping querystring filters in the qs but instead always
					 * adding them to the filters (if no filter set up in element settings then hidden fields added anyway
					 		* this is to try to get round issue of related data (countries->regions) filter region from country list,
					 		* then clear filters (ok) but then if you go to 2nd page of results country url filter re-applied
					 		*/
					/* if($el->getElement()->plugin !== 'textarea' && $el->getParams()->get('textarea-tagify') !== true) {
					 $qs[] = "$k=$v";
					} */
				}
			}
		}

		$action            = $page . implode('&amp;', $qs);
		$action            = preg_replace("/limitstart{$this->getId()}=(\d+)?(&amp;|)/", '', $action);
		$action            = FabrikString::removeQSVar($action, 'fabrik_incsessionfilters');
		$action            = FabrikString::rtrimword($action, '&');
		$this->tableAction = JRoute::_($action);

		return $this->tableAction;
	}

	/**
	 * Allow plugins to add arbitrary WHERE clauses.  Gets checked in buildQueryWhere().
	 *
	 * @param   string $pluginName  Plugin name
	 * @param   string $whereClause Where clause (WITHOUT prepended where/and etc)
	 *
	 * @return  bool
	 */

	public function setPluginQueryWhere($pluginName, $whereClause)
	{
		// Strip any prepended conditions off
		$whereClause = preg_replace('#(^where |^and |^or )#i', '', $whereClause);

		if (trim($whereClause) === '')
		{
			return;
		}
		/* only do anything if it's a different clause ...
		 * if it's the same, no need to clear the table data, can use cached
		*/
		if (!array_key_exists($pluginName, $this->pluginQueryWhere) || $whereClause != $this->pluginQueryWhere[$pluginName])
		{
			// Set the internal data, which will get used in buildQueryWhere
			$this->pluginQueryWhere[$pluginName] = $whereClause;
			/* as we are modifying the main getData query, we need to make sure and
			 * clear table data, forcing next getData() to do the query again, no cache
			*/
			$this->resetQuery();
		}

		// Return true just for the heck of it
		return true;
	}

	/**
	 * Plugins sometimes need to clear their where clauses
	 *
	 * @param   string $pluginName Pugin name
	 *
	 * @return  bool
	 */

	public function unsetPluginQueryWhere($pluginName)
	{
		if (array_key_exists($pluginName, $this->pluginQueryWhere))
		{
			unset($this->pluginQueryWhere[$pluginName]);
		}

		return true;
	}

	/**
	 * If all filters are set to read only then don't return a clear button
	 * otherwised do
	 *
	 * @return  string    clear filter button link
	 */

	public function getClearButton()
	{
		$filters = $this->getFilters('listform_' . $this->getRenderContext(), 'list');
		$params  = $this->getParams();

		if (count($filters) > 0 || $params->get('advanced-filter'))
		{
			$table = $this->getTable();
			$tmpl  = $this->getTmpl();
			$title = '<span>' . FText::_('COM_FABRIK_CLEAR') . '</span>';
			$opts  = array('alt' => FText::_('COM_FABRIK_CLEAR'), 'class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => $title);
			$img   = FabrikHelperHTML::image('filter_delete.png', 'list', $tmpl, $opts);

			return '<a href="#" class="clearFilters">' . $img . '</a>';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the join display mode - merge, normal or reduce
	 *
	 * @return  string    1 if merge, 2 if reduce, 0 if no merge or reduce
	 */

	public function mergeJoinedData()
	{
		$params  = $this->getParams();
		$display = $params->get('join-display', '');

		switch ($display)
		{
			case 'merge':
				$merge = 1;
				break;
			case 'reduce':
				$merge = 2;
				break;
			default:
				$merge = 0;
				break;
		}

		return $merge;
	}

	/**
	 * Ask each element to preFormatFormJoins() for $data
	 *
	 * @param   array &$data to preformat
	 *
	 * @return  void
	 */

	protected function preFormatFormJoins(&$data)
	{
		$profiler             = JProfiler::getInstance('Application');
		$form                 = $this->getFormModel();
		$tableParams          = $this->getParams();
		$table                = $this->getTable();
		$pluginManager        = Worker::getPluginManager();
		$method               = 'renderListData_' . $this->outputFormat;
		$this->_aLinkElements = array();

		// $$$ hugh - temp foreach fix
		$groups = $form->getGroupsHierarchy();
		$ec     = count($data);

		foreach ($groups as $groupModel)
		{
			/* if (($tableParams->get('group_by_template', '') !== '' && $this->getGroupBy() != '') || $this->outputFormat == 'csv'
			 || $this->outputFormat == 'feed')
			{
			$elementModels = $groupModel->getPublishedElements();
			}
			else
			{
			$elementModels = $groupModel->getPublishedListElements();
			} */

			/*
			 * $$$ rob 29/10/2012 - see http://fabrikar.com/forums/showthread.php?t=28830
			* Calc may be set to show in list via menu item, but groupModel::getPublishedListElements() doesn't know
			* this. Seems best to run all calcs regardless of whether they are set to show in list.
			*/
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$col = $elementModel->getFullName(true, false);

				if (!empty($data) && array_key_exists($col, $data[0]))
				{
					for ($i = 0; $i < $ec; $i++)
					{
						$thisRow        = $data[$i];
						$colData        = $thisRow->$col;
						$data[$i]->$col = $elementModel->preFormatFormJoins($colData, $thisRow);
					}
				}
			}
		}
	}

	/**
	 * $$$ rob 19/10/2011 now called before formatData() from getData() as otherwise element tips (created in
	 * element->renderListData()) only contained first merged records data and not all merged records
	 *
	 * Collapses 'repeated joined' rows into a single row.
	 * If a group is not repeating we just use the first row's data (as subsequent rows will contain the same data
	 * Otherwise if the group is repeating we append each repeated record's data into the first row's data
	 * All rows execpt the first row for each group are then unset (as unique subsequent row's data will be contained
	 * within the first row)
	 *
	 * @param   array &$data list data
	 *
	 * @return  void
	 */

	protected function formatForJoins(&$data)
	{
		$merge = $this->mergeJoinedData();

		if (empty($merge))
		{
			return;
		}

		$dbPrimaryKey = FabrikString::safeColNameToArrayKey($this->getTable()->get('list.db_primary_key'));
		$formModel    = $this->getFormModel();
		$db           = $this->getDb();
		FabrikHelperHTML::debug($data, 'render:before formatForJoins');

		$last_pk             = '';
		$last_i              = 0;
		$count               = count($data);
		$can_repeats         = array();
		$can_repeats_tables  = array();
		$can_repeats_keys    = array();
		$can_repeats_pk_vals = array();
		$remove              = array();

		if (empty($data))
		{
			return;
		}
		/* First, go round first row of data, and prep some stuff.
		 * Basically, if doing a "reduce data" merge (merge == 2), we need to know what the
		* PK element is for each joined group (well, for each element, really)
		*/
		foreach ($data[0] as $key => $val)
		{
			$shortKey = FabrikString::rtrimword($key, '_raw');
			/* $$$ hugh - had to cache this stuff, because if you have a lot of rows and a lot of elements,
			 * doing this many hundreds of times causes huge slowdown, exceeding max script execution time!
			* And we really only need to do it once for the first row.
			*/
			if (!isset($can_repeats[$shortKey]))
			{
				$elementModel = $formModel->getElement($shortKey);

				// $$$ rob - testing for linking join which is repeat but linked join which is not - still need separate info from linked to join
				// $can_repeats[$shortKey] = $elementModel ? ($elementModel->getGroup()->canRepeat()) : 0;
				if ($merge == 2 && $elementModel)
				{
					if ($elementModel->getGroup()->canRepeat() || $elementModel->getGroup()->isJoin())
					{
						// We need to work out the PK of the joined table.
						// So first, get the table name.
						$group           = $elementModel->getGroup();
						$join            = $group->getJoinModel()->getJoin();
						$join_table_name = $join->table_join;

						// We have the table name, so see if we already have it cached ...
						if (!isset($can_repeats_tables[$join_table_name]))
						{
							// We don't have it yet, so grab the PK
							$keys = $this->getPrimaryKeyAndExtra($join_table_name);

							if (!empty($keys) && array_key_exists('key', $keys[0]))
							{
								// OK, now we have the PK for the table
								$can_repeats_tables[$join_table_name] = $keys[0];
							}
							else
							{
								// $$$ hugh - might be a view, so Hail Mary attempt to get PK
								$query = $db->getQuery(true);

								// FIXME - not right for json metafiles
								$query->select('db_primary_key')->from('#__fabrik_lists')
									->where('db_table_name = ' . $db->q($join_table_name));
								$db->setQuery($query);
								$join_pk = $db->loadResult();

								if (!empty($join_pk))
								{
									$can_repeats_tables[$join_table_name] = array('colname' => FabrikString::shortColName($join_pk));
								}
							}
						}
						// Hopefully we now have the PK
						if (isset($can_repeats_tables[$join_table_name]))
						{
							$can_repeats_keys[$shortKey] = $join_table_name . '___' . $can_repeats_tables[$join_table_name]['colname'];
						}

						$crk_sk = $can_repeats_keys[$shortKey];

						// Create the array if it doesn't exist
						if (!isset($can_repeats_pk_vals[$crk_sk]))
						{
							$can_repeats_pk_vals[$crk_sk] = array();
						}
						// Now store the
						if (!isset($can_repeats_pk_vals[$crk_sk][0]) and isset($data[0]->$crk_sk))
						{
							$can_repeats_pk_vals[$crk_sk][0] = $data[0]->$crk_sk;
						}
					}
				}

				$can_repeats[$shortKey] = $elementModel ? ($elementModel->getGroup()->canRepeat() || $elementModel->getGroup()->isJoin()) : 0;
			}
		}

		for ($i = 0; $i < $count; $i++)
		{
			// $$$rob if rendering J article in PDF format __pk_val not in pdf table view
			$next_pk = isset($data[$i]->__pk_val) ? $data[$i]->__pk_val : $data[$i]->$dbPrimaryKey;

			if (!empty($last_pk) && ($last_pk == $next_pk))
			{
				foreach ($data[$i] as $key => $val)
				{
					$origKey  = $key;
					$shortKey = FabrikString::rtrimword($key, '_raw');

					if ($can_repeats[$shortKey])
					{
						if ($merge == 2
							&& !isset($can_repeats_pk_vals[$can_repeats_keys[$shortKey]][$i])
							&& isset($data[$i]->$can_repeats_keys[$shortKey])
						)
						{
							$can_repeats_pk_vals[$can_repeats_keys[$shortKey]][$i] = $data[$i]->$can_repeats_keys[$shortKey];
						}

						if ($origKey == $shortKey)
						{
							/* $$$ rob - this was just appending data with a <br> but as we do thie before the data is formatted
							 * it was causing all sorts of issues for list rendering of links, dates etc. So now turn the data into
							* an array and at the end of this method loop over the data to encode the array into a json object.
							*/
							$do_merge = true;

							if ($merge == 2)
							{
								$pk_vals = array_count_values(array_filter($can_repeats_pk_vals[$can_repeats_keys[$shortKey]]));

								if (isset($data[$i]->$can_repeats_keys[$shortKey]))
								{
									if ($pk_vals[$data[$i]->$can_repeats_keys[$shortKey]] > 1)
									{
										$do_merge = false;
									}
								}
							}

							if ($do_merge)
							{
								/* The raw data is not altererd at the moment - not sure that that seems correct but can't see any issues
								 * with it currently
								* $$$ hugh - added processing of raw data, needed for _raw placeholders
								* in things like custom links
								*/
								$data[$last_i]->$key = (array) $data[$last_i]->$key;
								array_push($data[$last_i]->$key, $val);
								$rawkey = $key . '_raw';
								/* if (!isset($data[$i]->$rawkey))
								{
									$rawkey = $key;
								}
								$rawval = $data[$i]->$rawkey;
								$data[$last_i]->$rawkey = (array) $data[$last_i]->$rawkey;
								array_push($data[$last_i]->$rawkey, $rawval); */

								if (isset($data[$i]->$rawkey))
								{
									$rawval                 = $data[$i]->$rawkey;
									$data[$last_i]->$rawkey = (array) $data[$last_i]->$rawkey;
									array_push($data[$last_i]->$rawkey, $rawval);
								}
							}
						}
						else
						{
							/* $$$ hugh - don't think we need this, now we're processing _raw data?
							 if (!is_array($data[$last_i]->$origKey)) {
							$json= $val;
							$data[$last_i]->$origKey = json_encode($json);
							}
							*/
						}
					}
				}

				$remove[] = $i;
				continue;
			}
			else
			{
				if ($merge == 2)
				{
					foreach ($data[$i] as $key => $val)
					{
						$origKey  = $key;
						$shortKey = FabrikString::rtrimword($key, '_raw');

						if ($can_repeats[$shortKey]
							&& !isset($can_repeats_pk_vals[$can_repeats_keys[$shortKey]][$i])
							&& isset($data[$i]->$can_repeats_keys[$shortKey])
						)
						{
							$can_repeats_pk_vals[$can_repeats_keys[$shortKey]][$i] = $data[$i]->$can_repeats_keys[$shortKey];
						}
					}
				}

				$last_i = $i;

				// $$$rob if rendering J article in PDF format __pk_val not in pdf table view
				$last_pk = $next_pk;
			}
			// $$$ rob ensure that we have a sequental set of keys otherwise ajax json will turn array into object
			$data = array_values($data);
		}

		for ($c = count($remove) - 1; $c >= 0; $c--)
		{
			unset($data[$remove[$c]]);
		}
		// $$$ rob loop over any data that was merged into an array and turn that into a json object
		foreach ($data as $gkey => $d)
		{
			foreach ($d as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as &$v2)
					{
						$v2 = Worker::JSONtoData($v2);
					}

					$v               = json_encode($v);
					$data[$gkey]->$k = $v;
				}
			}
		}

		$data = array_values($data);
	}

	/**
	 * Does the list model have an associated table (can occur when form model
	 * which does not store in db, gets its list model)
	 *
	 * @return boolean
	 */

	public function noTable()
	{
		$id = $this->getId();

		return (bool) empty($id);
	}

	/**
	 * Save an individual element value to the fabrik db
	 *
	 * @param   string $rowId row id
	 * @param   string $key   key
	 * @param   string $value value
	 *
	 * @return  void
	 */

	public function storeCell($rowId, $key, $value)
	{
		$data[$key] = $value;

		// Ensure the primary key is set in $data
		$primaryKey = FabrikString::shortColName($this->getTable()->get('list.db_primary_key'));
		$primaryKey = str_replace("`", "", $primaryKey);

		if (!isset($data[$primaryKey]))
		{
			$data[$primaryKey] = $rowId;
		}

		$this->storeRow($data, $rowId);
	}

	/**
	 * Increment a value in a cell
	 *
	 * @param   string $rowId Row's id
	 * @param   string $key   Field to increment
	 * @param   string $dir   -1/1 etc
	 *
	 * @return  bool
	 */

	public function incrementCell($rowId, $key, $dir)
	{
		$db        = $this->getDb();
		$table     = $this->getTable();
		$tableName = $table->get('list.db_table_name');
		$pk        = $table->get('list.db_primary_key');
		$query     = "UPDATE $tableName SET $key = COALESCE($key, 0)  + $dir WHERE $pk = " . $db->q($rowId);
		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Get the output format
	 *
	 * @return  string    Outputformat
	 */

	public function getOutPutFormat()
	{
		return $this->outputFormat;
	}

	/**
	 * Set the list output format
	 *
	 * @param   string $f Format html/pdf/raw/csv
	 *
	 * @return  void
	 */

	public function setOutputFormat($f)
	{
		$this->outputFormat = $f;
	}

	/**
	 * Update a series of rows with a key = val , works across joined tables
	 *
	 * @param   array  $ids                       Pk values to update
	 * @param   string $col                       Key to update should be in format 'table.element'
	 * @param   string $val                       Val to set to
	 * @param   string $update                    Optional update statement, overides $col = null
	 * @param          mixed                      $$joinPkVal  If deleteing a joined record, this value can specify
	 *                                                         which joined row to update if left blank then all rows
	 *                                                         are updated.
	 *
	 * @return  void
	 */

	public function updateRows($ids, $col, $val, $update = '', $joinPkVal = null)
	{
		if (empty($col) && empty($update))
		{
			return;
		}

		if (empty($ids))
		{
			return;
		}

		$db   = $this->getDb();
		$nav  = $this->getPagination(1, 0, 1);
		$data = $this->getData();

		// $$$ rob dont unshift as this messes up for grouped data
		// $data = array_shift($data);
		$table = $this->getTable();

		$update  = $update == '' ? $col . ' = ' . $db->q($val) : $update;
		$colBits = explode('.', $col);
		$tbl     = array_shift($colBits);

		$joinFound = false;
		ArrayHelper::toInteger($ids);
		$ids   = implode(',', $ids);
		$dbk   = $k = $table->get('list.db_primary_key');
		$joins = $this->getJoins();

		// If the update element is in a join replace the key and table name with the join table's name and key
		foreach ($joins as $join)
		{
			if ((int) $join->list_id != 0 && $join->table_join == $tbl)
			{
				$joinFound = true;
				$db->setQuery('DESCRIBE ' . $tbl);
				$fields        = $db->loadObjectList('Key');
				$joinPkField   = $fields['PRI']->Field;
				$k             = $tbl . '___' . $joinPkField;
				$dbk           = $db->qn($tbl . '.' . $joinPkField);
				$db_table_name = $tbl;
				$ids           = array();

				foreach ($data as $groupdata)
				{
					foreach ($groupdata as $d)
					{
						$v = $d->{$k . '_raw'};

						if ($v != '')
						{
							$ids[] = $db->q($v);
						}
					}
				}

				if (!empty($ids))
				{
					$query = $db->getQuery(true);
					$ids   = implode(',', $ids);
					$query->update($db_table_name)->set($update);

					if (!is_null($joinPkVal))
					{
						$query->where($dbk . ' = ' . $db->q($joinPkVal));
					}
					else
					{
						$query->where($dbk . ' IN (' . $ids . ')');
					}

					$db->setQuery($query);
					$db->execute();
				}
			}
		}

		if (!$joinFound)
		{
			$db_table_name = $table->get('list.db_table_name');
			$query         = $db->getQuery(true);
			$query->update($db_table_name)->set($update)->where($dbk . ' IN (' . $ids . ')');
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Update a single row with a key = val, does NOT work across joins, main table only
	 *
	 * @param   string  $id  Pk value to update
	 * @param   string $col Key to update should be in format 'table.element'
	 * @param   string $val Val to set to
	 *
	 * @return  void
	 */

	public function updateRow($id, $col, $val)
	{
		$field = FabrikString::shortColname($col);

		if (empty($field) || empty($id))
		{
			return;
		}

		$db    = $this->getDb();
		$table = $this->getTable();
		$query = $db->getQuery(true);
		$query
			->update($db->qn($table->get('list.db_table_name')))
			->set($db->qn($field) . ' = ' . $db->q($val))
			->where($table->get('list.db_primary_key') . ' = ' . $db->q($id));
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * unset a series of model properties
	 *
	 * @return  void
	 */

	public function reset()
	{
		unset($this->_whereSQL);
		unset($this->table);
		unset($this->filters);
		unset($this->prefilters);
		unset($this->params);
		unset($this->viewfilters);

		// $$$ hugh - added some more stuff to clear, as per:
		// http://fabrikar.com/forums/showthread.php?p=115122#post115122
		unset($this->asfields);
		unset($this->formModel);
		unset($this->filterModel);
		unset($this->searchAllAsFields);
		unset($this->joinsSQL);
		unset($this->joins);
		unset($this->orderBy);
		unset($this->_joinsNoCdd);
		unset($this->elements);
		unset($this->data);
		unset($this->tmpl);
		unset($this->selectedOrderFields);
	}

	/**
	 * make sure a new getData query wil recreate data and query from scratch
	 *
	 * @return  void
	 */

	public function resetQuery()
	{
		unset($this->_whereSQL);
		unset($this->data);
	}

	/**
	 * Get the lists <table> class
	 *
	 * @return string
	 */

	public function htmlClass()
	{
		$params = $this->getParams();
		$class  = array('table');

		if ($params->get('bootstrap_stripped_class', true))
		{
			$class[] = 'table-striped';
		}

		if ($params->get('bootstrap_bordered_class'))
		{
			$class[] = 'table-bordered';
		}

		if ($params->get('bootstrap_condensed_class'))
		{
			$class[] = 'table-condensed';
		}

		if ($params->get('bootstrap_hover_class', true))
		{
			$class[] = 'table-hover';
		}

		return implode(' ', $class);
	}

	/**
	 * Get the table template
	 *
	 * @since 3.0
	 *
	 * @return string template name
	 */

	public function getTmpl()
	{
		if (!isset($this->tmpl))
		{
			$input    = $this->app->input;
			$item     = $this->getTable();
			$params   = $this->getParams();
			$document = JFactory::getDocument();

			if ($this->app->isAdmin())
			{
				$this->tmpl = $input->get('layout', $params->get('admin_template'));
			}
			else
			{
				$this->tmpl = $input->get('layout', $item->get('list.template'));

				if ($this->app->scope !== 'mod_fabrik_list')
				{
					$this->tmpl = Worker::getMenuOrRequestVar('fabriklayout', $this->tmpl, $this->isMambot);
					/* $$$ rob 10/03/2012 changed menu param to listlayout to avoid the list menu item
					 * options also being used for the form/details view template
					*/
					$this->tmpl = Worker::getMenuOrRequestVar('listlayout', $this->tmpl, $this->isMambot);
				}
			}

			if ($this->tmpl == '')
			{
				$this->tmpl = 'bootstrap';
			}

			if ($this->app->scope !== 'mod_fabrik_list')
			{
				/* $$$ rob 10/03/2012 changed menu param to listlayout to avoid the list menu item
				 * options also being used for the form/details view template
				*/
				// $this->tmpl = Worker::getMenuOrRequestVar('fabriklayout', $this->tmpl, $this->isMambot);
				$this->tmpl = Worker::getMenuOrRequestVar('listlayout', $this->tmpl, $this->isMambot);
			}

			if ($document->getType() === 'pdf')
			{
				$this->tmpl = $params->get('pdf_template', $this->tmpl);
			}

			// Migration test
			$modFolder       = JPATH_SITE . '/templates/' . $this->app->getTemplate() . '/html/com_fabrik/list/' . $this->tmpl;
			$componentFolder = JPATH_SITE . '/components/com_fabrik/views/list/tmpl/' . $this->tmpl;

			if (!JFolder::exists($componentFolder) && !JFolder::exists($modFolder))
			{
				$this->tmpl = 'bootstrap';
			}
		}

		return $this->tmpl;
	}

	/**
	 * Set the lists elements' tempate to that of the list's
	 *
	 * @return  void
	 */

	protected function setElementTmpl()
	{
		$tmpl   = $this->getTmpl();
		$groups = $this->getFormModel()->getGroupsHierarchy();
		$params = $this->getParams();

		foreach ($groups as $groupModel)
		{
			if (($params->get('group_by_template', '') !== '' && $this->getGroupBy() != '') || $this->outputFormat == 'csv'
				|| $this->outputFormat == 'feed'
			)
			{
				$elementModels = $groupModel->getPublishedElements();
			}
			else
			{
				$elementModels = $groupModel->getPublishedListElements();
			}

			foreach ($elementModels as $elementModel)
			{
				$elementModel->tmpl = $tmpl;
			}
		}
	}

	/**
	 * Short cut to test if the lists connection is the same as the Joomla database
	 *
	 * @return bool
	 */

	public function inJDb()
	{
		return $this->getConnection()->isJdb();
	}

	/**
	 * Checks : J template html override css file then fabrik list tmpl template css file. Including them if found
	 *
	 * @since 3.0 loads lists's css files
	 *
	 * @return  void
	 */

	public function getListCss()
	{
		$tmpl = $this->getTmpl();

		// Check for a form template file (code moved from view)
		if ($tmpl != '')
		{
			$qs = '?c=' . $this->getRenderContext();

			// $$$rob need &amp; for pdf output which is parsed through xml parser otherwise fails
			$qs .= '&amp;buttoncount=' . $this->rowActionCount;
			$overRide = 'templates/' . $this->app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/template_css.php' . $qs;

			if (!FabrikHelperHTML::stylesheetFromPath($overRide))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/template_css.php' . $qs);
			}
			/* $$$ hugh - as per Skype convos with Rob, decided to re-instate the custom.css convention.  So I'm adding two files:
			 * custom.css - for backward compat with existing 2.x custom.css
			* custom_css.php - what we'll recommend people use for custom css moving foward.
			*/
			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $this->app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/custom.css' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/custom.css');
			}

			if (!FabrikHelperHTML::stylesheetFromPath('templates/' . $this->app->getTemplate() . '/html/com_fabrik/list/' . $tmpl . '/custom_css.php' . $qs))
			{
				FabrikHelperHTML::stylesheetFromPath('components/com_fabrik/views/list/tmpl/' . $tmpl . '/custom_css.php' . $qs);
			}
		}
	}

	/**
	 * Get a unique list identifier (enables the same list to be rendered in component and module at same time)
	 *
	 * @return  string
	 */

	public function getRenderContext()
	{
		if ($this->renderContext === '')
		{
			$this->setRenderContext($this->getId());
		}

		$filter = JFilterInput::getInstance();
		return $filter->clean($this->getId() . $this->renderContext, 'WORD');
	}

	/**
	 * Lists can be rendered in articles, as components and in modules
	 * we need to set a unique reference for them to avoid conflicts
	 *
	 * @param   int $id Module/component list id
	 *
	 * @return  void
	 */

	public function setRenderContext($id = null)
	{
		$input = $this->app->input;
		$task  = $input->getCmd('task');

		if (strstr($task, '.'))
		{
			$task = explode('.', $task);
			$task = array_pop($task);
		}

		// $$$ rob if admin filter task = filter and not list.filter
		if ($task == 'filter' || ($this->app->isAdmin() && $input->get('task') == 'filter'))
		{
			$this->setRenderContextFromRequest();
		}
		else
		{
			$task = $input->getString('task');
			if ((($task == 'list.view' || $task == 'list.delete' || $task === 'view') && $input->get('format') == 'raw')
				|| $input->get('layout') == '_advancedsearch' || $task === 'list.elementFilter'
				|| $input->get('setListRefFromRequest') == 1
			)
			{
				// Testing for ajax nav in content plugin or in advanced search
				$this->setRenderContextFromRequest();
			}
			else
			{
				$this->renderContext = '_' . $this->app->scope . '_' . $id;
			}
		}

		if ($this->renderContext == '')
		{
			$this->renderContext = '_' . $this->app->scope . '_' . $id;
		}

		$filter              = JFilterInput::getInstance();
		$this->renderContext = $filter->clean($this->renderContext, 'WORD');
	}

	/**
	 * When dealing with ajax requests filtering etc we want to take the listref from the
	 * request array
	 *
	 * @return  string    listref
	 */

	protected function setRenderContextFromRequest()
	{
		$listref = $this->app->input->get('listref', '');

		if ($listref === '')
		{
			$this->renderContext = '';
		}
		else
		{
			$listref = explode('_', $listref);
			array_shift($listref);
			$this->renderContext = '_' . implode('_', $listref);
		}

		return $this->renderContext;
	}

	/**
	 * Get lists group by headings
	 *
	 * @return   array  heading names
	 */

	public function getGroupByHeadings()
	{
		$formModel = $this->getFormModel();
		$input     = $this->app->input;
		$base      = JURI::getInstance();
		$base      = $base->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path'));
		$qs        = $input->server->get('QUERY_STRING', '', 'string');

		if (String::stristr($qs, 'group_by'))
		{
			$qs = FabrikString::removeQSVar($qs, 'group_by');
			$qs = FabrikString::ltrimword($qs, '?');
			$qs = str_replace('&', '&amp;', $qs);
		}

		$url = $base;

		if (!empty($qs))
		{
			$url .= String::strpos($url, '?') !== false ? '&amp;' : '?';
			$url .= $qs;
		}

		$url .= String::strpos($url, '?') !== false ? '&amp;' : '?';
		$a = array();
		list($h, $x, $b, $c) = $this->getHeadings();
		$o                      = new stdClass;
		$o->label               = FText::_('COM_FABRIK_NONE');
		$o->group_by            = '';
		$a[$url . 'group_by=0'] = $o;

		foreach ($h as $key => $v)
		{
			if (!in_array($key, array('fabrik_select', 'fabrik_edit', 'fabrik_view', 'fabrik_delete', 'fabrik_actions')))
			{
				/**
				 * $$$ hugh - other junk is showing up in $h, like 85___14-14-86_list_heading, or element
				 * names ending in _form_heading.  May not be the most efficient method, but we need to
				 * test if $key exists as an element, as well as the simple in_array() test above.
				 */

				if ($formModel->hasElement($key, false, false))
				{
					$thisurl     = $url . 'group_by=' . $key;
					$o           = new stdClass;
					$o->label    = strip_tags($v);
					$o->group_by = $key;
					$a[$thisurl] = $o;
				}
			}
		}

		return $a;
	}

	/**
	 * Get a list of elements to export in the csv file.
	 *
	 * @since 3.0b
	 *
	 * @return array full element names.
	 */

	public function getCsvFields()
	{
		$params    = $this->getParams();
		$formModel = $this->getFormModel();
		$csvFields = array();

		if ($params->get('csv_which_elements', 'selected') == 'visible')
		{
			$csvIds = $this->getAllPublishedListElementIDs();
		}
		elseif ($params->get('csv_which_elements', 'selected') == 'all')
		{
			// Export code will export all, if list is empty
			$csvIds = array();
		}
		elseif ($params->get('csv_elements') == '' || $params->get('csv_elements') == 'null')
		{
			$csvIds = array();
		}
		else
		{
			$csvIds = json_decode($params->get('csv_elements'))->show_in_csv;
		}

		foreach ($csvIds as $id)
		{
			if ($id !== '')
			{
				$elementModel = $formModel->getElement($id, true);

				if ($elementModel !== false)
				{
					$csvFields[$elementModel->getFullName(true, false)] = 1;
				}
			}
		}

		return $csvFields;
	}

	/**
	 * Helper function for view to determine if filters should be shown
	 *
	 * @return  bool
	 */

	public function getShowFilters()
	{
		$filters    = $this->getFilters('listform_' . $this->getRenderContext());
		$params     = $this->getParams();
		$filterMode = (int) $params->get('show-table-filters');

		return (count($filters) > 0 && $filterMode !== 0) && $this->app->input->get('showfilters', 1) == 1 ? true : false;
	}

	/**
	 * Get the number of buttons that are rendered for the list
	 *
	 * @return  number
	 */

	protected function getButtonCount()
	{
		$buttonCount = 0;

		return $buttonCount;
	}

	/**
	 * Helper view function to determine if any buttons are shown
	 *
	 * @return  bool
	 */

	public function getHasButtons()
	{
		$params = $this->getParams();

		if (($this->canAdd() && $params->get('show-table-add')) || $this->getShowFilters()
			|| $this->getAdvancedSearchLink() || $this->canGroupBy() || $this->canCSVExport()
			|| $this->canCSVImport() || $params->get('rss') || $params->get('pdf') || $this->canEmpty()
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Compacts the ordering sequence of the selected records
	 *
	 * @param   string $colid column name to order on
	 * @param   string $where additional where query to limit ordering to a particular subset of records
	 *
	 * @since   3.0.5
	 *
	 * @return  bool
	 */

	public function reorder($colid, $where = '')
	{
		$elementModel = $this->getFormModel()->getElement($colid, true);
		$asfields     = array();
		$fields       = array();
		$elementModel->getAsField_html($asfields, $fields);
		$col   = $asfields[0];
		$field = explode("AS", $col);
		$field = array_shift($field);
		$db    = $this->getDb();
		$k     = $this->getTable()->get('list.db_primary_key');
		$tbl   = $this->getTable()->get('list.db_table_name');

		// Get the primary keys and ordering values for the selection.
		$query = $db->getQuery(true);
		$query->select($k . ' AS id, ' . $field . ' AS ordering');
		$query->from($tbl);
		$query->where($field . ' >= 0');
		$query->order($field);

		// Setup the extra where and ordering clause data.
		if ($where)
		{
			$query->where($where);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		// Compact the ordering values.
		foreach ($rows as $i => $row)
		{
			// Only update rows that are necessary.
			if ($row->ordering != $i + 1)
			{
				// Update the row ordering field.
				$query = $db->getQuery(true);
				$query->update($tbl);
				$query->set($field . ' = ' . ($i + 1));
				$query->where($k . ' = ' . $db->q($row->id));
				$db->setQuery($query);
				$db->execute();
			}
		}

		return true;
	}

	/**
	 * Load the JS files into the document
	 *
	 * @param   array &$srcs reference: js script srcs to load in the head
	 *
	 * @return  null
	 */

	public function getCustomJsAction(&$srcs)
	{
		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/table_' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/table_' . $this->getId() . '.js';
		}

		if (JFile::exists(COM_FABRIK_FRONTEND . '/js/list_' . $this->getId() . '.js'))
		{
			$srcs[] = 'components/com_fabrik/js/list_' . $this->getId() . '.js';
		}
	}

	/**
	 * When saving an element it can effect the list parameters, update them here.
	 *
	 * @param   object $elementModel element model
	 *
	 * @deprecated since 3.0b
	 *
	 * @since      3.0.6
	 *
	 * @return  void
	 */

	public function updateFromElement($elementModel)
	{
	}

	/**
	 * Get / set formatAll, which forces formatData() to ignore 'show in table'
	 * and just format everything, needed by things like the table email plugin.
	 * If called without an arg, just returns current setting.
	 *
	 * $$$ hugh - doesn't work, now that finesseData() is called via call_user_func().
	 *
	 * @param   bool $format_all optional arg to set format
	 *
	 * @return  bool
	 */

	public function formatAll($format_all = null)
	{
		if (isset($format_all))
		{
			$this->format_all = $format_all;
		}

		return $this->format_all;
	}

	/**
	 * Copy rows
	 *
	 * @param   mixed $ids array or string of row ids to copy
	 *
	 * @since    3.0.6
	 *
	 * @return  bool    all rows copied (true) or false if a row copy fails.
	 */

	public function copyRows($ids)
	{
		$ids       = (array) $ids;
		$formModel = $this->getFormModel();
		$formModel->copyingRow(true);
		$state = true;

		foreach ($ids as $id)
		{
			$formModel->rowId = $id;
			$formModel->unsetData();
			$row = $formModel->getData();
			$formModel->copyFromRaw($row, 'fromraw', true);
			$row['Copy']                   = '1';
			$row['fabrik_copy_from_table'] = '1';
			$formModel->formData           = $row;

			if (!$formModel->process())
			{
				$state = false;
			}
		}

		return $state;
	}

	/**
	 * Return an array of element ID's of all published and visible list elements
	 * Created to call from GetCsvFields()
	 *
	 * @return   array  array of element IDs
	 */
	public function getAllPublishedListElementIDs()
	{
		$ids    = array();
		$form   = $this->getFormModel();
		$groups = $form->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedListElements();

			foreach ($elementModels as $key => $elementModel)
			{
				$ids[] = $elementModel->getId();
			}
		}

		return $ids;
	}

	/**
	 * Return an array of elements which are set to always render
	 *
	 * @param   bool $not_shown_only Only return elements which have 'always render' enabled, AND are not displayed in
	 *                               the list
	 *
	 * @return  bool  array of element models
	 */

	public function getAlwaysRenderElements($not_shown_only = true)
	{
		$form         = $this->getFormModel();
		$alwaysRender = array();
		$groups       = $form->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				if ($elementModel->isAlwaysRender($not_shown_only))
				{
					$alwaysRender[] = $elementModel;
				}
			}
		}

		return $alwaysRender;
	}

	/**
	 * Does the list have any 'always render' elements?
	 *
	 * @return   bool
	 */

	public function hasAlwaysRenderElements()
	{
		$alwaysRender = $this->getAlwaysRenderElements();

		return !empty($alwaysRender);
	}

	/**
	 * Get the name of the tab field
	 *
	 * @return string  tablename___elementname
	 */

	private function getTabField()
	{
		if (!isset($this->tabsField))
		{
			$params          = $this->getParams();
			$tabsField       = $params->get('tabs_field', '');
			$this->tabsField = FabrikString::safeColNameToArrayKey($tabsField);
		}

		return $this->tabsField;
	}

	/**
	 * Get tab categories and merge as necessary to get down to tab limit
	 *
	 * @return   array  Tabs
	 */

	private function getTabCategories()
	{
		/**
		 * 2013-07-19 Paul - This function determines the tabs to be shown at the top of the list table.
		 * The tabs are defined by the contents of a user specified field e.g for alphabetic tabs you might
		 * use a calc field with the first character of a surname.
		 * To prevent a list of
		 **/
		$params    = $this->getParams();
		$tabsField = $tabsElName = $this->getTabField();

		if (empty($tabsField))
		{
			return;
		}

		list($tableName, $tabsField) = explode('___', $tabsField);
		$table = $this->getTable();

		if ($tableName != $table->get('list.db_table_name'))
		{
			$this->app->enqueueMessage(sprintf(FText::_('COM_FABRIK_LIST_TABS_TABLE_ERROR'), $tableName, $table->get('list.db_table_name')), 'error');

			return;
		}

		$tabsMax = (int) $params->get('tabs_max', 10);
		$tabsAll = (bool) $params->get('tabs_all', '1');

		// @FIXME - starting to implement code to handle join elements, not cooked yet

		$formModel    = $this->getFormModel();
		$elementModel = $formModel->getElement($tabsElName);
		$is_join      = (is_subclass_of($elementModel, 'PlgFabrik_ElementDatabasejoin') || get_class($elementModel) == 'PlgFabrik_ElementDatabasejoin');
		if (!$is_join)
		{
			// Get values and count in the tab field
			$db    = $this->getDb();
			$query = $db->getQuery(true);
			$query->select(array($tabsField, 'Count(' . $tabsField . ') as count'))
				->from($db->qn($table->get('list.db_table_name')))
				->group($tabsField)
				->order($tabsField);
		}
		else
		{
			$this->app->enqueueMessage(sprintf(FText::_('COM_FABRIK_LIST_TABS_TABLE_ERROR'), $tableName, $table->get('list.db_table_name')), 'error');
			$joinTable = $elementModel->getJoinModel()->getJoin();
			$fullFk    = $joinTable->table_join . '___' . $joinTable->table_join_key;

			return;
		}

		/**
		 * Filters include any existing tab filters - so we cannot calculate tabs based on any user set filters
		 * or pre-filters, until we can exclude them from being used here.
		 * $this->buildQueryWhere($this->app->input->getInt('incfilters', 1), $query, false);
		 **/
		$db->setQuery($query);
		FabrikHelperHTML::debug($query->dump(), 'list getTabCategories query:' . $table->label);
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark('before fabrik list tabs query run') : null;
		$db->execute();
		$counts = $db->loadRowList();
		JDEBUG ? $profiler->mark('after fabrik list tabs query run') : null;
		FabrikHelperHTML::debug($counts, 'list getTabCategories counts: ' . $table->label);

		/**
		 * We consolidate by finding the two consecutive rows with the smallest total and merging them.
		 * To avoid excessive looping if user tabField is too fragmented, we should skip tabs if
		 * we will iterate more than 100 times.
		 **/
		if (count($counts) - $tabsMax > 100)
		{
			$this->app->enqueueMessage(sprintf(FText::_('COM_FABRIK_LIST_TABS_MERGE_ERROR'), count($counts), $tabsMax), 'notice');

			return;
		}

		$tabs = array();

		if ($tabsAll)
		{
			// Set value to null to differentiate between all and empty string values
			$tabs[] = array(FText::_('COM_FABRIK_LIST_TABS_ALL'), null);
		}

		while (count($counts) > $tabsMax)
		{
			/**
			 * Primary algorithm is to find the two consecutive rows whose total count is smallest and merge them.
			 *
			 * If this turns out to be too time consuming in real-life scenarios, we can add further optimisation
			 * by adding logic to the loop so that first time through it sums the total of count, and
			 * second time through it merges any consecutive rows whose values total less than a small proportion (say 1/4)
			 * of the total count / tabs. This will not be completely optimum but will be a whole lot quicker for many small
			 * categories than the base iteration. Using 1/4 should result in no more than 3 further iterations per tab to get to
			 * the number of tabs required. In this case we can probably remove the test / error message above.
			 **/
			$minCount = 999999999;
			$minIndex = 0;

			for ($i = 1; $i < count($counts); $i++)
			{
				$totCount = $counts[$i - 1][1] + $counts[$i][1];

				if ($totCount < $minCount)
				{
					$minCount = $totCount;
					$minIndex = $i;
				}
			}

			// Merge mins
			$counts[$minIndex - 1][0] = (array) $counts[$minIndex - 1][0];
			$counts[$minIndex][0]     = (array) $counts[$minIndex][0];
			$counts[$minIndex - 1][0] = array($counts[$minIndex - 1][0][0], end($counts[$minIndex][0]));
			$counts[$minIndex - 1][1] += $counts[$minIndex][1];

			// Array_splice not working as advertised - working like array_slice for some reason!!
			// $counts = array_splice($counts, $minIndex, 1);
			unset($counts[$minIndex]);
			$counts = array_values($counts);
		}

		JDEBUG ? $profiler->mark('after fabrik list tabs counts merge') : null;
		FabrikHelperHTML::debug($counts, 'list getTabCategories merged counts: ' . $table->label);

		for ($i = 0; $i < count($counts); $i++)
		{
			if (is_array($counts[$i][0]))
			{
				$tabs[] = array($counts[$i][0][0] . '-' . $counts[$i][0][1], $counts[$i][0]);
			}
			else
			{
				$tabLabel = empty($counts[$i][0]) ? '-' : $counts[$i][0];
				$tabs[]   = array($tabLabel, $counts[$i][0]);
			}
		}

		return $tabs;
	}

	/**
	 * Set the List's tab HTML
	 *
	 * @return  array  Tabs
	 */

	public function loadTabs()
	{
		$this->tabs = array();
		$tabs       = $this->getTabCategories();

		if (!is_array($tabs) || empty($tabs))
		{
			return;
		}

		$package   = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$listId    = $this->getId();
		$tabsField = $this->getTabField();
		$itemId    = Worker::itemId();
		$uri       = JURI::getInstance();
		$urlBase   = $uri->toString(array('path'));
		$urlBase .= '?option=com_' . $package . '&';

		if ($this->app->isAdmin())
		{
			$urlBase .= 'task=list.view&';
		}
		else
		{
			$urlBase .= 'view=list&';
		}

		$urlBase .= 'listid=' . $listId . '&resetfilters=1';
		$urlEquals = $urlBase . '&' . $tabsField . '=%s';
		$urlRange  = $urlBase . '&' . $tabsField . '[value][]=%s&' . $tabsField . '[value][]=%s&' . $tabsField . '[condition]=BETWEEN';
		$uri       = JURI::getInstance();
		$thisUri   = rawurldecode($uri->toString(array('path', 'query')));

		foreach ($tabs as $i => $tabArray)
		{
			$row = new stdClass;
			list($label, $range) = $tabArray;
			$row->label = $label;

			if (is_null($range))
			{
				$row->url = $urlBase;
			}
			elseif (!is_array($range))
			{
				$row->url = sprintf($urlEquals, $range);
			}
			else
			{
				list($low, $high) = $range;
				$row->url = sprintf($urlEquals, sprintf($urlRange, $low, $high));
			}

			if ($itemId)
			{
				$row->url .= '&Itemid=' . $itemId;
			}

			$row->class   = ($thisUri == $row->url) ? 'class="active"' : '';
			$this->tabs[] = $row;
		}

		return $this->tabs;
	}

	/**
	 * Is the element selected for edither 'OR user edit' or 'OR use delete' acl
	 *
	 * @param   string $name Element full name
	 *
	 * @return boolean
	 */
	public function isUserDoElement($name)
	{
		$acl_types = array('allow_edit_details2', 'allow_delete2');
		$params    = $this->getParams();

		foreach ($acl_types as $acl_type)
		{
			$userDoCol = $params->get($acl_type, '');

			if ($userDoCol != '')
			{
				$userDoCol = FabrikString::safeColNameToArrayKey($userDoCol);

				if ($userDoCol == $name)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Build array for list toggle column feature
	 *
	 * @since 3.1rc3
	 *
	 * @return multitype:
	 */
	public function toggleCols()
	{
		$w          = new Worker;
		$formModel  = $this->getFormModel();
		$groups     = $formModel->getGroupsHierarchy();
		$showInList = $this->showInList();
		$cols       = array();

		foreach ($groups as $groupModel)
		{
			if ($groupModel->canView('list') === false)
			{
				continue;
			}

			$elementModels = $groupModel->getPublishedListElements();

			if (count($elementModels) > 0)
			{
				$group                               = $groupModel->getGroup();
				$groupLabel                          = $w->parseMessageForPlaceHolder($group->get('label'), array(), false);
				$cols[$group->get('id')]['name']     = $groupLabel;
				$cols[$group->get('id')]['elements'] = array();

				foreach ($elementModels as $key => $elementModel)
				{
					$element = $elementModel->getElement();

					// If we define the elements to show in the list - e.g in admin list module then only show those elements
					if (!empty($showInList) && !in_array($element->get('id'), $showInList))
					{
						continue;
					}

					$cols[$group->get('id')]['elements'][$elementModel->getFullName(true, false)] = $elementModel->getElement()->get('label');
				}
			}
		}

		return $cols;
	}
}
