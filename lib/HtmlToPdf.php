<?php
namespace VanXuan\PdfTable;

class HtmlToPdf extends Utf8Pdf
{
    const VERSION = '2.0.1';
    const SBREAK = 1;
	const HBREAK = 2;
	const FONTSIZE = 14;
	const LINEHEIGHT = 1.2; //FONTSIZE * 1.2

	protected $clientLeft;		//Toa do le trai cua trang
	protected $clientRight;		//Toa do le phai cua trang
	protected $clientTop;		//Toa do le tren cua trang
	protected $clientBottom;	//Toa do le duoi cua trang
	protected $clientWidth;     //Width of writable zone of page
	protected $clientHeight;    //Height of writable zone of page
	protected $LineHeight;
	protected $SpacingBefore;
	protected $SpacingAfter;
	protected $LineStyle;
	protected $LineStyleF;

	private $defaultFontFamily;
	private $defaultFontStyle;
	private $defaultFontSize;
	private $defaultLineHeight;

	public function __construct($orientation = 'P', $unit = 'pt', $format = 'A4')
	{
		parent::__construct($orientation, 'pt', $format);
		$this->defaultFontFamily = $this->FontFamily;
		$this->defaultFontSize = $this->FontSizePt;
		$this->defaultFontStyle = $this->FontStyle;
		$this->defaultLineHeight = $this->FontSizePt;
		$this->AutoPageBreak = 0;
	}

	/**
	 * Convert unit to point
	 *
	 * @param $val
	 * @param $unit
	 *
	 * @return int
	 */
	private function convertUnit($val, $unit)
	{
		switch($unit){
		case 'mm':
			return intval($val*72/25.4);
		case 'cm':
			return intval($val*72/2.54);
		case 'in':
			return intval($val*72);
		}
		return intval($val);
	}
	protected function fixCSS(HtmlDocument $document)
	{
		$attrUnit = [
			'width','minWidth','maxWidth',
			'height','minHeight','maxHeight',
			'borderTopWidth','borderBottomWidth','borderLeftWidth','borderRightWidth',
			'paddingTop','paddingBottom','paddingLeft','paddingRight',
			'marginTop','marginBottom','marginLeft','marginRight',
			'spacingBefore','spacingAfter','textIndent'
		];
		foreach ($document->getElement() as $el) {
			/** var HtmlElement $el */
			if (!empty($el->style)) {
				/** @var CssClass $s */
				$s = $el->style;
				if ($s->width && preg_match('/.*%$/', $s->width)) {
					$s->widthPercent = floatval($s->width)/100;
					$s->width = null;
				}
				if ($s->height && preg_match('/.*%$/', $s->height)) {
					$s->heightPercent = floatval($s->height)/100;
					$s->height = null;
				}
				if ($s->textAlign)
					$s->textAlign = strtolower($s->textAlign);
				if ($s->verticalAlign)
					$s->verticalAlign = strtolower($s->verticalAlign);
				if ($s->fontWeight=='bold')
					$s->fontStyle .= ' bold';
				if ($s->fontStyle) {
					$styles = array_unique(preg_split('/[\s]/', $s->fontStyle, -1, PREG_SPLIT_NO_EMPTY));
					$s->fontStyle = '';
					foreach($styles as $style)
						$s->fontStyle .= strtoupper($style[0]);
				}
				if (preg_match_all('/([\d\.]+)(px|pt|em|%)/',$s->fontSize,$matches)){
					$f = $matches[1][0]; $u = $matches[2][0];
					if ($u=='em')
						$f *= self::FONTSIZE;
					elseif($u=='%')
						$f = $f*self::FONTSIZE/100;
					$s->fontSize = $f;
				}
				if (preg_match_all('/([\d\.]+)(px|pt|em|%)/',$s->lineHeight,$matches)){
					$f = $matches[1][0]; $u = $matches[2][0];
					if ($u=='em')
						$f *= $s->fontSize ? $s->fontSize : self::FONTSIZE;
					elseif($u=='%')
						$f = $f*($s->fontSize ? $s->fontSize : self::FONTSIZE)/100;
					$s->lineHeight = $f;
				}
				if (!$s->fontSize && $s->lineHeight)
					$s->fontSize = self::FONTSIZE;
				if ($s->fontSize){
					if (!$s->lineHeight)
						$s->lineHeight = $s->fontSize*self::LINEHEIGHT;
					if (!$s->spacingAfter) $s->spacingAfter = 0;
					if (!$s->spacingBefore) $s->spacingBefore = 0;
				}
				if (preg_match_all('/[^\s]+/',$s->padding,$matches)){
					$padding = $matches[0];
					switch(count($padding)){
					case 1:
						$s->paddingTop = $s->paddingBottom = $padding[0];
						$s->paddingLeft = $s->paddingRight = $padding[0];
						break;
					case 2:
						$s->paddingTop = $s->paddingBottom = $padding[0];
						$s->paddingLeft = $s->paddingRight = $padding[1];
						break;
					case 4:
						$s->paddingTop = $padding[0];
						$s->paddingRight = $padding[1];
						$s->paddingBottom = $padding[2];
						$s->paddingLeft = $padding[3];
						break;
					}
				}
				if ($el->name == 'td' || $el->name == 'th') {
					if (!isset($s->rowSpan))
						$s->rowSpan = 1;
					if (!isset($s->columnSpan))
						$s->columnSpan = 1;
				}
				if ($el instanceof HTMLImage)
					$el->check();
				foreach($attrUnit as $attr){
					if (isset($s->$attr) && preg_match_all('/([\d\.]+)(.*)/',$s->$attr,$matches)){
						$value = $matches[1][0];
						$unit = isset($matches[2][0])?$matches[2][0]:'px';
						$s->$attr = $this->convertUnit($value, $unit);
					}
				}
			}
		}
	}

