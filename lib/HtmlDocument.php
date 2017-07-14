<?php
namespace VanXuan\PdfTable;

class HtmlDocument
{
    const TAGALONE = ['img','input','br','hr','area','base','col','command','link','meta','param','source'];
	public $root;
	public $allNodes=[];
	public $index=[];

	public function __construct($html = null)
	{
		if ($html)
			$this->parse($html);
	}

    public function skipComment($html)
    {
        while(preg_match('/<!--/',$html,$mstart,PREG_OFFSET_CAPTURE)) {
            $ends = substr($html, $mstart[0][1]);
            if (preg_match('/-->/',$ends,$mend,PREG_OFFSET_CAPTURE)){
                $html = substr($html,0,$mstart[0][1]).substr($ends,$mend[0][1]+3);
            }
        }
        return $html;
    }
    /**
     * Parse html document and apply Css style for each element
     *
     * @param $html
     *
     * @throws CssException
     */
    public function parse($html)
	{
		$parser = new HtmlParser($this->skipComment($html));
        $this->allNodes = [];
        $idIndex = [];
        $nameIndex = [];
        $classIndex = [];
        $ni = 0;
        $stack = [];
        $si = 0;
        /** @var HtmlNode $lastParent */
        $lastParent = $currentNode = null;
		while ($parser->parse()){
            switch($parser->iNodeType){
            case HtmlParser::TYPE_TEXT:
                $el = new HtmlText($parser->iNodeValue);
                $el->parent = $lastParent;
                if ($lastParent) $lastParent->childs[] = $el;
                $this->allNodes[$ni] = $el;
                $ni++;
                break;
            case HtmlParser::TYPE_ELEMENT:
                switch($parser->iNodeName){
                case 'table':
                    $el = new Table();
                    break;
                case 'td':
                case 'th':
                    $el = new Cell();
                    break;
                case 'img':
                    $el = new HtmlImage();
                    break;
                default:
                    $el = new HtmlElement();
                }
                $el->parent = $lastParent;
                if ($lastParent) $lastParent->childs[] = $el;
				$el->name = $parser->iNodeName;
				$el->attribute = (object)$parser->iNodeAttributes;
                if (!in_array($el->name,self::TAGALONE)) {
                    $this->allNodes[$ni] = $lastParent = $el;
                    $stack[$si++] = $el;
                }
                $ni++;
                if (isset($nameIndex[$el->name]))
                    $nameIndex[$el->name][] = $el;
                else
                    $nameIndex[$el->name] = [$el];
                if (isset($el->attribute->class)){
                    preg_match_all('/[\w\-]+/',$el->attribute->class,$clsList);
                    foreach($clsList[0] as $cls) {
                        if (isset($classIndex[$cls]))
                            $classIndex[$cls][] = $el;
                        else
                            $classIndex[$cls] = [$el];
                    }
                }
                if (isset($el->attribute->id)){
                    $cls = $el->attribute->id;
                    if (isset($idIndex[$cls]))
                        $idIndex[$cls][] = $el;
                    else
                        $idIndex[$cls] = [$el];
                }
                break;
            case HtmlParser::TYPE_COMMENT:
                break;
            case HtmlParser::TYPE_ENDELEMENT:
                while ($si>0 && $stack[$si-1]->name != $parser->iNodeName) {
                    unset($stack[--$si]);
                }
                unset($stack[--$si]);
                $lastParent = ($si > 0) ? $stack[$si - 1] : null;
                break;
            }
		}
        $this->index = [
            'id' => $idIndex,
            'class' => $classIndex,
            'name' => $nameIndex
        ];
        $this->root = count($this->allNodes) ? $this->allNodes[0] : null;

        $this->applyStyle();
	}
    private function applyStyle()
    {
        $css = new Css();
        /** @var HtmlElement $node */
        foreach($this->getByName('style') as $node){
            if (count($node->childs))
        	    $css->Parse($node->childs[0]->value);
        }
        $css->Apply($this);

        //Convert all attribute to class
        $attr2css = CssClass::ATTR2CSS;
        
        foreach($this->getElement() as $node){
            /** HtmlElement $node */
            if ($node->style === null)
                $node->style = new CssClass();
            //Convert attribute to real style
            $style = $node->style;
            foreach($node->attribute as $name=>$value){
                if (isset($attr2css[$name])){
                    $a2c = $attr2css[$name];
                    if (is_array($a2c))
                        $style->{$a2c['name']} = $a2c['value'];
                    else
                        $style->$a2c = $value;
                }
            }
            switch($node->name){
            case 'b':
            case 'h4':
                $this->addTreeFont($node, 'bold');
                break;
            case 'i':
                $this->addTreeFont($node, 'italic');
                break;
            case 'h1':
                $this->addTreeFont($node, 'bold');
                if (!$style->fontSize)
                    $style->fontSize = '2em';
                break;
            case 'h2':
                $this->addTreeFont($node, 'bold');
                if (!$style->fontSize)
                    $style->fontSize = '1.5em';
                break;
            case 'h3':
                $this->addTreeFont($node, 'bold');
                if (!$style->fontSize)
                    $style->fontSize = '1.17em';
                break;
            }
        }
    }

