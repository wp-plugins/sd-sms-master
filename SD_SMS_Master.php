<?php
/*
Plugin Name: SD SMS Master
Plugin URI: https://it.sverigedemokraterna.se/program/sms-master/
Description: Control an SD SMS Master server. 
Version: 1.2
Author: Sverigedemokraterna IT
Author URI: https://it.sverigedemokraterna.se
Author Email: it@sverigedemokraterna.se
License: GPLv3
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER[ 'PHP_SELF' ])) { die('You are not allowed to call this page directly.'); }

require_once( 'SD_SMS_Master_Base.php' );

/**
	@brief		SMS Master plugin for controlling a SMS master server.
	
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_SMS_Master
	extends SD_SMS_Master_Base
{
	/**
		Local options.
		
		- @b server_url URL of master to which to connect.
		
		@var	$site_options
	**/
	protected $local_options = array(
		'server_url' => 'http://my.sms.master',
	);
	
	/**
		@brief		How many things to show per page.
		@var		$page_limit
	**/
	protected $page_limit = 50;
	
	/**
		@brief		The path where SD SMS Master expects the common include to be.
		@var		$sms_master_include_common
	**/
	public static $sms_master_include_common = 'git/lib/sms_master_include_common.php';
	
	/**
		@brief		Location of the lib git repository.
		@var		$lib_git
	**/
	public static $lib_git = 'https://github.com/sverigedemokraterna-it/sms_master.git';
	
	/**
		@brief		Inherited constructor.
	**/
	public function __construct()
	{
		parent::__construct( __FILE__ );
		add_action( 'admin_menu',									array( $this, 'admin_menu') );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		The admin menu adds the Q&A menu to the admin panel.
	**/
	public function admin_menu( $menus )
	{
		$this->load_language();
		add_menu_page(
			$this->_('SD SMS Master'),
			$this->_('SD SMS Master'),
			'read',
			'sd_sms_master',
			array( &$this, 'admin_overview' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Orders'),
			$this->_('Orders'),
			'read',
			'sd_sms_master_orders',
			array( &$this, 'admin_orders' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Phones'),
			$this->_('Phones'),
			'read',
			'sd_sms_master_phones',
			array( &$this, 'admin_phones' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Settings'),
			$this->_('Settings'),
			'read',
			'sd_sms_master_settings',
			array( &$this, 'admin_settings' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Slaves'),
			$this->_('Slaves'),
			'read',
			'sd_sms_master_slaves',
			array( &$this, 'admin_slaves' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Statistics'),
			$this->_('Statistics'),
			'read',
			'sd_sms_master_statistics',
			array( &$this, 'admin_statistics' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Tools'),
			$this->_('Tools'),
			'read',
			'sd_sms_master_tools',
			array( &$this, 'admin_tools' ),
			null
		);
		
		$this->add_submenu_page(
			'sd_sms_master',
			$this->_('Users'),
			$this->_('Users'),
			'read',
			'sd_sms_master_users',
			array( &$this, 'admin_users' ),
			null
		);
		
		$this->add_submenu_pages();
	}
	
	/**
		@brief		Allow the user to download the latest version of the library.
	**/
	public function admin_download()
	{
		$common = '../' . $this->paths['path_from_base_directory'] . '/' . SD_SMS_Master::$sms_master_include_common;
		$lib = dirname( $common );
		$git = dirname( $lib );
		$root = dirname( $git );
		$old_dir = getcwd();
		
		$rv = '';
		$form = $this->form();
		$rv .= $form->start();
		
		if ( isset( $_POST[ 'clone' ] ) )
		{
			chdir( $root );
			shell_exec( 'git clone -v ' . self::$lib_git . ' git 2>&1' );
			$this->message_( 'The lib directory has been prepared. Click on the menu again.' );
			chdir( $old_dir );
		}
		
		if ( isset( $_POST[ 'git_pull' ] ) )
		{
			chdir( $git );
			$this->message( shell_exec( 'git pull 2>&1' ) );
			chdir( $old_dir );
		}
		
		if ( isset( $_POST[ 'unlink' ] ) )
		{
			exec( 'rm -rf "' . $git . '"' );
			$this->message( 'The lib directory has been removed.' );
		}
		
		if ( is_dir( $git ) )
		{
			$rv .= $this->p_( 'The SMS Master git is currently installed. Use the buttons below to update or completely remove the local copy.' );
			
			$inputs = array(
				'git_pull' => array(
					'css_class' => 'button-secondary',
					'type' => 'submit',
					'value' => $this->_( 'Update git using git pull' ),
				),
				'unlink' => array(
					'css_class' => 'button-secondary',
					'type' => 'submit',
					'value' => $this->_( 'Delete git directory completely' ),
				),
			);
			
			if ( ! is_readable( $common ) )
				unset( $inputs[ 'git_pull' ] );
			
			$rv .= $this->display_form_table( $inputs );
			
			$rv .= '<h4>' . $this->_( 'Latest git log' ) . '</h4>';
			chdir( $git );
			$rv .= $this->p( shell_exec( 'git log -n 5' ) );
			chdir( $old_dir );
		}
		else
		{
			if ( ! is_readable( $common ) )
			{
				$rv .= $this->p_( 'The git directory must contain the SD SMS Master package. If the webserver has git installed, try using the button below to automatically download the latest version from the git repository.' );
				$rv .= $this->p_( 'If not, you will have to clone the repository yourself.' );
				$rv .= $this->p_( 'After installation this text can be found in the Tools submenu.' );
				
				$inputs = array(
					'clone' => array(
						'css_class' => 'button-primary',
						'type' => 'submit',
						'value' => $this->_( 'Try to download using git' ),
					),
				);
				$rv .= $this->display_form_table( $inputs );
			}
		}
		
		$rv .= $this->p_( 'The git repository can be found at: %s', self::$lib_git );
				
		$rv .= $form->stop();
		
		return $rv;
	}
	
	/**
		@brief		Overview.
	**/
	public function admin_overview()
	{
		$rv = '';
		
		$common = '../' . $this->paths['path_from_base_directory'] . '/' . SD_SMS_Master::$sms_master_include_common;
		if ( ! is_readable( $common ) )
		{
			$rv = $this->admin_download();
			$rv .= $this->wrap( $rv, $this->_( 'Initial installation' ) );
			return;
		}
		
		if ( isset( $_POST[ 'switch' ] ) )
		{
			$enable = key( $_POST[ 'switch' ] ) === 'enable';
			try
			{
				$req = new SMS_Master_Switch_Request();
				$req->enabled = $enable;
				$result = $this->send_request( $req );
				if ( $result->enabled == true )
					$this->message_( 'System is now on.' );
				else
					$this->message_( 'System is now off.' );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			}
		}
		
		try
		{
			$result = $this->send_request( new SMS_Master_Status_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$enabled = ( $result->enabled == true );
		$switch = array(
			'css_class' => ( $enabled ? 'button-secondary' : 'button-primary' ),
			'name' => ( $enabled ? 'disable' : 'enable' ),
			'nameprefix' => '[switch]',
			'type' => 'submit',
			'value' => ( $enabled ? $this->_( 'Switch off' ) : $this->_( 'Switch on' ) ),
		);
		
		$url_view_stats = add_query_arg( array(
			'page' => 'sd_sms_master_statistics',
		) );
		
		$form = $this->form();
		$rv .= $form->start();
		$rv .= '
			<table class="widefat">
				<caption>' . $this->_( 'Overview' ) . '</caption>
				<tr>
					<th>' . $this->_( 'Status' ) . '</th>
					<td>' . ( $result->ok === true ? $this->_( 'OK' ) : $result->ok ) . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Enabled' ) . '</th>
					<td>
						' . $this->p( $enabled ? $this->_( 'System is currently switched on.' ) : $this->_( 'System is currently switched off.' ) ) . '
						' . $this->p( $form->make_input( $switch ) ) . '
					</td>
				</tr>
				<tr>
					<th><a href="' . $url_view_stats . '" title="' . $this->_( 'View all sent SMS statistics' ) . '">' . $this->_( 'Sent SMSs' ) . '</a></th>
					<td>' . $result->stats->stat_sent_sms . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Completed orders' ) . '</th>
					<td>' . $result->completed_orders. '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Uncompleted orders' ) . '</th>
					<td>' . $result->uncompleted_orders. '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Users' ) . '</th>
					<td>' . $result->user_count . '</td>
				</tr>
			</table>
		';
		$rv .= $form->stop();
		
		foreach( $result->slaves as $slave )
		{
			$rv .= sprintf( '<h3>%s %s:%s</h3>', $this->_( 'Slave' ), $slave->hostname, $slave->port );
			
			if ( count( $slave->phones ) < 1 )
				$rv .= $this->p_( 'This slave has no connected phones.' );
			
			$o = new stdClass();
			$o->display_sent_stats = true;
			$o->display_edit_link = true;
			foreach( $slave->phones as $phone )
			{
				$rv .= $this->display_phone( $phone, $o );
			}
		}
		
		// Display the config
		$rv .= '<h3>' . $this->_( 'Configuration' ) . '</h3>';
		$rv .= '
			<table class="widefat">
				<caption>' . $this->_( 'Overview' ) . '</caption>
				<thead>
					<tr>
						<th>' . $this->_( 'Key' ) . '</th>
						<th>' . $this->_( 'Value' ) . '</th>
					</tr>
				</thead>
				<tbody>
		';
		ksort( $result->config );
		foreach( $result->config as $key => $value )
		{
			if ( is_array( $value ) )
				$value = '<pre>' . var_export( $value, true ) . '</pre>';
			$rv .= '
					<tr>
						<th>' . $key . '</th>
						<th>' . $this->p( $value ) . '</th>
					</tr>
			';
		}
		$rv .= '
				</tbody>
			</table>
		';
		
		$rv = $this->wrap( $rv, $this->_( 'Overview' ) );
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Orders
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Order overview with tabs.
	**/
	public function admin_orders()
	{
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data[ 'default' ] = 'admin_order_overview';

		$tab_data[ 'tabs' ][ 'admin_order_new' ] = $this->_( 'New order' );
		$tab_data[ 'functions' ][ 'admin_order_new' ] = 'admin_order_new';

		$tab_data[ 'tabs' ][ 'admin_orders_uncompleted' ] = $this->_( 'Uncompleted orders' );
		$tab_data[ 'functions' ][ 'admin_orders_uncompleted' ] = 'admin_orders_uncompleted';

		$tab_data[ 'tabs' ][ 'admin_orders_completed' ] = $this->_( 'Completed orders' );
		$tab_data[ 'functions' ][ 'admin_orders_completed' ] = 'admin_orders_completed';
		
		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'view' )
		{
			$tab_data[ 'tabs' ][ 'view' ] = $this->_( 'Order details' );
			$tab_data[ 'functions' ][ 'view' ] = 'admin_order_view';
		}
		
		$this->tabs($tab_data);
	}
	
	public function admin_order_new()
	{
		$form = $this->form();
		$rv = '';
		
		$inputs = array(
			'text' => array(
				'label' => $this->_( 'Text to send' ),
				'maxlength' => 160,
				'size' => 40,
				'type' => 'text',
			),
			'email_addresses' => array(
				'label' => $this->_( 'Comma separated e-mail addresses that will receive the report. Leave empty for no report.' ),
				'cols' => 20,
				'label' => $this->_( 'E-mail addresses for report.' ),
				'rows' => 2,
				'type' => 'textarea',
				'validation' => array( 'empty' => true ),
			),
			'priority' => array(
				'description' => $this->_( 'Higher priority gets sent first. Negative is treated as bulk.' ),
				'label' => $this->_( 'Priority' ),
				'maxlength' => 3,
				'size' => 3,
				'type' => 'text',
				'value' => 0,
			),
			'numbers' => array(
				'cols' => 10,
				'description' => $this->_( 'Numbers to which to send the text. One number per line.' ),
				'label' => $this->_( 'Numbers' ),
				'rows' => 5,
				'type' => 'textarea',
			),
			'censor_text' => array(
				'description' => $this->_( 'Censor the text after completion?' ),
				'label' => $this->_( 'Censor text' ),
				'type' => 'checkbox',
			),
			'censor_numbers' => array(
				'description' => $this->_( 'Censor the numbers after completion?' ),
				'label' => $this->_( 'Censor numbers' ),
				'type' => 'checkbox',
			),
			'censor_report' => array(
				'description' => $this->_( 'Censor the report after completion?' ),
				'label' => $this->_( 'Censor report' ),
				'type' => 'checkbox',
			),
			'create_order' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Create order' ),
			),
		);
		
		if ( isset( $_POST[ 'create_order' ] ) )
		{
			$errors = false;
			$text = trim( $_POST[ 'text' ] );
			if ( strlen( $text ) > 160 )
				$this->error_( 'The text is too long. You have written %s characters and %s is the maximum! Umlauts count as two characters.', strlen( $text ), 160 ) || $errors = true;
			
			$numbers = array();
			$numbers_text = $_POST[ 'numbers' ];
			$numbers_text = str_replace( "\r", '', $numbers_text );
			$numbers_text = str_replace( ' ', '', $numbers_text );
			$numbers_text = str_replace( '-', '', $numbers_text );
			$lines = array_filter( explode( "\n", $numbers_text ) );
			foreach( $lines as $line )
				$numbers[ $line ] = $line;
			if ( count( $numbers ) < 1 )
			{
				$this->error_( 'The text must be sent to at least one number.' ) || $errors = true;
			}
			
			if ( ! $errors )
			{
				try
				{
					$req = new SMS_Master_Create_Order_Request();
					$req->options->numbers = $numbers;
					$req->options->priority = intval( $_POST[ 'priority' ] );
					$req->options->censor_numbers = isset( $_POST[ 'censor_numbers' ] );
					$req->options->censor_report = isset( $_POST[ 'censor_report' ] );
					$req->options->censor_text = isset( $_POST[ 'censor_text' ] );
					$req->options->text = $text;
					
					// Email addresses?
					$emails = $_POST[ 'email_addresses' ];
					$emails = str_replace( "\r", '', $emails );
					$emails = str_replace( ' ', '', $emails );
					$emails = str_replace( '-', '', $emails );
					$lines = array_filter( explode( "\n", $emails ) );
					$emails = array();
					foreach( $lines as $email )
						if ( $this->is_email( $email ) )
							$emails[] = $email;
					if ( count( $emails ) > 0 )
						$req->options->email_addresses = implode( ',', $emails );
					
					$result = $this->send_request( $req );
					$order = $result->order;
					$url = add_query_arg( array(
						'id' => $order->order_id,
						'tab' => 'view',
					) );
					$this->message_( 'Order created! %sView the order%s.',
						'<a href="' . $url . '">',
						'</a>'
					);
				}
				catch( SMS_Master_Exception $e )
				{
					echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
					return;
				}
				
			}
		}
		
		foreach( $inputs as $key => $input )
		{
			$inputs[ $key ][ 'name' ] = $key;
			if ( isset( $_POST[$key] ) )
				$form->use_post_value( $inputs[$key], $_POST );
		}

		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_order_view()
	{
		$id = intval( $_GET[ 'id' ] );
		
		if ( isset( $_POST[ 'cb' ] ) )
		{
			$req = null;
			$verb = '';
			switch( $_POST[ 'mass' ] )
			{
				case 'delete':
					$req = new SMS_Master_Delete_Number_Request();
					$verb = $this->_( 'deleted' );
					break;
				case 'reset_failures':
					$req = new SMS_Master_Reset_Number_Failures_Request();
					$verb = $this->_( 'now has zero failures' );
					break;
				case 'sent':
					$req = new SMS_Master_Mark_Number_Sent_Request();
					$verb = $this->_( 'marked as sent' );
					break;
				case 'touch':
					$req = new SMS_Master_Touch_Number_Request();
					$verb = $this->_( 'touched' );
					break;
				case 'unsent':
					$req = new SMS_Master_Mark_Number_Unsent_Request();
					$verb = $this->_( 'marked as unsent' );
					break;
				case 'untouch':
					$req = new SMS_Master_Untouch_Number_Request();
					$verb = $this->_( 'marked as ready to send' );
					break;
			}
			if ( $req !== null )
			{
				$numbers = array_keys( $_POST[ 'cb' ] );
				foreach( $numbers as $number )
				{
					$req->number_id = $number;
					try
					{
						$result = $this->send_request( $req );
						$this->message_( 'Number %s has been %s.', $number, $verb );
					}
					catch( SMS_Master_Exception $e )
					{
						echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
					}
				}
			}
		}
		
		if ( isset( $_POST[ 'complete_order' ] ) )
		{
			try
			{
				// We need to get the order's uuid
				$req = new SMS_Master_Get_Order_Request();
				$req->order_id = $id;
				$result = $this->send_request( $req );
				// Here it is!
				$uuid = $result->order->order_uuid;
				$req = new SMS_Master_Complete_Order_Request();
				$req->order_uuid = $uuid;
				$result = $this->send_request( $req );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
				return;
			}
			$this->message_( 'Order has been marked as complete!' );
		}

		try
		{
			$req = new SMS_Master_Get_Order_Request();
			$req->order_id = $id;
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		if ( isset( $_POST[ 'delete_order' ] ) )
		{
			try
			{
				$req = new SMS_Master_Delete_Order_Request();
				$req->order_id = $id;
				$result = $this->send_request( $req );
				$this->message_( 'Order deleted!' );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			}
			// We return if the req was ok... and if the request failed.
			return;
		}
		$form = $this->form();
		$order = $result->order;
		$completed = $order->datetime_completed != null;		// Conv
		
		$numbers = array_filter( explode( ",", $order->numbers ) );
		array_unshift( $numbers, $order->number_count );
		$numbers = implode( "<br />\n", $numbers );
		
		$rv = '';
		
		$rv .= $form->start();
		$rv .= '
			<table class="widefat">
				<tr>
					<th>' . $this->_( 'Order ID' ) . '</th>
					<td>' . $order->order_id . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Order UUID' ) . '</th>
					<td>' . $order->order_uuid . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Created' ) . '</th>
					<td>' . $order->datetime_created . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Completed' ) . '</th>
					<td>' . $order->datetime_completed . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Numbers' ) . '</th>
					<td>' . $numbers . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Priority' ) . '</th>
					<td>' . $order->priority . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Text' ) . '</th>
					<td>' . $order->text . '</td>
				</tr>
		';
		
		if ( $order->error_log != '' )
			$rv .= '
				<tr>
					<th>' . $this->_( 'Error log' ) . '</th>
					<td>' . $this->p( $order->error_log ) . '</td>
				</tr>
			';
		
		if ( $order->email_addresses != '' )
		{
			$rv .= '
				<tr>
					<th>' . $this->_( 'E-mail addresses for report' ) . '</th>
					<td>' . $order->email_addresses . '</td>
				</tr>
			';
			if ( $completed )
			{
				$text = $order->email_report_text;
				$text = base64_decode( $text );
				$text = unserialize( $text );
				$text = $this->p( $text[ 'body' ] );
				$rv .= '
					<tr>
						<th>' . $this->_( 'Email report' ) . '</th>
						<td>' . $text . '</td>
					</tr>
					<tr>
						<th>' . $this->_( 'Email report sent' ) . '</th>
						<td>' . $order->email_report_sent . '</td>
					</tr>
				';
			}
		}
		
		$rv .= '</table>';
		
		if ( isset( $result->order_numbers ) )
		{
			$mass_input = array(
				'type' => 'select',
				'name' => 'mass',
				'label' => $this->_( 'With the selected rows do:' ),
				'options' => array(
					'' => $this->_('Nothing'),
					'delete'			=> $this->_('Delete'),
					'untouch'			=> $this->_('Mark as ready to send'),
					'touch'				=> $this->_('Mark as not ready to send'),
					'sent'				=> $this->_('Mark as sent'),
					'unsent'			=> $this->_('Mark as unsent'),
					'reset_failures'	=> $this->_('Reset failure counter'),
				),
			);
			
			$mass_input_submit = array(
				'type' => 'submit',
				'name' => 'mass_submit',
				'value' => $this->_( 'Apply' ),
				'css_class' => 'button-primary',
			);
			
			$rv .= '
				<p>&nbsp;</p>
				<div style="float: left; width: 50%;">
					' . $form->make_label( $mass_input ) . '
					' . $form->make_input( $mass_input ) . '
					' . $form->make_input( $mass_input_submit ) . '
				</div>
			';
			
			$rv .= '<table class="widefat">
				<caption>' . $this->_( 'Number details' ) . '</caption>
				<thead>
					<tr>
						' . $this->check_column() . '
						<th>' . $this->_( 'Number' ) . '</th>
						<th>' . $this->_( 'Failures' ) . '</th>
						<th>' . $this->_( 'Touched' ) . '</th>
						<th>' . $this->_( 'Sent' ) . '</th>
					</tr>
				</thead>
				<tbody>
			';
			
			foreach( $result->order_numbers as $number )
			{
				$options = array(
					'name' => $number->number_id,
					'checked' => isset( $_POST[ 'cb' ][ $number->number_id ]  ),
				);
				
				$rv .= '
					<tr>
						' . $this->check_column_body( $options ) . '
						<td>' . $number->number . '</td>
						<td>' . $number->failures . '</td>
						<td>' . $number->touched . '</td>
						<td>' . $number->sent . '</td>
					</tr>
				';
			}
			$rv .= '</tbody></table>';
		}
		$rv .= $form->stop();

		if ( $order->datetime_completed === null )
		{
			$inputs = array(
				'complete_order' => array(
					'css_class' => 'button-secondary',
					'type' => 'submit',
					'value' => $this->_( 'Mark the order as complete' ),
				),
			);
			$rv .= $form->start();
			$rv .= $this->display_form_table( $inputs );
			$rv .= $form->stop();
		}
		
		$inputs = array(
			'delete_order' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Delete the order' ),
			),
		);
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_orders_completed()
	{
		try
		{
			$req = new SMS_Master_List_Completed_Orders_Request();
			$req->options = array(
				'limit' => $this->page_limit,
				'page' => ( isset( $_GET[ 'paged' ] ) ? intval( $_GET[ 'paged' ] ) : 1 ),
			);
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		echo $this->display_orders( array(
			'count' => $result->count,
			'orders' => $result->orders,
			'type' => 'completed',
		) );
	}
	
	public function admin_orders_uncompleted()
	{
		try
		{
			$req = new SMS_Master_List_Uncompleted_Orders_Request();
			$req->options = array(
				'limit' => $this->page_limit,
				'page' => ( isset( $_GET[ 'paged' ] ) ? intval( $_GET[ 'paged' ] ) : 1 ),
			);
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$rv = $this->display_orders( array(
			'count' => $result->count,
			'orders' => $result->orders,
			'type' => 'uncompleted',
		) );
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Phones
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Phone overview.
	**/
	public function admin_phones()
	{
		$tab_data = array(
			'default' => 'admin_phone_overview',
			'tabs'		=>	array(),
			'functions' =>	array(),
		);

		$tab_data[ 'tabs' ][ 'admin_phone_overview' ] = $this->_( 'Phone overview' );
		$tab_data[ 'functions' ][ 'admin_phone_overview' ] = 'admin_phone_overview';

		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'delete' )
		{		
			$tab_data[ 'tabs' ][ 'delete' ] = $this->_( 'Delete phone' );
			$tab_data[ 'functions' ][ 'delete' ] = 'admin_delete_phone';
		}
		
		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'edit' )
		{		
			$tab_data[ 'tabs' ][ 'edit' ] = $this->_( 'Edit phone' );
			$tab_data[ 'functions' ][ 'edit' ] = 'admin_edit_phone';
		}

		$this->tabs($tab_data);
	}
	
	public function admin_delete_phone()
	{
		$id = intval( $_GET[ 'id' ] );
		
		try
		{
			$req = new SMS_Master_Get_Phone_Request();
			$req->phone_id = $id;
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$form = $this->form();
		$phone = $result->phone;
		$rv = '';
		
		if ( isset( $_POST[ 'delete' ] ) )
		{
			try
			{
				$req = new SMS_Master_Delete_Phone_Request();
				$req->phone_id = $id;
				$result = $this->send_request( $req );
				$this->message_( 'Phone has been deleted! You can now return to the overview.' );
				return;
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
				return;
			}
		}
		
		$inputs = array(
			'delete' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Delete phone' ),
			),
		);
		
		$rv .= $this->p_( 'Phone description for phone %s on host %s:',
			$phone->phone_index,
			$phone->hostname . ':' . $phone->port
		);
		$rv .= $this->p( '<blockquote>' . $phone->phone_description . '</blockquote>' );
		$rv .= $this->p_( 'Do you wish to permanently delete this phone?' );
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_edit_phone()
	{
		$id = intval( $_GET[ 'id' ] );
		
		try
		{
			$req = new SMS_Master_Get_Phone_Request();
			$req->phone_id = $id;
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$form = $this->form();
		$phone = $result->phone;
		$rv = '';
		
		if ( isset( $_POST[ 'clean_phone' ] ) )
		{
			try
			{
				$req = new SMS_Master_Clean_Phone_Request();
				$req->phone_id = $id;
				$result = $this->send_request( $req );
				$this->message( $this->p( implode( "\n", $result->output ) ) );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			}
		}
		
		if ( isset( $_POST[ 'identify' ] ) )
		{
			try
			{
				$req = new SMS_Master_Identify_Phone_Request();
				$req->phone_id = $id;
				$result = $this->send_request( $req );
				$this->message( $this->p( implode( "\n", $result->output ) ) );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			}
		}
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			try
			{
				$req = new SMS_Master_Update_Phone_Request();
				$req->phone = (object) array(
					'phone_id' => $id,
					'enabled' => isset( $_POST[ 'enabled' ] ),
					'clean' => isset( $_POST[ 'clean' ] ),
					'phone_description' => $this->check_plain( $_POST[ 'phone_description' ] ),
					'phone_index' => intval( $_POST[ 'phone_index' ] ),
				);
				$result = $this->send_request( $req );
				$phone = $result->phone;
				$this->message_( 'Phone updated.' );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			}
		}
		
		$inputs = array(
			'phone_description' => array(
				'description' => $this->_( 'A short description of the phone.' ),
				'label' => $this->_( 'Description' ),
				'maxlength' => 128,
				'size' => 40,
				'type' => 'text',
				'value' => $phone->phone_description,
			),
			'enabled' => array(
				'description' => $this->_( 'Phone is enabled for sending.' ),
				'checked' => $phone->enabled,
				'label' => $this->_( 'Enabled' ),
				'type' => 'checkbox',
			),
			'clean' => array(
				'description' => $this->_( 'Should the phone be cleaned periodically?' ),
				'checked' => $phone->clean,
				'label' => $this->_( 'Clean' ),
				'type' => 'checkbox',
			),
			'phone_index' => array(
				'description' => $this->_( 'Index of phone configuration in the gnokii config file.' ),
				'label' => $this->_( 'Phone index' ),
				'size' => 2,
				'type' => 'text',
				'value' => $phone->phone_index,
				'validation' => array( 'valuemin' => 1, 'valuemax' => 99 ),
			),
			'touched' => array(
				'description' => $this->_( 'When the phone was last touched by the master.' ),
				'label' => $this->_( 'Touched' ),
				'readonly' => true,
				'size' => 15,
				'type' => 'text',
				'value' => $phone->touched,
				'validation' => array( 'empty' => true ),
			),
			'touched_successfully' => array(
				'description' => $this->_( 'When the phone was last used.' ),
				'label' => $this->_( 'Used' ),
				'readonly' => true,
				'size' => 15,
				'type' => 'text',
				'value' => $phone->touched_successfully,
				'validation' => array( 'empty' => true ),
			),
			'update' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Update phone settings' ),
			),
			'clean_phone' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Clean phone' ),
			),
			'identify' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Identify phone' ),
			),
		);
		
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_new_phone()
	{
		$form = $this->form();
		$rv = '';
		
		// Get a list of all slaves. Done easiest by using a status.
		try
		{
			$result = $this->send_request( new SMS_Master_Status_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			return;
		}
		
		if ( count( $result->slaves ) < 1 )
		{
			echo $this->error( 'Phones can only be created if there is at least one slave. Create a slave first.' );
			return;
		}
		
		$inputs = array(
			'slave_id' => array(
				'label' => $this->_( 'Create for slave' ),
				'options' => array(),
				'type' => 'select',
			),
			'create' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Create a new phone' ),
			),
		);
		foreach( $result->slaves as $slave )
			$inputs[ 'slave_id' ][ 'options' ][ $slave->slave_id ] = sprintf( '%s:%s - %s', $slave->hostname, $slave->port, $slave->slave_description );

		if ( isset( $_POST[ 'create' ] ) )
		{
			// Create the phone
			try
			{
				$req = new SMS_Master_Create_Phone_Request();
				$req->options = (object) array(
					'slave_id' => $_POST[ 'slave_id' ],
					'phone_description' => $this->_( 'Phone created %s', $this->now() ),
				);
				$result = $this->send_request( $req );
				$phone_id = $result->phone_id;
				
				$url = add_query_arg( array(
					'tab' => 'edit',
					'id' => $phone_id,
				) );
				$url = sprintf( '<a href="%s" title="%s">', $url, $this->_( 'Edit this phone' ) );
				$rv .= $this->message_( '%sA new slave%s has been created!', $url, '</a>' );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}

		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		return $rv;
	}
	
	public function admin_phone_overview()
	{
		$create = $this->admin_new_phone();
		
		try
		{
			// Get a status, which also lists all slaves and phones.
			$result = $this->send_request( new SMS_Master_Status_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings.' );
			return;
		}
		$rv = '';
		
		foreach( $result->slaves as $slave )
		{
			if ( count( $slave->phones ) < 1 )
				continue;
			$rv .= sprintf( '<h3>%s:%s</h3>', $slave->hostname, $slave->port );
			
			foreach( $slave->phones as $phone )
			{
				$o = new stdClass();
				$o->display_delete_link = true;
				$o->display_edit_link = true;
				$rv .= $this->display_phone( $phone, $o );
			}
		}
		
		$rv .= '<h3>' . $this->_( 'Create a new phone' ) . '</h3>';
		$rv .= $this->_( 'Create a new phone by selecting a slave under which to place the phone.' );
		$rv .= $create;
		echo $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Settings
	// --------------------------------------------------------------------------------------------	
	
	/**
		@brief		Settings.
	**/
	public function admin_settings()
	{
		$form = $this->form();
		$rv = '';
		
		$inputs = array(
			'test' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Test settings' ),
			),
			'server_url' => array(
				'description' => $this->_( 'The URL to the SD SMS Master server.' ),
				'label' => $this->_( 'Server URL' ),
				'maxlength' => 100,
				'setting' => true,
				'size' => 50,
				'type' => 'text',
				'value' => $this->get_local_option( 'server_url' ),
			),
			'server_public_key' => array(
				'description' => $this->_( "The server's public key." ),
				'label' => $this->_( 'Server public key' ),
				'cols' => 80,
				'rows' => 10,
				'setting' => true,
				'type' => 'textarea',
				'value' => $this->get_local_option( 'server_public_key' ),
			),
			'client_public_key' => array(
				'description' => $this->_( 'The public key the plugin uses to contact the server.' ),
				'label' => $this->_( 'Client public key' ),
				'cols' => 80,
				'rows' => 10,
				'setting' => true,
				'type' => 'textarea',
				'value' => $this->get_local_option( 'client_public_key' ),
			),
			'client_private_key' => array(
				'description' => $this->_( 'The private key the plugin uses to contact the server.' ),
				'label' => $this->_( 'Client private key' ),
				'cols' => 80,
				'rows' => 10,
				'setting' => true,
				'type' => 'textarea',
				'value' => $this->get_local_option( 'client_private_key' ),
			),
			'save' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Save settings' ),
			),
			'install' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Install to SMS Master' ),
			),
		);
		
		foreach( $inputs as $key => $input )
		{
			if ( ! isset( $input[ 'setting' ] ) )
				continue;
			$inputs[ $key ][ 'name' ] = $key;
			if ( isset( $_POST[$key] ) )
				$form->use_post_value( $inputs[$key], $_POST );
		}
		
		if ( $inputs[ 'client_public_key' ][ 'value' ] == '' )
			unset( $inputs[ 'install' ] );
		
		if ( isset( $_POST[ 'save' ] ) )
		{
			$url = trim( $_POST[ 'server_url' ], '/ ' );
			$url .= '/';
			$this->update_local_option( 'server_url', $url );
			$this->update_local_option( 'server_public_key', trim( $_POST[ 'server_public_key' ] ) );
			$this->update_local_option( 'client_public_key', trim( $_POST[ 'client_public_key' ] ) );
			$this->update_local_option( 'client_private_key', trim( $_POST[ 'client_private_key' ] ) );
			$rv .= $this->message_( 'Settings saved!' );
		}
		
		if ( isset( $_POST[ 'test' ] ) )
		{
			try
			{
				$result = $this->send_request( new SMS_Master_Test_Connection_Request() );
				$rv .= $this->message_( 'Connection OK! %s', date( 'Y-m-d H:i:s', $result->connection_timestamp ) );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}
		
		if ( isset( $_POST[ 'install' ] ) )
		{
			try
			{
				$o = new stdClass();
				$o->container = new SMS_Master_Install_Container;
				$o->container->client_public_key = $inputs[ 'client_public_key' ][ 'value' ];
				$o->request = new SMS_Master_Install_Request;
				$result = $this->send_request( $o );
				$rv .= $this->message_( 'Admin user %s has been installed on the SMS Master.', $result->user->user_id );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}
				
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $this->wrap( $rv, $this->_( 'Settings' ) );
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Slaves
	// --------------------------------------------------------------------------------------------
	
	public function admin_slaves()
	{
		$tab_data = array(
			'default'	=> 'admin_slave_overview',
			'functions' =>	array(),
			'tabs'		=>	array(),
		);
				
		$tab_data[ 'tabs' ][ 'admin_slave_overview' ] = $this->_( 'Slave overview' );
		$tab_data[ 'functions' ][ 'admin_slave_overview' ] = 'admin_slave_overview';
		
		if ( isset( $_GET[ 'tab' ] ) )
		{
			switch( $_GET[ 'tab' ] )
			{
				case 'delete':
					$tab_data[ 'tabs' ][ 'delete' ] = $this->_( 'Delete slave' );
					$tab_data[ 'functions' ][ 'delete' ] = 'admin_delete_slave';
					break;
				case 'edit':
					$tab_data[ 'tabs' ][ 'edit' ] = $this->_( 'Edit slave' );
					$tab_data[ 'functions' ][ 'edit' ] = 'admin_edit_slave';
					break;
			}
		}
		
		$this->tabs($tab_data);
	}
	
	public function admin_delete_slave()
	{
		$form = $this->form();
		$id = $_GET[ 'id' ];
		$rv = '';
		
		try
		{
			$req = new SMS_Master_Get_Slave_Request();
			$req->slave_id = $id;
			$result = $this->send_request( $req );
			$slave = $result->slave;
		}
		catch( SMS_Master_Exception $e )
		{
			$this->error_( 'No connection! %s', $e->get_error_message() );
			return;
		}
		
		if ( isset( $_POST[ 'delete' ] ) )
		{
			try
			{
				$req = new SMS_Master_Delete_Slave_Request();
				$req->slave_id = $id;
				$result = $this->send_request( $req );
				
				echo $this->message_( 'The slave has been deleted. Return to the overview.' );
				return;
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}
		
		$inputs = array(
			'delete' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Delete the slave: %s', $slave->slave_description ),
			),
		);
		
		$rv .= $this->_( 'To delete the slave, precis the button below. The slave and all slave phones will be permanently deleted.' );
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_edit_slave()
	{
		$form = $this->form();
		$id = $_GET[ 'id' ];
		$rv = '';
		
		if ( isset( $_POST[ 'identify' ] ) )
		{
			try
			{
				$req = new SMS_Master_Identify_Slave_Request();
				$req->slave_id = $id;
				$result = $this->send_request( $req );
				$output = $result->output->output;
				
				if ( $result->output->code != 0 )
					$rv .= $this->error_( "SSH connection error %s.", $result->output->code );
				else
					$rv .= $this->message_( 'The slave identifies as user %s on host %s.', $output[ 0 ], $output[ 1 ] );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}
		
		try
		{
			$req = new SMS_Master_Get_Slave_Request();
			$req->slave_id = $id;
			$result = $this->send_request( $req );
			$slave = $result->slave;
		}
		catch( SMS_Master_Exception $e )
		{
			$this->error_( 'No connection! %s', $e->get_error_message() );
			return;
		}
		
		$inputs = array(
			'description' => array(
				'description' => $this->_( 'A short description to help the admin identify this slave.' ),
				'label' => $this->_( 'Description' ),
				'maxlength' => '128',
				'size' => '40',
				'type' => 'text',
				'value' => $slave->slave_description,
			),
			'username' => array(
				'description' => $this->_( 'The SSH username with which to log in.' ),
				'label' => $this->_( 'Username' ),
				'maxlength' => '128',
				'size' => '40',
				'type' => 'text',
				'value' => $slave->username,
			),
			'hostname' => array(
				'description' => $this->_( 'The hostname or IP of the slave.' ),
				'label' => $this->_( 'Hostname' ),
				'maxlength' => '128',
				'size' => '40',
				'type' => 'text',
				'value' => $slave->hostname,
			),
			'port' => array(
				'description' => $this->_( 'The port to which to SSH to.' ),
				'label' => $this->_( 'Port' ),
				'maxlength' => '5',
				'size' => '5',
				'type' => 'text',
				'validation' => array(
					'valuemin' => 1,
					'valuemax' => 65535,
				),
				'value' => $slave->port,
			),
			'public_key' => array(
				'description' => $this->_( 'Public SSH key for the specified username.' ),
				'label' => $this->_( 'Public key' ),
				'maxlength' => 512,
				'size' => 40,
				'type' => 'text',
				'value' => $slave->public_key,
			),
			'private_key' => array(
				'cols' => 40,
				'description' => $this->_( 'Private SSH key for the specified username.' ),
				'label' => $this->_( 'Private key' ),
				'rows' => 10,
				'type' => 'textarea',
				'value' => $slave->private_key,
			),
			'update' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Update slave' ),
			),
			'identify' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Identify slave' ),
			),
		);
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );
			if ( $result !== true )
				$this->error( implode('<br />', $result) );
			else
			{
				try
				{
					$req = new SMS_Master_Update_Slave_Request();
					$req->slave = (object) array(
						'hostname' => $_POST[ 'hostname' ],
						'port' => $_POST[ 'port' ],
						'private_key' => $_POST[ 'private_key' ],
						'public_key' => $_POST[ 'public_key' ],
						'slave_description' => $_POST[ 'description' ],
						'slave_id' => $id,
						'username' => $_POST[ 'username' ],
					);
					$result = $this->send_request( $req );
					
					$rv .= $this->message_( 'The slave has been updated.' );
				}
				catch( SMS_Master_Exception $e )
				{
					$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
				}
			}
		}
		
		foreach( $inputs as $index => $input )
		{
			$inputs[ $index ][ 'name' ] = $index;
			$form->use_post_value( $inputs[ $index ] );
		}
		
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_new_slave()
	{
		$form = $this->form();
		$rv = '';
		
		$inputs = array(
			'create' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Create a new slave' ),
			),
		);
		
		if ( isset( $_POST[ 'create' ] ) )
		{
			// Create the slave
			try
			{
				$req = new SMS_Master_Create_Slave_Request();
				$req->options = (object) array(
					'slave_description' => $this->_( 'Slave created %s', $this->now() ),
				);
				$result = $this->send_request( $req );
				$slave_id = $result->slave_id;
				
				$url = add_query_arg( array(
					'tab' => 'edit',
					'id' => $slave_id,
				) );
				$url = sprintf( '<a href="%s" title="%s">', $url, $this->_( 'Edit this slave' ) );
				$rv .= $this->message_( '%sA new slave%s has been created!', $url, '</a>' );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}
		
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		return $rv;
	}
	
	public function admin_slave_overview()
	{
		// This needs to be called before the status request so that newly-created slaves show up in the status.
		
		$create = $this->admin_new_slave();
		try
		{
			$result = $this->send_request( new SMS_Master_Status_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		$rv = '';
		
		$t_body = '';
		foreach( $result->slaves as $slave )
		{
			$url_edit = add_query_arg( array(
				'id' => $slave->slave_id,
				'tab' => 'edit',
			) );
			$url_delete = add_query_arg( array(
				'tab' => 'delete',
			), $url_edit );
			$t_body .= '
				<tr>
					<td><div>' .htmlspecialchars( $slave->slave_description ) . '</div>
						<div class="row-actions">
							<a href="'.$url_edit.'" title="'.$this->_( 'Edit the settings of the slave' ).'">'.$this->_( 'Edit' ).'</a>
							| 
							<span class="trash"><a href="'.$url_delete.'" title="'.$this->_( 'Delete this slave and its phones' ).'">'.$this->_( 'Delete' ).'</a></span>
						</div>
					</td>
					<td>' .$slave->hostname . ':' . $slave->port . '</td>
				</tr>
			';
		}
		
		$rv .= '<table class="widefat">
			<caption>' . $this->_( 'Slave overview' ) . '</caption>
			<thead>
				<tr>
					<th>' . $this->_( 'Description' ) . '</th>
					<th>' . $this->_( 'Connection' ) . '</th>
				</tr>
			</thead>
			<tbody>
				' . $t_body . '
			</tbody>
			</table>
		';
		
		$rv .= '<h3>' . $this->_( 'Create a new slave' ) . '</h3>';
		$rv .= $create;
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Statistics
	// --------------------------------------------------------------------------------------------
	
	public function admin_statistics()
	{
		try
		{
			$result = $this->send_request( new SMS_Master_Status_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$stats = $result->stats;
		$rv = $this->p_( "Total SMS's sent: %s", $stats->stat_sent_sms );
		
		$base_key = 'stat_sent_sms_';
		$year = date( 'Y' );
		while ( true )
		{
			$year_key = $base_key . $year;
			if ( ! isset( $stats->$year_key ) )
				break;
			$rv .= '<h3>' . sprintf( '%s: %s', $year, $stats->$year_key ) . '</h3>';
			
			$rv .= '<table>';
			
			for ( $month = 1; $month < 13; $month ++ )
			{
				if ( $month < 10 )
					$month = '0' . $month;
				$month_key = $year_key . '_' . $month;
				if ( ! isset( $stats->$month_key ) )
					continue;
				$time = strtotime( '2013-' . $month . '-01' );
				$rv .= $this->p( '<tr><td>%s</td><td>%s</td></tr>', date( 'F', $time ), $stats->$month_key );
			}
			$rv .= '</table>';
			$year--;
		}
		
		$rv = $this->wrap( $rv, $this->_( 'Statistics' ) );
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Tools
	// --------------------------------------------------------------------------------------------
		
	public function admin_tools()
	{
		if ( isset( $_POST[ 'send_unsent' ] ) )
		{
			try
			{
				$result = $this->send_request( new SMS_Master_Send_Unsent_Request() );
				$this->message_( '%s messages in queue before command. %s messages after.', $result->count_before, $result->count_after );
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
				return;
			}
		}
		
		$form = $this->form();
		$rv = '';
		
		// KEYPAIR GENERATION
		// ------------------
		
		$rv .= '<h3>' . $this->_( 'Generate SSH keypair' ) . '</h3>';
		
		if ( isset( $_POST[ 'generate_keypair' ] ) )
		{
			$keypair = SMS_Master::generate_keypair();
			$rv .= $this->p_( 'Public key:' );
			$rv .= '<pre>' . $keypair->public . '</pre>';
			$rv .= $this->p_( 'Private key:' );
			$rv .= '<pre>' . $keypair->private . '</pre>';
		}
		
		$inputs = array(
			'generate_keypair' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Generate SSH keypair' ),
			),
		);
		
		$rv .= $this->p_( 'Use the button below to generate an SSH keypair (public and private key) in openssl format.' );
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		// PUBLIC KEY ID
		// -------------

		$inputs = array(
			'public_key' => array(
				'description' => $this->_( 'Retrieve the public key ID for this public key.' ),
				'label' => $this->_( 'Public key' ),
				'cols' => 80,
				'rows' => 10,
				'type' => 'textarea',
			),
			'get_public_key_id' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Get public key ID' ),
			),
		);
		
		foreach( $inputs as $key => $input )
		{
			$inputs[ $key ][ 'name' ] = $key;
			if ( isset( $_POST[$key] ) )
				$form->use_post_value( $inputs[$key], $_POST );
		}
		
		if ( isset( $_POST[ 'get_public_key_id' ] ) )
		{
			$key = trim( $_POST[ 'public_key' ] );
			$id = SMS_Master::get_public_key_id( $key );
			$rv .= $this->message_( 'The public key ID is: %s', $id );
		}
		
		$rv .= $form->start();
		$rv .= $this->p_( '<h3>Public key ID</h3>' );
		$rv .= $this->p_( 'Public key IDs are used by the SMS Master to quickly find public keys in the database. Input the public key of a user to retrieve the corresponding ID.' );
		$rv .= $this->display_form_table( array(
			$inputs[ 'public_key' ],
			$inputs[ 'get_public_key_id' ],
		) );
		$rv .= $form->stop();
		
		$rv .= $this->p_( '<h3>Send unsent</h3>' );
		$rv .= $this->p_( 'Order the SMS Master to send any unsent orders.' );

		$form = $this->form();
		$inputs = array(
			'send_unsent' => array(
				'css_class' => 'button-secondary',
				'type' => 'submit',
				'value' => $this->_( 'Send unsent messages' ),
			),
		);
		
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		// GIT UPDATE
		$rv .= $this->p_( '<h3>GIT</h3>' );
		
		$rv .= $this->admin_download();

		
		echo $this->wrap( $rv, $this->_( 'Tools' ) );
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Users
	// --------------------------------------------------------------------------------------------
		
	/**
		@brief		User overview.
	**/
	public function admin_users()
	{
		$tab_data = array(
			'default' => 'admin_user_overview',
			'tabs'		=>	array(),
			'functions' =>	array(),
		);

		$tab_data[ 'tabs' ][ 'admin_user_overview' ] = $this->_( 'User overview' );
		$tab_data[ 'functions' ][ 'admin_user_overview' ] = 'admin_user_overview';

		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'delete' )
		{		
			$tab_data[ 'tabs' ][ 'delete' ] = $this->_( 'Delete user' );
			$tab_data[ 'functions' ][ 'delete' ] = 'admin_delete_user';
		}
		
		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'edit' )
		{		
			$tab_data[ 'tabs' ][ 'edit' ] = $this->_( 'Edit user' );
			$tab_data[ 'functions' ][ 'edit' ] = 'admin_edit_user';
		}

		$this->tabs($tab_data);
	}
	
	public function admin_user_overview()
	{
		$form = $this->form();
		$rv = '';
		
		if ( isset( $_POST[ 'create' ] ) )
		{
			// Create the user
			try
			{
				$req = new SMS_Master_Create_User_Request();
				$req->options = (object) array(
					'user_description' => $this->_( 'User created %s', $this->now() ),
				);
				$created_user = $this->send_request( $req );
				$user_id = $created_user->user_id;
				
				$url = add_query_arg( array(
					'tab' => 'edit',
					'id' => $user_id,
				) );
				$url = sprintf( '<a href="%s" title="%s">', $url, $this->_( 'Edit this user' ) );
				$rv .= $this->message_( '%sA new user%s has been created!', $url, '</a>' );
			}
			catch( SMS_Master_Exception $e )
			{
				$rv .= $this->error_( 'No connection! %s', $e->get_error_message() );
			}
		}

		try
		{
			$result = $this->send_request( new SMS_Master_List_Users_Request() );
		}
		catch( SMS_Master_Exception $e )
		{
			$this->error_( 'No connection! %s', $e->get_error_message() );
			return;
		}

		$t_body = '';
		foreach( $result->users as $user )
		{
			if ( $user->public_key != $this->get_local_option( 'client_public_key' ) )
			{
				$url_edit = add_query_arg( array(
					'id' => $user->user_id,
					'tab' => 'edit',
				) );
				$url_delete = add_query_arg( array(
					'tab' => 'delete',
				), $url_edit );
				$actions = '<div class="row-actions">
								<a href="'.$url_edit.'" title="'.$this->_( 'Edit the user' ).'">'.$this->_( 'Edit' ).'</a>
								| 
								<span class="trash"><a href="'.$url_delete.'" title="'.$this->_( 'Delete this user' ).'">'.$this->_( 'Delete' ).'</a></span>
							</div>
				';
			}
			else
			{
				$actions = $this->p_( 'This user cannot be edited or removed because it is being used to connect to the SMS master.' );
			}
			
			$t_body .= '
				<tr>
					<td><div>' .$this->p( $user->user_description ) . '</div>
						' . $actions . '
					</td>
					<td>' . $this->yes_no( $user->enabled ) . '</td>
					<td>' . $this->yes_no( $user->administrator ) . '</td>
				</tr>
			';
		}
		
		$rv .= '<table class="widefat">
			<caption>' . $this->_( 'User overview' ) . '</caption>
			<thead>
				<tr>
					<th>' . $this->_( 'Description' ) . '</th>
					<th>' . $this->_( 'Active' ) . '</th>
					<th>' . $this->_( 'Administrator' ) . '</th>
				</tr>
			</thead>
			<tbody>
				' . $t_body . '
			</tbody>
			</table>
		';
		
		$create = array(
			'css_class' => 'button-primary',
			'name' => 'create',
			'type' => 'submit',
			'value' => $this->_( 'Create a new user' ),
		);
		
		$rv .= '<h3>' . $this->_( 'Create a new user' ) . '</h3>';
		$rv .= $this->p_( "Use the button below to create a new user. After creation the user can be edited." );
		$rv .= $form->start();
		$rv .= $form->make_input( $create );
		$rv .= $form->stop();
		
		echo $rv;	
	}
	
	public function admin_delete_user()
	{
		$id = intval( $_GET[ 'id' ] );
		
		try
		{
			$req = new SMS_Master_Get_User_Request();
			$req->user_id = $id;
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$form = $this->form();
		$user = $result->user;
		$rv = '';
		
		if ( isset( $_POST[ 'delete' ] ) )
		{
			try
			{
				$req = new SMS_Master_Delete_User_Request();
				$req->user_id = $id;
				$result = $this->send_request( $req );
				$this->message_( 'The user has been deleted! You can now return to the overview.' );
				return;
			}
			catch( SMS_Master_Exception $e )
			{
				echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
				return;
			}
		}
		
		$inputs = array(
			'delete' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Delete user' ),
			),
		);
		
		$rv .= $this->p_( 'Description for user %s:', $id );
		$rv .= $this->p( '<blockquote>' . $user->user_description . '</blockquote>' );
		$rv .= $this->p_( 'Do you wish to permanently delete this user?' );
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_edit_user()
	{
		$id = intval( $_GET[ 'id' ] );
		
		try
		{
			$req = new SMS_Master_Get_User_Request();
			$req->user_id = $id;
			$result = $this->send_request( $req );
		}
		catch( SMS_Master_Exception $e )
		{
			echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
			return;
		}
		
		$form = $this->form();
		$user = $result->user;
		$rv = '';
		
		$inputs = array(
			'user_description' => array(
				'description' => $this->_( 'A short description of the user.' ),
				'label' => $this->_( 'Description' ),
				'maxlength' => 128,
				'size' => 40,
				'type' => 'text',
				'value' => $user->user_description,
			),
			'datetime_created' => array(
				'description' => $this->_( 'When the user was created.' ),
				'label' => $this->_( 'Created' ),
				'readonly' => true,
				'size' => 19,
				'type' => 'text',
				'value' => $user->datetime_created,
				'validation' => array( 'empty' => true ),
			),
			'administrator' => array(
				'description' => $this->_( 'User is an administrator.' ),
				'checked' => $user->administrator,
				'label' => $this->_( 'Administrator' ),
				'type' => 'checkbox',
			),
			'enabled' => array(
				'description' => $this->_( 'User is enabled.' ),
				'checked' => $user->enabled,
				'label' => $this->_( 'Enabled' ),
				'type' => 'checkbox',
			),
			'public_key' => array(
				'description' => $this->_( 'The public SSH key used to identify and communicate with this user.' ),
				'cols' => 80,
				'label' => $this->_( 'Public key' ),
				'rows' => 9,
				'type' => 'textarea',
				'value' => $user->public_key,
			),
			'update' => array(
				'css_class' => 'button-primary',
				'type' => 'submit',
				'value' => $this->_( 'Update user' ),
			),
		);
		
		foreach( $inputs as $key => $input )
		{
			$inputs[ $key ][ 'name' ] = $key;
			if ( isset( $_POST[$key] ) )
				$form->use_post_value( $inputs[$key], $_POST );
		}
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );
			if ( $result !== true )
				$this->error( implode('<br />', $result) );
			else
			{
				try
				{
					$req = new SMS_Master_Update_User_Request();
					$req->user = (object) array(
						'user_id' => $id,
						'administrator' => isset( $_POST[ 'administrator' ] ),
						'enabled' => isset( $_POST[ 'enabled' ] ),
						'public_key' => $this->check_plain( $_POST[ 'public_key' ] ),
						'user_description' => $this->check_plain( $_POST[ 'user_description' ] ),
					);
					$result = $this->send_request( $req );
					$user = $result->user;
					$this->message_( 'User updated.' );
				}
				catch( SMS_Master_Exception $e )
				{
					echo $this->error_( 'Connection error! Check your settings. %s', $e->get_error_message() );
				}
			}
		}
		
		$rv .= $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	public function display_orders( $options )
	{
		$options = (object)$options;
		$completed = $options->type == 'completed';
		$rv = '';
		$t_body = '';
		
		$max_pages = floor( $options->count / $this->page_limit);
		$page = (isset($_GET['paged']) ? intval( $_GET['paged'] ) : 1);
		$page = $this->minmax($page, 1, $max_pages);
		
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'current' => $page,
			'total' => $max_pages,
		));
		
		if ( $page_links )
			$page_links = '<div style="width: 50%; float: right;" class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';

		foreach( $options->orders as $order )
		{
			if ( $order->number_count > 1 )
				$numbers = $order->number_count;
			else
				$numbers = $order->numbers;
			
			$url = add_query_arg( array(
				'id' => $order->order_id,
				'tab' => 'view',
			) );
			$url = sprintf( '<a href="%s" title="%s">', $url, $this->_( 'View the order details' ) );
			$text_and_url = sprintf( '%s%s</a>',
				$url,
				$order->text
			);
			
			$t_body .= '<tr>';
			$t_body .= '<td>' . $order->order_id . '</td>';
			$t_body .= '<td>' . $url . $this->ago( $order->datetime_created ) . '</a></td>';
			if ( $completed )
				$t_body .= '<td>' . $this->ago( $order->datetime_completed ) . '</td>';
			$t_body .= '<td>' . $numbers . '</td>';
			$t_body .= '<td>' . $text_and_url . '</td>';
			$t_body .= '</tr>';
		}
		
		$rv .= $page_links;
		$rv .= '
			<table class="widefat">
				<thead>
					<tr>
						<th>' . $this->_( 'Order ID' ) . '</th>
						<th>' . $this->_( 'Created' ) . '</th>
		';
		if ( $completed )
			$rv .= '<th>' . $this->_( 'Completed' ) . '</th>';
		$rv .= '
						<th>' . $this->_( 'Numbers' ) . '</th>
						<th>' . $this->_( 'Text' ) . '</th>
					</tr>
				</thead>
				<tbody>
					' . $t_body . '
				</tbody>
			</table>
		';
		
		$rv .= $page_links;
		
		return $rv;
	}
	
	public function display_phone( $phone, $options = array() )
	{
		$options = (object) array_merge( array(
			'display_edit_link' => false,
			'display_delete_link' => false,
			'display_sent_stats' => false,
		), (array) $options );
		
		$rv = '';
		
		$rv .= '
			<table class="widefat">
				<caption>' . $this->_( 'Phone' ) . ': ' . $phone->phone_id . '</caption>
				<tr>
					<th>' . $this->_( 'Description' ) . '</th>
					<td>' . $this->p( $phone->phone_description ) . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Phone index' ) . '</th>
					<td>' . $phone->phone_index . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Enabled' ) . '</th>
					<td>' . $this->yes_no( $phone->enabled ) . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Clean' ) . '</th>
					<td>' . $this->yes_no( $phone->clean ) . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Touched' ) . '</th>
					<td>' . $phone->touched . '</td>
				</tr>
				<tr>
					<th>' . $this->_( 'Touched successfully' ) . '</th>
					<td>' . $phone->touched_successfully . '</td>
				</tr>
		';
		
		if ( $options->display_sent_stats )
		{
			$month = 12;
			$sent = '';
			$year = date( 'Y' );
			
			$key = sprintf( 'phone_%s_sent', $phone->phone_id );
			$sent .= sprintf( '<strong>%s</strong> - %s<br />',
				$this->_( 'Total' ),
				$phone->settings->$key
			);
			
			while ( true )
			{
				$key = sprintf( 'phone_%s_sent_%s', $phone->phone_id, $year );
				if ( ! isset( $phone->settings->$key ) )
					break;
				$sent .= sprintf( '<strong>%s</strong> - %s<br />', $year, $phone->settings->$key );
				
				for( $month = 12; $month > 0; $month -- )
				{
					if ( strlen( $month ) < 2 )
						$month = '0' . $month;
					$key = sprintf( 'phone_%s_sent_%s_%s', $phone->phone_id, $year, $month );
					if ( ! isset( $phone->settings->$key ) )
						continue;
					$time = strtotime( '2013-' . $month . '-01' );
					$sent .= sprintf( '%s - %s<br />', date( 'F', $time ), $phone->settings->$key );
				}
				$year--;
			}

			$rv .= '
					<tr>
						<th>' . $this->_( 'Sent messages' ) . '</th>
						<td>' . $sent . '</td>
					</tr>
			';
		}
		
		$rv .= '</table>';
		
		if ( $options->display_delete_link )
		{
			$url = add_query_arg( array(
				'page' => 'sd_sms_master_phones',
				'tab' => 'delete',
				'id' => $phone->phone_id,
			) );
			$rv .= $this->p( sprintf( '<a href="%s">%s</a>',
				$url,
				$this->_( 'Delete phone' )
			) );
		}
		
		if ( $options->display_edit_link )
		{
				$url = add_query_arg( array(
					'page' => 'sd_sms_master_phones',
					'tab' => 'edit',
					'id' => $phone->phone_id,
				) );
				$rv .= $this->p( sprintf( '<a href="%s">%s</a>',
					$url,
					$this->_( 'Edit phone' )
				) );
		}

		return $rv;
	}
	
	public function send_request( $options )
	{
		// Lazy people can send Requests directly, instead of putting them into an options array.
		if ( is_a( $options, 'SMS_Master_Request' ) )
		{
			$o = new stdClass();
			$o->request = $options;
			$options = $o;
		}
			
		$options = (object) array_merge( array(
			'client_private_key' => $this->get_local_option( 'client_private_key' ),
			'client_public_key' => $this->get_local_option( 'client_public_key' ),
			'master_public_key' => $this->get_local_option( 'server_public_key' ),
			'master_url' => $this->get_local_option( 'server_url' ),
		), (array) $options );
		
		return SMS_Master::send_request( $options );
	}
	
	public function yes_no( $boolean )
	{
		if ( $boolean )
			return $this->_( 'Yes' );
		return $this->_( 'No' );
	}
}

$sms_master_include_common = dirname( __FILE__ ) . '/' . SD_SMS_Master::$sms_master_include_common;
if ( is_readable( $sms_master_include_common ) )
	require_once( $sms_master_include_common );

$SD_SMS_Master = new SD_SMS_Master();

