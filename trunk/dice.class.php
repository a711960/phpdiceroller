<?php
// create the customized command
require_once dirname(__FILE__)."/../pfccommand.class.php";
class pfcCommand_lancer extends pfcCommand
{

	var $usage = "/test a test :)";

  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    
    $c  =& pfcGlobalConfig::Instance();
    $u  =& pfcUserConfig::Instance();
    $ct =& pfcContainer::Instance();
    
    $nick = $u->getNickname();
    $text_src = phpFreeChat::PreFilterMsg(trim($param));
	$text_src = str_replace(" ","",$text_src);
	
	try {
		$p = new Parser($text_src);
		$p->setDebug($ct, $nick, $recipient);
		$ov = $p->Parse();
		$text = $nick." : ".$text_src." >> ".$ov->str." = ".$ov->val;

	}
	catch (Exception $e) {
		$text = "Error: ".$e->getMessage();
	}
	
	$ct->write($recipient, $nick, "send", $text);

  }
}


class TokenStream {
	public $tokenArray = array();
	public $current = 0;
	
	public function __construct($text) {
		for ($i=0, $m = strlen($text);$i<$m;$i++){
			if ($text[$i] == Kind::digit_0) {
				$t = new Token(Kind::digit_0);
			} elseif ($text[$i] == Kind::digit_1) {
				$t = new Token(Kind::digit_1);
			} elseif ($text[$i] == Kind::digit_2) {
				$t = new Token(Kind::digit_2);
			} elseif ($text[$i] == Kind::digit_3) {
				$t = new Token(Kind::digit_3);
			} elseif ($text[$i] == Kind::digit_4) {
				$t = new Token(Kind::digit_4);
			} elseif ($text[$i] == Kind::digit_5) {
				$t = new Token(Kind::digit_5);
			} elseif ($text[$i] == Kind::digit_6) {
				$t = new Token(Kind::digit_6);
			} elseif ($text[$i] == Kind::digit_7) {
				$t = new Token(Kind::digit_7);
			} elseif ($text[$i] == Kind::digit_8) {
				$t = new Token(Kind::digit_8);
			} elseif ($text[$i] == Kind::digit_9) {
				$t = new Token(Kind::digit_9);
			} elseif ($text[$i] == Kind::op_add) {
				$t = new Token(Kind::op_add);
			} elseif ($text[$i] == Kind::op_sub) {
				$t = new Token(Kind::op_sub);
			} elseif ($text[$i] == Kind::op_mul) {
				$t = new Token(Kind::op_mul);
			} elseif ($text[$i] == Kind::op_div) {
				$t = new Token(Kind::op_div);
			} elseif ($text[$i] == Kind::d) {
				$t = new Token(Kind::d);
			} else {
				$t = new Token(Kind::EOF);
				throw new Exception("Invalid input character at position ".($i+1)." out of ".$m." char in chain: '".$text."'. Syntax should be in the form of: (number or dice) [operation (number or dice)]. A dice is: number 'd' number");
				break;
			}

			$this->addToken($t);
		}
		$this->addToken(new Token(Kind::EOF));
	}
	
	function addToken(Token $t) {
		$this->tokenArray[count($this->tokenArray)] = $t;
	}
	
	function ToString() {
		$s = "";
		for ($i=0;$i<count($this->tokenArray)-1;$i++){
			$s .= $this->tokenArray[$i]->ToString();
		}
		return $s;
	}
	
	function GetCurrent() {
		return $this->tokenArray[$this->current];
	}
	
	function GetCurrentKind() {
		return $this->tokenArray[$this->current]->kind;
	}
	
	function MoveNext() {
		$this->current++;
	}
}

class Parser {
	public $ts = NULL;
	public $text="";
	public $ct = NULL;
	public $nick = NULL;
	public $recipient = NULL;
	
	public function __construct($text) {
		$this->ts = new TokenStream($text);
	}
	
	public function setDebug( $ct, $nick, $recipient) {
		$this->ct = $ct;
		$this->nick = $nick;
		$this->recipient = $recipient;
	}

	function debug($text) {
		if ( $this->ct != NULL) {
			$this->ct->write($this->recipient, $this->nick, "send", $text);
		}
	}
	
	function Parse() {
		$ov = $this->E($this->ts);
		if ($this->ts->GetCurrentKind() != Kind::EOF) {
			throw new Exception ("Syntax is invalid. Check your input: '".$this->ts->ToString()."'. Syntax should be in the form of: (number or dice) [operation (number or dice)]. A dice is: number 'd' number" );
		}
		return $ov; 
	}
	
	// And here comes all the rules that are implemented for the following grammar: (with @ == empty)
	//	E 	= T Eopt
	//	Eopt 	= '+' T Eopt | '-' T Eopt | @
	//	T 	= F Topt
	//	Topt	= '*' F Topt | '/' F Topt | @
	//	F	= N Fopt
	//	Fopt	= 'd' N | @
	//	N	= D Nopt
	//	Nopt	= D Nopt | @
	//	D is a digit
	function E(TokenStream $ts) {
		$ov1 = $this->T($ts);
		$ov2 = $this->Eopt($ts, $ov1);
		/*
		if ($ov2->val == "") {
			return $ov1;
		} else {
			$this->debug("Val: ".($ov2->val)." Str: ".$ov2->str);
			return new OutValue($ov2->val, $ov2->str);
		}*/
		return $ov2;
	}
	
