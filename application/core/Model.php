<?php
namespace Ssg\Core;

use Psr\Log\LoggerInterface;

/**
 * Class Model
 * The part that handles all the output
 */
class Model
{
	
	 /** @var LoggerInterface logger the logger object */
	public $logger;

    /**
     * Construct the (base) controller. This happens when a real controller is constructed, like in
     * the constructor of IndexController when it says: parent::__construct();
     */
    function __construct(LoggerInterface $logger = null)
    {
		//initialize the logger object 
		$this->logger = $logger;
    }
    
}

