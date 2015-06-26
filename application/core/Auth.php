<?php
namespace Ssg\Core;

/**
 * Class Auth
 * Checks if user is logged in, if not then sends the user to "yourdomain.com/login".
 * Auth::checkAuthentication() can be used in the constructor of a controller (to make the
 * entire controller only visible for logged-in users) or inside a controller-method to make only this part of the
 * application available for logged-in users.
 */
class Auth
{
    public static function checkAuthentication()
    {
        // initialize the session (if not initialized yet)
        Session::init();

        // if user is not logged in...
        if (!Session::userIsLoggedIn()) {
            // ... then treat user as "not logged in", destroy session, redirect to login page
            Session::destroy();
            header('location: ' . Config::get('URL') . 'login');
            // to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
            // the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
            // this is not optimal and will be fixed in future releases
            exit();
        }
    }
	
	
	public static function checkIPAuthentication()
    {
		//get client ip address and white list from config file
		$client_ip = self::getClientIPAddress();
		$ip_white_list = Config::get('ALLOWED_IPS');
		
		//check the IP  in the white list
		foreach ($ip_white_list as $value){
			if ($client_ip == $value) {
				return; 
			}
		}
		
		//redirect to page 401
		header('location: ' . Config::get('URL') . 'error/httperror401');
		// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
		// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
		// this is not optimal and will be fixed in future releases
		exit();
        
    }
	
	
	private static function getClientIPAddress()
	{
		$ip_address = '';
		 if(isset($_SERVER['REMOTE_ADDR']))
			$ip_address = $_SERVER['REMOTE_ADDR'];
		else if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ip_address = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ip_address = $_SERVER['HTTP_FORWARDED'];
		else
			$ip_address = 'UNKNOWN';
		return $ip_address;
	}
	
	
}
