<?php
class Discord
{
    private $webhookUrl;
    private $_data = array('message' => null, 'username' => null);
    
    public function __construct($webhookUrl) {
        $this->webhookUrl = $webhookUrl;
    }

    public function __get($key) {
        switch($key) {
	    case 'message':
	    case 'username':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'message':
	    case 'username':
            $this->_data[$key] = trim($val);
            break;
        default:
            $this->_data[$key] = $val;
            break;
        }	
    }

    public function getVars() {
        return sprintf("DiscordWebhook: <b>%s</b>, Username: <b>%s</b>, Nachricht: <b>%s</b>",
                       $webhookUrl,
                       $this->username,
                       $this->message
        );
    }

    public function sendMessage($message, $username = "Bot", $embed = NULL) {
        $this->message = $message;
        $this->username = $username;
        if(!$this->is_valid()) return false;
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        if($embed) {
            $payload = json_encode([
                "username" => $username,
                "avatar_url" => "https://www.musikverein-bonn-duisdorf.de/uploads/images/Gallery/Divers/MVD_Wappen.jpg",
                "content" => $message,
                "embeds" => $embed
            ]);
        }
        else {
            $payload = json_encode([
                "username" => $username,
                "avatar_url" => "https://www.musikverein-bonn-duisdorf.de/uploads/images/Gallery/Divers/MVD_Wappen.jpg",
                "content" => $message
            ]);
        }

        echo $payload;
        
        // Initialize cURL
        $ch = curl_init($this->webhookUrl);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }

        // Close cURL
        curl_close($ch);

        return $response;
    }
   
    public function is_valid() {
        if(!$this->username) return false;
        if(!$this->message) return false;
        return true;
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            $this->_data[$key] = $val;
        }
    }
};
?>
