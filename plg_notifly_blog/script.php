<?php
/**
 * @package		Quix
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

class plgNotiflyBlogInstallerScript
{	
	function preflight( $type, $parent ) {
		// if($type == 'install' or $type == 'discover_install'){
		// 	self::incertTemplateInfo();
		// }

		return;
	}

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
		self::incertTemplateInfo();
		return;
	}
	
	/**
	* enable necessary plugins to avoid bad experience
	*/
	function incertTemplateInfo()
	{

		$db = JFactory::getDBO();
		$sql = "SELECT extension_id from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'blog'";
		$db->setQuery($sql);
		$plugin = $db->loadObject();

		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '".$plugin->extension_id."' AND `alias` = 'new_articale_created'";
		$db->setQuery($sql);
		$templates = $db->loadObjectList();
		

		if(!count($templates)){
			// Create a new query object.
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = array('extension_id', 'name', 'alias', 'message', 'state', 'published', 'access', 'language');

			// Insert values.
			$values = array($plugin->extension_id, $db->quote('New Blog Created'), $db->quote('new_articale_created'), $db->quote('{{ name }} in {{ city }} Just Created New Article! {{ time_ago }}'), '1', '1', '0', $db->quote('*'));

			// Prepare the insert query.
			$query
			    ->insert($db->quoteName('#__notifly_templates'))
			    ->columns($db->quoteName($columns))
			    ->values(implode(',', $values));

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			$db->execute();
			$templatesid = $db->insertid();

			// Create a new query object.
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = array('template_id', 'extension_id', 'url');

			// Insert values.
			$values = array($templatesid, $plugin->extension_id, $db->quote('#'));

			// Prepare the insert query.
			$query
			    ->insert($db->quoteName('#__notifly_events'))
			    ->columns($db->quoteName($columns))
			    ->values(implode(',', $values));

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			$db->execute();
			if ($db->execute()) {
				die();
			}
		}

		return true;

	}

}
