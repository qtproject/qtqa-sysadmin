<?php
session_start();
?>

<?php
#############################################################################
##
## Copyright (C) 2013 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Metrics web portal.
##
## $QT_BEGIN_LICENSE:LGPL$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and Digia.  For licensing terms and
## conditions see http://qt.digia.com/licensing.  For further information
## use the contact form at http://qt.digia.com/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 as published by the Free Software
## Foundation and appearing in the file LICENSE.LGPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU Lesser General Public License version 2.1 requirements
## will be met: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## In addition, as a special exception, Digia gives you certain additional
## rights.  These rights are described in the Digia Qt LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## GNU General Public License Usage
## Alternatively, this file may be used under the terms of the GNU
## General Public License version 3.0 as published by the Free Software
## Foundation and appearing in the file LICENSE.GPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU General Public License version 3.0 requirements will be
## met: http://www.gnu.org/copyleft/gpl.html.
##
##
## $QT_END_LICENSE$
##
#############################################################################
?>

<?php
include "definitions.php";
include "functions.php";
include(__DIR__.'/../commonfunctions.php');
include "metricsboxdefinitions.php";

$timeStart = microtime(true);

/* Get the input parameters */
$round = $_GET["round"];
$arrayFilters = array();
$arrayFilter = array();
$filters = $_GET["filters"];
$filters = rawurldecode($filters);            // Decode the encoded parameter (encoding in ajaxrequest.js)
$arrayFilters = explode(FILTERSEPARATOR, $filters);
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERPROJECT]);
$project = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIPROJECT]);
$ciProject = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIBRANCH]);
$ciBranch = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIPLATFORM]);
$ciPlatform = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCONF]);
$conf = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERBUILD]);
$build = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALETYPE]);
$timescaleType = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALEVALUE]);
$timescaleValue = $arrayFilter[1];

/* Connect to the server */
require(__DIR__.'/../connect.php');
$timeConnect = microtime(true);
include(__DIR__.'/../commondefinitions.php');

/* Select database */
if ($useMysqli) {
    // Selected in mysqli_connect() call
} else {
    $selectdb="USE $db";
    $result = mysql_query($selectdb) or die ("Failure: Unable to use the database !");
}

/* Platform filter definitions */
if ($ciPlatform == "All")
    $ciPlatform = 0;
$ciPlatform = (int)$ciPlatform;
$ciPlatformName = $arrayPlatform[$ciPlatform][0];
$ciPlatformFilter = $arrayPlatform[$ciPlatform][1];
$ciPlatformFilterSql = str_replace('*', '%', $arrayPlatform[$ciPlatform][1]);     // Change the format for MySQL (wildcard '*' -> '%')

/************************************************************/
/* NESTED LEVEL 1: No project filtering done (default view) */
/************************************************************/

if ($project == "All") {
    echo '<div class="metricsBoxHeader">';
    echo '<div class="metricsBoxHeaderIcon">';
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";    // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel1.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '</div>';
    echo '<div class="metricsBoxHeaderText">';
    echo '<b>PROJECT DASHBOARD:</b> Select Project';
    echo '</div>';
    echo '</div>';
    if (isset($_SESSION['arrayProjectName'])) {

        /* Print the used filters */
        if ($ciProject <> "All" OR $ciBranch <> "All" OR $ciPlatform <> 0 OR $conf <> "All" OR $timescaleType <> "All") {
            echo '<table>';
            if ($ciProject <> "All")
                echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $ciProject . '</td></tr>';
            if ($ciBranch <> "All")
                echo '<tr><td>Branch:</td><td class="tableCellBackgroundTitle">' . $ciBranch . '</td></tr>';
            if ($ciPlatform <> 0 AND $conf == "All") {
                echo '<tr><td>Platform:</td><td class="tableCellBackgroundTitle">' . $ciPlatformName . '</td></tr>';
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle fontColorGrey">' . $ciPlatformFilter . '</td></tr>';
            }
            if ($conf <> "All")
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
            if ($timescaleType == "Since")
                echo '<tr><td>Since:</td><td class="timescaleSince">' . $timescaleValue . '</td></tr>';
            echo '</table>';
        }
        $projectTitle = "<b>Projects</b>";
        if ($ciPlatform <> 0 OR $conf <> "All")
            $projectTitle = "<b>Projects built in selected Configurations</b> (Note: The data is from Project level)";
        echo '<div class="metricsTitle">';
        echo $projectTitle;
        echo '</div>';

        /* Show list of Projects (from the session variable that was saved for the filters */
        require('listprojects.php');

    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/************************************************************/
/* NESTED LEVEL 2: Project filtered                         */
/************************************************************/

if ($project <> "All" AND $conf == "All") {
    echo '<div class="metricsBoxHeader">';
    echo '<div class="metricsBoxHeaderIcon">';
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";    // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel2.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '</div>';
    echo '<div class="metricsBoxHeaderText">';
    echo '<b>PROJECT DASHBOARD:</b> <a href="javascript:void(0);" onclick="clearProjectFilters()">Select Project</a> -> ' . $project;
    echo '</div>';
    echo '</div>';
    if (isset($_SESSION['arrayProjectName'])) {
        $projectFilter = "project=\"$project\"";
        $confFilter = "";
        if ($ciPlatform <> 0)
            $confFilter = 'cfg LIKE "' . $ciPlatformFilterSql . '"';
        /* Show general data */
        require('listgeneraldata.php');
        /* Show Build history */
        require('listbuilds.php');
        /* Show Configurations for the latest/selected Build */
        require('listconfigurations.php');
        /* Show Top failing autotests */
        require('listfailingautotests.php');
    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/************************************************************/
/* NESTED LEVEL 3: Project and Configuration filtered        */
/************************************************************/

if ($project <> "All" AND $conf <> "All") {
    echo '<div class="metricsBoxHeader">';
    echo '<div class="metricsBoxHeaderIcon">';
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";    // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel3.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '</div>';
    echo '<div class="metricsBoxHeaderText">';
    echo '<b>PROJECT DASHBOARD:</b> <a href="javascript:void(0);" onclick="clearProjectFilters()">Select Project</a> ->
        <a href="javascript:void(0);" onclick="filterConf(\'All\')">' . $project . '</a> -> ' . $conf;
    echo '</div>';
    echo '</div>';
    if (isset($_SESSION['arrayProjectName'])) {
        /* Show general data */
        $projectFilter = "project=\"$project\"";
        $confFilter = "cfg=\"$conf\"";
        require('listgeneraldata.php');
        if ($projectConfValid) {
            /* Show Build history */
            require('listbuilds.php');
            /* Show Top failing autotests */
            require('listfailingautotests.php');
        } else {
            echo "<br/>Configuration $conf not built for $project<br/>";
        }
    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

?>