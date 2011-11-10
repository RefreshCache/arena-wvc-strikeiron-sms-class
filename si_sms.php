<?php
/**********************************************************************
* Description: provides a php object for interacting with StrikeIron's
*				SMS Notifications Service (Version 4).
* Created By: Matt Baylor, Woodmen Valley Chapel, @mattbaylor
* Date Created: 11/2011
*
**********************************************************************/


class si_sms {
	// Set the default WSDL address
	public $WSDL = 'http://ws.strikeiron.com/SMSAlerts4?WSDL';
	public $USER_ID = '';
	public $PASSWORD = '';
	public $result = array();
	
	private $client = '';
	
	// Constructor function
	// Inputs: StrikeIron Username and Password
	// Ouputs: None
	function __construct($username,$password) {
		if(strlen($username) == 0 || strlen($password) == 0 ){
			die('Username or password not supplied. Ensure object is instantiated with username and password');	
		}
		
		$this->PASSWORD = $password;
		$this->USER_ID = $username;
			
		$this->createClient();
	}
	
	// Creates the SOAP Client object, called by the constructor function
	// Inputs: None
	// Ouputs: None
	// Creates: SOAP Client
	private function createClient() {
		// create client
		$this->client = new SoapClient($this->WSDL, array('trace' => 1, 'exceptions' => 1));
		
		// create registered user for soap header
		$registered_user = array("RegisteredUser" => array("UserID" => $this->USER_ID,"Password" => $this->PASSWORD));
		$header = new SoapHeader("http://ws.strikeiron.com", "LicenseInfo", $registered_user);

		// set soap headers - this will apply to all operations
		$this->client->__setSoapHeaders($header);	
	}
	
	// Sends a single message using the SOAP client created in the object constructor, will handle long messages and send multiple messages
	// Inputs:  $to: 10 digit string to deliver the message to
	//			$from: string added to the message in the body
	//			$message: string message to be delivered
	// Outputs: None
	// Populates: Result property of the object
	
	// 
	public function sendMessage ($to, $from, $message){
		$smsLen = 147;
		// If message + from is less than or equal to 147, just send the message
		if(strlen($from) + strlen($message) <= $smsLen){
			// deliver the message
			$this->deliverMessage($to, $from, $message);	
		} else {
			// else, calculate the max message length (147 minus length of from)
			$maxMessageLength = $smsLen - (strlen($from));
			// calculate the number of messages, both for the looping and for reporting
			$numMessages = ceil(strlen($message)/$maxMessageLength);
			// loop through the message chunking it up into max message length sizes
			for($i=1;$i<=$numMessages;$i++){
				//send the message chunk, substr starts at zero so we have to subtract 1 from $i
				$this->deliverMessage($to, $from, substr($message,(($i-1)*$maxMessageLength),$maxMessageLength)." [$i of $numMessages]");
			}
		}	
	}
	
	// Delivers one message using the SOAP client created in the constructor. Private function called by sendMessage after message length is handled
	// Inputs:  $to: 10 digit string to deliver the message to
	//			$from: string added to the message in the body
	//			$message: string message to be delivered
	// Outputs: Unique identifier for the send from StrikeIron
	// Populates: Result property of the object
	private function deliverMessage ($to, $from, $message){
		//set up parameter array
		//note that from-name is hard-coded here.  This name appears in the message BODY.
		//optionally, if you wish to send a message in unicode, you can add another parameter named "OptionalTextFormat." This parameter value should be "UNICODE".
		//The UNICODE parameter only works for international messages. Both the carrier and the receiving device need to support unicode characters
		$params = array("ToNumber" => $to, "FromName" => $from, "MessageText" => $message);
		
		//call the web service operation
		$this->result = $this->client->__soapCall("SendMessage", array($params), null, null, $output_header);
		return $this->result->SendMessageResult->ServiceResult->Ticket;	
	}
	
	// Tracks one message in the StrikeIron service
	// Inputs:	$tag: string of numbers representing the ticket for one message sent using StrikeIron
	// Ouputs: statusDescription of the ticket number input
	// Populates: result property of the object
	public function trackMessage ($tag){
		//set up parameter array
		$params = array("TrackingTicket" => $tag);

		//call the web service operation
		$this->result = $this->client->__soapCall("TrackMessage", array($params), null, null, $output_header);
		return $this->result->TrackMessageResult->ServiceStatus->StatusDescription;
	}
	
	// Returns the full set of properties of the object. Primarily for debugging
	// Inputs: None, reports last action of the object
	// Outputs: Array of property objects
	public function getSMSStatus(){
		return array(
			'$WSDL' => $this->WSDL,
			'$USER_ID' => $this->USER_ID,
			'$PASSWORD' => $this->PASSWORD,
			'$client' => print_r($this->client,true),
			'$result' => print_r($this->result,true)
		);	
	}
	
	public function getServiceInfo(){
		die('Appears not to be properly implemented by StrikeIron');	
	}
	
	public function getStatusCodes(){
		die('Not yet implemented by si_sms');	
	}
	
	public function getStatusCodesForMethod(){
		die('Not yet implemented by si_sms, unknown if functional from StrikeIron');	
	}
	
	public function sendMessagesBulk(){
		die('Not yet implemented by si_sms. Might be better handled by a custom function approach using $this->sendMessage.');	
	}
	
	public function trackMessagesBulk(){
		die('Not yet implemented by si_sms. Might be better handled by a custom function approach using $this->trackMessage.');	
	}
	
	public function getRemainingHits(){
		die('Not yet implemented by si_sms. Doesn\'t appear implemented by StrikeIron');	
	}
}
?>