<?php

namespace App\Model\Services;


use MongoDB;


class BaseService
{

	const DATABASE_NAME = 'company';

	/** @var MongoDB\Client */
	protected $mongodb;


	public function __construct( MongoDB\Client $mongodb )
	{
		$this->mongodb = $mongodb->selectDatabase( self::DATABASE_NAME );
	}


	public function getMongodb()
	{
		return $this->mongodb;
	}

}