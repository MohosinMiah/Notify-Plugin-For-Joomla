<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.joomla
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JLoader::register('NotiflyMessageHelper', JPATH_ADMINISTRATOR . '/components/com_notifly/helpers/message.php');
jimport('joomla.filesystem.file');

/**
 * Example Content Plugin
 *
 * @since  1.6
 */
class PlgNotiflyK2 extends JPlugin
{
	 public function __construct(&$subject , $params)
	 {
	 	if (!$this->exists()) {
	 		return;
		}
		
	 	$input = JFactory::getApplication()->input;
	 	$this->extension = $input->get('option');
	 	$this->view = $input->get('view');

		// Load language file for use throughout the plugin
		JFactory::getLanguage()->load('com_k2', JPATH_ROOT);


	 	parent::__construct($subject, $params);
	 }

	/**
	 * Tests if K2 exists
	 *
	 * @since	4.0
	 * @access	private
	 */
	private function exists()
	{
		static $exists = null;

		if (is_null($exists)) {
			$file = JPATH_ADMINISTRATOR . '/components/com_k2/k2.php';
			$exists = JFile::exists($file);

			if ($exists) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Utility method to act on a k2 Items after Save.
	 *
	 * This method called after k2 items created
	 *
	 * @param   array    $raw     Holds the new items data.
	 * @param   boolean  $isnew    True if a new  is stored.
	 */
	public function onAfterK2Save($raw,$isnew)
	{



		//Check K2 Item is new or not
 		if (!$isnew)
		{
			return;
		}


		// check if event is enabled

		if(!$this->params->get('enable_k2_item', 0))
		{
			return;
		}

		// Get user Data 

		$user = JFactory::getUser();

				if (empty($user))
		{
			return false;
		}

       

			// K2 Items  event


			$this->logK2PurchaseEvent($raw, $user);
    

		return true;
	}


	public function logK2PurchaseEvent($raw, $user)
	{
		$plugin = $this->getPluginInfo();
		
		$extension_id = $plugin->extension_id;
		$template = 'new_k2_items';
		$template = $this->getTemplateInfo($extension_id);

		$table = $this->getTable();
		$table->template_id = $template->id;
		$table->extension_id = $extension_id;

		// process the event url
		$table->url = "index.php?option=com_k2&view=item&id=".$raw->id;

		if(!$template->image_disable)
		{
			if($template->avatar){
				$table->image_url = NotiflyMessageHelper::getGravater($user->email);
			}
			else
			{
				$table->image_url =  $template->image_url;
			}
		}
		
		// get location from helper
		$ip = NotiflyMessageHelper::getRealIpAddr();
		$location = NotiflyMessageHelper::getLocation($ip);
		
		$table->title 	= $raw->title;
		$table->name 	= $user->name;
		$table->email 	= $user->email;
		$table->ip 		= $ip;

		$table->city = $location['city'];
		$table->state = $location['region_name'];
		$table->country = $location['country_name'];
		
		$table->created = JHtml::date('now', 'Y-m-d H:i:s');
		$table->published = 1;
		
		$table->store();



		return true;
	}

	public function getPluginInfo(){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'k2'";
		$db->setQuery($sql);
		return $db->loadObject();
	}
	public function getTemplateInfo($extension_id){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '".$extension_id."' AND `alias` = 'new_k2_items'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

	public function getTable()
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_notifly/tables');
		return JTable::getInstance('Event', 'NotiflyTable', array());
	}

}
