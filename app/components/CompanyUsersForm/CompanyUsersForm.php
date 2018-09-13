<?php

namespace App\Components;


use App;
use App\Model\Services\CompanyService;
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

	protected $coins = [
		'500e' => 500, '200e' => 200, '100e' => 100, '50e' => 50, '20e' => 20, '10e' => 10, '5e' => 5, '2e' => 2, '1e' => 1,
		'50c' => 0.5, '20c' => 0.2, '10c' => 0.1, '5c' => 0.05, '2c' => 0.02, '1c' => 0.01
	];

	/** @var  CompanyService */
	protected $companyService;

	/** @var  SessionSection */
	protected $sessionSection;


	public function __construct( CompanyService $cS, Session $session )
	{
		parent::__construct();
		$this->companyService = $cS;
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
				->setRequired('Meno spoluvlastníka je povinný údaj.')
				->setAttribute( 'class', 'form-control' )
				->setDefaultValue( $user->name );

			$usersContainer->addText( "user_share_$user->key", '' )
				->setRequired('Čitateľ podielu je povinný údaj.')
				->addRule( $form::FLOAT, 'Čitateľ podielu musí byť číslo.')
				->setAttribute( 'data-key', $user->key )
				->setAttribute( 'class', 'form-control w-45-p' )
				->setAttribute( 'style', 'display: inline' )
				->setDefaultValue( $user->share );

			$usersContainer->addText( "user_share_base_$user->key", '' )
				->setRequired('Menovateľ podielu je povinný údaj.')
				->addRule( $form::FLOAT, 'Menovateľ podielu musí byť číslo.')
				->setAttribute( 'data-key', $user->key )
				->setAttribute( 'class', 'form-control w-45-p' )
				->setAttribute( 'style', 'display: inline' )
				->setDefaultValue( $user->shareBase );

			$usersContainer->addSubmit( "user_remove_$user->key", 'Odstrániť' )
				->setAttribute( 'class', 'btn btn-danger btn-sm' )
				->setValidationScope( FALSE );

			if( $user->name != 'new' )
			{
				$usersContainer["user_name_$user->key"]->setAttribute( 'readonly', TRUE );
				$usersContainer["user_share_$user->key"]->setAttribute( 'readonly', TRUE );
				$usersContainer["user_share_base_$user->key"]->setAttribute( 'readonly', TRUE );
			}

		}

		$form->addSubmit( 'addUserSbmt', 'Pridať užívateľa' )
			->setAttribute( 'class', 'btn btn-primary' );

		$form->addSubmit( 'calculateSbmt', 'Prepočítať zisky' )
			->setValidationScope( [$usersContainer] )
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

		Debugger::barDump( $values );

		if( Strings::startsWith( $submitName, 'user_remove' ) ) return;
		if( Strings::startsWith( $submitName, 'addUserSbmt' ) ) return;

		try
		{
			$usersContainer = $values->usersContainer;
			// Next line return ensures continue to native form validation to show error message.
			if( ! $usersContainer->user_share_base_new || ! $usersContainer->user_share_new || ! $usersContainer->user_name_new ) return;

			$sum = 0;
			foreach ( $this->sessionSection->users as $user )
			{
				$sum += $usersContainer->{"user_share_$user->key"} / $usersContainer{"user_share_base_$user->key"};
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
				if( isset( $this->sessionSection->users['new'] ) ) return;  // Session already contains new user item.
				$this->addUserToSession();
				$this->flashMessage( 'Nový spoluvlastník bol pridaný. Vyplňte údaje prosím.' );
			}
			elseif( Strings::startsWith( $submitName, 'user_remove' ) )
			{
				$explode = explode( '_', $submitName );
				$this->removeUserFromSession( end($explode) );
			}
			elseif( $submitName === 'sbmt' )
			{
				Debugger::barDump( 'Saving data to mongo!!!' );
				if( isset( $values->usersContainer->user_name_new ) )
				{
					$usersContainer = $values->usersContainer;
					unset( $this->sessionSection->users['new'] );
					$this->addUserToSession( $usersContainer->user_name_new, $usersContainer->user_share_base_new, $usersContainer->user_share_new );
				}
				$companyId = $this->companyService->saveCompany( $this->sessionSection->companyId, $values->companyName, $values->economicalResult, $this->sessionSection->users );
				$this->addCompanyToSession( $values->companyName, $values->economicalResult, $companyId );
			}
			elseif( $submitName === 'calculateSbmt' )
			{

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

	protected function addUserToSession( $name = '', $shareBase = '', $share = '' )
	{
		try
		{
			$user = new \stdClass();
			$user->name = $name ?: 'new';
			$user->key = $user->name == 'new' ? $user->name : Strings::webalize( $name ) . time();  // New user has key new
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
		$this->flashMessage( 'Pre úplné odstránenie užívateľa z databazy kliknite na tlačítko Uložiť úpravy.' );
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


	/**
	 * @desc Return an array of coin count for avery coin from $this->coins.
	 * @param $personalProfit
	 * @param array $result
	 * @return array
	 */
	protected function getCoinsCount( $personalProfit, array $result = [] )
	{
		// Negative numbers rounding solution.
		$personalProfit = $personalProfit < 0 ? abs( $personalProfit ) : $personalProfit;
		$coin = current( $this->coins );
		$coinKey = key( $this->coins );

		$result[$coinKey] = (int)floor( $personalProfit / $coin );

		if( next( $this->coins ) )
		{
			$personalProfitRest = round( fmod( $personalProfit, $coin ), 2, PHP_ROUND_HALF_UP );
			$result = $this->getCoinsCount( $personalProfitRest, $result );
		}

		return $result;
	}

}
