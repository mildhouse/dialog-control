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
