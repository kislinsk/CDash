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

class GitLabProvider extends OAuth2Provider
{
    private $NameParts;

    public function __construct()
    {
        parent::__construct();

        $this->AuthorizationOptions = ['scope' => ['read_user']];
        $this->NameParts = null;

        if (array_key_exists('GitLab', $this->OAuth2Settings)) {
            $gitlab_settings = $this->OAuth2Settings['GitLab'];
            if (array_key_exists('clientId', $gitlab_settings) &&
                    array_key_exists('clientSecret', $gitlab_settings) &&
                    array_key_exists('redirectUri', $gitlab_settings)) {
                $this->Provider = new Omines\OAuth2\Client\Provider\Gitlab(
                        $gitlab_settings);
                $this->Valid = true;
            }
        }
    }

    public function getEmail()
    {
        $this->loadOwnerDetails();
        return strtolower($this->OwnerDetails->getEmail());
    }

    private function loadNameParts()
    {
        $this->loadOwnerDetails();
        $name = $this->OwnerDetails->getName();
        $this->NameParts = explode(' ', $name);
    }

    public function getFirstName()
    {
        $this->loadNameParts();
        return $this->NameParts[0];
    }

    public function getLastName()
    {
        $this->loadNameParts();
        return $this->NameParts[1];
    }
}
