<?php

/**
 * DialogControl resi problem s formularem v modalnim okne.
 * Pro zobrazeni modalniho okna nepouzivat klasicke signaly zobrazOkno! (tzn. www.stranka.cz?do=zobrazOkno),
 * ale misto toho pouzit parametr jiny (pseudo signal), param. 'do' potom prevezme formular a nedoslo by k zobrazeni okna.
 * 
 * @author  Miloslav Koštíř
 *    
 */
 
namespace Components;      

class DialogControl extends BaseControl {

		/** @var array */
		protected $html;
		
		/** @var	string */ 
		protected $text;
		
		/** @var	string */
		protected $class;
		
		/** @var	string */
		protected $el;
		
		/** @var	string */
		protected $file;
		
		/** @var	mixed */
		protected $control;
		
		/** @var	string */
		protected $name;
		
		/** @var	string */
		protected $width = '400px';
		
		/** @var bool */
		private $open = FALSE;
		
		/** @var array */
		public $onStore;
		
		/** @var array */
		public $onRestore;
		
		/** @var array */
		public $onClose;
				
		/** @var string*/
		private $newToken;
		
		/** @NSessionSection */
		private $storage;
		
		/** @var string */
		private $signal;
		
		/** @var array */
		private $parameters;
		
	
	
	/**
	 * Spusti se pri pripojeni k presenteru	
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		if(!$presenter instanceof \Nette\Application\UI\Presenter) return;
		
		if($presenter->isAjax()){
			$this->invalidateControl();
		}

		$this->signal = $this->parent->getParameter('dialogDo');
		$this->newToken = $presenter->name.$presenter->action;
		$this->storage = $presenter->getSession('dialog');
		$this->parameters = $presenter->getParameter();
		unset($this->parameters["do"]);

		$this->checkToken();
	}
	
	protected function configure($presenter){
	
	}
	
	
	private function checkToken(){
		if(isset($this->storage->token) AND $this->storage->token != $this->newToken){	
			$this->storage->remove();
			$this->close();
		}
	}
	
	private function setToken(){
		if(!isset($this->storage->token)){
			$this->storage->token = $this->newToken;
		}
	}
	
	
	/**
	 * Inicializace
	 * @param  string  $signal
	 * @param  Nette\Callback  $callback
	 * @return bool
	 */	 	 	 	 	
	public function init($signal, $callback = NULL){
		// neotestovano
		if(($signal instanceof \Nette\Callback OR is_callable($signal)) AND $callback === NULL){
			$callback = $signal;
			$signal = NULL;
		}
		
		if($signal === NULL OR (!empty($this->signal) AND $this->signal == $signal)){			
			$this->storage->handle = $this->signal;
			$this->storage->args = $this->parameters;
			$this->setToken();
			$this->configure($this->presenter);
			$this->onStore($this);
			$return = TRUE;
		}
		elseif(!empty($this->storage->handle) AND $this->storage->handle == $signal){
			$this->configure($this->presenter);				
			$this->onRestore($this);
			$return = TRUE;
		}
		else{
			return FALSE;
		}
		
		if($callback instanceof \Nette\Callback){
			$callback->invoke($this);
		}		
		elseif(is_callable($callback)){
			call_user_func($callback, $this);
		}
				
		return $return;
	}
	
	
	/**
	 * Vrati argumenty z URL
	 * @param  string  $name
	 * @return string
	 */	 	 	 	
	public function getArgs($name = NULL){
		if(isset($name) AND isset($this->storage->args[$name])) return $this->storage->args[$name];
		elseif(isset($name)) return NULL;
		else return $this->storage->args;
	}
	
	
	/**
	 * Vrati nazev pseudo-handl-funkce
	 * @return  string
	 */	 	   
	public function getHandle(){ 
		if(isset($this->storage->handle)){	
		  return $this->storage->handle;
		}
		else return NULL;  				
	}
	
	
	/**
	 * Zjisti, zda je dialog zobrazen
	 * @return  bool
	 */
	public function isOpen(){
		return $this->open;
	}
	
	
	/**
	 * Vykreslí text v elementu $el s třídou $class
	 * @param 	string 	$text
	 * @param		string 	$class
	 * @param  	string 	$el
	 * @return 	DialogControl
	 */
	public function message($text, $class = NULL, $el = 'div'){
		$this->text = $text;
		$this->class = $class;
		$this->el = $el;
		return $this;
	}
	
	
	/**
	 * Vlozi do dialogoveho okna objekt \Nette\Utils\Html a vykresli
	 * @param   Nette\Utils\Html  $html
	 * @return  DialogControl
	 */	 	 	 	
	public function html(\Nette\Utils\Html $html){
		$this->html[] = $html;
		return $this;
	}
	
	
	/**
	 * Vykreslí předem nadefinovaný blok $name ze souboru $file
	 * @param		string	$name
	 * @param		string	$file
	 * @return	DialogControl
	 */		 		 		 		 		     
	public function block($name, $file = NULL){
		$this->name = $name;
		$this->setFile($file);
		return $this;
	}
	
