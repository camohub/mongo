<?php

namespace App\Model\Services;


use Tracy\Debugger;


class CompanyPdfService extends BasePdfService
{

	protected $format = 'A4-L';
	protected $default_font_size = 10;
	protected $margin_top = 20;
	protected $margin_bottom = 20;
	protected $margin_left = 10;
	protected $margin_right = 10;


	public function export( $templateParams = [], $exportAs = self::EXPORT_AS_DEFAULT )
	{
		$this->prepareTemplate( $templateParams );
		$this->generatePdf( 'zhodnotenie-zisku-spolocnosti.pdf', $exportAs );
	}


	protected function prepareTemplate( $params = [] )
	{
		$this->template->setFile( APP_DIR . '/components/CompanyUsersForm/Pdf.latte' );

		foreach( $params as $key => $val )
		{
			$this->template->$key = $val;
		}
	}
}