<?php
/*=========================================================================
  Program:   CDash - Cross-Platform Dashboard System
  Module:    $Id$
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) Kitware, Inc. All rights reserved.
  See LICENSE or http://www.cdash.org/licensing/ for details.

  This software is distributed WITHOUT ANY WARRANTY; without even
  the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
  PURPOSE. See the above copyright notices for more information.
=========================================================================*/

require_once dirname(__DIR__) . '/config/config.php';
require_once 'include/common.php';
require_once 'models/user.php';
use CDash\Config;

class OAuth2Provider
{
    public  $BaseUrl;
    public  $Valid;

    protected  $OwnerDetails;
    protected $Provider;
    protected $Token;

    private $Config;

    public function __construct()
    {
        $this->AuthorizationOptions = [];
        $this->OwnerDetails = null;
        $this->Provider = null;
        $this->Valid = false;
        $this->Token = null;

        $this->Config = Config::getInstance();
        $this->BaseUrl = $this->Config->get('CDASH_BASE_URL');
        $this->OAuth2Settings = $this->Config->get('OAUTH2_PROVIDERS');
    }

    public function initializeSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('CDash');
            session_cache_limiter('private_no_expire');
            $cookie_time = $this->Config->get('CDASH_COOKIE_EXPIRATION_TIME');
            session_set_cookie_params($cookie_time);
            @ini_set('session.gc_maxlifetime', $cookie_time + 600);
            session_start();
            if (!array_key_exists('cdash', $_SESSION)) {
                $_SESSION['cdash'] = [];
            }
            // Store the URI that the user is trying to access in the session.
            if (array_key_exists('dest', $_GET)) {
                $_SESSION['cdash']['dest'] = $_GET['dest'];
            }
        }
    }

    public function getAuthorizationCode()
    {
        // If we don't have an authorization code then get one
        $authUrl = $this->Provider->getAuthorizationUrl(
                $this->AuthorizationOptions);
        $_SESSION['cdash']['oauth2state'] = $this->Provider->getState();

        // Prevent the browser from caching this redirect.
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header('Location: '. $authUrl);
        exit;
    }

    public function checkState()
    {
        if (empty($_GET['state']) ||
                ($_GET['state'] !== $_SESSION['cdash']['oauth2state'])) {
            unset($_SESSION['cdash']['oauth2state']);
            exit('Invalid state');
        }
    }

    public function loadOwnerDetails()
    {
        if (!$this->Token) {
            return false;
        }
        $this->OwnerDetails = $this->Provider->getResourceOwner($this->Token);
        return true;
    }

    public function auth()
    {
        $this->initializeSession();

        // Get an authorization code if we do not already have one.
        if (!isset($_GET['code'])) {
            $this->getAuthorizationCode();
        }

        // Check given state against previously stored one to mitigate
        // CSRF attack.
        $this->checkState();

        // Try to get an access token using the authorization code grant.
        $this->Token = $this->Provider->getAccessToken('authorization_code',
                [ 'code' => $_GET['code'] ]);

        // Use the access token to get the user's email.
        try {
            $email = $this->getEmail();
            if ($email) {
                // Check if this email address appears in our user database.
                $user = new User();
                $userid = $user->GetIdFromEmail($email);
                if (!$userid) {
                    // if no match is found, redirect to pre-filled out
                    // registration page.
                    $firstname = $this->getFirstName();
                    $lastname = $this->getLastName();
                    header("Location: $this->BaseURL/register.php?firstname=$firstname&lastname=$lastname&email=$email");
                    return false;
                }

                $user->Id = $userid;
                $user->Fill();

                /*
                   if ($state->rememberMe) {
                   require_once 'include/login_functions.php';
                   setRememberMeCookie($user->Id);
                   }
                 */

                $dest = $_SESSION['cdash']['dest'];
                $sessionArray = array(
                        'login' => $email,
                        'passwd' => $user->Password,
                        'ID' => session_id(),
                        'valid' => 1,
                        'loginid' => $user->Id);
                $_SESSION['cdash'] = $sessionArray;
                session_write_close();
                header("Location: $dest");
                // Authentication succeeded.
                return true;
            } else {
                // TODO: error handling
            }
        } catch (Exception $e) {
        // TODO: error handling
        echo $e->getMessage() . "<br>\n";
        echo $e->getTraceAsString();
        }
    }
}
