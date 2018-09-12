<?php

namespace App\Components;


use App;
use App\Model\Services\CompanyService;
use App\Model\Services\UsersService;
use MongoDB\BSON\ObjectID;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Strings;
use Tracy\Debugger;


interface ICompanyUsersFormFactory
{
	/**
	 * @return CompanyUsersForm
	 */
	public function create() : CompanyUsersForm;
}


class CompanyUsersForm extends Control
{

	/** @var  CompanyService */
	protected $companyService;

	/** @var  UsersService */
	protected $usersService;

	/** @var  SessionSection */
	protected $sessionSection;


	public function __construct( CompanyService $cS, UsersService $uS, Session $session )
	{
		parent::__construct();
		$this->companyService = $cS;
		$this->usersService = $uS;
		$this->sessionSection = $session->getSection( self::class );
		$this->sessionSection->users = $this->sessionSection->users ?: [];
		//unset($this->sessionSection->companyId);
		Debugger::barDump($this->sessionSection);
	}


	public function render()
	{
		$this->template->setFile( __DIR__ . '/CompanyUsersForm.latte' );
		$this->template->users = $this->sessionSection->users;

		$this->template->render();
	}


	public function handleUpdateShare( $id )
	{
		$post = $this->presenter->getHttpRequest()->getPost();
		if( ! $post['name'] || ! $post['value'] ) return;
	}


	public function createComponentCompanyUsersForm()
	{
		$form = new Form;

		$form->addText( 'companyName', 'Názov firmy' )
			->setRequired( 'Názov firmy je povinný údaj.' )
			->setAttribute( 'class', 'form-control' )
			->setDefaultValue( $this->sessionSection->companyName );

		$form->addText( 'economicalResult', 'Finančné zhodnotenie firmy' )
			->setRequired( 'Finančné zhodnotenie firmy je povinný údaj.' )
			->addRule( $form::FLOAT, 'Finančné zhodnotenie firmy musí byť číselný údaj.' )
			->setAttribute( 'class', 'form-control' )
			->setDefaultValue( $this->sessionSection->economicalResult );

		$usersContainer = $form->addContainer( 'usersContainer' );

		foreach ( $this->sessionSection->users as $user )
		{
			$usersContainer->addText("user_name_$user->key")
				->setAttribute( 'class', 'form-control' )
				->setAttribute( 'readonly', TRUE )
				->setDefaultValue( $user->name );

			$usersContainer->addText( "user_share_$user->key", '' )
				->setAttribute( 'data-key', $user->key )
				->setAttribute( 'class', 'form-control w-45-p' )
				->setAttribute( 'style', 'display: inline' )
				->setAttribute( 'readonly', TRUE )
				->setDefaultValue( $user->share );

			$usersContainer->addText( "user_share_base_$user->key", '' )
				->setAttribute( 'data-key', $user->key )
				->setAttribute( 'class', 'form-control w-45-p' )
				->setAttribute( 'style', 'display: inline' )
				->setAttribute( 'readonly', TRUE )
				->setDefaultValue( $user->shareBase );

			$usersContainer->addSubmit( "user_remove_$user->key", 'Odstrániť' )
				->setAttribute( 'class', 'btn btn-danger btn-sm' )
				->setValidationScope( FALSE );

		}

		$newUserContainer = $form->addContainer('newUserContainer');

		$newUserContainer->addText( 'newUser', 'Pridať spoluvlastníka' )
			->setAttribute( 'class', 'form-control' )
			->setRequired('Meno spoluvlastníka je povinný údaj.')
			->addRule( $form::MAX_LENGTH, 'Meno užívateľa nesmie byť dlhšie ako 50 znakov.', 50 );

		$newUserContainer->addText( 'newShareBase', 'Základ podielu' )
			->setAttribute( 'class', 'form-control w-45-p' )
			->setAttribute( 'style', 'display: inline' )
			->setRequired('Základ podielu je povinný údaj.')
			->addRule( $form::FLOAT, 'Čitateľ podielu musí byť číslo.');

		$newUserContainer->addText( 'newShare', 'Pridať podiel' )
			->setAttribute( 'class', 'form-control w-45-p' )
			->setAttribute( 'style', 'display: inline' )
			->setRequired('Podiel je povinný údaj.')
			->addRule( $form::FLOAT, 'Menovateľ užívateľa musí byť číslo.');

		$form->addSubmit( 'addUserSbmt', 'Pridať užívateľa' )
			->setValidationScope( [$newUserContainer] )
			->setAttribute( 'class', 'btn btn-primary' );

		$form->addSubmit( 'sbmt', 'Uložiť úpravy' )
			->setValidationScope( [$usersContainer, $form['companyName'], $form['economicalResult']] )
			->setAttribute( 'class', 'btn btn-primary' );

		$form->onValidate[] = [$this, 'usersFormValidate'];

		$form->onSuccess[] = [$this, 'usersFormSucceeded'];

		return $form;
	}


