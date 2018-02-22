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
require_once 'auth/OAuth2Provider.php';

class GoogleProvider extends OAuth2Provider
{
    public function __construct()
    {
        parent::__construct();
        $this->AuthorizationOptions =
            [ 'scope' => ['https://www.googleapis.com/auth/userinfo.email'] ];
        if (array_key_exists('Google', $this->OAuth2Settings)) {
            $google_settings = $this->OAuth2Settings['Google'];
            if (array_key_exists('clientId', $google_settings) &&
                    array_key_exists('clientSecret', $google_settings) &&
                    array_key_exists('redirectUri', $google_settings)) {
                // Get domain from redirect URI.
                $url_parts = parse_url($google_settings['redirectUri']);
                $hosted_domain = $url_parts['scheme'] . '://' . $url_parts['host'];
                $google_settings['hostedDomain'] = $hosted_domain;

                $this->Provider = new League\OAuth2\Client\Provider\Google(
                        $google_settings);
                $this->Valid = true;
            }
        }
    }

    public function getEmail()
    {
        $this->loadOwnerDetails();
        $email = strtolower($this->OwnerDetails->getEmail());
        return $email;
    }

    public function getFirstName()
    {
        return $this->OwnerDetails->getFirstName();
    }

    public function getLastName()
    {
        return $this->OwnerDetails->getLastName();
    }
}
