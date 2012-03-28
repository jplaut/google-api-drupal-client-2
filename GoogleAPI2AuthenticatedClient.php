<?php
/*
* Takes an unauthenticated ApiClient and returns either a
* fully authenticated client or NULL if no authentication has been requested 
*/
class GoogleApi2AuthenticatedClient {
	public $unuthenticatedClient;
	public $service;
	public $client;
	
	public function __construct() {
		$this->unuthenticatedClient = new apiClient();
		$this->service = new apiAnalyticsService($this->unuthenticatedClient);
		$this->client = NULL;
		
		if (!variable_get("googleAPI2AccessToken", "") || !variable_get("googleAPI2RefreshToken", "")) {
			if ($_GET['code']) {
				// If there is no access token or refresh token and client is returned
				// to the config page with an access code, complete the authentication
				self::full_auth();
			}
		} else {
			if (time() > variable_get("googleAPI2TokenExpires", "")) {
				// If the site has an access token and refresh token, but the
				// refresh token has expired, authenticate the user with the
				// refresh token
				self::refresh_token();
			} else {
				// If the access token is still valid, authenticate the user with that
				self::access_token();
			}
		}
		
		return $this->client;
	}
	
	/*
	* Authenticates a client using Client ID and Client Secret and saves the returned tokens
	* to the database. Returns authenticated client, or NULL if unsuccessful.
	*/
	protected function full_auth() {
		$this->unuthenticatedClient -> setClientId(variable_get("googleAPI2ClientId", ""));
		$this->unuthenticatedClient -> setClientSecret(variable_get("googleAPI2ClientSecret", ""));
		$this->unuthenticatedClient -> setRedirectUri(url($_GET['q'], array('absolute' => TRUE)));
		$this->unuthenticatedClient -> setScopes(SCOPE);
		
		try{
			$this->unuthenticatedClient -> authenticate();
			$this->client = $this->unuthenticatedClient;
			self::get_profile_info();
			self::set_google_api_variables();
			drupal_set_message(t("Authentication successful."), 'status', FALSE);
		}
		catch (Exception $e) {
			drupal_set_message(t("Unable to access profile. Please check Property Id, Client ID and Client Secret and try again."), 'error', FALSE);
			$this->client = NULL;
		}
	}
	
	/*
	* Authenticates a client using a valid refresh token and saves the variables
	* to the database. Returns authenticated client, or NULL if unsuccessful.
	*/
	protected function refresh_token() {
		$this->unuthenticatedClient -> setClientId(variable_get("googleAPI2ClientId", ""));
		$this->unuthenticatedClient -> setClientSecret(variable_get("googleAPI2ClientSecret", ""));
		$this->unuthenticatedClient -> refreshToken(variable_get('googleAPI2RefreshToken', ""));
		
		try{
			$this->client = $this->unuthenticatedClient;
			self::set_google_api_variables();
		}
		catch (Exception $e) {
			drupal_set_message(t("Authentication failed. Please check Client ID and Client Secret."), 'error', FALSE);
			$this->client = NULL;
		}
	}
	
	/*
	* Authenticates a client using a valid access token and returns authenticated client, 
	* or NULL if unsuccessful.
	*/
	protected function access_token() {
		try {
			$this->unuthenticatedClient -> setAccessToken(variable_get("googleAPI2FullResponse", ""));
			$this->client = $this->unuthenticatedClient;
		}
		catch (Exception $e) {
			drupal_set_message(t("Authentication failed. Please check Client ID and Client Secret."), 'error', FALSE);
			$this->client = NULL;
		}
	}
	
	/*
	* Returns Google OAuth URL for provided client information
	*/
	public function get_auth_url() {
		$this->unuthenticatedClient -> setClientId(variable_get("googleAPI2ClientId", ""));
		$this->unuthenticatedClient -> setClientSecret(variable_get("googleAPI2ClientSecret", ""));
		$this->unuthenticatedClient -> setRedirectUri(url($_GET['q'], array('absolute' => TRUE)));
		$this->unuthenticatedClient -> setScopes(SCOPE);
	
		$authUrl = $this->unuthenticatedClient -> createAuthUrl();
		
		return $authUrl;
	}
	
	/*
	* Query Google for Analytics profile info and save
	* variables to db
	*/
	public function get_profile_info() {
		if (!variable_get('googleAPI2ProfileId', '')) {
			$response = $this->service->management_profiles->listManagementProfiles(
				variable_get("googleAPI2AccountId", ""), 
				variable_get("googleAPI2PropertyId", ""), 
				array(
					'max-results' => 1,
					'fields' => "items(id,name)"
				)
			);
			variable_set('googleAPI2ProfileName', $response['items'][0]['name']);
			variable_set('googleAPI2ProfileId', $response['items'][0]['id']);
		}
		$profile = array();
		$profile['id'] = variable_get('googleAPI2ProfileId', '');
		$profile['name'] = variable_get('googleAPI2ProfileName', '');

		return $profile;
	}
	
	/*
	* Save returned access token to database
	*/
	protected function set_google_api_variables() {
		$tokenJSON = json_decode($this->client -> getAccessToken(), TRUE);
		
		variable_set("googleAPI2FullResponse", $this->client -> getAccessToken());
		variable_set("googleAPI2AccessToken", $tokenJSON['access_token']);
		variable_set("googleAPI2TokenExpires", $tokenJSON['created'] + $tokenJSON['expires_in']);
		
		if ($tokenJSON['refresh_token']) {
			variable_set("googleAPI2RefreshToken", $tokenJSON['refresh_token']);
		}
	}
	
	public function is_authenticated() {
		return ($this->client) ? TRUE : FALSE;
	}
}
?>