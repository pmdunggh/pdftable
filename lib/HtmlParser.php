<?php
namespace VanXuan\PdfTable;

/*
 * Copyright (c) 2003 Jose Solorzano.  All rights reserved.
 * Redistribution of source must retain this copyright notice.
 *
 * Jose Solorzano (http://jexpert.us) is a software consultant.
 *
 * Contributions by:
 * - Leo West (performance improvements)
 */


/**
 * Class HtmlParser.
 * To use, create an instance of the class passing
 * HTML text. Then invoke parse() until it's false.
 * When parse() returns true, $iNodeType, $iNodeName
 * $iNodeValue and $iNodeAttributes are updated.
 *
 */
class HtmlParser {
	//HTML attribute stand alone
	const HASA = ['checked','compact','declare','defer','disabled','ismap','multiple','nohref','noresize','noshade','nowrap','readonly','selected'];
    const TYPE_ELEMENT = 1;
    const TYPE_ENDELEMENT = 2;
    const TYPE_TEXT = 3;
    const TYPE_COMMENT = 4;
    const TYPE_DONE = 5;

    /**
     * Field iNodeType.
     * May be one of the NODE_TYPE_* constants above.
     */
    var $iNodeType;

    /**
     * Field iNodeName.
     * For elements, it's the name of the element.
     */
    var $iNodeName = "";

    /**
     * Field iNodeValue.
     * For text nodes, it's the text.
     */
    var $iNodeValue = "";

    /**
     * Field iNodeAttributes.
     * A string-indexed array containing attribute values
     * of the current node. Indexes are always lowercase.
     */
    var $iNodeAttributes;

    // The following fields should be 
    // considered private:

    var $iHtmlText;
    var $iHtmlTextLength;
    var $iHtmlTextIndex = 0;
    var $iHtmlCurrentChar;
	private $iCurrentChar;

	/**
	 * Constructor.
	 * Constructs an HtmlParser instance with
	 * the HTML text given.
	 *
	 * @param $aHtmlText
	 */
    function __construct($aHtmlText) {
        $this->iHtmlText = $aHtmlText;
        $this->iHtmlTextLength = strlen($aHtmlText);
        $this->setTextIndex (0);
    }

    /**
     * Method parse.
     * Parses the next node. Returns false only if
     * the end of the HTML text has been reached.
     * Updates values of iNode* fields.
     */
    function parse() {
        $text = trim($this->skipToElement());
        if ($text != "") {
            $this->iNodeType = self::TYPE_TEXT;
            $this->iNodeName = "Text";
            $this->iNodeValue = $text;
            $this->iNodeAttributes = 0;
            return true;
        }
        return $this->readTag();
    }

    function clearAttributes() {
        $this->iNodeAttributes = array();
    }

    function readTag() {
        if ($this->iCurrentChar != "<") {
            $this->iNodeType = self::TYPE_DONE;
            return false;
        }
        $this->skipInTag (array("<"));
        $this->clearAttributes();
        $name = $this->skipToBlanksInTag();
        $pos = strpos($name, "/");
        if ($pos === 0) {
            $this->iNodeType = self::TYPE_ENDELEMENT;
            $this->iNodeName = substr ($name, 1);
            $this->iNodeValue = "";
        } 
        else {
            if (!$this->isValidTagIdentifier ($name)) {
                $comment = false;
                if ($name == "!--") {
                    $rest = $this->skipToStringInTag ("-->");    
                    if ($rest != "") {
                        $this->iNodeType = self::TYPE_COMMENT;
                        $this->iNodeName = "Comment";
                        $this->iNodeValue = "<" . $name . $rest;
                        $comment = true;
                    }
                }
                if (!$comment) {
                    $this->iNodeType = self::TYPE_TEXT;
                    $this->iNodeName = "Text";
                    $this->iNodeValue = "<" . $name;
                }
                return true;
            }
            else {
                $this->iNodeType = self::TYPE_ELEMENT;
                $this->iNodeValue = "";
                $nameLength = strlen($name);
                if ($nameLength > 0 && substr($name, $nameLength - 1, 1) == "/") {
                	$this->iNodeName = substr($name, 0, $nameLength - 1);
                }else {
                    $this->iNodeName = $name;
                }
                $this->iNodeName = strtolower($this->iNodeName);
            } 
        }
        while ($this->skipBlanksInTag()) {
            $attrName = $this->skipToBlanksOrEqualsInTag();
            if ($attrName != "") {
				$attrName = strtolower($attrName);
				if (array_search($attrName, self::HASA)!==false){
					$this->iNodeAttributes[$attrName] = 1;
				}else{
	                $this->skipBlanksInTag();
	                if ($this->iCurrentChar == "=") {
	                    $this->skipEqualsInTag();
	                    $this->skipBlanksInTag();
	                    $value = $this->readValueInTag();
	                    $this->iNodeAttributes[$attrName] = $value;
	                }else {
	                    $this->iNodeAttributes[$attrName] = "";
	                }
				}
            }
        }
        $this->skipEndOfTag();
        return true;            
    }

