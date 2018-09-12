<?php

namespace App\Presenters;


use App\Components\ICompanyUsersFormFactory;
use App\Model\Services\CompanyService;


class DefaultPresenter extends BasePresenter
{

	/** @var CompanyService @inject */
	public $companyService;

	/** @var ICompanyUsersFormFactory @inject */
	public $companyUsersFormFactory;


	public function renderDefault()
	{

	}


//////// COMPONENTS /////////////////////////////////////////////////////////////////////

	public function createComponentCompanyUsersForm( $name )
	{
		return $this->companyUsersFormFactory->create();
	}

}
