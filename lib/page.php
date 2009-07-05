<?php
// $Header$

/**
 * Page Rendering Functions
 *
 * @author The phpLDAPadmin development team
 * @package phpLDAPadmin
 */

/**
 * This class controls the final output to the browser.
 *
 * @package phpLDAPadmin
 * @subpackage Page
 */
class page {
	# pre-HTML headers
	protected $_pageheader;

	# Items to get into the <head>
	protected $_head;

	# Settings for this application
	protected $_app;

	# Default values array.
	protected $_default;

	public function __construct($index=null) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with [%s]',129,__FILE__,__LINE__,__METHOD__,$index);

		# To be defined in a configuration file.
		$this->_app['title'] = app_name();

		# Default Values for configurable items.
		$this->_default['stylecss'] = CSSDIR.'style.css';
		$this->_default['logo'] = IMGDIR.'logo-small.png';
		$this->_default['sysmsg']['error'] = IMGDIR.'error-big.png';
		$this->_default['sysmsg']['warn'] = IMGDIR.'warn-big.png';
		$this->_default['sysmsg']['info'] = IMGDIR.'info-big.png';

		# Capture any output so far (in case we send some headers below) - there shouldnt be any output anyway.
		$preOutput = '';

		# Try and work around if php compression is on, or the user has set compression in the config.
		# type = 1 for user gzip, 0 for php.ini gzip.
		$obStatus = ob_get_status();
		if (isset($obStatus['type']) && $obStatus['type'] && $obStatus['status']) {
			$preOutput = ob_get_contents();
			ob_end_clean();
		}

		header('Content-type: text/html; charset="UTF-8"');
		if (isCompress()) {
			header('Content-Encoding: gzip');

			if (DEBUG_ENABLED)
				debug_log('Sent COMPRESSED header to browser and discarded (%s)',129,__FILE__,__LINE__,__METHOD__,$preOutput);
		}

		if (isset($_SESSION[APPCONFIG])
			&& $_SESSION[APPCONFIG]->getValue('appearance','compress')
			&& ini_get('zlib.output_compression'))
				$this->setsysmsg(array('title'=>_('Warning'),'body'=>_('WARNING: You cannot have PHP compression and application compression enabled at the same time. Please unset zlib.output_compression or set $config->custom->appearance[\'compress\']=false'),'type'=>'warn'));

		# Turn back on output buffering.
		ob_start();

		# Initial Values
		$this->_pageheader[] .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
		$this->_pageheader[] .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"'."\n";
		$this->_pageheader[] .= '"http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">'."\n";
		$this->_pageheader[] .= "\n";
		$this->_pageheader[] .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="auto" lang="auto" dir="ltr">'."\n";
		$this->_pageheader[] .= "\n";

		$this->_app['logo'] = $this->_default['logo'];

		if (! is_null($index))
			$this->_app['urlcss'] = sprintf('%s%s',CSSDIR,$_SESSION[APPCONFIG]->getValue('appearance','stylesheet'));
		else
			$this->_app['urlcss'] = sprintf('%s%s',CSSDIR,'style.css');