    /**
     * Add a font to all element in the tree node
     *
     * @param HtmlElement $el
     * @param string      $style
     */
    public function addTreeFont(HtmlElement $el, $style)
    {
        $el->style->fontStyle .= " $style";
        foreach($el->childs as $child)
            $this->addTreeFont($child, $style);
    }

	/**
     * Get list element by element's id
     *
     * @param $id
     *
     * @return array
     */
    public function getById($id)
    {
        return isset($this->index['id'][$id]) ? $this->index['id'][$id] : [];
    }

	/**
     * Get list element by tag name
     *       
     * @param string $name
     *
     * @return array
     */
    public function getByName($name)
    {
        return isset($this->index['name'][$name]) ? $this->index['name'][$name] : [];
    }

	/**
     * Get all element
     * 
     * @return array
     */
    public function getElement()
    {
        $list = [];
        foreach($this->allNodes as $node)
            if ($node instanceof HtmlElement)
                $list[] = $node;
        return $list;
    }

	/**
     * Get list element by element's Css classname
     *
     * @param $class
     *
     * @return array
     */
    public function getByClass($class)
    {
        return isset($this->index['class'][$class]) ? $this->index['class'][$class] : [];
    }

	/**
     * Get list element
     *
     * @param string $selector A lite version of jquery's selector
     *
     * @return array
     */
    public function select($selector)
    {
        preg_match_all('/[^\,]+/',$selector,$matches);
        $list = [];
        foreach($matches[0] as $path)
            $list = array_merge($list,$this->selectPath($path));
        return $list;
    }

    private function selectPath($path)
    {
        $paths = array_reverse(preg_split('/\s/',$path,-1,PREG_SPLIT_NO_EMPTY));
        $elements = [];
        $currents = [];
        /** @var HtmlElement $el */
        foreach($paths as $selector){
            preg_match_all('/[\#\.]{0,1}[\w\-]+/',$selector,$multi);
            $multi = $multi[0];
            $selector = array_shift($multi);
            if ($selector[0]=='#'){
                $id = substr($selector, 1);
                $this->findId($id, $elements, $currents);
            }elseif ($selector[0]=='.'){
                $cls = substr($selector, 1);
                $this->findCls($cls, $elements, $currents);
            }else{
                $this->findTag($selector, $elements, $currents);
            }
            while(count($multi)){
                $selector = array_shift($multi);
                if ($selector[0]=='#'){
                    $id = substr($selector, 1);
                    foreach($currents as $i=>$el){
                        if ($el->get('id') != $id){
                            unset($currents[$i]);
                            unset($elements[$i]);
                        }
                    }
                }elseif ($selector[0]=='.'){
                    $cls = substr($selector, 1);
                    foreach($currents as $i=>$el){
                        $acls = preg_split('/\s/',$el->get('class'),-1,PREG_SPLIT_NO_EMPTY);
                        if (!in_array($cls, $acls)){
                            unset($currents[$i]);
                            unset($elements[$i]);
                        }
                    }
                }else{
                    foreach($currents as $i=>$el){
                        if ($el->name != $selector){
                            unset($currents[$i]);
                            unset($elements[$i]);
                        }
                    }
                }
            }
            if (count($elements)==0)
                break;
        }
        return $elements;
    }
    private function findId($id, &$elements, &$currents){
        if (count($elements)==0) {
            $elements = $currents = $this->getById($id);
        }else {
            /** @var HtmlElement $element */
            foreach ($currents as $i => $element) {
                $element = $element->parent;
                $nomatch = 1;
                while ($element) {
                    if ($element->get('id') == $id) {
                        $currents[$i] = $element;
                        $nomatch = 0;
                        break;
                    }
                    $element = $element->parent;
                }
                if ($nomatch) {
                    unset($elements[$i]);
                    unset($currents[$i]);
                }
            }
        }
    }
    private function findCls($cls, &$elements, &$currents)
    {
        if (count($elements)==0) {
            foreach($this->getByClass($cls) as $el) {
                $elements[] = $el;
                $currents[] = $el;
            }
        }else {
            /** @var HtmlElement $el */
            foreach ($currents as $i => $el) {
                $el = $el->parent;
                $nomatch = 1;
                while ($el) {
                    $listCls = $el->get('class');
                    $listCls = preg_split('/\s/', $listCls, -1, PREG_SPLIT_NO_EMPTY);
                    if (in_array($cls, $listCls)) {
                        $currents[$i] = $el;
                        $nomatch = 0;
                        break;
                    }
                    $el = $el->parent;
                }
                if ($nomatch) {
                    unset($elements[$i]);
                    unset($currents[$i]);
                }
            }
        }
    }
    private function findTag($tag, &$elements, &$currents)
    {
        $tag = strtolower($tag);
        if (count($elements)==0) {
            foreach($this->getByName($tag) as $element) {
                $elements[] = $element;
                $currents[] = $element;
            }
        }else {
            /** @var HtmlElement $element */
            foreach ($currents as $i => $element) {
                $element = $element->parent;
                $nomatch = 1;
                while ($element) {
                    if ($element->name == $tag) {
                        $currents[$i] = $element;
                        $nomatch = 0;
                        break;
                    }
                    $element = $element->parent;
                }
                if ($nomatch) {
                    unset($elements[$i]);
                    unset($currents[$i]);
                }
            }
        }
    }
}
class HtmlNode
{
    /** @var HtmlElement */
	public $parent;
	public $childs=[];
}
class HtmlElement extends HtmlNode
{
	public $name;
	public $attribute;
    /** @var  CssClass */
	public $style;