    function isValidTagIdentifier ($name) {
        return preg_match ("/[A-Za-z0-9]+/", $name);
    }
    
    function skipBlanksInTag() {
        return "" != ($this->skipInTag (array (" ", "\t", "\r", "\n" )));
    }

    function skipToBlanksOrEqualsInTag() {
        return $this->skipToInTag (array (" ", "\t", "\r", "\n", "=" ));
    }

    function skipToBlanksInTag() {
        return $this->skipToInTag (array (" ", "\t", "\r", "\n" ));
    }

    function skipEqualsInTag() {
        return $this->skipInTag (array ( "=" ));
    }

    function readValueInTag() {
        $ch = $this->iCurrentChar;
        if ($ch == "\"") {
            $this->skipInTag (array ( "\"" ));
            $value = $this->skipToInTag (array ( "\"" ));
            $this->skipInTag (array ( "\"" ));
        }
        else if ($ch == "'") {
            $this->skipInTag (array ( "'" ));
            $value = $this->skipToInTag (array ( "'" ));
            $this->skipInTag (array ( "'" ));
        }                
        else {
            $value = $this->skipToBlanksInTag();
        }
        return $value;
    }

    function setTextIndex ($index) {
        $this->iHtmlTextIndex = $index;
        if ($index >= $this->iHtmlTextLength) {
            $this->iCurrentChar = -1;
        }
        else {
            $this->iCurrentChar = $this->iHtmlText{$index};
        }
    }

    function moveNext() {
        if ($this->iHtmlTextIndex < $this->iHtmlTextLength) {
            $this->setTextIndex ($this->iHtmlTextIndex + 1);
            return true;
        }
        else {
            return false;
        }
    }

    function skipEndOfTag() {
        $sb = "";
        if (($ch = $this->iCurrentChar) !== -1) {
            $match = ($ch == ">");
            if (!$match) {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;
    }

    function skipInTag ($chars) {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
            if ($ch == ">") {
                return $sb;
            } else {
                if (array_search($ch,$chars) === false)
                	return $sb;
                $sb .= $ch;
                $this->moveNext();
            }
        }
        return $sb;
    }

    function skipToInTag ($chars) {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
        	if ($ch == '>' || array_search($ch,$chars) !== false)
               	return $sb;
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;
    }

    function skipToElement() {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
            if ($ch == "<") {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;             
    }

	/**
	 * Returns text between current position and $needle,
	 * inclusive, or "" if not found. The current index is moved to a point
	 * after the location of $needle, or not moved at all
	 * if nothing is found.
	 *
	 * @param $needle
	 *
	 * @return string
	 */
    function skipToStringInTag ($needle) {
        $pos = strpos ($this->iHtmlText, $needle, $this->iHtmlTextIndex);
        if ($pos === false) {
            return "";
        }
        $top = $pos + strlen($needle);
        $retvalue = substr ($this->iHtmlText, $this->iHtmlTextIndex, $top - $this->iHtmlTextIndex);
        $this->setTextIndex ($top);
        return $retvalue;
    }
}
