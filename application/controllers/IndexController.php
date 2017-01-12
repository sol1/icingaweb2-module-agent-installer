<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;

class Opsgenie_IndexController extends Controller {
    public function indexAction()
    {
	$this->view->apikey = $this->Config()->get('opsgenie', 'api_key', 'No API key specified in config.');
	$this->view->duration = $this->Config()->get('opsgenie', 'duration', '24');
	$this->view->schedules = $this->Config()->get('opsgenie', 'schedule_names', 'No schedule names listed');
    }
    
    public function simulateAction(){
	$apiKey = $this->Config()->get('opsgenie', 'api_key', 'No API key specified in config.');
	$hours = $this->Config()->get('opsgenie', 'duration', '24');
	$emailDomain = $this->Config()->get('opsgenie', 'email_domain', 'example.com.au');

	$dateFormat = "Y-m-d H:i";

	$fromDate = date($dateFormat);
	$endDate = date($dateFormat, strtotime(sprintf("+%d hours", $hours)));

	$scheduleName = $_GET['schedule'];

	$username = Session::getSession()->get('user')->getUsername();

	$json = file_get_contents("https://api.opsgenie.com/v1/json/schedule?apiKey=$apiKey&name=$scheduleName");
	$scheduleID = json_decode($json)->id;

	
	$requestBody = '{
     		"apiKey": "'. $apiKey .'",
     		"id" : "'. $scheduleID .'",
     		"timezone" : "Australia/Sydney",
     		"enabled" : true,
    		"rotations" : 
		[
    	     		{
				"startDate":"'. $fromDate .'",
    	        	 	"endDate":"'. $endDate .'",
    	        	 	"participants":["'. $username . '@' . $emailDomain . '"],
    	        	 	"rotationType":"daily"
    	     		}
    		]
	}';


	$opts = array('http' =>
	    array(
	        'method'  => 'POST',
	        'header'  => 'Content-type: application/json', 
	        'content' => $requestBody
	    )
	);
	
	$context  = stream_context_create($opts);
	
	$result = json_decode(file_get_contents('https://api.opsgenie.com/v1/json/schedule', false, $context));
	
	echo "<h1>$result->status</h1>";
	echo ($result->code != 200 ? "Error code: $result->code" : NULL);
    }
}
