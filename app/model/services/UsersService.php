<?php

namespace App\Model\Services;


use MongoDB;
use MongoDB\Model\BSONDocument;
use Tracy\Debugger;


class UsersService extends BaseService
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


	public function getUsersIdsToSelect( $users = NULL )
	{
		$users = $users ?: $this->users->find();
		$result = [];

		foreach( $users as $user ) $result[(string)$user->_id] = $user->name . ' ' . $user->surname;

		return $result;
	}

}