	/**
	 * Vloží do dialogového okna hotovou (vytvorenou) komponentu a rovnou vykresli
	 * zadavat hotovou komponentu - $presenter["controlName"]     
	 * @param		mixed			$control   		
	 * @param		bool			$addProtection
	 * @return	DialogControl
	 */
	public function control($control, $addProtection = TRUE){
		if($addProtection === TRUE){
			if(!isset($control[\Nette\Application\UI\Form::PROTECTOR_ID]) AND method_exists($control, "addProtection")) {
				$control->addProtection();
			}					
		}
		$this->control[] = $control;

		return $this;
	}
	
	/**
	 * Vytvoří v dialogovém okně komponentu, nebude hned vykreslena, musi se volat v sablone {control controlName}
	 * @param		string		$name
	 * @param		mixed			$control 	Komponenta, formular, atd.
	 * @return	DialogControl
	 */		 		 		 		     
	public function createControl($name, $control = NULL, $addProtection = TRUE){
		$this[$name] = $control;
		if($addProtection === TRUE AND !isset($control[\Nette\Application\UI\Form::PROTECTOR_ID]) AND method_exists($control, "addProtection")){ 
			$this[$name]->addProtection();
		}
	
		return $this;
	}
	
	/**
	 * Nastaví šířku okna
	 * @param		string	$width		Default '400px'
	 * @return	DialogControl
	 */
	public function setWidth($width){
		$this->width = $width;
		return $this;
	}
	
	/**
	 * Otevře dialogové okno
	 * @return void
	 */
	public function open(){ 
		$this->open = TRUE;      
	}
	
	
	/**
	 * Zavře dialogové okno
	 * @return void
	 */
	public function close($location = 'this'){ 
		$this->storage->remove();
		$this->open = FALSE;		
		if(!$this->presenter->isAjax()){
			if(isset($location)) $this->presenter->redirect($location);
		}
	}

	
	public function handleClose($location){
		// vyvoláme události přidané do onClose
		$this->onClose($this);
		if(empty($location)) $location = 'this';
		$this->close($location);
	}
	
	
	/**
	 * Nastaví cestu k souboru s šablonou
	 * @param  string	 $file
	 * @return DialogControl
	 */		 		 		     
	private function setFile($file = NULL){
		if(!isset($file)){
			$p = array_reverse(explode(":", $this->presenter->name));
			$file = APP_DIR."/";
			$file .= isset($p[1]) ? $p[1]."Module/templates/" : "templates/";
			$file .= $p[0]."/".$this->presenter->action.".latte";
		}
		$this->file = $file;
		return $this;
	}





	public function render() {			
		$this->template->componentName = $this->getName();
		$this->template->setFile(dirname(__FILE__) . '/DialogControl.latte');
		
		$this->template->width = $this->width;
		$this->template->text = $this->text;
		$this->template->class = $this->class;
		$this->template->el = $this->el;    
		$this->template->file = $this->file;
		$this->template->control = $this->control;
		$this->template->html = $this->html;
		$this->template->name = $this->name;
		$this->template->close = !$this->isOpen();

		$this->template->render();
	}

}