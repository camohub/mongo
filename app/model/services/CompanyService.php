<?php

namespace App\Model\Services;


use MongoDB;
use MongoDB\BSON\ObjectID;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use Tracy\Debugger;


class CompanyService extends BaseService
{

	/** @var MongoDB\Collection  */
	public $companies;

	/** @var  MongoDB\Collection */
	public $users;


	public function __construct( MongoDB\Client $mongodb )
	{
		parent::__construct( $mongodb );
		$this->companies = $this->mongodb->companies;
		$this->users = $this->mongodb->users;
	}


	public function findCompanyBy( array $by )
	{
		return $company = $this->companies->findOne( $by );
		//$users = $this->users->find( ['_id' => ['$in' => $this->getCompanyUsersIds( $company )]] )->toArray();

		//return new CompanyUsersEntity( $company, $users );
	}


	public function saveCompany( ObjectID $companyId = NULL, $companyName, $economicalResult, array $usersData )
	{
		try
		{
			$data = [
				'companyName' => $companyName,
				'companyEconomicalResult' => $economicalResult,
				'users' => []
			];

			foreach ( $usersData as $userData )
			{
				$user = [
					'name' => $userData->name,
					'shareBase' => $userData->shareBase,
					'share' => $userData->share,
				];
				$data['users'][] = $user;
			}

			$result = $companyId
				? $this->companies->updateOne( ['_id' => $companyId], ['$set' => $data], ["upsert" => TRUE])
				: $this->companies->insertOne( $data );

			return $result instanceof InsertOneResult ? $result->getInsertedId() : $companyId;
		}
		catch( \Exception $e )
		{
			Debugger::log( $e, Debugger::ERROR );
			throw $e;
		}
	}


	public function getCompanyUsersIds( BSONDocument $company )
	{
		$result = [];

		foreach( $company->coOwners as $coOwner ) $result[] = $coOwner->userId;

		return $result;
	}

}