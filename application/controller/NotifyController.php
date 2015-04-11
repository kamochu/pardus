<?php

class NotifyController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }
	
    /**
     * Handles what happens when user moves to URL/notify/sms
     */
    public function sms()
    {
        $this->View->renderWithoutHeaderAndFooter('notify/sms');
    }
	
}
