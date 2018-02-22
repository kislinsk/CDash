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
require_once 'auth/GoogleProvider.php';

if (!empty($_GET['error'])) {
    // Got an error, probably user denied access.
    exit('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
}

$provider = new GoogleProvider();
if (!$provider->Valid) {
    exit('CDash could not initialize the Google OAuth2 provider');
}

$provider->auth();
