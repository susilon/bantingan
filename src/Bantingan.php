<?php
namespace Bantingan;
/*		
    This is the application base

    Bantingan Framework v3
    Copyright (C) 2023 by Susilo Nurcahyo
    susilonurcahyo@gmail.com

    Some library are copyright to their respective owners.

	Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.	

	This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.

	Want to thank me?
	- send me email, let me know how you using it,
	- want to thank more? consider buying me a coffe,
	- still not enough? let's discuss
	Thank you for your support!
*/
use Bantingan\AppRouter;
use Bantingan\Controllers;
use Bantingan\Model;
use Bantingan\PageGenerator;
use Bantingan\Settings;

//use Modules\Common\SQLSession\Session;

class Bantingan
{	
	private $controllername;
	private $actionname;
	private $parameter;
	private $route;

	public function __construct()
	{	        
		try {						
			// session settings
			if (isset(APPLICATION_SETTINGS["Session_DB"]))
			{
				if (APPLICATION_SETTINGS["Session_DB"]) // if true
				{
					//$session = new Session(); // session stored in mysql
                    session_start();
				} else {
					session_start();
				}
			} else {
				session_start();
			}
		
			$routeConfig = new AppRouter;
			$this->route = $routeConfig->RouterStart();	
			$this->Start($this->route);					
		} 
		catch (\Throwable $err) { 
			$this->mainErrorHandler($err);
		}
		catch (\Exception $err)
		{			
			$this->mainErrorHandler($err);
		}							
	}

	public function Start($route=null) {
		if (isset($route))
		{
			$this->route = $route; 	
		}
		
		// does the route indicate a controller?
		if ($this->route["controller"] != null) {
		    // take the controller class directly from the route
		    $this->controllername = $this->route["controller"];
		} else {
		    // use a default controller
		    $this->controllername = APPLICATION_SETTINGS["DefaultController"];//'Home';
		}
		define("BANTINGAN_CONTROLLER_NAME", $this->controllername);

		// does the route indicate an action?
		if ($this->route["action"] != null) {			
		    // take the action method directly from the route
		    $this->actionname = $this->route["action"];
		} else {
		    // use a default action
		    $this->actionname = 'index';
		}
		define("BANTINGAN_ACTION_NAME", $this->actionname);

		// does the route indicate a parameter?
		if ($this->route["parameters"] != null) {
			$this->parameter = $this->route["parameters"];
		} else {
			$this->parameter = array();
		}
		define("BANTINGAN_PARAMETER", $this->parameter);

		if (strtolower($this->controllername) == "error") {			
			$this->closeDBConnection();
			$this->errorHandler(new \Exception($_SESSION["error_messages"], $_SESSION["error_code"]));
			exit();
		}		

		try {
			$namespace = isset($this->route["namespace"])?$this->route["namespace"]:"";
			$namespacepath = "";
			$namespaceclass = "";
			if ($namespace != "") {
				$namespacepath = $namespace."/";
				$namespaceclass = $namespace."\\";
			}

			$controllerFile = __DIR__ . '/../'.				
				APPLICATION_SETTINGS["Controllers"].'/'.
				$namespacepath.
				ucfirst(BANTINGAN_CONTROLLER_NAME).'Controller.php';	
			
			if(file_exists($controllerFile)) {	 				
				// requested controller file	
				$controllerFunction = ucfirst(APPLICATION_SETTINGS["Controllers"])."\\".
					$namespaceclass.
					ucfirst(strtolower(BANTINGAN_CONTROLLER_NAME))."Controller";
				$controller = new $controllerFunction();
				$controller->namespace = $namespace;

				$classFunction = array($controller,BANTINGAN_ACTION_NAME);			
				$method = 	BANTINGAN_ACTION_NAME;	

				if (!method_exists($controller, BANTINGAN_ACTION_NAME)) {			
					throw new \Exception('Method does not exists', 404);			
				} else {				
					$findmethod = new \ReflectionMethod($controller, BANTINGAN_ACTION_NAME);			
					if ($findmethod->getNumberOfRequiredParameters() > sizeof(BANTINGAN_PARAMETER)) {
						throw new \Exception('Arguments not valid', 404);
					}
				}
				switch(sizeof(BANTINGAN_PARAMETER))  {
				// optimize for better performance if parameter are 5 or less			    
					case 0: $controller->$method();
					break;
					case 1: $controller->$method(BANTINGAN_PARAMETER[0]);
					break;
					case 2: $controller->$method(BANTINGAN_PARAMETER[0], BANTINGAN_PARAMETER[1]);
					break;
					case 3: $controller->$method(BANTINGAN_PARAMETER[0], BANTINGAN_PARAMETER[1], BANTINGAN_PARAMETER[2]);
					break;
					case 4: $controller->$method(BANTINGAN_PARAMETER[0], BANTINGAN_PARAMETER[1], BANTINGAN_PARAMETER[2], BANTINGAN_PARAMETER[3]);
					break;
					case 5: $controller->$method(BANTINGAN_PARAMETER[0], BANTINGAN_PARAMETER[1], BANTINGAN_PARAMETER[2], BANTINGAN_PARAMETER[3], BANTINGAN_PARAMETER[4]);
					break;
					default:call_user_func_array($classFunction, BANTINGAN_PARAMETER);
					break;
				}				
			} else {				
	            throw new \Exception('Resources Not Found..', 404);					            
	        }  		
			$this->closeDBConnection();	    
		}
		catch (\Throwable $err) { 
			$this->errorHandler($err);
		}
        catch(\Exception $err) {          	
        	$this->errorHandler($err);
        }

	}

	private function errorHandler($errorException=null)
	{
		if ($errorException->getCode() != 200)
		{
			if(!headers_sent()) {
				header("HTTP/1.0 ".$errorException->getCode()." ".$errorException->getMessage());
			}			
			$controllerName = APPLICATION_SETTINGS["SharedViewFolder"];
			$methodName = APPLICATION_SETTINGS["ErrorFileTemplate"];
			$errorPage = new PageGenerator;
			
			$errorPage->baseUrl = APPLICATION_SETTINGS["BaseUrl"];
			$errorPage->baseController = $controllerName;		
			$errorPage->viewBag->pageTitle = "Error ".$errorException->getCode();
			$errorPage->viewBag->errorCode = $errorException->getCode();
			$errorPage->viewBag->errorMessage = $errorException->getMessage();
			$errorPage->viewBag->errorException = $errorException;

			try {
				$errorPage->Render($controllerName.$methodName);
			}
			catch (\Throwable $err) { 
				echo "Sorry, resources not found!<br>".$errorException->getMessage();
			}
			catch(\Exception $err) {          	
				echo "Sorry, resources not found!<br>".$errorException->getMessage();
			}
		} 

		$this->closeDBConnection();	
	}	

	private function mainErrorHandler($err)
	{
		if (isset(APPLICATION_SETTINGS["ShowRoutingError"]) && APPLICATION_SETTINGS["ShowRoutingError"]) {
			$err = new \Exception('Resources Not Found.', 404);	
			$this->errorHandler($err);
		} else {
			$this->controllername = APPLICATION_SETTINGS["DefaultController"];//'Home';
			$this->actionname = 'index';
			$this->Start($this->route);	
		}	
	}

	private function closeDBConnection()
	{
		// closing database connection
		if (isset($GLOBALS['redbeans'])) 
        {		        	
        	$dataModel = new Model();
        	$dataModel->Close();
        }
	}
	
}