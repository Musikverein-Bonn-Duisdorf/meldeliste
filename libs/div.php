<?php
class div
{
    private $_data = array('indent' => 0, 'tag' => 'div', 'name' => null, 'class' => null, 'body' => null, 'id' => null, 'style' => null, 'onclick' => null, 'bold' => false, 'value' => null, 'type' => null, 'min' => null, 'default' => null, 'emptyBody' => false, 'href' => null);
    public function __get($key) {
        switch($key) {
	    case 'indent':
	    case 'class':
	    case 'body':
	    case 'id':
        case 'style':
        case 'onclick':
        case 'bold':
        case 'name':
        case 'tag':
        case 'value':
        case 'type':
        case 'min':
        case 'href':
        case 'default':
        case 'emptyBody':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'indent':
            $this->_data[$key] = (int)$val;
            break;
        case 'style':
	    case 'class':
            if($this->_data[$key]) {
                if($val) $this->_data[$key] = $this->_data[$key]." ".$val;
            }
            else {
                $this->_data[$key] = $val;                
            }
            break;
	    case 'body':
            $this->_data[$key] = $this->_data[$key].$val;
            break;
        case 'onclick':
	    case 'id':
        case 'name':
        case 'tag':
        case 'value':
        case 'type':
        case 'href':
        case 'min':
        case 'default':
            $this->_data[$key] = trim($val);
            break;
	    case 'bold':
	    case 'emptyBody':
            $this->_data[$key] = (bool)$val;
            break;
        default:
            break;
        }	
    }
    public function col($sizeL, $sizeM, $sizeS) {
        $str="w3-col";
        if($sizeL) {
            $str=$str." l".$sizeL;
        }
        if($sizeM) {
            $str=$str." m".$sizeM;
        }
        if($sizeS) {
            $str=$str." s".$sizeS;
        }
        $this->class=$str;
    }
    public function bold() {
        $this->bold=true;
    }
    public function open() {
        $str=str_repeat("\t", $this->indent);
        $str=$str."<".$this->tag;
        if($this->id) {
            $str=$str." id=\"".$this->id."\"";
        }
        if($this->class) {
            $str=$str." class=\"".$this->class."\"";
        }
        if($this->style) {
            $str=$str." style=\"".$this->style."\"";
        }
        if($this->onclick) {
            $str=$str." onclick=\"".$this->onclick."\"";
        }
        if($this->name) {
            $str=$str." name=\"".$this->name."\"";
        }
        if($this->value) {
            $str=$str." value=\"".$this->value."\"";
        }
        if($this->type) {
            $str=$str." type=\"".$this->type."\"";
        }
        if($this->min != null) {
            $str=$str." min=\"".$this->min."\"";
        }
        if($this->default != null) {
            $str=$str." default=\"".$this->default."\"";
        }
        if($this->href != null) {
            $str=$str." href=\"".$this->href."\"";
        }
        $str=$str.">\n";
        if($this->body) {
            $str=$str.str_repeat("\t", $this->indent+1);
            if($this->bold) {
                $str=$str."<b>";
            }
            $str=$str.$this->body;
            if($this->bold) {
                $str=$str."</b>";
            }
            $str=$str."\n";
        }
        return $str;
    }
    public function close() {
        $str=str_repeat("\t", $this->indent);
        $str=$str."</".$this->tag.">\n";
        return $str;
    }
    public function print() {
        $str=$this->open();
        if(!$this->body && $this->emptyBody == false) $str=$str.str_repeat("\t", $this->indent+1)."&nbsp;\n";
        $str=$str.$this->close();
        return $str;
    }
    public function make($indent, $class, $body) {
        $this->indent = $indent;
        $this->class = $class;
        $this->body = $body;
        return $this->print();
    }
};
