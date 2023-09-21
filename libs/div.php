<?php
class div
{
    private $_data = array('indent' => 0, 'tag' => 'div', 'name' => null, 'class' => null, 'body' => null, 'id' => null, 'style' => null, 'onclick' => null, 'bold' => false, 'value' => null, 'type' => null, 'min' => null, 'step' => null, 'default' => null, 'emptyBody' => false, 'href' => null, 'action' => null, 'method' => null, 'placeholder' => null, 'checked' => null);
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
        case 'step':
        case 'href':
        case 'default':
        case 'emptyBody':
        case 'action':
        case 'method':
        case 'placeholder':
        case 'checked':
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
        case 'step':
        case 'action':
        case 'method':
        case 'placeholder':
        case 'default':
            $this->_data[$key] = trim($val);
            break;
	    case 'bold':
	    case 'emptyBody':
	    case 'checked':
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
        if($this->step != null) {
            $str=$str." step=\"".$this->step."\"";
        }
        if($this->default != null) {
            $str=$str." default=\"".$this->default."\"";
        }
        if($this->href != null) {
            $str=$str." href=\"".$this->href."\"";
        }
        if($this->action != null) {
            $str=$str." action=\"".$this->action."\"";
        }
        if($this->method != null) {
            $str=$str." method=\"".$this->method."\"";
        }
        if($this->placeholder != null) {
            $str=$str." placeholder=\"".$this->placeholder."\"";
        }
        if($this->checked) {
            $str=$str." checked";
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
