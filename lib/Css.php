<?php
namespace VanXuan\PdfTable;

class CssClass
{
	const ATTR2CSS = [
		'width' => 'width',
		'height' => 'height',
		'border' => 'border',
		'colspan' => 'columnSpan',
		'rowspan' => 'rowSpan',
		'bgcolor' => 'backgroundColor',
		'color' => 'color',
		'nowrap' => [
			'name'=>'whiteSpace',
			'value'=>'nowrap'
		],
		'nobreak' => [
			'name'=>'pageBreakInside',
			'value'=>'none'
		],
		'repeat' => [
			'name'=>'pageHeader',
			'value'=>1
		],
	];
	const COLORNAME = [
		'aliceblue' => '#f0f8ff',
		'antiquewhite' => '#faebd7',
		'aqua' => '#00ffff',
		'aquamarine' => '#7fffd4',
		'azure' => '#f0ffff',
		'beige' => '#f5f5dc',
		'bisque' => '#ffe4c4',
		'black' => '#000000',
		'blanchedalmond' => '#ffebcd',
		'blue' => '#0000ff',
		'blueviolet' => '#8a2be2',
		'brown' => '#a52a2a',
		'burlywood' => '#deb887',
		'cadetblue' => '#5f9ea0',
		'chartreuse' => '#7fff00',
		'chocolate' => '#d2691e',
		'coral' => '#ff7f50',
		'cornflowerblue' => '#6495ed',
		'cornsilk' => '#fff8dc',
		'crimson' => '#dc143c',
		'cyan' => '#00ffff',
		'darkblue' => '#00008b',
		'darkcyan' => '#008b8b',
		'darkgoldenrod' => '#b8860b',
		'darkgray' => '#a9a9a9',
		'darkgrey' => '#a9a9a9',
		'darkgreen' => '#006400',
		'darkkhaki' => '#bdb76b',
		'darkmagenta' => '#8b008b',
		'darkolivegreen' => '#556b2f',
		'darkorange' => '#ff8c00',
		'darkorchid' => '#9932cc',
		'darkred' => '#8b0000',
		'darksalmon' => '#e9967a',
		'darkseagreen' => '#8fbc8f',
		'darkslateblue' => '#483d8b',
		'darkslategray' => '#2f4f4f',
		'darkslategrey' => '#2f4f4f',
		'darkturquoise' => '#00ced1',
		'darkviolet' => '#9400d3',
		'deeppink' => '#ff1493',
		'deepskyblue' => '#00bfff',
		'dimgray' => '#696969',
		'dimgrey' => '#696969',
		'dodgerblue' => '#1e90ff',
		'firebrick' => '#b22222',
		'floralwhite' => '#fffaf0',
		'forestgreen' => '#228b22',
		'fuchsia' => '#ff00ff',
		'gainsboro' => '#dcdcdc',
		'ghostwhite' => '#f8f8ff',
		'gold' => '#ffd700',
		'goldenrod' => '#daa520',
		'gray' => '#808080',
		'grey' => '#808080',
		'green' => '#008000',
		'greenyellow' => '#adff2f',
		'honeydew' => '#f0fff0',
		'hotpink' => '#ff69b4',
		'indianred ' => '#cd5c5c',
		'indigo ' => '#4b0082',
		'ivory' => '#fffff0',
		'khaki' => '#f0e68c',
		'lavender' => '#e6e6fa',
		'lavenderblush' => '#fff0f5',
		'lawngreen' => '#7cfc00',
		'lemonchiffon' => '#fffacd',
		'lightblue' => '#add8e6',
		'lightcoral' => '#f08080',
		'lightcyan' => '#e0ffff',
		'lightgoldenrodyellow' => '#fafad2',
		'lightgray' => '#d3d3d3',
		'lightgrey' => '#d3d3d3',
		'lightgreen' => '#90ee90',
		'lightpink' => '#ffb6c1',
		'lightsalmon' => '#ffa07a',
		'lightseagreen' => '#20b2aa',
		'lightskyblue' => '#87cefa',
		'lightslategray' => '#778899',
		'lightslategrey' => '#778899',
		'lightsteelblue' => '#b0c4de',
		'lightyellow' => '#ffffe0',
		'lime' => '#00ff00',
		'limegreen' => '#32cd32',
		'linen' => '#faf0e6',
		'magenta' => '#ff00ff',
		'maroon' => '#800000',
		'mediumaquamarine' => '#66cdaa',
		'mediumblue' => '#0000cd',
		'mediumorchid' => '#ba55d3',
		'mediumpurple' => '#9370db',
		'mediumseagreen' => '#3cb371',
		'mediumslateblue' => '#7b68ee',
		'mediumspringgreen' => '#00fa9a',
		'mediumturquoise' => '#48d1cc',
		'mediumvioletred' => '#c71585',
		'midnightblue' => '#191970',
		'mintcream' => '#f5fffa',
		'mistyrose' => '#ffe4e1',
		'moccasin' => '#ffe4b5',
		'navajowhite' => '#ffdead',
		'navy' => '#000080',
		'oldlace' => '#fdf5e6',
		'olive' => '#808000',
		'olivedrab' => '#6b8e23',
		'orange' => '#ffa500',
		'orangered' => '#ff4500',
		'orchid' => '#da70d6',
		'palegoldenrod' => '#eee8aa',
		'palegreen' => '#98fb98',
		'paleturquoise' => '#afeeee',
		'palevioletred' => '#db7093',
		'papayawhip' => '#ffefd5',
		'peachpuff' => '#ffdab9',
		'peru' => '#cd853f',
		'pink' => '#ffc0cb',
		'plum' => '#dda0dd',
		'powderblue' => '#b0e0e6',
		'purple' => '#800080',
		'rebeccapurple' => '#663399',
		'red' => '#ff0000',
		'rosybrown' => '#bc8f8f',
		'royalblue' => '#4169e1',
		'saddlebrown' => '#8b4513',
		'salmon' => '#fa8072',
		'sandybrown' => '#f4a460',
		'seagreen' => '#2e8b57',
		'seashell' => '#fff5ee',
		'sienna' => '#a0522d',
		'silver' => '#c0c0c0',
		'skyblue' => '#87ceeb',
		'slateblue' => '#6a5acd',
		'slategray' => '#708090',
		'slategrey' => '#708090',
		'snow' => '#fffafa',
		'springgreen' => '#00ff7f',
		'steelblue' => '#4682b4',
		'tan' => '#d2b48c',
		'teal' => '#008080',
		'thistle' => '#d8bfd8',
		'tomato' => '#ff6347',
		'turquoise' => '#40e0d0',
		'violet' => '#ee82ee',
		'wheat' => '#f5deb3',
		'white' => '#ffffff',
		'whitesmoke' => '#f5f5f5',
		'yellow' => '#ffff00',
		'yellowgreen' => '#9acd32'
	];
	const FONTINORGE = [
			'small-caps', 'serif', 'sans-serif'
	];
	const FONTSIZE = [
			'xx-small' => '0.64em',
			'x-small' => '0.71em',
			'small' => '0.93em',
			'medium' => '1em',
			'large' => '1.29em',
			'x-large' => '1.71em',
			'xx-large' => '2.29em',
			'smaller' => '0.83em',
			'larger' => '1.2em'
	];
	//Font
	public $font;
	public $fontWeight;
	public $fontStyle;
	public $fontSize;
	public $fontFamily;
	public $lineHeight;
	public $spacingBefore;
	public $spacingAfter;
	public $textIndent;
	//Align
	public $textAlign;
	public $verticalAlign;
	//Box
	public $border;
	public $borderColor;
	public $borderWidth;
	public $borderStyle;
	public $borderTop;
	public $borderTopColor;
	public $borderTopWidth;
	public $borderTopStyle;
	public $borderRight;
	public $borderRightColor;
	public $borderRightWidth;
	public $borderRightStyle;
	public $borderBottom;
	public $borderBottomColor;
	public $borderBottomWidth;
	public $borderBottomStyle;
	public $borderLeft;
	public $borderLeftColor;
	public $borderLeftWidth;
	public $borderLeftStyle;
	public $padding;
	public $paddingTop;
	public $paddingRight;
	public $paddingBottom;
	public $paddingLeft;
	public $margin;
	public $marginTop;
	public $marginRight;
	public $marginBottom;
	public $marginLeft;
	public $width;
	public $height;
	public $minWidth;
	public $minHeight;
	public $maxWidth;
	public $maxHeight;
	//Color
	public $color;
	public $backgroundColor;
	//Break
	public $whiteSpace;
	public $pageBreakInside;
	public $pageBreakBefore;
	//Header
	public $display;//table-header-group for repeat row each page

