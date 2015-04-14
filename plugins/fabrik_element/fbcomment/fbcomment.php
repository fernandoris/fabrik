<?php
/**
 * Plugin element to render facebook open graph comment widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookcomment
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render facebook open graph comment widget
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.facebookcomment
 * @since       3.0
 */

class PlgFabrik_ElementFbcomment extends PlgFabrik_Element
{
	/**
	 * Does the element have a label
	 *
	 * @var bool
	 */
	protected $hasLabel = false;

	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'INT(%s)';

	/**
	 * Db table field size
	 *
	 * @var  string
	 */
	protected $fieldLength = '1';

	/**
	 * Draws the form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string  returns element html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$data = new stdClass;
		$data->num = $params->get('fbcomment_number_of_comments', 10);
		$data->width = $params->get('fbcomment_width', 300);
		$data->colour = $params->get('fb_comment_scheme') == '' ? '' : ' colorscheme="dark" ';
		$data->href = $params->get('fbcomment_href', '');

		if (empty($data->href))
		{
			$app = JFactory::getApplication();
			$rowId = $app->input->getString('rowid', '', 'string');

			if ($rowId != '')
			{
				$formModel = $this->getFormModel();
				$formId = $formModel->getId();
				$href = 'index.php?option=com_fabrik&view=form&formid=' . $formId . '&rowid=' . $rowId;
				$href = JRoute::_($href);
				$data->href = COM_FABRIK_LIVESITE_ROOT . $href;
			}
		}

		if (!empty($data->href))
		{
			$w = new FabrikWorker;
			$data->href = $w->parseMessageForPlaceHolder($data->href, $data);
			$locale = $params->get('fbcomment_locale', 'en_US');

			if (empty($locale))
			{
				$locale = 'en_US';
			}

			$data->graphApi = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $locale);
		}

		$layout = $this->getLayout('form');

		return $layout->render($data);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbComment', $id, $opts);
	}
}
