<?php

/**
 * DialogControl - modal window for Nette framework 2.x
 *
 * @author			Miloslav Koštíř
 * @copyright		Copyright (c) 2013 Miloslav Koštíř
 * @license     New BSD Licence
 */
 
namespace MiloslavKostir\DialogControl;

use Nette;      

class DialogControl extends Nette\Application\UI\Control
{
	
	/** @var string */
	protected $templatesDir;
		
	/** @var array */
	protected $html;
	
	/** @var	string */
	protected $file;
	
	/** @var	array */
	protected $control;
	
	/** @var	string */
	protected $block;
	
	/** @var	string */
	protected $width = '400px';
	
	/** @var bool */
	protected $open;
	
	/** @var bool */
	protected $storing;
	
	/** @var bool */
	protected $restoring;
	
	/** @var array */
	public $onClose;
			
	/** @var string*/
	private $newToken;
	
	/** @var Nette\Http\SessionSection */
	private $storage;
	
	/** @var string */
	private $signal;
	
	/** @var array */
	private $parameters;
	
	
	/**
	 * @param string
	 */	 	
	public function __construct($templatesDir = NULL)
	{
		$this->templatesDir = $templatesDir;
	}
	
	
	/**
	 * Self-setting.
	 * @return void
	 */	 	  	
	protected function setUp()
	{
		$presenter = $this->presenter;
		
		if($presenter->isAjax())
		{
			$this->invalidateControl();
		}

		$this->signal = $this->parent->getParameter('dialogDo');
		$this->newToken = $presenter->name.$presenter->action;
		$this->storage = $presenter->getSession('dialog');
		$this->parameters = method_exists($presenter, 'getParameters') ? $presenter->getParameters() : $presenter->getParameter();
		unset($this->parameters['do']);

		$this->checkToken();
	}
	
	
	/**
	 * Configuration for advanced use. Use it instead of callback
	 * @param Nette\Application\UI\Presenter
	 * @return void	 
	 */	 	 	
	protected function configure($presenter)
	{
	}
	
	
	/**
	 * Recognizes another page by token and closes the window.
	 * @return void
	 */	 	 	
	private function checkToken()
	{
		if(isset($this->storage->token) AND $this->storage->token != $this->newToken)
		{	
			$this->storage->remove();
			$this->close();
		}
	}
	
	
	/**
	 * Sets the token
	 * @return void
	 */	 	 	
	private function setToken()
	{
		if(!isset($this->storage->token))
		{
			$this->storage->token = $this->newToken;
		}
	}
	
	
	/**
	 * Initialization. 
	 * @param string $signal
	 * @param Nette\Callback $callback
	 * @return bool
	 */	 	 	 	 	
	public function init($signal = NULL, $callback = NULL)
	{
		$this->setUp();

		if(($signal instanceof Nette\Callback OR is_callable($signal)) AND $callback === NULL)
		{
			$callback = $signal;
			$signal = NULL;
		}
		
		if($signal === NULL OR (!empty($this->signal) AND $this->signal == $signal))
		{
			$this->storing = TRUE;			
			$this->storage->handle = $this->signal;
			$this->storage->args = $this->parameters;
			$this->setToken();
			$this->configure($this->presenter);
			$return = TRUE;
		}
		elseif(!empty($this->storage->handle) AND $this->storage->handle == $signal)
		{
			$this->restoring = TRUE;
			$this->configure($this->presenter);				
			$return = TRUE;
		}
		else
		{
			return FALSE;
		}
		
		if($callback instanceof Nette\Callback)
		{
			$callback->invoke($this);
		}		
		elseif(is_callable($callback))
		{
			call_user_func($callback, $this);
		}
				
		return $return;
	}
	
	
	/**
	 * Gets URL parameters
	 * @param  string  $name
	 * @return string|array
	 */	 	 	 	
	public function getArgs($name = NULL)
	{
		if(isset($name) AND isset($this->storage->args[$name])) return $this->storage->args[$name];
		elseif(isset($name)) return NULL;
		else return $this->storage->args;
	}
	
	
	/**
	 * Gets name of pseudo handl function
	 * @return  string
	 */	 	   
	public function getHandle()
	{ 
		if(isset($this->storage->handle))
		{	
		  return $this->storage->handle;
		}
		else return NULL;  				
	}
	
	
	/**
	 * Is the window open?
	 * @return  bool
	 */
	public function isOpen()
	{
		return (bool) $this->open;
	}
	
	
	/**
	 * Is the window storing to session now?
	 * @return  bool
	 */
	public function isStoring()
	{
		return (bool) $this->storing;
	}
	
	
	/**
	 * Is the window restoring from session now?
	 * @return  bool
	 */
	public function isRestoring()
	{
		return (bool) $this->restoring;
	}
	
	
	/**
	 * Adds message with text $text in HTML element $el with css class $class for later render
	 * @param 	string 	$text
	 * @param		string 	$class
	 * @param  	string 	$el
	 * @return 	self
	 */
	public function message($text, $class = NULL, $el = 'div')
	{
		$html = Nette\Utils\Html::el($el)->setText($text)->setClass($class);
		$this->html($html);
		return $this;
	}
	
	
	/**
	 * Adds Nette\Utils\Html for later render
	 * @param   Nette\Utils\Html  $html
	 * @return  self
	 */	 	 	 	
	public function html(Nette\Utils\Html $html)
	{
		$this->html[] = $html;
		return $this;
	}
	
	
	/**
	 * Sets block $block from template $file for later render
	 * @param		string	$block
	 * @param		string	$file  If NULL, is's trying to find template by $block in $templatesDir property 
	 * @return	self
	 * @throws 	Nette\InvalidArgumentException If both $file and DialogControl::$templatesDir are empty 
	 */		 		 		 		 		     
	public function block($block, $file = NULL)
	{
		$this->block = $block;
		if(!isset($file) AND isset($this->templatesDir))
		{
			$this->file = $this->templatesDir . '/' . $block . '.latte';
		}
		elseif(isset($file))
		{
			$this->file = $file;
		}
		else
		{
			throw new Nette\InvalidArgumentException('No template path defined. Second argument must not be NULL if property '.__CLASS__.'::templateDir is empty.');
		}
		return $this;
	}
	
	
	/**
	 * Adds control for instant render and adds XSRF protection   
	 * @param		mixed	$control   		
	 * @param		bool	$addProtection
	 * @return	self
	 */
	public function control($control, $addProtection = TRUE)
	{
		if($addProtection === TRUE)
		{
			if(!isset($control[Nette\Application\UI\Form::PROTECTOR_ID]) AND method_exists($control, 'addProtection')) 
			{
				$control->addProtection();
			}					
		}
		$this->control[] = $control;

		return $this;
	}
	
