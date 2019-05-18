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
 * virtuemart purchase notify plugin
 *
 * @since  1.6
 */
class PlgNotiflyVirtuemart extends JPlugin
{
	public function __construct(&$subject, $params)
	{
		// Check com_virtuemart is exist or not
		if (!$this->exists()) {
			return;
		}

		$input = JFactory::getApplication()->input;
		$this->extension = $input->get('option');
		$this->view = $input->get('view');

		// Load language file for use throughout the plugin
		JFactory::getLanguage()->load('com_virtuemart', JPATH_ROOT);


		parent::__construct($subject, $params);
	}

	/**
	 * Tests if com_virtuemart exists
	 *
	 * @since	4.0
	 * @access	private
	 */
	private function exists()
	{
		static $exists = null;

		if (is_null($exists)) {
			$file = JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.xml';

			$exists = JFile::exists($file);

			if ($exists) {
				return true;
			}
		}
		return false;
	}


	/**
	 * This event is fired after the order has been stored; it gets the shipment method-
	 * specific data.
	 *
	 * @param cart    contain cart info
	 * @param order    contain order info
	 */
	function plgVmConfirmedOrder($cart, $order)
	{


		// check if event is enabled

		if (!$this->params->get('enable_virtuemart_purchase', 0)) {

			return;
		}

		//user info 

		$customer = JFactory::getUser($order['details']['BT']->virtuemart_user_id);

		//products info

		$items = $order['items'];

		// we have items .Now here we iterate

		foreach ($items as $key => $item) {

			// Pass to logPurchaseEvent two arguments 

			$this->logPurchaseEvent($customer, $item);

		}


		return true;
	}


	// Storing Data in notifly_events . From this table notify will display

	public function logPurchaseEvent($customer, $item)
	{
		$plugin = $this->getPluginInfo();

		$extension_id = $plugin->extension_id;
		$template = 'new_product_purchase';
		$template = $this->getTemplateInfo($extension_id);

		$table = $this->getTable();
		$table->template_id = $template->id;
		$table->extension_id = $extension_id;
		$table->url = "index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=" . $item->virtuemart_product_id . "";



		if (!$template->image_disable) {
			if ($template->avatar) {
				$table->image_url = NotiflyMessageHelper::getGravater($customer->email);
			} else {
				$table->image_url =  $template->image_url;
			}
		}

		// get location from helper
		$ip = NotiflyMessageHelper::getRealIpAddr();
		$location = NotiflyMessageHelper::getLocation($ip);

		$table->title 	= $item->order_item_name;
		$table->name 	= $customer->name;
		$table->email 	= $customer->email;
		$table->ip 		= $ip;

		$table->city = $location['city'];
		$table->state = $location['region_name'];
		$table->country = $location['country_name'];

		$table->created = JHtml::date('now', 'Y-m-d H:i:s');
		$table->published = 1;

		$table->store();



		return true;
	}

	public function getPluginInfo()
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'virtuemart'";
		$db->setQuery($sql);
		return $db->loadObject();
	}
	public function getTemplateInfo($extension_id)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '" . $extension_id . "' AND `alias` = 'virtuemart_product_purchase'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

	public function getTable()
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_notifly/tables');
		return JTable::getInstance('Event', 'NotiflyTable', array());
	}
}
