<?php
/*
	Copyright (C) 2023 AppsConda Limited. All Rights Reserved.
	License: GNU General Public License version 2 or later;
	Author: appsconda.com
	Website: https://appsconda.com
	Modified: 09/30/2023
	------------------------------------------------------------------------
*/

defined('_JEXEC') or die('No direct access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

class plgAuthenticationAppscondalogin extends CMSPlugin
{
    protected $autoloadLanguage = true;
    
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }

    public function onUserAuthenticate($credentials, $options, &$response)
    {
        $app = \Joomla\CMS\Factory::getApplication();
		$userid = isset($credentials['userid']) ? $credentials['userid'] : null; //got this from the controller file
		$passwordraw = isset($credentials['password']) ? $credentials['password'] : null; //got this from the controller file
		$passwordhashed = hash('sha256', $passwordraw);
		$deviceidlogin = isset($credentials['deviceidlogin']) ? $credentials['deviceidlogin'] : null; //got this from the controller file
        $db = JFactory::getDbo();
		
        // Check if userid and password exist in database
        $query = $db->getQuery(true)
            ->select($db->quoteName(array('userid', 'password')))
            ->from($db->quoteName('#__appsconda_mobileapplogins'))
            ->where($db->quoteName('userid') . ' = ' . $db->quote($userid))
			->where($db->quoteName('deviceidlogin') . ' = ' . $db->quote($deviceidlogin))
            ->where($db->quoteName('password') . ' = ' . $db->quote($passwordhashed));

        $db->setQuery($query);
        $result = $db->loadObject();

        // If the result is not null, then the userid, deviceidlogin and password matched
        if ($result) {
            // Load the user details from #__users
            $user = User::getInstance($userid);

            // Set the response variables
            $response->type             = 'Joomla';
            $response->status           = JAuthentication::STATUS_SUCCESS;
            $response->error_message    = '';
            $response->username         = $user->username;
            $response->fullname         = $user->name;
            $response->password         = $user->password;
            $response->email            = $user->email;
            $response->language         = $user->getParam('language');
            $response->timezone         = $user->getParam('timezone');

            // Log the user in
            $options = ['action' => 'core.login.site'];
            $result  = $app->triggerEvent('onUserLogin', array((array) $response, $options));

            return true;
        } else {
            $response->status        = JAuthentication::STATUS_FAILURE;
            $response->error_message = 'Invalid userid or deviceidlogin or password';
            return false;
        }
    }
}
