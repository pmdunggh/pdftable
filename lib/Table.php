<?php
namespace VanXuan\PdfTable;

class Table extends HtmlElement
{
	public $numCol = 0; //Number of column
	public $numRow = 0; //Number of row

	public $repeat = []; //List index row repeatable after page break
	public $cells = [];  //A two directions matrix of all cell
	public $widthColumns = [];
	public $heightRows = [];
	public $breakable = [];
	public $spanRows = []; //Matrix cell have rowspan>1
	public $spanCols = []; //Matix cell have colspan>1

	public function getHeight()
	{
		return array_sum($this->heightRows);
	}
	public function isBreakable()
	{
		return $this->style->pageBreakInside!='avoid';
	}
	public function getCellWidth($i,$j){
		/** @var Cell $c */
		$c = $this->cells[$i][$j];
		if ($c instanceof Cell){
			if (!isset($c->cachedLeft)) {
				$x = 0;
				$wc = $this->widthColumns;
				for ($k = 0; $k < $j; $k++)
					$x += $wc[$k];
				$w = $wc[$j];
				for ($k = $j + $c->style->columnSpan - 1; $k > $j; $k--)
					$w += $wc[$k];
				$c->cachedLeft = $x;
				$c->cachedWidth = $w;
			}
			return [$c->cachedLeft, $c->cachedWidth];
		}
		return [0,0];
	}
	public function getCellHeight($i,$j){
		/** @var Cell $c */
		$c = $this->cells[$i][$j];
		if ($c instanceof Cell){
			if (!isset($c->cachedHeight)) {
				$hr = $this->heightRows;
				$h = $hr[$i];
				for ($k = $i + $c->style->rowSpan - 1; $k > $i; $k--)
					$h += $hr[$k];
				$c->cachedHeight = $h;
			}
			return $c->cachedHeight;
		}
		return 0;
	}
	public function checkCollapse()
	{
		if ($this->style->borderCollapse != 'collapse')
			return;
		$this->markLastCell();

		foreach($this->cells as $i=>$row){
			/** @var Cell $cell */
			foreach($row as $j=>$cell){
				if ($cell instanceof Cell){
					/** @var CssClass $s */
					$s = $cell->style;
					//Remember bottom width in borderCollapse and will be draw when needed
					$s->borderCollapse = $cell->getCellAttr('borderBottomWidth');
					$s->borderBottomWidth = $cell->bottomCell ? $s->borderBottomWidth : 0;
					$s->borderRightWidth = $cell->rightCell ? $s->borderRightWidth : 0;
				}
			}
		}
	}
	public function removeRow($from, $count)
	{
		array_splice($this->cells, $from, $count);
		array_splice($this->heightRows, $from, $count);
		array_splice($this->breakable, $from, $count);
		array_splice($this->spanRows, $from, $count);
		array_splice($this->spanCols, $from, $count);
		$this->numRow -= $count;
	}
	public function copyRepeatRow(Table $from)
	{
		$repeat = [];
		$reverse = array_reverse($from->repeat);
		$j = 0;
		foreach($reverse as $i){
			$repeat[] = $j;
			$this->cells = array_merge([$from->cells[$i]],$this->cells);
			$this->heightRows = array_merge([$from->heightRows[$i]],$this->heightRows);
			$this->breakable = array_merge([$from->breakable[$i]],$this->breakable);
			$this->spanRows = array_merge([$from->spanRows[$i]],$this->spanRows);
			$this->spanCols = array_merge([$from->spanCols[$i]],$this->spanCols);
			$j++;
		}
		$this->repeat = array_reverse($repeat);
		$this->numRow += $j;
	}
	public function markLastCell()
	{
		$j = $this->numCol-1;
		for($i=0;$i<$this->numRow;$i++){
			if ($this->cells[$i][$j] instanceof Cell) {
				$this->cells[$i][$j]->rightCell = true;
			}else{//$this->cells[$i][$j] instanceof RefCell
				$this->cells[$i][$j]->cell->rightCell = true;
			}
		}
		$i = $this->numRow-1;
		for($j=0;$j<$this->numCol;$j++){
			if ($this->cells[$i][$j] instanceof Cell) {
				$this->cells[$i][$j]->bottomCell = true;
			}else{//$this->cells[$i][$j] instanceof RefCell
				$this->cells[$i][$j]->cell->bottomCell = true;
			}
		}
	}
	public function validateRefCellOfFirstRow()
	{
		for($j=0;$j<$this->numCol;$j++){
			if ($this->cells[0][$j] instanceof Cell) {
				/** @var CssClass $s */
				$s = $this->cells[0][$j]->style;
				if ($s->rowSpan>1||$s->columnSpan>1){
					$ref = new RefCell($this->cells[0][$j], $j);
					for($ri=0;$ri<$s->rowSpan;$ri++){
						for($ci=0;$ci<$s->columnSpan;$ci++){
							if ($ri != 0 || $ci != 0)
								$this->cells[$ri][$j+$ci] = $ref;
						}
					}
				}
			}
		}
	}
	public function verifyRepeatRow($maxHeight)
	{
		//Rule 1: No rowspan out of repeat row
		foreach($this->repeat as $ri){
			for($ci=0;$ci<$this->numCol;$ci++)
				if ($this->cells[$ri][$ci] instanceof Cell){
					/** @var CssClass $s */
					$s = $this->cells[$ri][$ci]->style;
					for($i=1;$i<$s->rowSpan;$i++)
						if (!in_array($ri+$i,$this->repeat)){
							$this->repeat = [];
							return false;
						}
				}
		}
		//Rule 2: Total height < $maxHeight/2
		$height = 0;
		foreach($this->repeat as $ri)
			$height += $this->heightRows[$ri];
		if ($height >= $maxHeight/2){
			$this->repeat = [];
			return false;
		}
		return true;
	}
}