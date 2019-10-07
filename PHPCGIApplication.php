<?php
/*	PHPCGIApplication v0.01
 *	(Working Title)
 *	Current maintainer: Chris Harshman, wingedgeek@puntumarchimedis.com
 *
 *	A lightweight PHP web application framework, loosely based on the Perl
 *	CGI::Application module (at least, that's the starting point): 
 *	https://metacpan.org/source/MARTO/CGI-Application-4.61/lib/CGI/Application.pm
 */

include_once("lib/template.php");

class PHPCGIApplication {
	private $error_mode = "error";
	private $start_mode = "error";	// If no default runmode is specified, default to error output

	private $params = array();

	private $runmode_qp = "rm";
	private $run_modes = array();
	private $template_path = "/path/to/templates";
	private $error_message;

	
	function __construct( $init_params = array() ) {
		$this->setup();
	}
/*
	setup()

	This method is called by the inherited new() constructor method. The setup() method 
	should be used to define the following property/methods:

	mode_param() - set the name of the run mode CGI param.
	start_mode() - text scalar containing the default run mode.
	error_mode() - text scalar containing the error mode.
	run_modes() - hash table containing mode => function mappings.
	tmpl_path() - text scalar or array reference containing path(s) to template files.
*/
	public function setup() { }	// stub

/*
	If implemented, this method is called automatically after your application runs. 
	It can be used to clean up after your operations. A typical use of the teardown() 
	function is to disconnect a database connection which was established in the setup() 
	function. You could also use the teardown() method to store state information about 
	the application to the server.
*/
	public function teardown() { } // stub

/*	If implemented, this method is called automatically right before the setup() method
	is called. This method provides an optional initialization hook, which improves the 
	object-oriented characteristics of CGI::Application. The cgiapp_init() method 
	receives, as its parameters, all the arguments which were sent to the new() method.

	An example of the benefits provided by utilizing this hook is creating a custom 
	"application super-class" from which all your web applications would inherit, 
	instead of CGI::Application.
*/
	public function cgiapp_init( $init_params ) { } // stubb
	

/*	If implemented, this method is called automatically right before the selected run mode 
	method is called. This method provides an optional pre-runmode hook, which permits 
	functionality to be added at the point right before the run mode method is called. To 
	further leverage this hook, the value of the run mode is passed into cgiapp_prerun().
*/
	public function cgiapp_prerun( $runmode ) { } // stub
	
	
/*	If implemented, this hook will be called after the run mode method has returned its 
	output, but before HTTP headers are generated. This will give you an opportunity to 
	modify the body and headers before they are returned to the web browser.

	A typical use for this hook is pipelining the output of a CGI-Application through a 
	series of "filter" processors. For example:

	* You want to enclose the output of all your CGI-Applications in
	  an HTML table in a larger page.
 
	* Your run modes return structured data (such as XML), which you
	  want to transform using a standard mechanism (such as XSLT).
 
	* You want to post-process CGI-App output through another system,
	  such as HTML::Mason.
 
	* You want to modify HTTP headers in a particular way across all
	  run modes, based on particular criteria.
	  
	The cgiapp_postrun() hook receives a reference to the output from your run mode 
	method, in addition to the CGI-App object.
*/
	public function cgiapp_postrun( $content ) {
		return $content;
	}

	
	public function start_mode( $mode ) { $this->setStartMode($mode); }
	public function setStartMode( $mode ) {
		$this->start_mode = $mode;
	}
	
	public function getStartMode() { 
		return $this->start_mode;
	}
	
	
	public function error_mode( $mode ) { $this->setErrorMode($mode); }
	public function setErrorMode( $mode ) {
		$this->error_mode = $mode;
	}
	
	public function getErrorMode() { 
		return $this->error_mode;
	}
	
	public function tmpl_path( $path ) { $this->setTmplPath($path); }
	public function setTmplPath( $path ) {
		$this->template_path = $path;	
	}
	
