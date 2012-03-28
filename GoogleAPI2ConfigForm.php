<?php 
/*
* Object that takes an GoogleApi2AuthenticatedClient and returns a form array
*/
class googleAPI2ConfigForm {
	public $client;
	
	public function __construct($client) {
		$this->client = $client;
	}
	
	public function make_form() {
		// Add standard form elements
		$form['uaNumber'] = array(
			'#type' => 'textfield',
			'#title' => 'Google Analytics Property ID',
			'#default_value' => variable_get('googleAPI2PropertyId', 'UA-'),
			'#size' => 30,
			'#description' => t('Unique identifier for Google Analytics property in format UA-xxxxxxxx'),
		);
		
		$form['googleAPI2ClientId'] = array(
			'#type' => 'textfield',
			'#title' => t('Client ID'),
			'#default_value' => variable_get('googleAPI2ClientId', ''),
			'#size' => 30,
			'#description' => t('Client ID created for the app in the access tab of the ') . l('Google API Console', 'http://code.google.com/apis/console', array('attributes' => array('target' => '_blank'))),
		);

		$form['googleAPI2ClientSecret'] = array(
			'#type' => 'textfield',
			'#title' => t('Client Secret'),
			'#default_value' => variable_get('googleAPI2ClientSecret', ''),
			'#size' => 30,
			'#description' => t('Client Secret created for the app in the Google API Console'),
		);

		if ($this->client -> is_authenticated() && variable_get("googleAPI2PropertyId", '')) {
			try{
				// If authentication was successful, try to get the profile name
				$profile = $this->client -> get_profile_info();

				$form['googleAnalyticsProfile'] = array(
					'#type' => 'item',
					'#title' => t('Google Analytics profile for this HQ is: '),
					'#value' => $profile['name'],
				);
			}
			catch (Exception $e) {
				// If unable to get profiles, throw an error
				drupal_set_message(t("Could not retrieve profile. Please make sure you have entered the correct property ID for your HQ site and try authenticating again."), 'error', false);
			}
		}

		if (!$this->client -> is_authenticated()) {
			// If site is not authenticated, add standard submit buttons
			$form['auth'] = array(
				'#type' => 'submit',
				'#value' => 'Authenticate',
				'#submit' => array('googleAPI2_config_auth'),
			);
		} else {	
			// If site is authenticated, add Deauthenticate and Save buttons
			$form['save'] = array(
				'#type' => 'submit',
				'#value' => 'Save',
				'#submit' => array('googleAPI2_config_save'),
			);
			$form['deauth'] = array(
				'#type' => 'submit',
				'#value' => 'Deauthenticate',
				'#validate' => array(),
				'#submit' => array('googleAPI2_config_deauth'),
			);
		}
	
		return $form;
	}
}
?>