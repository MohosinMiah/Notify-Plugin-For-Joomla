<?php
/**
 * @package		Quix
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

class plgNotiflyJudirectoryInstallerScript
{	




	/**
	 * Function to perform changes during install
	 *
	 * @param   JInstallerAdapterComponent  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function postflight($parent)
	{
		self::insertTemplateInfo();
		return;
	}
	
	/**
	* enable necessary plugins to avoid bad experience
	*/
	function insertTemplateInfo()
	{
		$db = JFactory::getDBO();
		$sql = "SELECT extension_id from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'judirectory'";
		$db->setQuery($sql);
		$plugin = $db->loadObject();

		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '".$plugin->extension_id."'";
		$db->setQuery($sql);
		$templates = $db->loadObjectList();
		

		if(!count($templates)){
			// Create a new query object.
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = array('extension_id', 'name', 'alias', 'message', 'state', 'published', 'access', 'language');

			// Insert values.
			$values = array($plugin->extension_id, $db->quote('NEW judirectory Items Created'), $db->quote('new_judirectory_items'), $db->quote('{{name}} from {{ country }} just judirectory Items created! **{{title_with_link}}**
			
			{{ time_ago }}'), '1', '1', '0', $db->quote('*'));

			// Prepare the insert query.
			$query
			    ->insert($db->quoteName('#__notifly_templates'))
			    ->columns($db->quoteName($columns))
			    ->values(implode(',', $values));

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			$db->execute();
			
		}

		return true;

	}

}