	//Css extend
	public $widthPercent;
	public $heightPercent;
	//Span for Cell
	public $columnSpan;
	public $rowSpan;

	public $borderCollapse;

	public function merge(CssClass $second)
	{
		foreach($second as $name=>$value){
			if ($value !== null)
				$this->$name = $value;
		}
	}
	public function validFont()
	{
		if (preg_match_all('/"([^"]+)"|([^\s,]+)/', $this->font, $matches)) {
			$style = $this->fontStyle ? [$this->fontStyle] : [];
			$SIZE = self::FONTSIZE;
			foreach ($matches[2] as $i => $f) {
				if ($f === '')
					$f = $matches[1][$i];
				if ($f == 'bold' || $f == 'italic')
					$style[] = $f;
				elseif (preg_match_all('/([\d\.]+)(px|pt|em|%)/', $f, $fm)) {
					$f = $fm[0];
					$this->fontSize = $f[0];
					if (count($f) > 1)
						$this->lineHeight = $f[1];
				} elseif (in_array($f, self::FONTINORGE) || preg_match('/[\d\.]+[^\s]+/', $f))
					continue;
				elseif (isset($SIZE[$f]))
					$this->fontSize = $SIZE[$f];
				else
					$this->fontFamily = $f;
			}
			if (count($style))
				$this->fontStyle = join(' ', $style);
		}
		if (preg_match_all('/"([^"]+)"|([^,]+)/', $this->fontFamily, $matches)){
			foreach ($matches[2] as $i => $f) {
                $f = trim($f);
				if (in_array($f, self::FONTINORGE) || preg_match('/[\d\.]+[^\s]+/', $f))
					continue;
				$this->fontFamily = $f;
				break;
			}
		}
	}
	public function validBorder()
	{
		$color = self::COLORNAME;
		$style = ['solid','dashed','dotted'];
		$width = ['thin'=>'0.8px','medium'=>'2.4px','thick'=>'4px'];
		$border = preg_split('/\s/',strtolower($this->border),-1,PREG_SPLIT_NO_EMPTY);
		foreach($border as $b){
			if (isset($color[$b]) || preg_match('/#[0-9abcdef]+/',$b)){
				$this->borderColor = $b;
			}elseif(in_array($b,$style)){
				$this->borderStyle = $b;
			}elseif(isset($width[$b])){
				$this->borderWidth = $width[$b];
			}elseif(preg_match('/[\d\.]+px|0/',$b))
				$this->borderWidth = $b;
		}
		$color = preg_split('/\s/',$this->borderColor,-1,PREG_SPLIT_NO_EMPTY);
		switch(count($color)){
		case 1:
			$this->borderTopColor = $this->borderBottomColor =
			$this->borderLeftColor = $this->borderRightColor = $color[0];
			break;
		case 2:
			$this->borderTopColor = $this->borderBottomColor = $color[0];
			$this->borderLeftColor = $this->borderRightColor = $color[1];
			break;
		case 4:
			$this->borderTopColor = $color[0];
			$this->borderRightColor = $color[1];
			$this->borderBottomColor = $color[2];
			$this->borderLeftColor = $color[3];
			break;
		}
		$style = preg_split('/\s/',$this->borderStyle,-1,PREG_SPLIT_NO_EMPTY);
		switch(count($style)){
		case 1:
			$this->borderTopStyle = $this->borderBottomStyle =
			$this->borderLeftStyle = $this->borderRightStyle = $style[0];
			break;
		case 2:
			$this->borderTopStyle = $this->borderBottomStyle = $style[0];
			$this->borderLeftStyle = $this->borderRightStyle = $style[1];
			break;
		case 4:
			$this->borderTopStyle = $style[0];
			$this->borderRightStyle = $style[1];
			$this->borderBottomStyle = $style[2];
			$this->borderLeftStyle = $style[3];
			break;
		}
		$width = preg_split('/\s/',$this->borderWidth,-1,PREG_SPLIT_NO_EMPTY);
		switch(count($width)){
		case 1:
			$this->borderTopWidth = $this->borderBottomWidth =
			$this->borderLeftWidth = $this->borderRightWidth = $width[0];
			break;
		case 2:
			$this->borderTopWidth = $this->borderBottomWidth = $width[0];
			$this->borderLeftWidth = $this->borderRightWidth = $width[1];
			break;
		case 4:
			$this->borderTopWidth = $width[0];
			$this->borderRightWidth = $width[1];
			$this->borderBottomWidth = $width[2];
			$this->borderLeftWidth = $width[3];
			break;
		}
	}
	public function validColor()
	{
		$attrs = ['color','borderTopColor','borderBottomColor','borderLeftColor','borderRightColor','backgroundColor'];
		foreach($attrs as $color)
			if (is_string($this->$color))
				$this->$color = $this->color2rgb($this->$color);

	}
	private function color2rgb($c)
	{
		$names = self::COLORNAME;
		if ($c && isset($names[$c]))
			$c = $names[$c];
		if (preg_match_all('/^#[0-9abcdef]{3,6}$/', $c, $matches)) {
			switch(strlen($c)){
			case 4:
				return (object)[
					'r' => hexdec($c[1].$c[1]),
					'g' => hexdec($c[2].$c[2]),
					'b' => hexdec($c[3].$c[3])
				];
			case 7:
				return (object)[
					'r' => hexdec($c[1].$c[2]),
					'g' => hexdec($c[3].$c[4]),
					'b' => hexdec($c[5].$c[6])
				];
				break;
			}
		}
		return null;
	}

