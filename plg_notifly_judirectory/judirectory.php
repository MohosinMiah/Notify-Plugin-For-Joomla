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
 * Judirectory notify plugin 
 *
 * @since  1.6
 */
class PlgNotiflyJudiRectory extends JPlugin
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
		JFactory::getLanguage()->load('com_judirectory', JPATH_ROOT);

		parent::__construct($subject, $params);
		 
	 }

	/**
	 * Tests if Judirectory exists
	 *
	 * @since	4.0
	 * @access	private
	 */
	private function exists()
	{
		static $exists = null;

		if (is_null($exists)) {
			$file = JPATH_ADMINISTRATOR . '/components/com_judirectory/judirectory.php';
			$exists = JFile::exists($file);

			if ($exists) {

				return true;

			}

		}

		return false;
	}

	/**
	 * Method Call After Judirectory item create
	 *
	 * @param   string    $context     Context name give data request come from which context
	 * @param   array    $article      give article info
	 * @param   boolean  $isNew      True if item is new otherwise false
	 *
	 * @since   1.6
	 */


	function onContentAfterSave($context, $article, $isNew)
	{

   // Check Context is com_judirectory.listing or not
		if($context != "com_judirectory.listing"){
     return;
		}

		/* If article is not new then return false
		@params $isNew : check article is new or old

		*/

		if (!$isNew)
		{
			return;
		}
    
		// check if event is enabled
		if(!$this->params->get('enable_judirectory_item', 0))
		{
			return;
		}

		$user_id = $article->created_by;

		$user = JFactory::getUser($user_id);
		


	       	// add  judirectory new events
	 			$this->logUsersArticleCreation($user, $article);

	       
		


		return true;
	}
	



	public function logUsersArticleCreation($user,$article)
	{



		$data = $this->latestRowData();
	

		$plugin = $this->getPluginInfo();
	

		$extension_id = $plugin->extension_id;

		$template = 'new_judirectory_items';

		$template = $this->getTemplateInfo($extension_id);

		$table = $this->getTable();

		$table->template_id = $template->id;

		$table->extension_id = $extension_id;

		$table->url = "index.php?option=com_judirectory&view=listing&id=".$data->id;

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

		$table->title 	= $data->title;
		$table->name 	= $user->name;
		$table->email 	= $user->email;
		$table->ip 		= $ip;

		$table->city = $location->city;
		$table->state = $location->region_name;
		$table->country = $location->country_name;
		
		$table->created = JHtml::date('now', 'Y-m-d H:i:s');
		$table->published = 1;
		
		$table->store();
		return true;
	}

	public function getPluginInfo(){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'judirectory'";
		$db->setQuery($sql);
		return $db->loadObject();

		
	}

// Get the last inserted row from notifly_events
  public function latestRowData(){ 
     

		$db = JFactory::getDBO();   
	    $query  = $db->getQuery(true);
		$query->select($db->quoteName(array('id','title')))
	    ->from($db->quoteName('#__judirectory_listings'))
	    ->orderBy($db->quoteName('id').' desc');
		$db->setQuery($query,0,1);  
		return $db->loadObject();
  }

	public function getTemplateInfo($extension_id){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '".$extension_id."' AND `alias` = 'new_judirectory_items'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

	public function getTable()
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_notifly/tables');
		return JTable::getInstance('Event', 'NotiflyTable', array());
	}


}