    public function findTag($name)
    {
        $list = [];
        foreach($this->childs as $child){
            if ($name == 'text' && $child instanceof HtmlText)
                $list[] = $child;
            elseif($child instanceof HtmlElement) {
                if ($name == $child->name)
                    $list[] = $child;
                $list = array_merge($list,$child->findTag($name));
            }
        }
        return $list;
    }
    public function get($name)
    {
        return isset($this->attribute->$name) ? $this->attribute->$name : null;
    }

	/**
     * Return value of property or null
     *
     * @param string $name  Attribute's name like HTML attribute
     * @param string $cssname Css's name like CSS2JS attribute
     *
     * @return mixed
     */
    public function getProperty($name, $cssname=null)
    {
        if (isset($this->attribute->$name))
            return $this->attribute->$name;
        if ($cssname===null)
            $cssname = $name;
        if (isset($this->style->$cssname))
            return $this->style->$cssname;
        return null;
    }

    /**
     * Return value of property or null, attribute will be inhirited if not exists at
     * current node
     *
     * @param string $name Attribute's name like HTML attribute
     * @param bool   $inherited Get attr inhirited when it doesn't exist
     * @param string $limit Parent tag will break inhirited flow
     *
     * @return mixed
     *
     */
    public function getAttr($name, $inherited = true, $limit = null)
    {
        $value = isset($this->style->$name) ? $this->style->$name : null;
        if ($inherited) {
            $node = $this->parent;
            while ($value === null && $node instanceof HtmlElement) {
                $value = isset($node->style->$name) ? $node->style->$name : null;
                if ($node->name == $limit)
                    break;
                $node = $node->parent;
            }
        }
        return $value;
    }
    public function getCellAttr($name)
    {
        return $this->getAttr($name, true, 'table');
    }
}
class HtmlImage extends HtmlElement
{
	public function check()
	{
        $src = $this->attribute->src;
        if (!is_file($src) && defined('RESOURCE_DIR') && is_file(RESOURCE_DIR.$src))
            $src = RESOURCE_DIR.$src;
        if (!is_file($src))
            throw new CssException("File $src not found!");
        $this->attribute->src = $src;
        $info = getimagesize($src);
        if ($info===false)
            throw new CssException("File $src is not an image");
        if (!in_array($info['mime'],['image/jpeg','image/png','image/gif']))
            throw new CssException("Unsupport {$info['mime']}");
        if (!$this->style)
            $this->style = new CssClass();
        $this->style->width = $info[0];
        $this->style->height = $info[1];

        return $this;
	}
}
class HtmlText extends HtmlNode
{
	public $value;

	public function __construct($value=null)
	{
		$this->value = html_entity_decode($value,null,'utf-8');
	}
}
class HtmlComment extends HtmlNode
{
	public $value;

	public function __construct($value=null)
	{
		$this->value = $value;
	}
}