	/**
	 * Set page margin like Css
	 *
	 * @param      $top
	 * @param null $right
	 * @param null $bottom
	 * @param null $left
	 */
	function setPageMargins($top, $right=null, $bottom=null, $left=null)
	{
		if ($right === null)
			$this->tMargin = $this->rMargin = $this->bMargin = $this->lMargin = $top;
		elseif ($bottom === null){
			$this->tMargin = $this->bMargin = $top;
			$this->lMargin = $this->rMargin = $right;
		}elseif ($left !== null){
			$this->tMargin = $top;
			$this->rMargin = $right;
			$this->bMargin = $bottom;
			$this->lMargin = $left;
		}
	}

	/**
	 * @param $html
	 *
	 * @return HtmlDocument
	 *
	 * @desc     Parse a string in html and return array of attribute of table
	 */
	protected function parseHtml($html)
	{
		$doc = new HtmlDocument($html);
		$this->fixCSS($doc);
		return $doc;
	}
	public function renderTable(HtmlDocument $doc)
	{
		if ($this->page==0)
			$this->addPage();
		$tables = $doc->getByName('table');
		/** @var Table $table */
		foreach ($tables as $table) {
			$breakable = $table->style->pageBreakInside!='avoid';
			$numRow = 0;
			$listRow = [];
			/** @var HtmlElement $part */
			foreach ($table->childs as $part) {
				if ($part->name == 'tr')
					$listRow[] = $part;
				elseif (in_array($part->name, ['thead','tbody','tfoot'])) {
					foreach ($part->childs as $pi)
						if ($pi->name == 'tr')
							$listRow[] = $pi;
				}
			}
			/** @var HtmlElement $row */
			foreach ($listRow as $i=>$row) {
				if ($row->style->display == 'table-header-group')
					$table->repeat[] = $i;
				$table->breakable[$i] = $breakable && $row->style->pageBreakInside!='avoid';
				$table->spanCols[$numRow] = [];
				$table->spanRows[$numRow] = [];
				$numRow++;
				$table->numRow++;
				$numCol = 0;
				foreach ($row->childs as $cell) {
					if ($cell instanceof Cell) {
						if (!$cell->isBreakable())
							$table->breakable[$i] = 0;
						$numCol++;
						while (isset($table->cells[$numRow - 1][$numCol - 1]))
							$numCol++;
						if ($table->numCol < $numCol)
							$table->numCol = $numCol;
						$table->cells[$numRow - 1][$numCol - 1] = $cell;
						$colspan = intval($cell->getAttr('columnSpan', false));
						$rowspan = intval($cell->getAttr('rowSpan', false));
						if ($colspan>1||$rowspan>1) {
							if ($colspan>1)
								$table->spanCols[$numRow - 1][$numCol - 1] = $cell;
							if ($rowspan>1)
								$table->spanRows[$numRow - 1][$numCol - 1] = $cell;
							$ref = new RefCell($cell, $numCol - 1);
							//Chiem dung vi tri de danh cho cell span
							for ($k = $numRow; $k < $numRow + $rowspan; $k++) {
								for ($l = $numCol; $l < $numCol + $colspan; $l++) {
									if ($k - $numRow || $l - $numCol)
										$table->cells[$k - 1][$l - 1] = $ref;
								}
							}
						}
					}
				}
			}
			$table->checkCollapse();
			$this->calculWidthColumn($table);
			$this->calculWidthTable($table, $this->clientWidth);
			$this->calculHeightTable($table);
			$this->FontFamily = '';//Reset font for new SetFont
			while ($table) {
				if ($table->isBreakable()){
					$newTable = $this->splitTable($table);
					$this->writeTable($table);
					$table = $newTable;
					if ($table)
						$this->addPage();
				}else{
					if ($this->clientBottom - $this->y < $table->getHeight())
						$this->addPage();
					$this->writeTable($table);
					break;
				}
			}
		}
	}
	private function makeChunks(Cell $c, HtmlElement $p, $text)
	{
		$words = preg_split('/\s+|\b(?=[!\?\.\-\+\*])(?!\.\s*)/',$text,-1,PREG_SPLIT_NO_EMPTY);
		$result = [];
		foreach($words as $i=>$word){
			if (preg_match('/^[\+\-\*].*/',$word)){
				$result[] = $word[0];
				$s = substr($word, 1);
				if ($s !== false) $result[] = $s;
			}else{
				$result[] = $word;
			}
		}
		//$result = preg_split('/\s/',$text,-1,PREG_SPLIT_NO_EMPTY);
		if (count($result)) {
			$this->setFontElement($p, true);
			$spaceWidth = $this->GetStringWidth(' ');
			$c->spaceMax = max($c->spaceMax,$spaceWidth);
			$first = true;
			foreach ($result as $word) {
				$c->chunks[] = new Chunk(
					$word,
					$spaceWidth,
					$width = $this->GetStringWidth($word),
					$this->LineHeight,
					$this->SpacingBefore,
					$this->SpacingAfter,
					$first ? $p : null
				);
				$first = false;
				$c->wordMax = max($c->wordMax, $width);
			}
		}
	}
	private function analyseCellContent(Cell $c, HtmlElement $el)
	{
		foreach($el->childs as $child){
			if ($child instanceof HTMLText){
				$this->makeChunks($c, $el, $child->value);
			}elseif ($child instanceof HtmlElement){
				if ($child->name == 'br'){
					$c->chunks[] = self::SBREAK;
				}elseif(in_array($child->name,['h1','h2','h3','h4','p','div'])){
					$n = count($c->chunks);
					if ($n && $c->chunks[$n-1] instanceof Chunk)
						$c->chunks[] = self::HBREAK;
					$this->analyseCellContent($c, $child);
					$c->chunks[] = self::HBREAK;
				}elseif(in_array($child->name,['span','b','i'])){
					$this->analyseCellContent($c, $child);
				}elseif($child instanceof HTMLImage) {
					$child->check();
					$s = $child->style;
					$bp = $s->borderLeftWidth+$s->borderRightWidth;
					$c->chunks[] = new Chunk(
						'',
						0,
						$width = $s->width+$bp,
						$s->height+$s->borderTopWidth+$s->borderBottomWidth,
						$this->SpacingBefore,
						$this->SpacingAfter,
						null,
						$child
					);
					$c->wordMax = max($c->wordMax, $width);
				}
			}
		}
	}
	private function calculWidthCell(Cell $c)
	{
		$this->analyseCellContent($c, $c);
		$n = count($c->chunks);
		if ($n && !$c->chunks[$n-1] instanceof Chunk)
			unset($c->chunks[$n-1]);
		$s = $c->style;
		$around = $c->getCellAttr('borderLeftWidth') + $c->getCellAttr('borderRightWidth')
				+ $c->getCellAttr('paddingLeft') + $c->getCellAttr('paddingRight');
		$rowMax = $c->wordMax + $c->spaceMax + $around;
		if ($s->whiteSpace === 'nowrap') {
			$aRowMax = 0;
			$max = $around;
			foreach($c->chunks as $chunk){
				if ($chunk instanceof Chunk) {
					$max += $chunk->width + $chunk->space;
				}else{
					if ($aRowMax < $max)
						$aRowMax = $max;
					$max = $around;
				}
			}
			$rowMax = max($rowMax, $aRowMax, $max);
		}
		if ($s->minWidth < $rowMax)
			$s->minWidth = $rowMax;
	}
	private function calculWidthColumn(Table $table)
	{
		//Xac dinh do rong cua cac cell va cac cot tuong ung
		for ($j = 0; $j < $table->numCol; $j++) {
			$table->widthColumns[$j] = $wc = new Dimension();
			for ($i = 0; $i < $table->numRow; $i++) {
				$c = &$table->cells[$i][$j];
				if ($c instanceof Cell) {
					$this->calculWidthCell($c);
					$s = $c->style;
					if ($s->minWidth && $s->minWidth < $s->width)
						$s->minWidth = null;
					if ($s->width && $s->minWidth >= $s->width){
						$s->width = $s->minWidth;
						$s->minWidth = null;
					}
					if ($s->maxWidth < $s->minWidth)
						$s->maxWidth = null;
					if ($s->columnSpan == 1)
						$wc->assign($s->minWidth,$s->maxWidth,$s->width,$s->widthPercent);
				}
			}
		}
		$this->arrangeWidthSpan($table);
	}