	public function getTmplPath() {
		return $this->template_path;
	}

	public function loadTmpl( $filename ) {
		$fn = $this->getTmplPath() . "/" . $filename;
		if( is_readable ( $fn ) ) 
			return new Template( $this->getTmplPath() . "/" . $filename );
		return NULL;
	}

	public function run_modes( $modes ) { $this->setRunModes( $modes ); }
	public function setRunModes( $modes ) {
		foreach( array_keys($modes) as $mode ) {
			$this->run_modes["{$mode}"] = $modes["{$mode}"];
		}
	}
	
	public function getRunmodeQueryParam() {
		return $this->runmode_qp;
	}
	
	public function setRunmodeQueryParam( $param ) {
		$this->runmode_qp = $param;
	}
	
	public function getRunmode( ) {
		$rmqp = $this->getRunmodeQueryParam();
		return isset($_REQUEST["{$rmqp}"]) ? $_REQUEST["{$rmqp}"] : $this->start_mode;
	}	
		
	public function param( $param, $value = null ) {
		if( $value == null )
			return $this->getParam( $param );
		else
			$this->setParam( $param, $value );
	}

	public function getParam( $param ) {
		if(isset($this->params["{$param}"]))
			return $this->params["{$param}"];
		return false;
	}

	public function setParam( $param, $value ) {
		$this->params["{$param}"] = $value;
	}
	
	public function run() {
		$this->setup();
		$run_function = $this->getRunmodeFunction();
		$this->cgiapp_prerun( $this->getRunmode() );
		$content = call_user_func_array(array($this, $run_function), array());
		$content = $this->cgiapp_postrun( $content );
		print $content;	
	}
	
	public function set_error_message( $msg ) {
		$this->error_message = $msg;
	}
	
	public function get_error_message() {
		return $this->error_message;
	}
	
	public function getRunmodeFunction() {
		// if a runmode isn't specified in a GET or POST string, use the default
		// $rmqp = $this->get_runmode_query_param();
		// $rm = isset($_REQUEST["{$rmqp}"]) ? $_REQUEST["{$rmqp}"] : $this->start_mode;
		$rm = $this->getRunmode();
		if( key_exists( $rm, $this->run_modes ) ) {
			$function_name = $this->run_modes["{$rm}"];
			if(! method_exists($this, $function_name)) {
				$this->set_error_message("Runmode '$rm' is defined but function '$function_name' is not implemented.<br/>\n");
				$this->error();
			}
			return $function_name;
		} else {
			$this->set_error_message("Runmode '$rm' is undefined.<br/>\n");
			$this->error();
		}
	}	
	
	
	/*********** Headers functionality ***********/
	// header_remove(); // nuke all existing headers
	// header()	// send raw header, examples:
/*			header('Location: http://www.example.com/');	// also returns a REDIRECT (302) status code to the browser unless the 201 or a 3xx status code has already been set.
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); 

			header('WWW-Authenticate: Negotiate');
			header('WWW-Authenticate: NTLM', false);
			// Spawn a download:
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="downloaded.pdf"');
			readfile('original.pdf');
			
			// Caching:
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

			header("Location: /foo.php",TRUE,301);	// permanently
			header("Location: /foo.php",TRUE,302);	// found
			header("Location: /foo.php",TRUE,303);	// see other
			header("Location: /foo.php",TRUE,307);	// temporarily

			
		The optional replace parameter indicates whether the header should replace a previous similar header, or add a second header of the same type. By default it will replace, but if you pass in FALSE as the second argument you can force multiple headers of the same type. For example:
*/
	


	
	
	public function error() {
		print <<<ERROR_START_HTML
<html>
<head>
<title>Error</title>
</head>
<body>
<h1>Error</h1>
<p><b>Message:</b>
ERROR_START_HTML;
		print $this->get_error_message();
		print <<<ERROR_END_HTML
</p>
</body>
</html>
ERROR_END_HTML;
		die;
	}
}

?>
