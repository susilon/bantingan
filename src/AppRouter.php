<?php
namespace Bantingan;
/*		
    This is the application routing handler

    Bantingan Framework v3
    Copyright (C) 2023 by Susilo Nurcahyo
    susilonurcahyo@gmail.com

    Some library are copyright to their respective owners.

	Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.	

	This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.
*/
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class AppRouter
{
	public function RouterStart()
	{	
		$routes = new RouteCollection();	

// route sample, but please check route.config file first in config folder
/*		
		$route = new Route('/test/{month}', // path
		    array('_controller' => 'showArchive'), // default values
		    //array('month' => '[0-9]{4}-[0-9]{2}', 'subdomain' => 'www|m'), // requirements
		    array('month'=>".*"), // requirements
		    array(), // options
		    'localhost', // host
		    array(), // schemes
		    array() // methods
		);

		
		$routes->add('route_name', $route);
		$routes->add('blog_list', new Route('/test', array(
		    '_controller' => [BlogController::class, 'list']
		)));
*/
/*

sample route.config 
  "api": {
    "path": "/api",
    "controller": "webservice"
  },
	"apiwithanyparameter": {
    "path": "/api",
    "controller": "webservice"
		"wildcard": true
  },
  "logout": {
    "path": "/logout",
    "controller": "home",
    "action": "logout"
  },
  "editgroupwildcard": { // just a name
    "path": "/register", // the path 
    "controller": "usermanagement", // the controller will handle
    "action": "groupform",  // the action will handle  
    "wildcard" : true // to enable standard REST parameter
  },

  "wparameter": {
    "path": "/aboutus",
    "controller": "home", 
    "action": "aboutus",
    "parameter": ["en"] // to fix the parameter
  },
  
*/
  		// Route Configuration
	if (ROUTE_SETTINGS != null && count(ROUTE_SETTINGS) > 0) {
		foreach (ROUTE_SETTINGS as $key => $routevalue) {    		
			if (isset($routevalue["path"])) {
				$path = $routevalue["path"];
				$namespace = $routevalue["namespace"]??"";
				
				$controller = "home";
				if (isset($routevalue["controller"])) {
					// path with predefined action
					$controller = $routevalue["controller"]; // set predefined action
				} else {
					$path .= "/{controller}"; // any action by url
				}

				$action = "index";
				if (isset($routevalue["action"])) {
					// path with predefined action
					$action = $routevalue["action"]; // set predefined action
				} else {
					$path .= "/{action}"; // any action by url
				}

				$parameters = null;
				if (isset($routevalue["parameter"])) {
					// path with predefined parameter
					if (!isset($routevalue["action"])) {
						exit('Action parameter not set at route '.$key);
					}
					$parameters = implode("/", $routevalue["parameter"]);					
				} else {
					if (isset($routevalue["wildcard"]) && $routevalue["wildcard"] == true) {
						// enable wildcard parameters
						$path .= "/{parameters}";
					}
				}

				$routes->add($key, new Route($path,// path with any parameter
					array('namespace' => $namespace,'controller' => $controller, 'action' => $action, 'parameters' => $parameters),
					array('parameters'=>'.*') // requirements
				));

				if ($namespace != "") {
					$routes->add($key."_base", new Route("/".strtolower($namespace)."/",
						array('namespace' => $namespace,'controller' => $controller, 'action' => $action, 'parameters' => $parameters)
					));

					$routes->add($key."_method", new Route("/".strtolower($namespace)."/{controller}/",
						array('namespace' => $namespace,'controller' => $controller, 'action' => $action, 'parameters' => $parameters)
					));
				}

				/*
				$routes->add($key, new Route($path,// path with any parameter
					array('controller' => $routevalue["controller"], 'action' => $action, 'parameters' => $parameters),
					array('parameters'=>'.*') // requirements
				));
				*/
			}
		}
	}
		
	
		$routes->add('default', new Route('/{controller}/{action}/{parameters}',// path
		    //array('controller' => 'home', 'action' => 'index', 'parameters' => null), // default values	
				array('controller' => strtolower(APPLICATION_SETTINGS["DefaultController"]), 'action' => 'index', 'parameters' => null), // default values	
		    array('parameters'=>'.*') // requirements
		));

		$routes->add('method', new Route('/{controller}/',// path
		    //array('controller' => 'home', 'action' => 'index', 'parameters' => null) // default values		
				array('controller' => strtolower(APPLICATION_SETTINGS["DefaultController"]), 'action' => 'index', 'parameters' => null) // default values		
		));		

		$routes->add('home', new Route('/',// path
		    //array('controller' => 'home', 'action' => 'index', 'parameters' => null) // default values		
				array('controller' => strtolower(APPLICATION_SETTINGS["DefaultController"]), 'action' => 'index', 'parameters' => null) // default values		
		));

		if (!empty(APPLICATION_SETTINGS["BaseUrl"])) {
			$routes->addPrefix(APPLICATION_SETTINGS["BaseUrl"]);
		}		
		$context = new RequestContext("/");
		$matcher = new UrlMatcher($routes, $context);
		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		$route = $matcher->match($path);	
		$route["parameters"] = explode("/", $route["parameters"]??'');	

		return $route;
	}
}