	function Eopt(TokenStream $ts, OutValue $ov) {
		if ( $ts->GetCurrentKind() == Kind::op_add) {
			$ts->MoveNext();
			$ov1 = $this->T($ts);
			$ov_ = new OutValue($ov->val + $ov1->val, "( ".$ov->str." + ".$ov1->str." )");
			$ov2 = $this->Eopt($ts, $ov_);
			/*
			if ($ov2->val == "") {
				return $ov1;
			} else {
				return new OutValue($ov->val + $ov2->val, $ov->str." + ".$ov2->str);
			}
			*/
			return $ov2;
		} elseif ( $ts->GetCurrentKind() == Kind::op_sub) {
			$ts->MoveNext();
			$ov1 = $this->T($ts);
			$ov_ = new OutValue($ov->val - $ov1->val, "( ".$ov->str." - ".$ov1->str." )");
			$ov2 = $this->Eopt($ts, $ov_);
			/*
			if ($ov2->val == "") {
				return $ov1;
			} else {
				return new OutValue($ov->val - $ov2->val, $ov->str." - ".$ov2->str);
			}
			*/
			return $ov2;
		}
		return $ov;
	}
	
	function T(TokenStream $ts) {
		$ov1 = $this->F($ts);
		$ov2 = $this->Topt($ts, $ov1);
		/*if ($ov2->val == "") {
			return $ov1;
		} else {
			return new OutValue($ov2->val, $ov2->str);
		}*/
		return $ov2;
	}
	
	
	function Topt(TokenStream $ts, OutValue $ov) {
		if ( $ts->GetCurrentKind() == Kind::op_mul) {
			$ts->MoveNext();
			$ov1 = $this->F($ts);
			$ov_ = new OutValue($ov->val * $ov1->val, "( ".$ov->str." * ".$ov1->str." )");
			//$this->debug("val: ".$ov_->val." str: ".$ov_->str);
			$ov2 = $this->Topt($ts, $ov_);
			/*if ($ov2 == $ov_) {
				return $ov_;
			} else {
				return new OutValue($ov->val * $ov2->val, "( ".$ov->str." * ".$ov2->str." )");
			}*/
			return $ov2;
		} elseif ( $ts->GetCurrentKind() == Kind::op_div) {
			$ts->MoveNext();
			$ov1 = $this->F($ts);
			$ov_ = new OutValue($ov->val / $ov1->val, "( ".$ov->str." / ".$ov1->str." )");
			$ov2 = $this->Topt($ts, $ov_);
			/*
			if ($ov2->val == "") {
				return $ov_;
			} else {
				return new OutValue($ov->val / $ov2->val, "( ".$ov->str." / ".$ov2->str." )");
			}	*/
			return $ov2;			
		}
		return $ov;
	}
	
	function F(TokenStream $ts) {
		$ov1 = $this->N($ts);
		$ov2 = $this->Fopt($ts);
		if ( $ov2->val == "") {
			//This is not a dice
			return $ov1;
		} else {
			//This is a dice
			$str = "[ ";
			$val = 0;
			for ($i=0;$i<$ov1->val;$i++){
				$tmp = rand(1,$ov2->val);
				$val += $tmp;
				$str .= $tmp;
				if ( $i< $ov1->val-1) {
					$str .= " + ";
				}
			}
			$str .= " ]";

			return new OutValue($val, $str);
		}
	}
	
	function Fopt(TokenStream $ts){
		if ( $ts->GetCurrentKind() == Kind::d) {
			$ts->MoveNext();
			return $this->N($ts);
		}
		return new OutValue("","");
	}
	
	function N(TokenStream $ts){
		$ov = $this->D($ts);
		$val = $ov->val;
		$str = $ov->str;
		
		$ov = $this->Nopt($ts);
		$val .= $ov->val;
		$str .= $ov->str;
		
		return new OutValue( $val*1, $str);
	}
	
	function Nopt(TokenStream $ts) {
		$cur = $ts->GetCurrentKind();
		if ($cur == Kind::digit_0
			or $cur == Kind::digit_1
			or $cur == Kind::digit_2
			or $cur == Kind::digit_3
			or $cur == Kind::digit_4
			or $cur == Kind::digit_5
			or $cur == Kind::digit_6
			or $cur == Kind::digit_7
			or $cur == Kind::digit_8
			or $cur == Kind::digit_9
			) {
				$ov = new OutValue($cur, $cur);
				$val = $ov->val;
				$str = $ov->str;

				$ts->MoveNext();
				
				$ov = $this->Nopt($ts);
				$val .= $ov->val;
				$str .= $ov->str;
				
				return new OutValue( $val, $str);
		}
	}
	
	function D(TokenStream $ts) {
		$cur = $ts->GetCurrentKind();

		if ($cur == Kind::digit_0
			or $cur == Kind::digit_1
			or $cur == Kind::digit_2
			or $cur == Kind::digit_3
			or $cur == Kind::digit_4
			or $cur == Kind::digit_5
			or $cur == Kind::digit_6
			or $cur == Kind::digit_7
			or $cur == Kind::digit_8
			or $cur == Kind::digit_9
			) {
			$ts->MoveNext();
			return new OutValue($cur, $cur);
		} else {
			throw new Exception("Input is incorrect.");
		}
	}
}

class OutValue {
	public $val;
	public $str;
	
	public function __construct($val, $str){
		$this->val = $val; $this->str = $str;
	}
}

class Kind {
	const digit_0 = "0";
	const digit_1 = "1";
	const digit_2 = "2";
	const digit_3 = "3";
	const digit_4 = "4";
	const digit_5 = "5";
	const digit_6 = "6";
	const digit_7 = "7";
	const digit_8 = "8";
	const digit_9 = "9";
	
	const op_add = "+";
	const op_sub = "-";
	const op_mul = "*";
	const op_div = "/";
	
	const d = "d";
	const emp = "@";
	
	const EOF = "!";
}

class Token {
	public $kind;
	
	function __construct($k) {
		$this->kind = $k;
	}
	
	public function ToString() {
		return $this->kind;
	}
}
?>