	public function usersFormValidate( Form $form, $values )
	{
		$presenter = $form->getPresenter();
		$submitName = $form->isSubmitted()->getName();

		if( Strings::startsWith( $submitName, 'user_remove' ) ) return;

		try
		{
			if( $submitName === 'addUserSbmt')
			{
				// Next line returns because code below requires this values. Native form validation adds error message.
				if( ! $values->newUserContainer->newShareBase || ! $values->newUserContainer->newShare || ! $values->newUserContainer->newUser ) return;
				if( $values->newUserContainer->newShareBase < $values->newUserContainer->newShare ) $form->addError( 'Čitateľ podielu musí byť menší ako menovateľ' );
			}

			// If inserting new user must include new values to the sum.
			$sum = $submitName === 'addUserSbmt' ? $values->newUserContainer->newShare / $values->newUserContainer->newShareBase : 0;
			foreach ( $this->sessionSection->users as $user )
			{
				$sum += $values->usersContainer->{"user_share_$user->key"} / $values->usersContainer{"user_share_base_$user->key"};
				//Debugger::log("user_share_$user->name"); Debugger::log($sum);
			}

			if( $sum > 1 ) $form->addError( 'Súčet podielov nemôže prekročiť 1 (100%).' );
		}
		catch ( \Exception $e )
		{
			Debugger::log( $e, Debugger::ERROR );
		}

		if( $presenter->isAjax() )
		{
			$this->redrawControl();
		}
	}


	public function usersFormSucceeded( Form $form, $values )
	{
		$presenter = $form->getPresenter();
		$submitName = $form->isSubmitted()->getName();

		try
		{
			if( $submitName === 'addUserSbmt' )
			{
				$this->addUserToSession( $values->newUserContainer->newUser, $values->newUserContainer->newShareBase, $values->newUserContainer->newShare );
				$this->flashMessage( 'Spoluvlastník ' . $values->newUserContainer->newUser . ' bol pridaný.' );
			}
			elseif( Strings::startsWith( $submitName, 'user_remove' ) )
			{
				$explode = explode( '_', $submitName );
				$this->removeUserFromSession( end($explode) );
			}
			elseif( $submitName === 'sbmt' )
			{
				Debugger::barDump( 'Saving data to mongo!!!' );
				$companyId = $this->companyService->saveCompany( $this->sessionSection->companyId, $values->companyName, $values->economicalResult, $this->sessionSection->users );
				$this->addCompanyToSession( $values->companyName, $values->economicalResult, $companyId );
			}
		}
		catch( \Exception $e )
		{
			Debugger::log( $e, Debugger::ERROR );
			$this->flashMessage( 'Pri ukladaní údajov došlo k chybe.' );
		}

		$presenter->redirect( 'this' );
	}


	public function createComponentCompanySelectForm( $name )
	{
		$form = new Form;

		$companies = $this->companyService->companies->find()->toArray();
		$selectArr = [];
		foreach ( $companies as $company )
		{
			$selectArr[(string)$company->_id] = $company->companyName;
		}

		$form->addSelect( 'company', 'Vyberte uloženú firmu', $selectArr )
			->setPrompt( '-- Vyberte uloženú firmu --' )
			->setRequired( 'Vyberte prosím firmu' )
			->setAttribute( 'class', 'form-control select2' )
			->setAttribute( 'style', 'display: inline' );

		$form->addSubmit( 'sbmt', 'Odoslať' )
			->setAttribute( 'class', 'btn btn-primary btn-sm' );

		$form->onSuccess[] = [$this, 'companySelectFormSucceeded'];

		return $form;
	}


	public function companySelectFormSucceeded( Form $form, $values )
	{
		$id = new ObjectID( $values->company );
		$company = $this->companyService->findCompanyBy( ['_id' => $id] );

		$this->addCompanyToSession( $company->companyName, $company->companyEconomicalResult, $company->_id );

		$this->sessionSection->users = [];
		foreach ( $company->users as $user )
		{
			$this->addUserToSession( $user->name, $user->shareBase, $user->share );
		}

		$this->presenter->redirect( 'this' );
	}


///// PROTECTED ////////////////////////////////////////////////////////////////////////////////////////////////////////

	protected function addUserToSession( $name, $shareBase, $share )
	{
		try
		{
			$user = new \stdClass();
			$user->name = $name;
			$user->key = Strings::webalize( $name ) . time();
			$user->shareBase = $shareBase;
			$user->share = $share;

			$this->sessionSection->users[$user->key] = $user;
		}
		catch( \Exception $e )
		{
			Debugger::log( $e, Debugger::ERROR );
			$this->flashMessage( 'Pri ukladaní údajov došlo k chybe.' );
		}

		return $user;
	}


	protected function removeUserFromSession( $id )
	{
		unset( $this->sessionSection->users[$id] );
		$this->flashMessage( 'Pre úplné odstránenie užívateľa z databze kliknite na tlačítko Uložiť úpravy.' );
	}


	protected function addCompanyToSession( string $companyName = NULL, float $economicalResult = NULL, ObjectID $companyId = NULL )
	{
		try
		{
			if( $companyId ) $this->sessionSection->companyId = $companyId;
			$this->sessionSection->companyName = $companyName ?: $this->sessionSection->companyName;
			$this->sessionSection->economicalResult = $economicalResult ?: $this->sessionSection->economicalResult;
		}
		catch( \Exception $e )
		{
			Debugger::log( $e, Debugger::ERROR );
			$this->flashMessage( 'Pri ukladaní údajov došlo k chybe.' );
		}

		return $this->sessionSection;
	}

}
