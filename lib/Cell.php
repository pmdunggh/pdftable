<?php
namespace VanXuan\PdfTable;

class Cell extends HtmlElement
{
	public $cachedLeft;
	public $cachedWidth;
	public $cachedHeight;

	public $hline;
	public $wlinet;
	public $wlines;

	public $rightCell;  //(bool) Is the most right cell of row
	public $bottomCell; //(bool) Is the most bottom of column

	public $chunks=[];
	public $groups=[];
	public $groupsHeight;
	public $spaceMax=0;
	public $wordMax=0;

	public function calculHeight()
	{
		$height = 0;
		/** @var ChunkGroup $group */
		foreach($this->groups as $group)
			$height += $group->height;
		$this->groupsHeight = $height;
		$this->style->height = $height
				+ $this->getCellAttr('borderTopWidth') + $this->getCellAttr('borderBottomWidth')
				+ $this->getCellAttr('paddingTop') + $this->getCellAttr('paddingBottom');
		$this->cachedHeight = null;
		return $height;
	}
	public function makeCopy()
	{
		$n = clone $this;
		$n->style = clone $n->style;
		return $n;
	}
	public function isBreakable()
	{
		return $this->style->pageBreakInside!='avoid';
	}
}

class RefCell
{
	public $col;
	/** @var  Cell */
	public $cell;

	public function __construct(Cell $c, $ci)
	{
		$this->cell = $c;
		$this->col  = $ci;
	}
}
class Chunk
{
	public $word;
	public $space;
	public $width;
	public $height;
	public $before;
	public $after;
	/** @var HtmlElement */
	public $font;
	/** @var HtmlElement */
	public $image;

	public function __construct($word, $space, $width, $height, $before=0, $after=0, $font=null, $image=null)
	{
		$this->word = $word;
		$this->space = $space;
		$this->width = $width;
		$this->height = $height;
		$this->before = $before;
		$this->after = $after;
		$this->font = $font;
		$this->image = $image;
	}
}
class ChunkGroup
{
	//public $id='';
	public $width;
	public $indent;
	public $height;
	public $before;
	public $after;
	public $hardBreak=0;
	public $chunks=[];
	private $max;
	public $count=0;

	public function __construct($max, $indent=0)
	{
		$this->max = $max;
		$this->width = $indent;
		$this->indent = $indent;
	}

	public function addChunk(Chunk $c)
	{
		$addWidth = $c->width + $c->space;
		if ($this->count && $this->width+$addWidth+$c->space > $this->max)
			return false;
		//$this->id .= $c->word ? $c->word.' ' : 'IMG ';
		$this->chunks[$this->count++] = $c;
		$this->width += $addWidth;
		if ($this->height < $c->height)
			$this->height = $c->height;
		if ($this->after < $c->after)
			$this->after = $c->after;
		if ($this->before < $c->before)
			$this->before = $c->before;
		return true;
	}
	public function findLastFont()
	{
		for($i=$this->count-1;$i>=0;$i--)
			if ($this->chunks[$i]->font)
				return $this->chunks[$i]->font;
		return null;
	}
	public function finish()
	{
		if ($this->count){
			/** @var Chunk $last */
			//$last = $this->chunks[$this->count-1];
			//$last->width += $last->space/2;
		}
		return $this;
	}
}