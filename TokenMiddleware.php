<?php

class TokenMiddleware extends Prefab {
	protected $app, $routes;
	public function __construct() {
		$this->app = Base::instance();
		$this->routes = array();
	}
	
	public function protect($pattern, $handler) {
		$bak = $this->app->ROUTES;
		$this->app->ROUTES=array();
		$this->app->route($pattern, $handler);
		$this->routes = (count($this->routes)) ? $this->app->extend('ROUTES',$this->routes) : $this->app->ROUTES;
		$this->app->ROUTES=$bak;
	}
	
	public function run() {
		if (!count($this->routes))
			return;
		$paths=[];
		foreach ($keys=array_keys($this->routes) as $key) {
			$path=preg_replace('/@\w+/','*@',$key);
			if (substr($path,-1)!='*')
				$path.='+';
			$paths[]=$path;
		}
		$vals=array_values($this->routes);
		array_multisort($paths,SORT_DESC,$keys,$vals);
		$this->routes=array_combine($keys,$vals);
		// Convert to BASE-relative URL
		$req=urldecode($this->app['PATH']);
		foreach ($this->routes as $pattern=>$routes) {
			if (!$args=$this->app->mask($pattern,$req))
				continue;
			ksort($args);
			$route=NULL;
			$ptr=$this->app->CLI?\Base::REQ_CLI:$this->app->AJAX+1;
			if (isset($routes[$ptr][$this->app->VERB]) ||
				isset($routes[$ptr=0]))
				$route=$routes[$ptr];
			if (!$route)
				continue;
			if ($this->app->VERB!='OPTIONS' &&
				isset($route[$this->app->VERB])) {
				if ($this->app['VERB']=='GET' &&
					preg_match('/.+\/$/',$this->app['PATH']))
					$this->app->reroute(substr($this->app['PATH'],0,-1).
						($this->app['QUERY']?('?'.$this->app['QUERY']):''));
				$handler=$route[$this->app->VERB][0];
				$alias=$route[$this->app->VERB][3];
				if (is_string($handler)) {
					// Replace route pattern tokens in handler if any
					$handler=preg_replace_callback('/({)?@(\w+\b)(?(1)})/',
						function($id) use($args) {
							$pid=count($id)>2?2:1;
							return isset($args[$id[$pid]])?
								$args[$id[$pid]]:
								$id[0];
						},
						$handler
					);
					if (preg_match('/(.+)\h*(?:->|::)/',$handler,$match) &&
						!class_exists($match[1]))
						$this->app->error(500,'PreRoute handler not found');
				}
				if (!$this->app['RAW'] && !$this->app['BODY'])
					$this->app['BODY']=file_get_contents('php://input');
				return $this->validate($handler, $args, $alias) !== FALSE;
			}
		}
		return true;
	}
	
	protected function validate($handler, $args, $alias) {
		$authHeader = $this->app->get('HEADERS.' . $this->app->get('TOKEN.KEY'));
		
		if(!$authHeader) {
			$this->app->call($handler, array($this->app, $args, $alias));
			return false;
		}
		
		$authToken = $authHeader;
		if($this->app->get('TOKEN.STARTS_WITH')) {
			$_ex = explode(' ', $authHeader);
			$authToken = isset($_ex[1]) ? $_ex[1] : null;
		}
		
		$model = $this->app->get('TOKEN.TABLE');
		
		if(!class_exists($model)) {
			throw new Exception($model . ' class not exists!');
			return false;
		}
		
		$model = new $model();
		$model->load([$this->app->get('TOKEN.TABLE_KEY') . ' = ?', $authToken]);
		
		if($model->dry()) {
			$this->app->call($handler, array($this->app, $args, $alias));
			return false;
		}
		
		$this->app->set('TOKEN.token', $authToken);
		if(isset($model->user)) {
			$this->app->set('TOKEN.user', $model->user);
		}
	}
}