	private function calculWidthTable(Table $table, $maxWidth)
	{
		$wc = &$table->widthColumns;
		$s = $table->style;
		$d = new Dimension();
		$d->assign($s->minWidth, $s->maxWidth, $s->width, $s->widthPercent);
		$s->width = $d->get($maxWidth);
		$totalColWidth = 0;
		$lstAuto = [];$totalAuto = 0;
		/** @var Dimension $ac */
		foreach ($wc as $j=>$ac) {
			$totalColWidth += $ac->get($s->width);
			if (!$ac->size){
				$lstAuto[$j] = $ac;
				$totalAuto += $ac->size;
			}
		}
		if ($totalColWidth > $s->width)
			$s->width = $totalColWidth;
		elseif ($totalColWidth < $s->width){
			$widthLeft = $s->width - $totalColWidth;
			if (count($lstAuto)){
				$addWidth = $widthLeft / count($lstAuto);
				foreach($lstAuto as $j=>$ac)
					$ac->assign(null,null,max($ac->min,$ac->size)+$addWidth,null);
			}else{
				$addWidth = $widthLeft / $table->numCol;
				foreach($wc as $j=>$ac)
					$ac->assign(null,null,max($ac->min,$ac->size)+$addWidth,null);
			}
			$this->arrangeWidthSpan($table);
		}
		//Convert Dimension to int
		/** @var Dimension $w */
		foreach($table->widthColumns as &$w)
			$w = $w->get();
	}
	private function calculHeightCell(Cell $c, $width){
		//$width += $c->getCellAttr('paddingLeft') + $c->getCellAttr('paddingRight')
		//	+ $c->getCellAttr('borderLeftWidth') + $c->getCellAttr('borderRightWidth');
		$height = 0;
		/** @var ChunkGroup $last */
		$last = null;
		$indent = $c->getCellAttr('textIndent');
		$group = new ChunkGroup($width, $indent);
		foreach($c->chunks as $chunk){
			if ($chunk instanceof Chunk) {
				$finish = $group->addChunk($chunk) == false;
			}else
				$finish = 1;
			if ($finish){
				$height += $group->height;
				if (is_int($chunk) && $chunk == self::HBREAK) {
					$group->hardBreak = 1;
					if ($last)
						$height += max($group->before,$last->after);
				}
				if (!$last)
					$height += $group->before;
				$c->groups[] = $last = $group->finish();
				$group = new ChunkGroup($width, $group->hardBreak ? $indent : 0);
				if ($chunk instanceof Chunk)
					$group->addChunk($chunk);
			}
		}
		if ($group->count) {
			$group->hardBreak = 1;
			$height += $group->height;
			if ($last)
				$height += max($group->before,$last->after);
			else
				$height += $group->before+$group->after;
			$c->groups[] = $group->finish();
		}
		$c->chunks = [];
		$s = $c->style;
		$c->groupsHeight = $height;
		$s->minHeight = $height+$c->style->paddingTop+$c->style->paddingBottom
				+$c->style->borderTopWidth+$c->style->borderBottomWidth;
		if ($s->minHeight < $s->height)
			$s->minHeight = null;
		if ($s->height && $s->minHeight > $s->height)
			$s->height = null;
		if ($s->maxHeight < $s->minHeight)
			$s->maxHeight = null;
		$d = new Dimension();
		$d->assign($s->minHeight,$s->maxHeight,$s->height,$s->heightPercent);
		return $d->get();
	}
	private function writeTable(Table $table, $left=null, $right=null){
		$table->markLastCell();
		$style = $table->style;
		if ($left===null)
			$left = $this->clientLeft;
		if ($right===null)
			$right = $this->clientRight;
		if ($this->x==null) $this->x = $left;
		if ($this->y==null)
			$this->y = $this->clientTop;
		else
			$this->y += $table->style->marginTop;
		$x = $this->x;
		switch ($style->textAlign){
		case 'C':
			$x += ($right - $left - $style->width) / 2;
			break;
		case 'R':
			$x = $right - $style->width;
			break;
		}
		for ($i = 0; $i < $table->numRow; $i++)
			$this->writeRow($table, $i, $x);
		$this->x = $x;
	}
	function addPage($orientation='', $size='', $rotation=0)
	{
		parent::AddPage($orientation, $size, $rotation);

		$this->clientLeft = $this->lMargin;
		$this->clientRight = $this->w - $this->rMargin;
		$this->clientTop = $this->tMargin;
		$this->clientBottom = $this->h - $this->bMargin;
		$this->clientWidth = $this->clientRight - $this->clientLeft;
		$this->clientHeight = $this->clientBottom - $this->clientTop;
	}
	private function splitCell(Cell $c, $maxHeight)
	{
		//Cell is not breakable
		if (!$c->isBreakable())
			return null;
		$h = $c->getCellAttr('borderTopWidth') + $c->getCellAttr('paddingTop')
				+$c->getCellAttr('borderBottomWidth') + $c->getCellAttr('paddingBottom');
		$n = count($c->groups);
		for($i=0;$i<$n;$i++){
			$h += $c->groups[$i]->height;
			if ($h > $maxHeight)
				break;
		}
		//Cell height is less than $maxHeight
		if ($i==$n || $i==0)
			return null;
		$nc = $c->makeCopy();
		//Cell bi break phai copy font tu chunk phia truoc
		if ($nc->groups[$i]->count && !$nc->groups[$i]->chunks[0]->font){
			$font = null;
			for($j=$i-1;$j>=0 && !$font;$j--) {
				/** @var ChunkGroup $cg */
				$cg = $nc->groups[$j];
				$font = $cg->findLastFont();
			}
			if ($font)
				$nc->groups[$i]->chunks[0]->font = $font;
		}
		array_splice($nc->groups,0,$i);
		$nc->calculHeight();
		array_splice($c->groups,$i,$n-$i);
		$c->calculHeight();
		return $nc;
	}
	private function splitRow(Table $table, $i, $maxHeight)
	{
		if (!$table->breakable[$i])
			return null;
		$newRow = [];
		$split = false;
		for ($j = 0; $j < $table->numCol;$j++) {
			if ($table->cells[$i][$j] instanceof Cell) {
				/** @var Cell $c */
				$c = $table->cells[$i][$j];
				/** @var Cell $newCell */
				$newCell = $this->splitCell($c, $maxHeight);
				if ($newCell instanceof Cell) {
					$split = true;
				}else{
					$newCell = $c->makeCopy();
					$newCell->groups = [];
					$newCell->groupsHeight = 0;
					$newCell->calculHeight();
				}
				$newRow[$j] = $newCell;
			}else{
				$newRow[$j] = $table->cells[$i][$j];
			}
		}
		return $split ? $newRow : null;
	}
	private function checkCellRowSpan(Table $table, &$newRow, $rowHeight, $i, $childSplited)
	{
		foreach ($newRow as $j=>$r) {
			if ($r instanceof RefCell){
				if ($r->col != $j) continue;
				$maxHeight = $rowHeight;
				for($pi = $i-1;$table->cells[$pi][$j] !== $r->cell;$pi--)
					$maxHeight += $table->heightRows[$pi];
				$newCell = $this->splitCell($r->cell, $maxHeight);
				if ($newCell instanceof Cell) {
					$newCell->style->minHeight -= $maxHeight;
				}else{
					$newCell = $r->cell->makeCopy();
					$newCell->style->minHeight = null;
					$newCell->groups = [];
				}
				$r->cell->style->rowSpan = $i - $pi + $childSplited;
				$newCell->style->rowSpan -= $i - $pi;
				$newCell->calculHeight();
				$newRow[$j] = $newCell;
			}
		}
	}
	private function getRowHeight($row)
	{
		$height = 0;
		/** @var Cell $c */
		foreach($row as $c){
			if ($c->style->rowSpan==1 && $height < $c->style->height)
				$height = $c->style->height;
		}
		return $height;
	}

