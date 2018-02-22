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

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once 'auth/GitHubProvider.php';

$provider = new GitHubProvider();
if (!$provider->Valid) {
    exit('CDash could not initialize the GitHub OAuth2 provider');
}

$provider->auth();
