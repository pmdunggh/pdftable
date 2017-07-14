<?php
namespace VanXuan\PdfTable;

define('FPDF_FONTPATH',__DIR__.'/../font');

class Utf8Pdf extends \FPDF{
	protected $unifontSubset;
	protected $diffs=[];
	protected $PageSizes=[];

	function GetStringWidth($s)
	{
		$s = (string)$s;
		$cw = &$this->CurrentFont['cw'];
		$w=0;
		if ($this->unifontSubset) {
			$unicode = $this->UTF8StringToArray($s);
			foreach($unicode as $char) {
				if (isset($cw[$char])) { $w += (ord($cw[2*$char])<<8) + ord($cw[2*$char+1]); }
				else if($char>0 && $char<128 && isset($cw[chr($char)])) { $w += $cw[chr($char)]; }
				else if(isset($this->CurrentFont['desc']['MissingWidth'])) { $w += $this->CurrentFont['desc']['MissingWidth']; }
				else if(isset($this->CurrentFont['MissingWidth'])) { $w += $this->CurrentFont['MissingWidth']; }
				else { $w += 500; }
			}
		}
		else {
			$l = strlen($s);
			for($i=0;$i<$l;$i++)
				$w += $cw[$s[$i]];
		}
		return $w*$this->FontSize/1000;
	}
	function AddFont($family, $style='', $file='', $uni=false)
	{
		// Add a TrueType, OpenType or Type1 font
		$family = strtolower($family);


		$style = strtoupper($style);
		if($style=='IB')
			$style='BI';
		if($file=='') {
			if ($uni) {
				$file = str_replace(' ','',$family).strtolower($style).'.ttf';
			}
			else {
				$file = str_replace(' ','',$family).strtolower($style).'.php';
			}
		}
		$fontkey = $family.$style;
		if(isset($this->fonts[$fontkey]))
			return;

		if ($uni) {
			if (defined("_SYSTEM_TTFONTS") && file_exists(_SYSTEM_TTFONTS.$file )) { $ttffilename = _SYSTEM_TTFONTS.$file ; }
			else { $ttffilename = $this->_getfontpath().'unifont/'.$file ; }
			$unifilename = $this->_getfontpath().'unifont/'.strtolower(substr($file ,0,(strpos($file ,'.'))));
			$name = $ttffile = '';
			$originalsize = 0;
			$up = $ut = 0;
			$ttfstat = stat($ttffilename);
			$desc = [];
			if (!isset($type) ||  !isset($name) || $originalsize != $ttfstat['size']) {
				$ttffile = $ttffilename;
				$ttf = new TTFontFile();
				$ttf->getMetrics($ttffile);
				$cw = $ttf->charWidths;
				$name = preg_replace('/[ ()]/','',$ttf->fullName);

				$desc= array('Ascent'=>round($ttf->ascent),
				             'Descent'=>round($ttf->descent),
				             'CapHeight'=>round($ttf->capHeight),
				             'Flags'=>$ttf->flags,
				             'FontBBox'=>'['.round($ttf->bbox[0])." ".round($ttf->bbox[1])." ".round($ttf->bbox[2])." ".round($ttf->bbox[3]).']',
				             'ItalicAngle'=>$ttf->italicAngle,
				             'StemV'=>round($ttf->stemV),
				             'MissingWidth'=>round($ttf->defaultWidth));
				$up = round($ttf->underlinePosition);
				$ut = round($ttf->underlineThickness);
				$originalsize = $ttfstat['size']+0;
				$type = 'TTF';
				// Generate metrics .php file
				$s='<?php'."\n";
				$s.='$name=\''.$name."';\n";
				$s.='$type=\''.$type."';\n";
				$s.='$desc='.var_export($desc,true).";\n";
				$s.='$up='.$up.";\n";
				$s.='$ut='.$ut.";\n";
				$s.='$ttffile=\''.$ttffile."';\n";
				$s.='$originalsize='.$originalsize.";\n";
				$s.='$fontkey=\''.$fontkey."';\n";
				$s.="?>";
				if (is_writable(dirname($this->_getfontpath().'unifont/'.'x'))) {
					$fh = fopen($unifilename.'.mtx.php',"w");
					fwrite($fh,$s,strlen($s));
					fclose($fh);
					$fh = fopen($unifilename.'.cw.dat',"wb");
					fwrite($fh,$cw,strlen($cw));
					fclose($fh);
					@unlink($unifilename.'.cw127.php');
				}
				unset($ttf);
			}
			else {
				$cw = @file_get_contents($unifilename.'.cw.dat');
			}
			$i = count($this->fonts)+1;
			if(!empty($this->AliasNbPages))
				$sbarr = range(0,57);
			else
				$sbarr = range(0,32);
			$this->fonts[$fontkey] = array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'ttffile'=>$ttffile, 'fontkey'=>$fontkey, 'subset'=>$sbarr, 'unifilename'=>$unifilename);

			$this->FontFiles[$fontkey]=array('length1'=>$originalsize, 'type'=>"TTF", 'ttffile'=>$ttffile);
			$this->FontFiles[$file]=array('type'=>"TTF");
			unset($cw);
		}
		else {
			$info = $this->_loadfont($file);
			$info['i'] = count($this->fonts)+1;
			if(!empty($info['diff']))
			{
				// Search existing encodings
				$n = array_search($info['diff'],$this->diffs);
				if(!$n)
				{
					$n = count($this->diffs)+1;
					$this->diffs[$n] = $info['diff'];
				}
				$info['diffn'] = $n;
			}
			if(!empty($info['file']))
			{
				// Embedded font
				if($info['type']=='TrueType')
					$this->FontFiles[$info['file']] = array('length1'=>$info['originalsize']);
				else
					$this->FontFiles[$info['file']] = array('length1'=>$info['size1'], 'length2'=>$info['size2']);
			}
			$this->fonts[$fontkey] = $info;
		}
	}
	function SetFont($family, $style='', $size=0)
	{
		// Select a font; size given in points
		if($family=='')
			$family = $this->FontFamily;
		else
			$family = strtolower($family);
		$style = strtoupper($style);
		if(strpos($style,'U')!==false)
		{
			$this->underline = true;
			$style = str_replace('U','',$style);
		}
		else
			$this->underline = false;
		if($style=='IB')
			$style = 'BI';
		if($size==0)
			$size = $this->FontSizePt;
		// Test if font is already selected
		if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
			return;
		// Test if font is already loaded
		$fontkey = $family.$style;
		if(!isset($this->fonts[$fontkey]))
		{
			// Test if one of the core fonts
			if($family=='arial')
				$family = 'helvetica';
			if(in_array($family,$this->CoreFonts))
			{
				if($family=='symbol' || $family=='zapfdingbats')
					$style = '';
				$fontkey = $family.$style;
				if(!isset($this->fonts[$fontkey]))
					$this->AddFont($family,$style);
			}
			else
				$this->Error('Undefined font: '.$family.' '.$style);
		}
		// Select it
		$this->FontFamily = $family;
		$this->FontStyle = $style;
		$this->FontSizePt = $size;
		$this->FontSize = $size/$this->k;
		$this->CurrentFont = &$this->fonts[$fontkey];
		$this->unifontSubset = $this->fonts[$fontkey]['type']=='TTF';
		if($this->page>0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
	}
	function Text($x, $y, $txt)
	{
		// Output a string
		if ($this->unifontSubset)
		{
			$txt2 = '('.$this->_escape($this->UTF8ToUTF16BE($txt, false)).')';
			foreach($this->UTF8StringToArray($txt) as $uni)
				$this->CurrentFont['subset'][$uni] = $uni;
		}
		else
			$txt2 = '('.$this->_escape($txt).')';
		$s = sprintf('BT %.2F %.2F Td %s Tj ET',$x*$this->k,($this->h-$y)*$this->k,$txt2);
		if($this->underline && $txt!='')
			$s .= ' '.$this->_dounderline($x,$y,$txt);
		if($this->ColorFlag)
			$s = 'q '.$this->TextColor.' '.$s.' Q';
		$this->_out($s);
	}
	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		// Output a cell
		$k = $this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			// Automatic page break
			$x = $this->x;
			$ws = $this->ws;
			if($ws>0)
			{
				$this->ws = 0;
				$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageSize);
			$this->x = $x;
			if($ws>0)
			{
				$this->ws = $ws;
				$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w = $this->w-$this->rMargin-$this->x;
		$s = '';
		if($fill || $border==1)
		{
			if($fill)
				$op = ($border==1) ? 'B' : 'f';
			else
				$op = 'S';
			$s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x = $this->x;
			$y = $this->y;
			if(strpos($border,'L')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'T')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			if(strpos($border,'R')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'B')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if($txt!=='')
		{
			if($align=='R')
				$dx = $w-$this->cMargin-$this->GetStringWidth($txt);
			elseif($align=='C')
				$dx = ($w-$this->GetStringWidth($txt))/2;
			else
				$dx = $this->cMargin;
			if($this->ColorFlag)
				$s .= 'q '.$this->TextColor.' ';

			// If multibyte, Tw has no effect - do word spacing using an adjustment before each space
			if ($this->ws && $this->unifontSubset) {
				foreach($this->UTF8StringToArray($txt) as $uni)
					$this->CurrentFont['subset'][$uni] = $uni;
				$space = $this->_escape($this->UTF8ToUTF16BE(' ', false));
				$s .= sprintf('BT 0 Tw %.2F %.2F Td [',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k);
				$t = explode(' ',$txt);
				$numt = count($t);
				for($i=0;$i<$numt;$i++) {
					$tx = $t[$i];
					$tx = '('.$this->_escape($this->UTF8ToUTF16BE($tx, false)).')';
					$s .= sprintf('%s ',$tx);
					if (($i+1)<$numt) {
						$adj = -($this->ws*$this->k)*1000/$this->FontSizePt;
						$s .= sprintf('%d(%s) ',$adj,$space);
					}
				}
				$s .= '] TJ';
				$s .= ' ET';
			}
			else {
				if ($this->unifontSubset)
				{
					$txt2 = '('.$this->_escape($this->UTF8ToUTF16BE($txt, false)).')';
					foreach($this->UTF8StringToArray($txt) as $uni)
						$this->CurrentFont['subset'][$uni] = $uni;
				}
				else
					$txt2='('.str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt))).')';
				$s .= sprintf('BT %.2F %.2F Td %s Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
			}
			if($this->underline)
				$s .= ' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
			if($this->ColorFlag)
				$s .= ' Q';
			if($link)
				$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
		}
		if($s)
			$this->_out($s);
		$this->lasth = $h;
		if($ln>0)
		{
			// Go to next line
			$this->y += $h;
			if($ln==1)
				$this->x = $this->lMargin;
		}
		else
			$this->x += $w;
	}

	function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
	{
		// Output text with automatic or explicit line breaks
		$cw = &$this->CurrentFont['cw'];
		if($w==0)
			$w = $this->w-$this->rMargin-$this->x;
		$wmax = ($w-2*$this->cMargin);
		$s = str_replace("\r",'',$txt);
		if ($this->unifontSubset) {
			$nb=mb_strlen($s, 'utf-8');
			while($nb>0 && mb_substr($s,$nb-1,1,'utf-8')=="\n")	$nb--;
		}
		else {
			$nb = strlen($s);
			if($nb>0 && $s[$nb-1]=="\n")
				$nb--;
		}
		$b = 0;
		$b2 = '';
		if($border)
		{
			if($border==1)
			{
				$border = 'LTRB';
				$b = 'LRT';
				$b2 = 'LR';
			}
			else
			{
				$b2 = '';
				if(strpos($border,'L')!==false)
					$b2 .= 'L';
				if(strpos($border,'R')!==false)
					$b2 .= 'R';
				$b = (strpos($border,'T')!==false) ? $b2.'T' : $b2;
			}
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = $ls = 0;
		$ns = 0;
		$nl = 1;
		while($i<$nb)
		{
			// Get next character
			if ($this->unifontSubset) {
				$c = mb_substr($s,$i,1,'UTF-8');
			}
			else {
				$c=$s[$i];
			}
			if($c=="\n")
			{
				// Explicit line break
				if($this->ws>0)
				{
					$this->ws = 0;
					$this->_out('0 Tw');
				}
				if ($this->unifontSubset) {
					$this->Cell($w,$h,mb_substr($s,$j,$i-$j,'UTF-8'),$b,2,$align,$fill);
				}
				else {
					$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				}
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if($border && $nl==2)
					$b = $b2;
				continue;
			}
			if($c==' ')
			{
				$sep = $i;
				$ls = $l;
				$ns++;
			}

			if ($this->unifontSubset) { $l += $this->GetStringWidth($c); }
			else { $l += $cw[$c]*$this->FontSize/1000; }

			if($l>$wmax)
			{
				// Automatic line break
				if($sep==-1)
				{
					if($i==$j)
						$i++;
					if($this->ws>0)
					{
						$this->ws = 0;
						$this->_out('0 Tw');
					}
					if ($this->unifontSubset) {
						$this->Cell($w,$h,mb_substr($s,$j,$i-$j,'UTF-8'),$b,2,$align,$fill);
					}
					else {
						$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
					}
				}
				else
				{
					if($align=='J')
					{
						$this->ws = ($ns>1) ? ($wmax-$ls)/($ns-1) : 0;
						$this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
					}
					if ($this->unifontSubset) {
						$this->Cell($w,$h,mb_substr($s,$j,$sep-$j,'UTF-8'),$b,2,$align,$fill);
					}
					else {
						$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
					}
					$i = $sep+1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if($border && $nl==2)
					$b = $b2;
			}
			else
				$i++;
		}
		// Last chunk
		if($this->ws>0)
		{
			$this->ws = 0;
			$this->_out('0 Tw');
		}
		if($border && strpos($border,'B')!==false)
			$b .= 'B';
		if ($this->unifontSubset) {
			$this->Cell($w,$h,mb_substr($s,$j,$i-$j,'UTF-8'),$b,2,$align,$fill);
		}
		else {
			$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
		}
		$this->x = $this->lMargin;
	}

	function Write($h, $txt, $link='')
	{
		// Output text in flowing mode
		$cw = &$this->CurrentFont['cw'];
		$w = $this->w-$this->rMargin-$this->x;

		$wmax = ($w-2*$this->cMargin);
		$s = str_replace("\r",'',$txt);
		if ($this->unifontSubset) {
			$nb = mb_strlen($s, 'UTF-8');
			if($nb==1 && $s==" ") {
				$this->x += $this->GetStringWidth($s);
				return;
			}
		}
		else {
			$nb = strlen($s);
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while($i<$nb)
		{
			// Get next character
			if ($this->unifontSubset) {
				$c = mb_substr($s,$i,1,'UTF-8');
			}
			else {
				$c = $s[$i];
			}
			if($c=="\n")
			{
				// Explicit line break
				if ($this->unifontSubset) {
					$this->Cell($w,$h,mb_substr($s,$j,$i-$j,'UTF-8'),0,2,'',0,$link);
				}
				else {
					$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
				}
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				if($nl==1)
				{
					$this->x = $this->lMargin;
					$w = $this->w-$this->rMargin-$this->x;
					$wmax = ($w-2*$this->cMargin);
				}
				$nl++;
				continue;
			}
			if($c==' ')
				$sep = $i;

			if ($this->unifontSubset) { $l += $this->GetStringWidth($c); }
			else { $l += $cw[$c]*$this->FontSize/1000; }

			if($l>$wmax)
			{
				// Automatic line break
				if($sep==-1)
				{
					if($this->x>$this->lMargin)
					{
						// Move to next line
						$this->x = $this->lMargin;
						$this->y += $h;
						$w = $this->w-$this->rMargin-$this->x;
						$wmax = ($w-2*$this->cMargin);
						$i++;
						$nl++;
						continue;
					}
					if($i==$j)
						$i++;
					if ($this->unifontSubset) {
						$this->Cell($w,$h,mb_substr($s,$j,$i-$j,'UTF-8'),0,2,'',0,$link);
					}
					else {
						$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
					}
				}
				else
				{
					if ($this->unifontSubset) {
						$this->Cell($w,$h,mb_substr($s,$j,$sep-$j,'UTF-8'),0,2,'',0,$link);
					}
					else {
						$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',0,$link);
					}
					$i = $sep+1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				if($nl==1)
				{
					$this->x = $this->lMargin;
					$w = $this->w-$this->rMargin-$this->x;
					$wmax = ($w-2*$this->cMargin);
				}
				$nl++;
			}
			else
				$i++;
		}
		// Last chunk
		if($i!=$j) {
			if ($this->unifontSubset) {
				$this->Cell($l,$h,mb_substr($s,$j,$i-$j,'UTF-8'),0,0,'',0,$link);
			}
			else {
				$this->Cell($l,$h,substr($s,$j),0,0,'',0,$link);
			}
		}
	}
	protected function _dochecks()
	{
		// Check availability of %F
		if(sprintf('%.1F',1.0)!='1.0')
			$this->Error('This version of PHP is not supported');
		// Check availability of mbstring
		if(!function_exists('mb_strlen'))
			$this->Error('mbstring extension is not available');
		// Check mbstring overloading
		if(ini_get('mbstring.func_overload') & 2)
			$this->Error('mbstring overloading must be disabled');
	}

	protected function _getfontpath()
	{
		return $this->fontpath;
	}
	protected function _putpages()
	{
		$nb = $this->page;
		if(!empty($this->AliasNbPages))
		{
			// Replace number of pages in fonts using subsets
			$alias = $this->UTF8ToUTF16BE($this->AliasNbPages, false);
			$r = $this->UTF8ToUTF16BE("$nb", false);
			for($n=1;$n<=$nb;$n++)
				$this->pages[$n] = str_replace($alias,$r,$this->pages[$n]);
			// Now repeat for no pages in non-subset fonts
			for($n=1;$n<=$nb;$n++)
				$this->pages[$n] = str_replace($this->AliasNbPages,$nb,$this->pages[$n]);
		}
		if($this->DefOrientation=='P')
		{
			$wPt = $this->DefPageSize[0]*$this->k;
			$hPt = $this->DefPageSize[1]*$this->k;
		}
		else
		{
			$wPt = $this->DefPageSize[1]*$this->k;
			$hPt = $this->DefPageSize[0]*$this->k;
		}
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		for($n=1;$n<=$nb;$n++)
		{
			// Page
			$this->_newobj();
			$this->_out('<</Type /Page');
			$this->_out('/Parent 1 0 R');
			if(isset($this->PageSizes[$n]))
				$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageSizes[$n][0],$this->PageSizes[$n][1]));
			$this->_out('/Resources 2 0 R');
			if(isset($this->PageLinks[$n]))
			{
				// Links
				$annots = '/Annots [';
				foreach($this->PageLinks[$n] as $pl)
				{
					$rect = sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
					$annots .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
					if(is_string($pl[4]))
						$annots .= '/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
					else
					{
						$l = $this->links[$pl[4]];
						$h = isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $hPt;
						$annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',1+2*$l[0],$h-$l[1]*$this->k);
					}
				}
				$this->_out($annots.']');
			}
			if($this->PDFVersion>'1.3')
				$this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
			$this->_out('/Contents '.($this->n+1).' 0 R>>');
			$this->_out('endobj');
			// Page content
			$p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
			$this->_newobj();
			$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
			$this->_putstream($p);
			$this->_out('endobj');
		}
		// Pages root
		$this->offsets[1] = strlen($this->buffer);
		$this->_out('1 0 obj');
		$this->_out('<</Type /Pages');
		$kids = '/Kids [';
		for($i=0;$i<$nb;$i++)
			$kids .= (3+2*$i).' 0 R ';
		$this->_out($kids.']');
		$this->_out('/Count '.$nb);
		$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$wPt,$hPt));
		$this->_out('>>');
		$this->_out('endobj');
	}
	protected function _putfonts()
	{
		$nf=$this->n;
		foreach($this->diffs as $diff)
		{
			// Encodings
			$this->_newobj();
			$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$diff.']>>');
			$this->_out('endobj');
		}
		foreach($this->FontFiles as $file=>$info)
		{
			if (!isset($info['type']) || $info['type']!='TTF') {
				// Font file embedding
				$this->_newobj();
				$this->FontFiles[$file]['n']=$this->n;
				$font='';
				$f=fopen($this->_getfontpath().$file,'rb',1);
				if(!$f)
					$this->Error('Font file not found');
				while(!feof($f))
					$font.=fread($f,8192);
				fclose($f);
				$compressed=(substr($file,-2)=='.z');
				if(!$compressed && isset($info['length2']))
				{
					$header=(ord($font[0])==128);
					if($header)
					{
						// Strip first binary header
						$font=substr($font,6);
					}
					if($header && ord($font[$info['length1']])==128)
					{
						// Strip second binary header
						$font=substr($font,0,$info['length1']).substr($font,$info['length1']+6);
					}
				}
				$this->_out('<</Length '.strlen($font));
				if($compressed)
					$this->_out('/Filter /FlateDecode');
				$this->_out('/Length1 '.$info['length1']);
				if(isset($info['length2']))
					$this->_out('/Length2 '.$info['length2'].' /Length3 0');
				$this->_out('>>');
				$this->_putstream($font);
				$this->_out('endobj');
			}
		}
		foreach($this->fonts as $k=>$font)
		{
			// Font objects
			//$this->fonts[$k]['n']=$this->n+1;
			$type = $font['type'];
			$name = $font['name'];
			if($type=='Core')
			{
				// Standard font
				$this->fonts[$k]['n']=$this->n+1;
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /'.$name);
				$this->_out('/Subtype /Type1');
				if($name!='Symbol' && $name!='ZapfDingbats')
					$this->_out('/Encoding /WinAnsiEncoding');
				$this->_out('>>');
				$this->_out('endobj');
			}
			elseif($type=='Type1' || $type=='TrueType')
			{
				// Additional Type1 or TrueType font
				$this->fonts[$k]['n']=$this->n+1;
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /'.$name);
				$this->_out('/Subtype /'.$type);
				$this->_out('/FirstChar 32 /LastChar 255');
				$this->_out('/Widths '.($this->n+1).' 0 R');
				$this->_out('/FontDescriptor '.($this->n+2).' 0 R');
				if($font['enc'])
				{
					if(isset($font['diff']))
						$this->_out('/Encoding '.($nf+$font['diff']).' 0 R');
					else
						$this->_out('/Encoding /WinAnsiEncoding');
				}
				$this->_out('>>');
				$this->_out('endobj');
				// Widths
				$this->_newobj();
				$cw=&$font['cw'];
				$s='[';
				for($i=32;$i<=255;$i++)
					$s.=$cw[chr($i)].' ';
				$this->_out($s.']');
				$this->_out('endobj');
				// Descriptor
				$this->_newobj();
				$s='<</Type /FontDescriptor /FontName /'.$name;
				foreach($font['desc'] as $kdesc=>$v)
					$s.=' /'.$kdesc.' '.$v;
				$file=$font['file'];
				if($file)
					$s.=' /FontFile'.($type=='Type1' ? '' : '2').' '.$this->FontFiles[$file]['n'].' 0 R';
				$this->_out($s.'>>');
				$this->_out('endobj');
			}
			// TrueType embedded SUBSETS or FULL
			else if ($type=='TTF') {
				$this->fonts[$k]['n']=$this->n+1;
				$ttf = new TTFontFile();
				$fontname = 'MPDFAA'.'+'.$font['name'];
				$subset = $font['subset'];
				unset($subset[0]);
				$ttfontstream = $ttf->makeSubset($font['ttffile'], $subset);
				$ttfontsize = strlen($ttfontstream);
				$fontstream = gzcompress($ttfontstream);
				$codeToGlyph = $ttf->codeToGlyph;
				unset($codeToGlyph[0]);

				// Type0 Font
				// A composite font - a font composed of other fonts, organized hierarchically
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/Subtype /Type0');
				$this->_out('/BaseFont /'.$fontname.'');
				$this->_out('/Encoding /Identity-H');
				$this->_out('/DescendantFonts ['.($this->n + 1).' 0 R]');
				$this->_out('/ToUnicode '.($this->n + 2).' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// CIDFontType2
				// A CIDFont whose glyph descriptions are based on TrueType font technology
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/Subtype /CIDFontType2');
				$this->_out('/BaseFont /'.$fontname.'');
				$this->_out('/CIDSystemInfo '.($this->n + 2).' 0 R');
				$this->_out('/FontDescriptor '.($this->n + 3).' 0 R');
				if (isset($font['desc']['MissingWidth'])){
					$this->_out('/DW '.$font['desc']['MissingWidth'].'');
				}

				$this->_putTTfontwidths($font, $ttf->maxUni);

				$this->_out('/CIDToGIDMap '.($this->n + 4).' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// ToUnicode
				$this->_newobj();
				$toUni = "/CIDInit /ProcSet findresource begin\n";
				$toUni .= "12 dict begin\n";
				$toUni .= "begincmap\n";
				$toUni .= "/CIDSystemInfo\n";
				$toUni .= "<</Registry (Adobe)\n";
				$toUni .= "/Ordering (UCS)\n";
				$toUni .= "/Supplement 0\n";
				$toUni .= ">> def\n";
				$toUni .= "/CMapName /Adobe-Identity-UCS def\n";
				$toUni .= "/CMapType 2 def\n";
				$toUni .= "1 begincodespacerange\n";
				$toUni .= "<0000> <FFFF>\n";
				$toUni .= "endcodespacerange\n";
				$toUni .= "1 beginbfrange\n";
				$toUni .= "<0000> <FFFF> <0000>\n";
				$toUni .= "endbfrange\n";
				$toUni .= "endcmap\n";
				$toUni .= "CMapName currentdict /CMap defineresource pop\n";
				$toUni .= "end\n";
				$toUni .= "end";
				$this->_out('<</Length '.(strlen($toUni)).'>>');
				$this->_putstream($toUni);
				$this->_out('endobj');

				// CIDSystemInfo dictionary
				$this->_newobj();
				$this->_out('<</Registry (Adobe)');
				$this->_out('/Ordering (UCS)');
				$this->_out('/Supplement 0');
				$this->_out('>>');
				$this->_out('endobj');

				// Font descriptor
				$this->_newobj();
				$this->_out('<</Type /FontDescriptor');
				$this->_out('/FontName /'.$fontname);
				foreach($font['desc'] as $kd=>$v) {
					if ($kd == 'Flags') { $v = $v | 4; $v = $v & ~32; }	// SYMBOLIC font flag
					$this->_out(' /'.$kd.' '.$v);
				}
				$this->_out('/FontFile2 '.($this->n + 2).' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// Embed CIDToGIDMap
				// A specification of the mapping from CIDs to glyph indices
				//$cidtogidmap = '';
				$cidtogidmap = str_pad('', 256*256*2, "\x00");
				foreach($codeToGlyph as $cc=>$glyph) {
					$cidtogidmap[$cc*2] = chr($glyph >> 8);
					$cidtogidmap[$cc*2 + 1] = chr($glyph & 0xFF);
				}
				$cidtogidmap = gzcompress($cidtogidmap);
				$this->_newobj();
				$this->_out('<</Length '.strlen($cidtogidmap).'');
				$this->_out('/Filter /FlateDecode');
				$this->_out('>>');
				$this->_putstream($cidtogidmap);
				$this->_out('endobj');

				//Font file
				$this->_newobj();
				$this->_out('<</Length '.strlen($fontstream));
				$this->_out('/Filter /FlateDecode');
				$this->_out('/Length1 '.$ttfontsize);
				$this->_out('>>');
				$this->_putstream($fontstream);
				$this->_out('endobj');
				unset($ttf);
			}
			else
			{
				// Allow for additional types
				$this->fonts[$k]['n'] = $this->n+1;
				$mtd='_put'.strtolower($type);
				if(!method_exists($this,$mtd))
					$this->Error('Unsupported font type: '.$type);
				$this->$mtd($font);
			}
		}
	}
	protected function _putTTfontwidths(&$font, $maxUni) {
		$rangeid = 0;
		$range = array();
		$prevcid = -2;
		$prevwidth = -1;
		$interval = false;
		$startcid = 1;
		$cwlen = $maxUni + 1;

		// for each character
		for ($cid=$startcid; $cid<$cwlen; $cid++) {
			if ($cid==128 && (!file_exists($font['unifilename'].'.cw127.php'))) {
				if (is_writable(dirname($this->_getfontpath().'unifont/x'))) {
					$fh = fopen($font['unifilename'].'.cw127.php',"wb");
					$cw127='<?php'."\n";
					$cw127.='$rangeid='.$rangeid.";\n";
					$cw127.='$prevcid='.$prevcid.";\n";
					$cw127.='$prevwidth='.$prevwidth.";\n";
					if ($interval) { $cw127.='$interval=true'.";\n"; }
					else { $cw127.='$interval=false'.";\n"; }
					$cw127.='$range='.var_export($range,true).";\n";
					$cw127.="?>";
					fwrite($fh,$cw127,strlen($cw127));
					fclose($fh);
				}
			}
			if ($font['cw'][$cid*2] == "\00" && $font['cw'][$cid*2+1] == "\00") { continue; }
			$width = (ord($font['cw'][$cid*2]) << 8) + ord($font['cw'][$cid*2+1]);
			if ($width == 65535) { $width = 0; }
			if ($cid > 255 && (!isset($font['subset'][$cid]) || !$font['subset'][$cid])) { continue; }
			if (!isset($font['dw']) || (isset($font['dw']) && $width != $font['dw'])) {
				if ($cid == ($prevcid + 1)) {
					if ($width == $prevwidth) {
						if ($width == $range[$rangeid][0]) {
							$range[$rangeid][] = $width;
						}
						else {
							array_pop($range[$rangeid]);
							// new range
							$rangeid = $prevcid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $prevwidth;
							$range[$rangeid][] = $width;
						}
						$interval = true;
						$range[$rangeid]['interval'] = true;
					} else {
						if ($interval) {
							// new range
							$rangeid = $cid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $width;
						}
						else { $range[$rangeid][] = $width; }
						$interval = false;
					}
				} else {
					$rangeid = $cid;
					$range[$rangeid] = array();
					$range[$rangeid][] = $width;
					$interval = false;
				}
				$prevcid = $cid;
				$prevwidth = $width;
			}
		}
		$prevk = -1;
		$nextk = -1;
		$prevint = false;
		foreach ($range as $k => $ws) {
			$cws = count($ws);
			if (($k == $nextk) AND (!$prevint) AND ((!isset($ws['interval'])) OR ($cws < 4))) {
				if (isset($range[$k]['interval'])) { unset($range[$k]['interval']); }
				$range[$prevk] = array_merge($range[$prevk], $range[$k]);
				unset($range[$k]);
			}
			else { $prevk = $k; }
			$nextk = $k + $cws;
			if (isset($ws['interval'])) {
				if ($cws > 3) { $prevint = true; }
				else { $prevint = false; }
				unset($range[$k]['interval']);
				--$nextk;
			}
			else { $prevint = false; }
		}
		$w = '';
		foreach ($range as $k => $ws) {
			if (count(array_count_values($ws)) == 1) { $w .= ' '.$k.' '.($k + count($ws) - 1).' '.$ws[0]; }
			else { $w .= ' '.$k.' [ '.implode(' ', $ws).' ]' . "\n"; }
		}
		$this->_out('/W ['.$w.' ]');
	}
	protected function _UTF8toUTF16($s)
	{
		// Convert UTF-8 to UTF-16BE with BOM
		$res = "\xFE\xFF";
		$nb = strlen($s);
		$i = 0;
		while($i<$nb)
		{
			$c1 = ord($s[$i++]);
			if($c1>=224)
			{
				// 3-byte character
				$c2 = ord($s[$i++]);
				$c3 = ord($s[$i++]);
				$res .= chr((($c1 & 0x0F)<<4) + (($c2 & 0x3C)>>2));
				$res .= chr((($c2 & 0x03)<<6) + ($c3 & 0x3F));
			}
			elseif($c1>=192)
			{
				// 2-byte character
				$c2 = ord($s[$i++]);
				$res .= chr(($c1 & 0x1C)>>2);
				$res .= chr((($c1 & 0x03)<<6) + ($c2 & 0x3F));
			}
			else
			{
				// Single-byte character
				$res .= "\0".chr($c1);
			}
		}
		return $res;
	}
	// Converts UTF-8 strings to UTF16-BE.
	function UTF8ToUTF16BE($str, $setbom=true) {
		$outstr = "";
		if ($setbom) {
			$outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
		}
		$outstr .= mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
		return $outstr;
	}

	// Converts UTF-8 strings to codepoints array
	function UTF8StringToArray($str) {
		$out = array();
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			$uni = -1;
			$h = ord($str[$i]);
			if ( $h <= 0x7F )
				$uni = $h;
			elseif ( $h >= 0xC2 ) {
				if ( ($h <= 0xDF) && ($i < $len -1) )
					$uni = ($h & 0x1F) << 6 | (ord($str[++$i]) & 0x3F);
				elseif ( ($h <= 0xEF) && ($i < $len -2) )
					$uni = ($h & 0x0F) << 12 | (ord($str[++$i]) & 0x3F) << 6
							| (ord($str[++$i]) & 0x3F);
				elseif ( ($h <= 0xF4) && ($i < $len -3) )
					$uni = ($h & 0x0F) << 18 | (ord($str[++$i]) & 0x3F) << 12
							| (ord($str[++$i]) & 0x3F) << 6
							| (ord($str[++$i]) & 0x3F);
			}
			if ($uni >= 0) {
				$out[] = $uni;
			}
		}
		return $out;
	}
	function Error($msg)
	{
		throw new PdfException($msg);
	}

}//end of class