	private function splitTable(Table $table)
	{
		$height = $table->getCellAttr('magrinTop')+$table->getCellAttr('magrinRight');
		$maxHeight = $this->clientBottom - $this->y;
		for($i=0;$i<$table->numRow;$i++) {
			if ($height + $table->heightRows[$i] < $maxHeight) {
				$height += $table->heightRows[$i];
				continue;
			}
			$nt = clone $table;
			$nt->removeRow(0,$i);
			$newRow = $this->splitRow($table, $i, $maxHeight-$height);
			if ($newRow){
				//Height of first part of row after split
				$rowHeight = $this->getRowHeight($table->cells[$i]);
				$this->checkCellRowSpan($table, $newRow, $rowHeight, $i, 1);

				//Second part of row $i in new table, is row 0th in new table
				$nt->cells[0] = $newRow;
				$nt->heightRows[0] = $this->getRowHeight($newRow);
				$this->arrangeHeightSpan($nt);
				//Keep first part of row $i in old table
				$table->heightRows[$i] = $this->getRowHeight($table->cells[$i]);
				$i++;
			}else{
				$this->checkCellRowSpan($table, $nt->cells[0], 0, $i, 0);
			}
			$nt->validateRefCellOfFirstRow();
			$nt->copyRepeatRow($table);
			$table->removeRow($i,$table->numRow-$i);
			return $nt;
		}
		return null;
	}
	private function writeRow(Table $table,$i,$x){
		$height = $table->heightRows[$i];
		$y = $this->y;
		for ($j=0;$j<$table->numCol;$j++){
			if ($table->cells[$i][$j] instanceof Cell){
				list($xr,$w) = $table->getCellWidth($i, $j);
				$h = $table->getCellHeight($i, $j);

				$this->drawCell($x+$xr, $y, $w, $h, $table->cells[$i][$j]);
			}
		}
		$this->y = $y + $height;
	}
	private function drawImage($x, $y, Chunk $c)
	{
		$w = $c->width; $h = $c->height;
		$s = $c->image->style;
		$x1 = $x; $y1 = $y;
		$x2 = $x + $w; $y2 = $y + $h;
		$width = $s->borderTopWidth;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $s->borderTopColor;
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($s->borderTopStyle);
			$half = $width/2;
			$this->Line($x1+$half , $y1+$half , $x2-$half, $y1+$half );
			$y += $width;
			$h -= $width;
		}
		$width = $s->borderBottomWidth;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $s->borderBottomColor;
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($s->borderBottomStyle);
			$half = $width/2;
			$this->Line($x1+$half , $y2-$half , $x2-$half, $y2-$half );
			$h -= $width;
		}
		$width = $s->borderRightWidth;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $s->borderRightColor;
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($s->borderRightStyle);
			$half = $width/2;
			$this->Line($x2-$half, $y1+$half , $x2-$half, $y2-$half);
			$w -= $width;
		}
		$width = $s->borderLeftWidth;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $s->borderLeftColor;
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($s->borderLeftStyle);
			$half = $width/2;
			$this->Line($x1+$half , $y1+$half, $x1+$half, $y2-$half );
			$x += $width;
			$w -= $width;
		}
		//x, y, w, h đã trừ border
		$this->Image($c->image->get('src'), $x, $y, $w, $h);
	}
	private function drawCell($x, $y, $w, $h, Cell $c)
	{
		$x1 = $x; $y1 = $y;
		$x2 = $x + $w; $y2 = $y + $h;
		$width = $c->getCellAttr('borderTopWidth');
		if ($width){
			$this->SetLineWidth($width);
			$lc = $c->getCellAttr('borderTopColor');
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($c->getCellAttr('borderTopStyle'));
			$half = $width/2;
			$this->Line($x1+$half , $y1+$half , $x2-$half, $y1+$half );
			$y += $width;
			$h -= $width;
		}
		$width = $c->getCellAttr('borderBottomWidth');
		if (!$width && $c->bottomCell)
			$width = $c->style->borderCollapse;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $c->getCellAttr('borderBottomColor');
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($c->getCellAttr('borderBottomStyle'));
			$half = $width/2;
			$this->Line($x1+$half , $y2-$half , $x2-$half, $y2-$half );
			$h -= $width;
		}
		$width = $c->getCellAttr('borderRightWidth');
		if (!$width && $c->rightCell)
			$width = $c->style->borderCollapse;
		if ($width){
			$this->SetLineWidth($width);
			$lc = $c->getCellAttr('borderRightColor');
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($c->getCellAttr('borderRightStyle'));
			$half = $width/2;
			$this->Line($x2-$half, $y1+$half , $x2-$half, $y2-$half);
			$w -= $width;
		}
		$width = $c->getCellAttr('borderLeftWidth');
		if ($width){
			$this->SetLineWidth($width);
			$lc = $c->getCellAttr('borderLeftColor');
			if ($lc)
				$this->SetDrawColor($lc->r, $lc->g, $lc->b);
			$this->setLineStyle($c->getCellAttr('borderLeftStyle'));
			$half = $width/2;
			$this->Line($x1+$half , $y1+$half, $x1+$half, $y2-$half );
			$x += $width;
			$w -= $width;
		}
		$fill = $c->getCellAttr('backgroundColor');
		if ($fill) {
			$this->SetFillColor($fill->r, $fill->g, $fill->b);
			$this->Rect($x, $y, $w, $h, 'F');
		}
		//x, y, w, h đã trừ border
		$this->drawContent($x, $y, $w, $h, $c);
	}
	private function drawContent($x, $y, $w, $h, Cell $c)
	{
		if ($c->groupsHeight==0) return;
		//Apply padding
		$pTop = $c->getCellAttr('paddingTop');
		$pBottom = $c->getCellAttr('paddingBottom');
		$pLeft = $c->getCellAttr('paddingLeft');
		$pRight = $c->getCellAttr('paddingRight');
		$x += $pLeft;
		$w -= $pLeft + $pRight;
		$y += $pTop;
		$h -= $pTop + $pBottom;
		//Apply align & valign
		$align = $c->style->textAlign ? $c->style->textAlign : 'left';
		switch($c->style->verticalAlign){
		case 'bottom':
			$y = $y + $h-$c->groupsHeight;
			break;
		case 'middle':
			$y = $y + ($h-$c->groupsHeight)/2;
			break;
		}
		$this->y = $y + $c->groups[0]->before;
		/** @var ChunkGroup $last */
		$last = null;
		/** @var ChunkGroup $group */
		foreach($c->groups as $group){
			$addSpace = 0;
			switch($align){
			case 'right':
				$space = $group->count?$group->chunks[$group->count-1]->space:0;
				$this->x = $x + $w - $group->width - $space;
				break;
			case 'center':
				$this->x = $x + ($w - $group->width)/2;
				break;
			case 'justify':
				$this->x = $x;
				if (!$group->hardBreak && $group->count>1) {
					$space = $group->chunks[$group->count-1]->space;
					$addSpace = ($w - $group->width-$space) / ($group->count - 1);
				}
				break;
			default:
				$this->x = $x;
			}
			$this->x += $group->indent;
			/** @var Chunk $chunk */
			foreach($group->chunks as $chunk){
				if ($chunk->image) {
					$this->drawImage($this->x, $this->y, $chunk);
				}else {
					if ($chunk->font)
						$this->setFontElement($chunk->font);
					$this->Cell($chunk->width, $chunk->height, $chunk->word, 0);
				}

				$this->x += $chunk->space + $addSpace;
			}
			$this->y += $group->height;
			if ($group->hardBreak)
				$this->y += max($last ? $last->after : 0,$group->before);
			$last = $group;
		}
	}
	function setFont($family, $style='', $size=0, $default=false)
	{
		parent::SetFont($family?$family:$this->defaultFontFamily, $style, $size);
		if ($default){
			if ($family) $this->defaultFontFamily = $family;
			if ($size) $this->defaultFontSize = $size;
			$this->defaultFontStyle = $style;
		}
	}

	/**
	 * @param HtmlElement $el Element which has font
	 * @param bool|false  $prepare SetFont for calcul, dont ouput to PDF
	 */
	protected function setFontElement(HtmlElement $el, $prepare=false){
		$size = $el->getAttr('fontSize');
		$size = $size ? $size : $this->defaultFontSize;
		$style = $el->getAttr('fontStyle');
		$style = $style ? $style : $this->defaultFontStyle;
		$family = $el->getAttr('fontFamily');
		$family = $family ? $family : $this->defaultFontFamily;
		$x = $el->getAttr('lineHeight');
		$this->LineHeight = $x ? $x : $this->defaultLineHeight;
		$x = $el->getAttr('spacingBefore');
		$this->SpacingBefore = $x ? $x : 0;
		$x = $el->getAttr('spacingAfter');
		$this->SpacingAfter = $x ? $x : 0;
		if ($prepare) {
			$old = $this->page;
			$this->page = 0;
			$this->setFont($family, $style, $size);
			$this->page = $old;
		}else {
			$this->setFont($family, $style, $size);
			$c = $el->getAttr('color');
			if ($c)
				$this->SetTextColor($c->r, $c->g, $c->b);
		}
	}
	private function calculHeightTable(Table $table)
	{
		$nc = $table->numCol;
		$nr = $table->numRow;
		for ($i = 0; $i < $nr; $i++) {
			$table->heightRows[$i] = 0;
			for ($j = 0; $j < $nc; $j++) {
				/** @var Cell $c */
				$c = $table->cells[$i][$j];
				if ($c instanceof Cell) {
					/** @var CssClass $s */
					$s = $c->style;
					$width = $table->getCellWidth($i,$j);
					$height = $this->calculHeightCell($c, $width[1]);

					if ($s->rowSpan == 1 && $table->heightRows[$i] < $height)
						$table->heightRows[$i] = $height;
				}
			}
		}
		$this->arrangeHeightSpan($table);
		$table->verifyRepeatRow($this->clientHeight);
	}
	private function arrangeWidthSpan(Table $table)
	{
		$wc = &$table->widthColumns;
		//Xac dinh su anh huong cua cac cell colspan len cac cot va nguoc lai
		for($i=0;$i<$table->numRow;$i++){
			foreach($table->spanCols[$i] as $j=>$c){
				/** @var CssClass $s */
				$s = $c->style;
				$lc = $j + $s->columnSpan;
				if ($lc > $table->numCol) {
					$lc = $table->numCol;
					$s->columnSpan = $lc - $j;
				}

				$totalMin = $totalMax = $totalSize = $totalPercent = 0;
				$lstFix = [];
				for ($k = $j; $k < $lc; $k++) {
					$totalMin += $wc[$k]->min;
					$totalMax += $wc[$k]->max;
					$totalSize += $wc[$k]->size;
					if ($wc[$k]->size)
						$lstFix[$k] = $wc[$k];
					$totalPercent += $wc[$k]->percent;
				}
				if ($s->minWidth < $totalMin)
					$s->minWidth = $totalMin;
				if ($s->maxWidth > $totalMax)
					$s->maxWidth = $totalMax;
				if ($s->widthPercent && $s->widthPercent<$totalPercent)
					$s->widthPercent = $totalPercent;
				if ($s->width) {
					if ($s->width < $totalMin)
						$s->width = $totalMin;
					if ($s->width < $totalSize)
						$s->width = $totalSize;
					elseif ($s->width > $totalSize && count($lstFix)) {
						$addWidth = ($s->width - $totalSize) / count($lstFix);
						/** @var Dimension $ac */
						foreach($lstFix as $k=>$ac)
							$ac->assign(null,null,max($ac->size,$ac->min)+$addWidth,null);
					}
				}
			}
		}
	}
	private function arrangeHeightSpan(Table $table)
	{
		$hr = &$table->heightRows;
		$nr = $table->numRow;
		for ($i = 0; $i < $nr; $i++) {
			foreach ($table->spanRows[$i] as $j=>$c) {
				$s = $c->style;
				$lr = $i + $s->rowSpan;
				if ($lr > $nr)
					$lr = $nr;
				$heightSpan = $heightAuto = 0;
				$listAuto = [];
				for ($k = $i; $k < $lr; $k++) {
					$heightSpan += $hr[$k];
					if ($s->height === null) {
						$listAuto[] = $k;
						$heightAuto += $hr[$k];
					}
				}
				if ($s->minHeight > $heightSpan) {
					if (!$heightSpan) {
						//Ko có yêu cầu về height: chia đều phần dư
						for ($k = $i; $k < $lr; $k++)
							$hr[$k] = $s->minHeight / $s->rowSpan;
					} elseif (count($listAuto)) {
						//1 số dòng ko yc về height: chia đều phần dư cho các dòng auto
						$hi = $s->minHeight - $heightAuto;
						foreach ($listAuto as $k)
							$hr[$k] += ($hr[$k] / $heightAuto) * $hi;
					} else {
						//Tất cả các dòng đều yc về height: chia đều phần dư cho tất cả
						$hi = $s->minHeight - $heightSpan;
						for ($k = $i; $k < $lr; $k++)
							$hr[$k] += ($hr[$k] / $heightSpan) * $hi;
					}
				}
			}
		}
		//Xử lý giới hạn chiều cao của các cell có rowspan
		for ($i = 0; $i < $nr; $i++) {
			foreach ($table->spanRows[$i] as $j=>$c) {
				/** @var CssClass $s */
				$s = $c->style;
				$height = $s->getHeight($table->style->getHeight($this->clientHeight));
				$lr = $i + $s->rowSpan;
				if ($lr > $nr)
					$lr = $nr;
				for ($k = $i; $k < $lr; $k++)
					$height -= $hr[$k];
				if ($height>0)
					$hr[$i] += $height;
			}
		}
	}

	function _putinfo()
	{
		$this->_out('/Producer ' . $this->_textstring('PDFTable ' .
						self::VERSION . ' based on FPDF ' . FPDF_VERSION));
		if (!empty($this->title))
			$this->_out('/Title ' . $this->_textstring($this->title));
		if (!empty($this->subject))
			$this->_out('/Subject ' . $this->_textstring($this->subject));
		if (!empty($this->author))
			$this->_out('/Author ' . $this->_textstring($this->author));
		if (!empty($this->keywords))
			$this->_out('/Keywords ' . $this->_textstring($this->keywords));
		if (!empty($this->creator))
			$this->_out('/Creator ' . $this->_textstring($this->creator));
		$this->_out('/CreationDate ' . $this->_textstring('D:' . @date('YmdHis')));
	}

	public function save($path)
	{
		$this->Output('F', $path);
	}

	public function download($name)
	{
		$this->Output('D', $name);
	}
	public function show($name='')
	{
		$this->Output('I', $name);
	}

	/**
	 * @param string $style {solid (default), dashed, dotted}
	 */
	public function setLineStyle($style = 'solid')
	{
		switch($style){
		case 'dotted':
			$blank = $this->LineWidth*2;
			$draw = $this->LineWidth*0.1;
			break;
		case 'dashed':
			$blank = $this->LineWidth*2;
			$draw = $this->LineWidth*2;
			break;
		default:
			$blank = $draw = 0;
		}
		$s = $draw ? sprintf('[%.3F %.3F] 0 d',$draw*$this->k,$blank*$this->k):'[] 0 d';
		if ($this->LineStyleF != $s) {
			$this->LineStyle = $style;
			$this->LineStyleF = $s;
			if ($this->page > 0)
				$this->_out($s);
		}
	}
}//HtmlToPdf