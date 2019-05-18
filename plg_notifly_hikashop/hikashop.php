<?php
/**
 * @package     Hikashop.Plugin
 * @subpackage  Hikashop.product
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JLoader::register('NotiflyMessageHelper', JPATH_ADMINISTRATOR . '/components/com_notifly/helpers/message.php');

/**
 *  Notify Plugin for Hikashop
 *
 * @since  1.6
 */
class PlgNotiflyHikashop extends JPlugin
{
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

	public function __construct(&$subject , $params)
	{
		if (!$this->exists()) {
			return;
		}
		
		$input = JFactory::getApplication()->input;
		$this->extension = $input->get('option');
		$this->view = $input->get('view');

		// Load language file for use throughout the plugin
		JFactory::getLanguage()->load('com_hikashop', JPATH_ROOT);


		parent::__construct($subject, $params);
	}

	/**
	 * Tests if hikaShop exists
	 *
	 * @since	4.0
	 * @access	private
	 */

	private function exists()

	{

		static $exists = null;

		if (is_null($exists)) {

			$file = JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.php';
			$exists = JFile::exists($file);

			if ($exists) {

				return true;

			}

		}

		return false;
	}


	/**
	 * onAfterOrderCreate hook call after order store
	 */

	function onAfterOrderCreate(&$order) {


	// check if event is enabled

		if(!$this->params->get('enable_hikashop_purchase', 0))
		{
			return;
		}


  /**
	 * onAfterOrderCreate hook call after order store
	 */

		if(!include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')){
			echo "Hikashop not loaded!";
			return;
		};

	/**
	 * Load Order Class From Hikashop 
	 */


		$orderClass = hikashop_get('class.order');
		$orderInfo = $orderClass->loadFullOrder($order->order_id, false, true);

	/**
	 * Check Order Info is empty or not
	 */
		if(empty($orderInfo)){
			return;
		}

    /** 
	 * Collected User and Products Information
	 */
		$user = $orderInfo->customer;		
		$products = $orderInfo->products;


 

	  /**
	 * Interate Products 
	  */

    	foreach ($products as $key => $product) {

			// Passing user and products info in logHikashopOrderCreation as a arguments
			$this->logPurchaseEvent($user, $product);

    	}

		return true;

	}
	

  /**
	 * Store Data in notify_event table for display notify hikashop ordered purches message
	 */

	public function logPurchaseEvent($user,$product)
	{
        
		$plugin = $this->getPluginInfo();

		$extension_id = $plugin->extension_id;
		$template = 'hikashop_new_product_added';
		$template = $this->getTemplateInfo($extension_id);

		$table = $this->getTable();
		$table->template_id = $template->id;
		$table->extension_id = $extension_id;
		$table->url = "index.php?option=com_hikashop&ctrl=product&task=show&cid=".$product->product_id."&name=".$product->order_product_name."";

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
		
	/**
	 * Get Location from Helper
	 */
		$ip = NotiflyMessageHelper::getRealIpAddr();
		$location = NotiflyMessageHelper::getLocation($ip);

		$table->title 	= $product->order_product_name;
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

    /**
	 * Collect  hikashop  plugin info
	 */
	public function getPluginInfo(){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'notifly' AND `element` = 'hikashop'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

    /**
	 * Collect  template info based on extension id
	 */

	public function getTemplateInfo($extension_id){
		$db = JFactory::getDBO();
		$sql = "SELECT * from `#__notifly_templates` WHERE `extension_id` = '".$extension_id."' AND `alias` = 'hikashop_new_product_added'";
		$db->setQuery($sql);
		return $db->loadObject();
	}

	public function getTable()
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_notifly/tables');
		return JTable::getInstance('Event', 'NotiflyTable', array());
	}

}
