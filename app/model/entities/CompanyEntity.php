<?php

namespace App\Model\Services;


use MongoDB;
use MongoDB\Model\BSONDocument;
use Tracy\Debugger;


class CompanyUsersEntity
{

	/** @var BSONDocument  */
	public $company;

	/** @var array */
	public $users;


	public function __construct( BSONDocument $company, array $users )
	{
		$this->company = $company;

		foreach ( $this->company->coOwners as $coOwner )
		{
			// Set indexes on users array as user->_id.
			$this->users[(string)$coOwner->userId] = new \stdClass();
			$this->users[(string)$coOwner->userId]->share = $coOwner->share;
		}

		foreach ( $users as $user )
		{
			// Check if user belong to company.
			if( ! array_key_exists( (string)$user->_id, $this->users ) )
			{
				throw new \Exception( "User $user->name $user->surname is not registered as company co-owner." );
			}

			$this->users[(string)$user->_id]->user = $user;
		}
	}

}