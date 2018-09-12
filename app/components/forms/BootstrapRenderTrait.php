<?php

namespace App\Components;


use Tracy\Debugger;
use Nette\Forms\Controls;


trait BootstrapRenderTrait
{
	/**
	 * Automatic Twitter Bootstrap form rendering.
	 *
	 * @param \Nette\Application\UI\Form $form
	 * @return \Nette\Application\UI\Form
	 */
	public function setBootstrapRender( $form )
	{
		$form->getElementPrototype()->addAttributes(['autocomplete' => 'off']);

		// setup form rendering
		$renderer = $form->getRenderer();
		$renderer->wrappers['group']['container'] = 'div class="panel panel-default"';
		$renderer->wrappers['group']['label'] = 'div class="panel-heading"';
		$renderer->wrappers['controls']['container'] = 'div class="panel-body"';
		$renderer->wrappers['pair']['container'] = 'div class="form-group"';
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['description'] = 'span class=help-block';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';

		// make form and controls compatible with Twitter Bootstrap
		foreach ( $form->getControls() as $control )
		{
			if ( $control instanceof Controls\Button )
			{
				//$control->setAttribute('class', empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
				//$usedPrimary = TRUE;

			}
			elseif ( $control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox )
			{
				$class = $control->getControlPrototype()->__get('type') == 'date' ? 'form-control datepicker' : 'form-control';
				$class = $control->getControlPrototype()->class ? $class.' '.$control->getControlPrototype()->class : $class;
				$control->setAttribute('class', $class);
			}
			elseif ( $control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList )
			{
				$control->getSeparatorPrototype()->setName('div')->class($control->getControlPrototype()->type);
			}
		}

		return $form;
	}

}
