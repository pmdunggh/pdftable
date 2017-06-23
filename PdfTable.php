<?php
namespace VanXuan\PdfTable;
class PdfTable extends HtmlToPdf{
	protected $documentId;
	protected $documentDate;

	public function __construct($orientation='P', $unit='pt', $format='a4')
	{
		parent::__construct($orientation, $unit, $format);
		$this->AddFont('Times New Roman','',  'times.ttf',true);
		$this->AddFont('Times New Roman','B', 'timesbd.ttf',true);
		$this->AddFont('Times New Roman','I', 'timesi.ttf',true);
		$this->AddFont('Times New Roman','BI','timesbi.ttf',true);

		$this->setPageMargins(30,20);
		$this->SetAuthor('Pham Minh Dung');
		$this->AliasNbPages();
	}

	public function Footer()
	{
		$this->setFont('Times New Roman','','12');
		$height = 12*self::LINEHEIGHT;
		$y = $this->clientBottom;
		$this->Text($x=$this->clientLeft, $y + $height, 'Trang '.$this->PageNo().'/{nb}');
		//$this->Line($x, $y, $this->clientRight, $y);
		if ($this->documentId) {
			$width = $this->GetStringWidth($this->documentId);
			$this->Text($this->clientRight - $width, $y + $height, $this->documentId);
		}
		//$txt = ' Trang: '.$this->PageNo().'/{nb}';
		//$this->x = $this->clientLeft;
		//$this->y = $this->clientBottom+10;
		//$this->Cell($this->clientWidth,$height, $txt, 0, 0, 'C');
	}
	public function render($html, $genId=false)
	{
		$this->documentDate = date('d/m/Y H:i:s');
		$this->documentId = $genId ? $this->documentDate.' '.md5($html.$this->documentDate) : 0;
		$doc = $this->parseHtml($html);
		$this->renderTable($doc);
	}
}//end of class