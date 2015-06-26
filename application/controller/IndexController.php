<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Core\Auth;

class IndexController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
		
		// this entire controller should only be visible/usable by logged in users, so we put authentication-check here
		Auth::checkAuthentication();
		
		//check the IP whitelist
		Auth::checkIPAuthentication();
    }

    /**
     * Handles what happens when user moves to URL/index/index - or - as this is the default controller, also
     * when user moves to /index or enter your application at base level
     */
    public function index()
    {
        $this->View->render('index/index');
    }
}