	/**
	 * Creates component in dialog window control which will be possible to use in block via macro {control controlName}
	 * @param		string	$name Name of control
	 * @param		mixed		$control Component factory eg. createComponentSomeControl()
	 * @param		bool		$addProtection	 
	 * @return	self
	 */		 		 		 		     
	public function createControl($name, $control = NULL, $addProtection = TRUE)
	{
		$this[$name] = $control;
		if($addProtection === TRUE AND !isset($control[Nette\Application\UI\Form::PROTECTOR_ID]) AND method_exists($control, 'addProtection'))
		{ 
			$this[$name]->addProtection();
		}
	
		return $this;
	}
	
	/**
	 * Sets width of window
	 * @param		string	$width	Default '400px'
	 * @return	self
	 */
	public function setWidth($width)
	{
		$this->width = $width;
		return $this;
	}
	
	/**
	 * Opens the window
	 * @return void
	 */
	public function open()
	{ 
		$this->open = TRUE;      
	}
	
	
	/**
	 * Closes the window
	 * @param  string $location	 
	 * @return void
	 */
	public function close($location = NULL)
	{ 
		$this->storage->remove();
		$this->open = FALSE;		
		if(!$this->presenter->isAjax())
		{
			if(isset($location)) $this->presenter->redirect($location);
			else $this->presenter->redirect('this');
		}
	}

	
	/**
	 * Closes the window
	 * @param  string $location	 
	 * @return void
	 */	
	public function handleClose($location = NULL){
		$this->onClose($this);
		$this->close($location);
	}


	/**
	 * Sets params in template
	 * return void
	 */	 	 	
	public function setTemplateParams() {			
		$this->template->componentName = $this->getName();		
		$this->template->width = $this->width;    
		$this->template->file = $this->file;
		$this->template->control = $this->control;
		$this->template->html = $this->html;
		$this->template->block = $this->block;
		$this->template->open = $this->isOpen();
	}
	
	
	/**
	 * Render. If you rewrite this method (change the file path) don't forget call setTemplateParams().
	 */	 	
	public function render() {
		$this->setTemplateParams();			
		
		$this->template->setFile(__DIR__ . '/dialogControl.latte');
		$this->template->render();
	}
	
}
