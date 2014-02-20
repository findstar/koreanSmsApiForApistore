<?php
/**
 * SMS Sender Class
 *
 * It use apistore.co.kr SMS API (support by KTH)
 * please set authkey and user id
 *
 *
 * how to use
 *
 *
 *
 * $smsSender = new SmsSender();
 *
 * $msg = "test sms message";
 *
 * // multi receiver
 * $receiver = array('01012344567','01009876543');
 *
 *
 * foreach($receiver as $receiverPhone)
 * {
 *  // sender , receiver, message
 *  $smsSender->setMessage('0212345678', $receiverPhone, $msg);
 *  $smsSender->send();
 *
 *  $resultCode = $sms->getResultCode();
 *
 *  if($resultCode == 200) echo "OK!";
 *  else    echo "error {$resultCode} !";
 * }
 */


class SmsSender
{

    private $userID             = "CHANGE_TO_API_SERVICE_USER_ID";
    private $develTestAuthKey   = "CHANGE_TO_TEST_AUTH_KEY";
    private $authKey            = "CHANGE_TO_REAL_AUTH_KEY";

    private $testEnabled        = true; // true on test, must change on real service

    private $messageType        = "sms";

    private $apiVersion         = 1;
    private $baseURL            = "http://api.apistore.co.kr/ppurio";
    private $apiURL             = null;

    // optional value
    private $cmid               = null;
    private $send_time          = null;
    private $send_name          = null;
    private $dest_name          = null;
    private $subject            = null;
    private $msg_body           = null;

    // must set value
    private $send_phone         = null;
    private $dest_phone         = null;


    private $result_code        = 0;
    private $result_cmid        = 0;

    private $errormsg           = null;


    public function __construct()
    {
        // if test enable == true  authkey set devel test key
        if($this->testEnabled)
            $this->authKey = $this->develTestAuthKey;

        $testURL = "";
        if($this->testEnabled){
            $testURL = "_test";
        }

        $this->apiURL = $this->baseURL
            .$testURL
            ."/"
            .$this->apiVersion
            ."/"
            ."message"
            .$testURL
            ."/"
            .$this->messageType
            ."/"
            .$this->userID;
    }

    public function setMessageType($type = "sms"){

        if($type=="sms" || $type == "mms" || $type=="lms"){
            $this->messageType = $type;

            $testURL = "";
            if($this->testEnabled){
                $testURL = "_test";
            }

            $this->apiURL = $this->baseURL
                .$testURL
                ."/"
                .$this->apiVersion
                ."/"
                ."message"
                .$testURL
                ."/"
                .$this->messageType
                ."/"
                .$this->userID;
        }

    }

    public function setCMID($cmid = null){
        if(!$cmid)
            $this->cmid = time();
    }


    public function setMessage($sender, $receiver, $context){

        //validation check
        $this->checkNumber($sender);
        $this->checkNumber($receiver);

        $this->send_phone = $sender;
        $this->dest_phone = $receiver;
        $this->msg_body = $context;
    }


    public function setSender($sender){

        if(!is_array($sender))
            $this->error("set sender error");

        $this->send_name = $sender['name'];

        $this->checkNumber($sender['phone']);
        $this->send_phone = $sender['phone'];

    }

    public function setReceiver($receiver){

        if(!is_array($receiver))
            $this->error("set receiver error");


        $this->checkNumber($receiver['phone']);


        $this->dest_name = $receiver['name'];
        $this->dest_phone = $receiver['phone'];

    }

    public function setSendTime($timeStamp){
        $this->send_time = $timeStamp;
    }

    public function setSubject($subject){
        $this->subject = $subject;
    }

    private function checkNumber($number){

        if(strpos($number, "-") !== false)
            $this->error("Phone number can't have '-' character");

        //just enable kor number
        if(substr($number, 0, 1) != "0")
            $this->error("Phone number must start '0' character. only support kor ");

    }

    private function error($msg = null){

        if($msg)
            $this->errormsg = $msg;

        if($this->errormsg!="")
            exit($this->errormsg);
    }

    public function send(){

        $paramArr = array(
            'cmid' => $this->cmid,
            'send_time' => $this->send_time,
            'send_phone' => $this->send_phone,
            'dest_phone' => $this->dest_phone,
            'send_name' => $this->send_name,
            'dest_name' => $this->dest_name,
            'subject' => $this->subject,
            'msg_body' => $this->msg_body);

        $param = http_build_query($paramArr);

        $optArr = array(
            'Content-type: application/x-www-form-urlencoded',
            'x-waple-authorization: '.$this->authKey
        );

        $cp = curl_init($this->apiURL);
        curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cp, CURLOPT_POST, true);
        curl_setopt($cp, CURLOPT_POSTFIELDS, $param);
        curl_setopt($cp, CURLOPT_HTTPHEADER, $optArr);

        $res = curl_exec($cp);
        curl_close($cp);

        $result = json_decode($res);

        if($result->cmid =="" && $result->result_code == "" && $result->description)
            $this->error($result->description);

        $this->result_cmid = $result->cmid;
        $this->result_code = $result->result_code;


    }

    public function getResultCode(){
        return $this->result_code;
    }

    public function getResultCMID(){
        return $this->result_cmid;
    }

}
?>