	public function getHeight($max=0)
	{
		$size = $this->heightPercent ? $this->heightPercent * $max : $this->height;
		if ($size < $this->minHeight)
			$size = $this->minHeight;
		if ($this->maxHeight && ($size === null || $size > $this->maxHeight))
			$size = $this->maxHeight;
		return $size === null ? $max : $size;
	}
}

class Css
{
	public $cls=[];

	public function parse($css)
	{
		$results = array();
		$css = preg_replace(['/\/\*.+\*\//','/\s+/'],['',' '],$css);

		preg_match_all('/[^\{\}]+\{[^\{\}]*\}/', $css, $matches);
		foreach($matches[0] as $i=>$cls){
			preg_match_all('/\s*([^\{]+)\s*\{\s*([^\}]*)\}/',$cls, $atts);
			if (empty($atts) or count($atts)<3)
				throw new CssException('Wrong selector in '.$cls);

			$selectors = $atts[1][0];
			preg_match_all('/\s*([^:;\s]+)\s*:\s*([^;]+)(;|$)/',$atts[2][0], $properties);
			if (empty($properties))
				throw new CssException('Wrong property in '.$cls);

			$cls = new CssClass();
			foreach($properties[1] as $pi=>$name){
				$name = $this->camelName($name);
				$cls->$name = trim(str_replace('!important','',$properties[2][$pi]));
			}
			$this->cls[$selectors] = $cls;
		}

		return $results;
	}