		$this->index = $index;
	}

	/* Add to the HTML Header */
	public function head_add($html) {
		$this->_head[] .= $html;
	}

	/* Print out the HTML header */
	private function pageheader_print() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		# HTML prepage requirements.
		foreach ($this->_pageheader as $line)
			echo $line."\n";

		# Page Title
		echo '<head>';

		if (isset($_SESSION[APPCONFIG]))
			printf('<title>%s (%s) - %s</title>',
				$this->_app['title'],app_version(),$_SESSION[APPCONFIG]->getValue('appearance','page_title'));
		else
			printf('<title>%s - %s</title>',$this->_app['title'],app_version());

		# Style sheet.
		printf('<link type="text/css" rel="stylesheet" href="%s" />',$this->_app['urlcss']);
		printf('<link rel="shortcut icon" href="%s/favicon.ico" type="image/vnd.microsoft.icon" />',IMGDIR);

		if (defined('JSDIR')) {
			printf('<link type="text/css" rel="stylesheet" media="all" href="%sjscalendar/calendar-blue.css" title="blue" />',JSDIR);
			printf('<link type="text/css" rel="stylesheet" href="%s/phplayersmenu/layerstreemenu.css" />',JSDIR);
			echo "\n";
			printf('<script type="text/javascript" src="%spla_ajax.js"></script>',JSDIR);
			printf('<script type="text/javascript" src="%stree_hide.js"></script>',JSDIR);
			printf('<script type="text/javascript" src="%sentry_chooser.js"></script>',JSDIR);
			printf('<script type="text/javascript" src="%sto_ascii.js"></script>',JSDIR);
			printf('<script type="text/javascript" src="%sjscalendar/calendar.js"></script>',JSDIR);
			echo "\n";
		}

		# HTML head requirements.
		if (is_array($this->_head) && count ($this->_head))
			foreach ($this->_head as $line)
				echo $line."\n";

		echo '</head>';
		echo "\n";
	}

	private function head_print() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		if (isset($_SESSION[APPCONFIG]))
			$pagetitle = $_SESSION[APPCONFIG]->getValue('appearance','page_title') ? ' - '.$_SESSION[APPCONFIG]->getValue('appearance','page_title') : '';
		else
			$pagetitle = '';

		echo '<tr class="pagehead">';

		echo '<td colspan=3><div id="ajHEAD"><table width=100% border=0><tr>';
		printf('<td align="left"><a href="%s" target="_blank"><img src="%s" alt="Logo" class="logo" /></a></td>',get_href('sf'),$this->_app['logo']);

		echo '<td class="imagetop">';
		$empty = true;
		if (function_exists('cmd_control_pane'))
			foreach (cmd_control_pane('top') as $cmd => $cmddetails) {
				$cmds = explode(':',$cmd);

				if (defined('APPCONFIG') && isset($_SESSION[APPCONFIG]) && method_exists($_SESSION[APPCONFIG],'isCommandAvailable'))
					if ($_SESSION[APPCONFIG]->isCommandAvailable('all',$cmds)) {
						if ((isset($cmddetails['enable']) && trim($cmddetails['enable'])) || ! isset($cmddetails['enable'])) {
							if (! $empty)
								echo ' ';

							printf('<a %s>%s</a>',$cmddetails['link'],$cmddetails['image']);

							$empty = false;
						}
					}
			}

		if ($empty)
			echo '&nbsp;';
		echo '</td>';
		echo '</tr></table></div></td>';
		echo '</tr>';
		echo "\n";
	}

	private function control_print() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		echo '<table class="control" width=100% border=0>';
		echo '<tr><td>';

		$empty = true;
		if (function_exists('cmd_control_pane'))
			foreach (cmd_control_pane('main') as $cmd => $cmddetails) {
				$cmds = explode(':',$cmd);

				if (defined('APPCONFIG') && isset($_SESSION[APPCONFIG]) && method_exists($_SESSION[APPCONFIG],'isCommandAvailable'))
					if ($_SESSION[APPCONFIG]->isCommandAvailable('all',$cmds)) {
						if ((isset($cmddetails['enable']) && trim($cmddetails['enable'])) || ! isset($cmddetails['enable'])) {
							if (! $empty)
								echo ' | ';

							printf('<a %s>%s</a>',$cmddetails['link'],
								$_SESSION[APPCONFIG]->getValue('appearance','control_icons') ? $cmddetails['image'] : $cmddetails['title']);

							$empty = false;
						}
					}
			}

		echo '</td>';
		if ($empty)
			echo '<td>&nbsp;</td>';

		echo '</tr>';
		echo '</table>';
	}

	protected function tree() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		if (! isset($_SESSION[APPCONFIG]))
			return;

		if (is_null($this->index))
			$this->index = min(array_keys($_SESSION[APPCONFIG]->getServerList()));

		if (count($_SESSION[APPCONFIG]->getServerList()) > 1) {
			echo '<form name="server_select" action="cmd.php" method="post">';
			echo '<table class="server_select"><tr><td>';
			printf('%s:<br />%s',_('Server Select'),
				server_select_list($this->index,false,'index',true,sprintf("onchange=\"tree_unhide('index',%s)\"",$this->index)));
			echo '</td></tr></table>';
			echo '</form>';
			echo "\n\n";
		}

		foreach ($_SESSION[APPCONFIG]->getServerList() as $index => $server) {
			printf('<div id="ajSID_%s" style="display: %s">',$server->getIndex(),($server->getIndex() == $this->index) ? 'block' : 'none');
			$tree = Tree::getInstance($server->getIndex());
			$tree->draw();
			echo '</div>';
			echo "\n\n";
		}
	}

	public function block_add($side,$object) {
		if (! is_object($object))
			error(sprintf('block_add called with [%s], but it is not an object',serialize($object)));

		$this->_block[$side][] = $object;
	}

	private function block_print($side) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		if (! isset($this->_block[$side]))
			return;

		printf('<td class="%s" colspan=2>',$side);
		foreach ($this->_block[$side] as $object)
			echo $object->draw($side);
		echo '</td>';
	}

	private function sysmsg() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		if (isset($this->sysmsg)) {
			foreach ($this->sysmsg as $index => $details) {
				switch ($details['type']) {
					case 'error':
						$icon = $this->_default['sysmsg']['error'];
						break;

					case 'warn':
						$icon = $this->_default['sysmsg']['warn'];
						break;

					case 'info':
					default:
						$icon = $this->_default['sysmsg']['info'];
						break;
				}

				if (isset($details['title']))
					printf('<tr><td class="icon" rowspan=2 align="right"><img src="%s" alt="%s" /></td><td class="head" align="right">%s</td></tr>',
						$icon,$details['type'],$details['title']);

				if (isset($details['body']))
					if (is_array($details['body'])) {
						echo '<tr><td class="body">';
						foreach ($details['body'] as $line)
							printf('%s<br />',$line);
						echo '</td></tr>';

					} else
						printf('<tr><td class="body">%s</td></tr>',$details['body']);
			}
		}
	}

	private function body($raw=false) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		# Add the Session System Messages
		if (isset($_SESSION['sysmsg']) && is_array($_SESSION['sysmsg'])) {
			foreach ($_SESSION['sysmsg'] as $msg) 
				$this->setsysmsg($msg);

			unset($_SESSION['sysmsg']);
		}

		if (isset($this->sysmsg)) {
			echo '<table class="sysmsg">';
			$this->sysmsg();
			echo '</table>';
			echo "\n";
		}

		if (isset($this->_block['body']))
			foreach ($this->_block['body'] as $object)
				echo $object->draw('body',$raw);
	}

	private function footer_print() {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with ()',129,__FILE__,__LINE__,__METHOD__);

		printf('<tr class="foot"><td><small>%s</small></td><td colspan=2><div id="ajFOOT">%s</div>%s</td></tr>',
			isCompress() ? '[C]' : '&nbsp;',
			app_version(),
			sprintf('<a href="%s"><img src="%s" border="0" alt="SourceForge.net Logo" /></a>',get_href('sf'),get_href('logo')));
	}

	/**
	 * Only show a particular page frame - used by an AJAX call
	 */
	public function show($frame,$compress=false,$raw=false) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with (%s)',129,__FILE__,__LINE__,__METHOD__,$compress);

		# If the body is called via AJAX, and compression is enable, we need to compress the output
		if ($compress && ob_get_level() && isCompress()) {
			ob_end_clean();
			ob_start();
		}

		switch ($frame) {
			case 'BODY':
				$this->body($raw);
				break;

			case 'TREE':
				$this->tree();
				break;

			default:
				error(sprintf('show called with unknown frame [%s]',$frame),'error','index.php');
		}

		if ($compress && ob_get_level() && isCompress()) {
			$output = ob_get_contents();
			ob_end_clean();

			if (DEBUG_ENABLED)
				debug_log('Sending COMPRESSED output to browser[(%s),%s]',129,__FILE__,__LINE__,__METHOD__,
					strlen($output),$output);

			print gzencode($output);
		}
	}

	public function display($filter=array()) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with [%s]',129,__FILE__,__LINE__,__METHOD__,$filter);

		# Control what is displayed.
		$display = array(
			'HEAD'=>true,
			'CONTROL'=>true,
			'TREE'=>true,
			'FOOT'=>true
		);

		$display = array_merge($display,$filter);

		# HTML Header
		$this->pageheader_print();

		# Start of body
		# Page Header
		echo '<body>';
		echo "\n";
		echo '<table class="page" border=0 width=100%>';

		if ($display['HEAD'])
			$this->head_print();

		# Control Line
		if ($display['CONTROL']) {
			echo '<tr class="control"><td colspan=3>';
			echo '<div id="ajCONTROL">';
			$this->control_print();
			echo '</div></td></tr>';
			echo "\n";
		}

		# Left Block
		echo '<tr>';

		if ($display['TREE']) {
			echo '<td class="tree" colspan=2>';
			printf('<acronym title="%s"><img src="%s/plus.png" align="right" onClick="if (document.getElementById(\'ajTREE\').style.display == \'none\') { document.getElementById(\'ajTREE\').style.display = \'block\' } else { document.getElementById(\'ajTREE\').style.display = \'none\' };"/></acronym>',_('Hide/Unhide the tree'),IMGDIR);
			echo '<div id="ajTREE">';
			$this->tree();
			echo '</div>';
			echo '</td>';
		}

		echo '<td class="body" width=80%>';
		echo '<div id="ajBODY">';
		echo "\n";
		$this->body();
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo "\n";

		# Page Footer
		if ($display['FOOT'])
			$this->footer_print();

		# Finish HTML
		echo '</table>';
		echo '</body>';
		echo '</html>';

		# compress output
		if (ob_get_level() && isCompress()) {
			$output = ob_get_contents();
			ob_end_clean();

			if (DEBUG_ENABLED)
				debug_log('Sending COMPRESSED output to browser[(%s),%s]',129,__FILE__,__LINE__,__METHOD__,
					strlen($output),$output);

			print gzencode($output);
		}
	}

	public function setsysmsg($data) {
		if (defined('DEBUG_ENABLED') && DEBUG_ENABLED)
			debug_log('Entered with [%s]',129,__FILE__,__LINE__,__METHOD__,$data);

		if (! is_array($data))
			return;

		if (isset($this->sysmsg))
			$msgnum = count($this->sysmsg) + 1;
		else
			$msgnum = 1;

		foreach (array('title','body','type') as $index)
			if (isset($data[$index]))
				$this->sysmsg[$msgnum][$index] = $data[$index];
	}
}

/**
 * This class draws a block.
 *
 * @package phpLDAPadmin
 * @subpackage Page
 */
class block {
	private $title;
	private $body;
	private $foot;

	public function __construct() {
	}

	public function setTitle($html) {
		$this->title = $html;
	}

	public function setBody($html) {
		$this->body = $html;
	}

	public function setFooter($html) {
		$this->foot = $html;
	}

	public function draw($side,$raw=false) {
		$output = '';

		if ($raw)
			$output .= $this->body;

		else {
			$output .= sprintf('<table class="%s">',$side);

			if (isset($this->title))
				$output .= sprintf('<tr><td class="head">%s</td></tr>',$this->title);

			if (isset($this->body))
				$output .= sprintf('<tr><td>%s</td></tr>',$this->body);

			if (isset($this->footer))
				$output .= sprintf('<tr><td class="foot">%s</td></tr>',$this->foot);

			$output .= '</table>';
		}

		return $output;
	}
}
?>