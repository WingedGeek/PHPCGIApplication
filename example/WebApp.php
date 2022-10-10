<?php

require_once "lib/PHPCGIApplication/PHPCGIApplication.php";
require_once "lib/PHPHTMLTemplate/template.php";

$session_id = PHPCGIApplication::startSession();

/* DEBUG */
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', 'error_log');
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', 1);

/* /DEBUG */

class WebApp extends PHPCGIApplication {
    function setup() {
        // System wide preferences. TODO: Move these to a database table and provide a web UI to edit them.
        $this->param('preferences', array(
        ));

        date_default_timezone_set("America/Los_Angeles");

        $this->tmpl_path(__DIR__ . DIRECTORY_SEPARATOR . "templates"); // was: $this->tmpl_path("/home/kpgsaa/shabyandassociates.com/manage/templates");

        $this->start_mode("main");
        $this->error_mode("my_error_rm");

        // Runmode convention: initials of the method called; alternately, something short and memorable and obvious ("json")
        // Method naming convention: <aspect>_<action> e.g. matter_display, password_reset
        $this->run_modes( array(
            'login' 			=> 	'display_page_login',
            'authenticate'		=> 	'process_authentication',
            'main'			    => 	'display_page_main',
        ) );

        // These runmodes can be accessed without being logged in
        // Whitelist:
        $this->param('no-auth-runmodes', array(
            'display_loginpage',
            'process_authentication'
        ));
    }

    function display_page_main() {
        return "Hello, " . $_SESSION["user_name"];
    }

    function display_page_login() {
        $t = $this->loadTmpl( "login_page.tmpl");
        if(isset($_SESSION["login_error"]) && $_SESSION["login_error"] > 0) {
            $t->param('error', $_SESSION["login_error"]);
            $t->param('error_message', $_SESSION["login_error_message"]);
        }
        return $t->output();
    }

    function process_authentication() {
        $error = 0;
        $error_message = "Username or password invalid.";

        require_once("db/DBBackEnd/User.php");  // Abstraction layer for database; insert your own authentication method as applicable
        $records = User::search( [ 'email' => $_REQUEST['email']);
        // email address should be unique, only one record returned (if any):
        if(! count( $records)) {
            $error = 1;
        } else {
            $user = $records[0];
            if(! password_verify( $_REQUEST["password"], $user->get('password'))) {
                $error = 1;
            } else if( $user->get('active') != 1) {
                $error = 2;
                $error_message = "Account disabled.";
            } else {
                unset($_SESSION["login_error"]);
                unset($_SESSION["login_error_message"]);

                $_SESSION["loggedin"] = 1;
                $_SESSION["user_uuid"] = $user->id();
                $_SESSION["user_name"] = $user->get('name');
                $_SESSION["user_organization_uuid"] = $user->get('organization')->id();
                $this->setParam('redirect', 1);
                $this->setParam('redirect_runmode', 'main');
            }
        }
    }

    function cgiapp_prerun($runmode) {
        // Is the specified run-mode in the white list of runmodes that doesn't require the user to be logged in?
// 		if( in_array($runmode, $this->param('no-auth-runmodes'))) {
// 			$this->debug("cgiapp_prerun($runmode): whitelisted");
// 			return true;
// 		}

        // Is the user logged in? Good to go.
// 		if($this->checkAccess()) {
// 			$this->debug("cgiapp_prerun($runmode): checkAccess() returned true");
// 			return true;
// 		}
        // Not white listed, and not logged in. Hmm. Record the current query in a cookie ...
// 		setcookie("ouri", base64_encode( $_SERVER['REQUEST_URI'] ), time() + 3600);	// Keep the URI for an hour as a cookie

        // ... and redirect to the login page
// 		$this->param('redirect', TRUE);
// 		$this->param('redirect_location', "./?rm=ld");
// 		$this->param('suppress_runmode', TRUE );
// 		return false;
    }

    function cgiapp_postrun($content) {
        $redirect = $this->param('redirect');
        if($redirect != NULL && $redirect == true) {
            // Issue a location header and GTFO
            $redirect_location = $this->param('redirect_location');
            if($redirect_location != NULL)
                header("Location: " . $redirect_location);
            else
                return "Error: 'redirect' parameter set but 'redirect_location' not specified.<br/>\n";
            // Fix for session cookie not being sent, if it's an issue:
            // header("Location:".$redirect_url.'?'.session_name().'='.session_id());
            exit();
        }

        if($this->param('raw')) {
            return $content;
        }

        $t = null;
        // Else, output a normal page:
        if($this->param('minimal')) {
            $t = $this->loadTmpl('master_minimal.tmpl');
        } else {
            $t = $this->loadTmpl("master_main.tmpl");

            // is a user logged in?
//          $t->param('logged_in', $this->checkAccess());	// TODO make this cleaner; this will work for now, though.
// 			$t->param('beta', $this->param('beta'));

//			$qstr = $this->getQueryString();
//// 			return "<pre>" . print_r($qstr, true) . "</pre>\n";
//			if(isset($qstr) && strlen($qstr) > 0)
//				$t->param('querystring', $qstr);
        }
        $t->param("title", "Bills: [" . $this->getRunmode() . "]" );
        $t->param('content', $content );
        if($t->ParamExists('urlbase'))
            $t->param('urlbase', $this->param('urlbase'));
        if($this->param('body_additional_tag'))
            $t->param('body_additional_tag', $this->param('body_additional_tag'));
//		$style = $this->loadTmpl( $this->getRunmode() . "_style.tmpl");
//		if($style != NULL ) {
//			$t->param('style', $style->getOutput());
//		}
//		if($this->param('display_header_donate_button')) {
//			$t->param('donate_button', true);
//		}
        return $t->output();
    }
}
?>
