<?php
/**
 * @package     blog.Plugin
 * @subpackage  Content.blog
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JLoader::register('NotiflyMessageHelper', JPATH_ADMINISTRATOR . '/components/com_notifly/helpers/message.php');

/**
 * Example Content Plugin
 *
 * @since  1.6
 */
class PlgNotiflyBlog extends JPlugin
{

    public function __construct(&$subject , $params)
	   {
			
			$input = JFactory::getApplication()->input;
			$this->extension = $input->get('option');
			$this->view = $input->get('view');

			// Load language file for use throughout the plugin
			JFactory::getLanguage()->load('com_blog', JPATH_ROOT);

			parent::__construct($subject, $params);
	   }

	/**
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method creates a contact for the saved user
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   boolean  $success  True if user was succesfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */


	function onContentAfterSave($context, $article, $isNew)
	{
  
		// add  judirectory new events

		if($context !=  "com_content.article"){
		   return;
	   	}
		
		/* If article is not new then return false
		@params $isNew : check article is new or old

		*/

		if (!$isNew) {
			return;
		}

		// check if event is enabled

		if (!$this->params->get('enable_article_item', 0)) {
			return;
		}

		$user_id = $article->created_by;
		$user = JFactory::getUser($user_id);

		// If the user id appears invalid then bail out just in case

		if (empty($user)) {
			return false;
		}

		// register users registration event
		$this->logUsersArticleCreation($user, $article);

		return true;
	    }




	public function logUsersArticleCreation($user, $article)

	   {

		$plugin = $this->getPluginInfo();

		$extension_id = $plugin->extension_id;
		$template = 'new_articale_created';
		$template = $this->getTemplateInfo($extension_id);

		$table = $this->getTable();
		$table->template_id = $template->id;
		$table->extension_id = $extension_id;
		$table->url = "index.php?option=com_content&view=article&id=" . $article->id . ":" . $article->title . "&catid=" . $article->catid;

		if (!$template->image_disable) {
			if ($template->avatar) {
				$table->image_url = NotiflyMessageHelper::getGravater($user->email);
			} else {
				$table->image_url =  $template->image_url;
			}
		}

		// get location from helper
		$ip = NotiflyMessageHelper::getRealIpAddr();
		$location = NotiflyMessageHelper::getLocation($ip);

		$table->title 	= $article->title;
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

	public function getPluginInfo()
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'blog'";
		$db->setQuery($sql);
		return $db->loadObject();
	}
	public function getTemplateInfo($extension_id)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '" . $extension_id . "' AND `alias` = 'new_articale_created'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

	public function getTable()
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_notifly/tables');
		return JTable::getInstance('Event', 'NotiflyTable', array());
	}
}
