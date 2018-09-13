<?php

namespace App\Model\Services;


use PHPExcel;
use PHPExcel_Writer_Excel2007;


class CompanyExcelExportService
{

	/** @var array  */
	protected $style1 = [
		'font'  => ['bold'  => true],
		'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'd1e8ff']]
	];

	/** @var array  */
	protected $style2 = [
		'font'  => ['bold'  => true],
		'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'e4d1ff']]
	];


	public function __construct()
	{

	}


	public function export( $companyName, array $personalProfitsArray, array $coinsArray )
	{
		$document = new PHPExcel();
		$document->setActiveSheetIndex(0);

		$list = $document->getActiveSheet();

		$row = 0;

		$row++;  // This also makes empty line between different surveyDefs.
		$column = 0;
		$list->setCellValueByColumnAndRow( $column, $row, 'Meno', TRUE );
		$list->setCellValueByColumnAndRow( ++$column, $row, 'Zisk' );

		foreach( $coinsArray as $key => $value )
		{
			$list->setCellValueByColumnAndRow( ++$column, $row, strtr( $key, ['e' => '€', 'c' => '¢'] ) );
		}

		$list->getStyleByColumnAndRow( 0, $row, $column, $row )->applyFromArray( $this->style1 );

		$row++;

		foreach( $personalProfitsArray as $personalProfit )
		{
			$column = 0;
			$list->setCellValueByColumnAndRow( $column, $row, $personalProfit['user']->name );
			$list->setCellValueByColumnAndRow( ++$column, $row, $personalProfit['personalProfit'] );

			foreach ( $personalProfit['coinsCount'] as $coinCount )
			{
				$list->setCellValueByColumnAndRow( ++$column, $row, $coinCount );
			}

			$row++;
		}

		foreach(range("A", $document->getActiveSheet()->getHighestDataColumn()) as $col)
		{
			$document->getActiveSheet()->getColumnDimension($col)->setAutoSize( TRUE );
		}

		if( ! is_dir( TEMP_DIR . "/excelfiles/" ) )
		{
			mkdir( TEMP_DIR . "/excelfiles/" );
		}

		//$excelWriter = PHPExcel_IOFactory::createWriter( $document, 'Excel2007' );
		$excelWriter = new PHPExcel_Writer_Excel2007( $document );
		$fileName = TEMP_DIR . "/excelfiles/temp" . $companyName . ".xlsx";
		$excelWriter->save( $fileName );

		return $fileName;
	}

}