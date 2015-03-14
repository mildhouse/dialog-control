DialogControl
=============

DialogControl is component for create modal windows in [Nette Framework](http://nette.org/en/). 


Requirements
------------

DialogControl requires Nette Framework 2.0.0 or higher and PHP 5.3 or later.


Installation
------------

- Download from Github: <https://github.com/miloslavkostir/dialog-control>
- Copy files from dialog-control/src to yourWeb/vendor/miloslavkostir/dialog-control
- Copy css files from dialog-control/resources to css folder in your application


Getting started
---------------

1.Create control in presenter
```php
protected function createComponentDialog(){
	return new \MiloslavKostir\DialogControl\DialogControl;
}
```

2.Put control into template
```html
{control dialog} 
```


Usage
-----

####Show "Hello world" in modal window
You must init the window (the best place for init is action method) :
```php
// presenter
public function defaultAction(){
	$this->getComponent('dialog')->init('show-message', function($dialog){
		$dialog->message('Hello world')->open();
	});
}
```

```html
<!-- template -->
<a n:href="this dialogDo => show-message">Show message</a>
```
Click on "Show message" and you should see modal window.

####Self-init or triggering
The callback in second parameter of init() is execute when value of first parameter equal to value of parameter 'dialogDo' in query URL.
It's self-initialization. But you can trigger the window by another event:
```php
// presenter
public function defaultAction(){
	if(!$this->user->isLoggedIn()){
		// You can write init(NULL, function($dialog){...}); or just init(function($dialog){...});
		$this->getComponent('dialog')->init(NULL, function($dialog){  
			$dialog->message('You are'n logged in', 'error')->open();
		});
	}
}
```

####Not just message
Method message() isn't only one what you can use. Try this:
```php
$this->getComponent('dialog')->init(function($dialog){

	$dialog->html(Nette\Utils\Html::el('span')->setClass('error')->setText('This is error'));  // adds HTML element (see Nette\Utils\Html)
	$dialog->message('In fact, message() is shortcut for html()', 'error', 'span');  // the same as html() above
	$dialog->control($this['myForm']);  // adds control for instant render
	
	$dialog->open();  // opens window and render all set elements 
}
```

####Rendering block
If you need to render window manualy, you can use method block(). First parameter is name of block, second parameter is path to file.
Second parameter is optional, then you must specify directory with dialog templates in constructor. 
```php
$this->getComponent('dialog')->init('show-block', function($dialog){
	$dialog->block('loginDialog', 'path/to/loginDialog.latte')->open();
}
```
If you want to render control in this block, you must create control in DialogControl component. Use createControl() for it.
```php
// presenter
protected function createComponentLoginForm(){
	$form = new \Nette\Application\UI\Form;
			
	$form->addText('login', 'Login');
	$form->addPassword('password', 'Password');
	$form->addSubmit('submit');
	...	
	return $form;
}
	
	
$this->getComponent('dialog')->init('show-block', function($dialog){
	$dialog->block('loginDialog', '../dialogs/loginDialog.latte')
			->createControl('loginForm', $this->createComponentLoginForm())
			->open();
}
```
```html
{* template loginDialog.latte *}
{block loginDialog}
	<h2>Login</h2>
	{form loginForm}
		{label login}{input login}<br>
		{label password}{input password}<br>
		{input submit}<br>
	{/form}
{/block}
```

####Advanced useage
Whole DialogControl is possible to move into own class. Then you will use method configure instead of callback
```php
namespace Components;

use Nette;      

class AdvancedDialogControl extends \MiloslavKostir\DialogControl\DialogControl {

	protected function configure($presenter){
		$this->block('advancedDialog', __DIR__.'/advancedDialogControl.latte');
		$this->open();
	}
	
	
	protected function createComponentSomeForm(){
		$form = new Nette\Application\UI\Form;
		
		$form->addText('name', 'Your name');
		$form->addSubmit('submit');
		$form->onSuccess[] = $this->someFormSucceeded;
		
		return $form;
	}
	
	
	public function someFormSucceeded($form){
		$this->presenter->flashMessage('My name is '.$form->value->name);
		$this->redirect('this');
	}
	
}
```
advancedDialogControl.latte
```html
{block advancedDialog}
	<h2>Advanced usage of DialogControl</h2>
	{form someForm}
		{label name}{input name}<br>
		{input submit}<br>
	{/form}
{/block}
```
In presenter :
```php
protected function createComponentAdvancedDialog(){
	return new \Components\AdvancedDialogControl();
}

public function defaultAction(){
	$this->getComponent('advancedDialog')->init('show');
	// or trigger
	if(!$this->user->isLoggedIn()){
		$this->getComponent('advancedDialog')->init();
	}
}
``` 
