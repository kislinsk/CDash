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

$noforcelogin = 1;
include dirname(dirname(dirname(__DIR__))) . '/config/config.php';
require_once 'include/pdo.php';
include 'public/login.php';

use CDash\Config;
use CDash\Model\Banner;

$start = microtime_float();
$config = Config::getInstance();
$response = begin_JSON_response();

$Banner = new Banner;
$Banner->SetProjectId(0);
$text = $Banner->GetText();
if ($text !== false) {
    $response['banner'] = $text;
}

$response['hostname'] = $_SERVER['SERVER_NAME'];
$response['date'] = date('r');

// Check if the database is up to date.
$pdo = get_link_identifier()->getPdo();
if ($pdo->query('SELECT authenticatesubmissions FROM project LIMIT 1') === false) {
    $response['upgradewarning'] = 1;
}

$response['title'] = $config->get('CDASH_MAININDEX_TITLE');
$response['subtitle'] = $config->get('CDASH_MAININDEX_SUBTITLE');
$response['googletracker'] = $config->get('CDASH_DEFAULT_GOOGLE_ANALYTICS');
if ($config->get('CDASH_NO_REGISTRATION') == 1) {
    $response['noregister'] = 1;
}

if (isset($_GET['allprojects']) && $_GET['allprojects'] == 1) {
    $response['allprojects'] = 1;
} else {
    $response['allprojects'] = 0;
}
$showallprojects = $response['allprojects'];
$response['nprojects'] = get_number_public_projects();

$projects = get_projects(!$showallprojects);
$projects_response = array();
foreach ($projects as $project) {
    $project_response = array();
    $project_response['name'] = $project['name'];
    $name_encoded = urlencode($project['name']);
    $project_response['name_encoded'] = $name_encoded;
    $project_response['description'] = $project['description'];
    if ($project['numsubprojects'] == 0) {
        $project_response['link'] = "index.php?project=$name_encoded";
    } else {
        $project_response['link'] = "viewSubProjects.php?project=$name_encoded";
    }

    if ($project['last_build'] == 'NA') {
        $project_response['lastbuild'] = 'NA';
    } else {
        $lastbuild = strtotime($project['last_build'] . 'UTC');
        $project_response['lastbuild'] = date(FMT_DATETIMEDISPLAY, $lastbuild);
        $project_response['lastbuilddate'] = date(FMT_DATE, $lastbuild);
        $project_response['lastbuild_elapsed'] =
            time_difference(time() - $lastbuild, false, 'ago');
        $project_response['lastbuilddatefull'] = $lastbuild;
    }

    if (!isset($project['nbuilds']) || $project['nbuilds'] == 0) {
        $project_response['activity'] = 'none';
    } elseif ($project['nbuilds'] < 20) {
        // 2 builds day

        $project_response['activity'] = 'low';
    } elseif ($project['nbuilds'] < 70) {
        // 10 builds a day

        $project_response['activity'] = 'medium';
    } elseif ($project['nbuilds'] >= 70) {
        $project_response['activity'] = 'high';
    }

    $projects_response[] = $project_response;
}
$response['projects'] = $projects_response;

$end = microtime_float();
$generation_time = round($end - $start, 2);
$response['generationtime'] = $generation_time;

echo json_encode(cast_data_for_JSON($response));