	public function apply(HtmlDocument $doc)
	{
		foreach($this->cls as $selector => $cls){
			$list = $doc->select($selector);
			foreach($list as $node){
				if ($node->style === null)
					$node->style = new CssClass();
				$node->style->merge($cls);
				$node->style->validFont();
				$node->style->validBorder();
				$node->style->validColor();
			}
		}
	}
	private function camelName($name)
	{
		$r = '';
		$len = strlen($name);
		for($i=0;$i<$len;$i++) {
			if ($name[$i] == '-'){
				$i++;
				$r .= strtoupper($name[$i]);
			}else
				$r .= $name[$i];
		}
		return $r;
	}
}
class Dimension
{
	public $min;
	public $max;
	public $size;
	public $percent;

	public function assign($min, $max, $size, $percent)
	{
		if ($min && $min > $this->min)
			$this->min = $min;
		if ($max && $max < $this->max)
			$this->max = $max;
		if ($size && $size > $this->size)
			$this->size = $size;
		if ($percent && $percent > $this->percent)
			$this->percent = $percent;
		if ($this->size && $this->size < $this->min)
			$this->size = $this->min;
		if ($this->size > $this->max && $this->max)
			$this->size = $this->max;
	}
	public function get($max=0)
	{
		$size = $this->percent ? $this->percent * $max : $this->size;
		if ($size < $this->min)
			$size = $this->min;
		if ($this->max && ($size === null || $size > $this->max))
			$size = $this->max;
		return $size === null ? 0 : $size;
	}
}