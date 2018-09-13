<?php

namespace App\Model\Services;


use Mpdf\Mpdf;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;


abstract class BasePdfService
{

	const EXPORT_AS_DEFAULT = '';
	const EXPORT_AS_MPDF = NULL;
	const EXPORT_AS_DOWNLOAD = 'D';
	const EXPORT_AS_SHOW = 'I';
	const EXPORT_AS_FILE = 'F';
	const EXPORT_AS_STRING = 'S';

	const VALIDATION_PARAMETER_INTEGER = "int";
	const VALIDATION_PARAMETER_BOOLEAN = "boolean";
	const VALIDATION_PARAMETER_STRING = "string";
	const VALIDATION_PARAMETER_ARRAY = "array";

	/** PDF settings */
	protected $format = 'A4';
	protected $default_font_size = 14;
	protected $default_font = 'verdana';
	protected $margin_top = 20;
	protected $margin_bottom = 20;
	protected $margin_left = 20;
	protected $margin_right = 20;
	protected $tempDir = TEMP_DIR . '/pdf';

	/** @var ITemplateFactory */
	private $templateFactory;

	/** @var Template */
	protected $template;

	protected $layout = '../@layout.latte';

	protected $validateParameters = TRUE;


	public function __construct( ITemplateFactory $templateFactory )
	{
		$this->templateFactory = $templateFactory;
		$this->template = $this->templateFactory->createTemplate();
	}


	public function generatePdf( $name, $exportAs = '' )
	{
		$mpdf = $this->createMpdf();
		$mpdf->writeHTML( $this->template->__toString() );
		return $mpdf->Output( $name, $exportAs );
	}


	protected function createMpdf()
	{
		$mpdf = new Mpdf([
			'mode' => 'UTF-8',
			'format' => $this->format,
			'default_font_size' => $this->default_font_size,
			'default_font' => $this->default_font,
			'margin_top' => $this->margin_top,
			'margin_bottom' => $this->margin_bottom,
			'margin_left' => $this->margin_left,
			'margin_right' => $this->margin_right,
		]);

		return $mpdf;
	}
}