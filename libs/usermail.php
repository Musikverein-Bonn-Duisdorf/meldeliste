<?php
class Usermail {
    private $_data = array('User' => null, 'Text' => null);
    public function __get($key) {
        switch($key) {
        case 'User':
        case 'Text':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
        case 'User':
            $this->_data[$key] = (int)$val;
            break;
        case 'Text':
            $this->_data[$key] = $val;
            break;
        default:
            break;
        }	
    }
    public function send($key, $val) {
    }
}