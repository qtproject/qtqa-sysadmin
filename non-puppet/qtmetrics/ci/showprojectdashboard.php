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
include "functions.php";

$timeStart = microtime(true);
$project = $_GET["project"];
$conf = $_GET["conf"];
$conf = str_replace("g  ","g++",$conf);     // Problem: Parameter passing (with GET method in URL) destroys the string "++", e.g. "linux-g++-32_Ubuntu_10.04_x86" -> "linux-g  -32_Ubuntu_10.04_x86"

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

/* Set to show or hide elapsed time printing */
$showElapsedTime = FALSE;                   // Set TRUE to show elapsed database and calculation times for each list in Project dashboard

/************************************************************/
/* NESTED LEVEL 1: No filtering done (default view)         */
/************************************************************/

if ($project == "All" AND $conf == "All") {
    echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel1.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>PROJECT DASHBOARD:</b> Select Project<br/><br/>';
    if(isset($_SESSION['arrayProjectName'])) {
        /* Show list of Projects (from the session variable that was saved for the filters */
        require('listprojects.php');
    } else {
        echo '<br/>Filter values not ready, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/************************************************************/
/* NESTED LEVEL 2: Project filtered                         */
/************************************************************/

if ($project <> "All" AND $conf == "All") {
    echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel2.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>PROJECT DASHBOARD:</b> <a href="javascript:void(0);" onclick="clearProjectFilters()">Select Project</a> -> ' . $project . '<br/><br/>';
    if(isset($_SESSION['arrayProjectName'])) {
        $projectFilter = "";
        $confFilter = "";
        /* Show general data */
        require('listgeneraldata.php');
        /* Show Build history */
        $projectFilter = " project=\"$project\"";
        require('listbuilds.php');
        /* Show Configurations for latest Build */
        echo '<br/>';
        $projectFilter = " project=\"$project\"";
        require('listconfigurations.php');
        /* Show Top failing autotests */
        echo '<br/>';
        $projectFilter = " AND project=\"$project\"";
        require('listfailingautotests.php');
    } else {
        echo '<br/>Filter values not ready, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
    if (isset($_SESSION['projectDashboardShowFullList']))                                                         // List cut mode: After diving to next level, set back to default mode when viewing the list next time
        unset($_SESSION['projectDashboardShowFullList']);
}

/************************************************************/
/* NESTED LEVEL 3: Project and Configuration filtered        */
/************************************************************/

if ($project <> "All" AND $conf <> "All") {
    echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgprojectdashboardlevel3.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>PROJECT DASHBOARD:</b> <a href="javascript:void(0);" onclick="clearProjectFilters()">Select Project</a> -> <a href="javascript:void(0);" onclick="filterConf(\'All\')">' . $project . '</a> -> ' . $conf . '<br/><br/>';
    if(isset($_SESSION['arrayProjectName'])) {
        /* Show general data */
        $projectFilter = " project=\"$project\"";
        $confFilter = " AND cfg=\"$conf\"";
        require('listgeneraldata.php');
        if ($projectConfValid) {
            /* Show Build history */
            require('listbuilds.php');
            /* Show Top failing autotests */
            echo '<br/>';
            $projectFilter = " AND project=\"$project\"";
            require('listfailingautotests.php');
        } else {
            echo "<br/>Configuration $conf not built for $project<br/>";
        }
    } else {
        echo '<br/>Filter values not ready, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
    if (isset($_SESSION['projectDashboardShowFullList']))                                                // List cut mode: After diving to next level, set back to default mode when viewing the list next time
        unset($_SESSION['projectDashboardShowFullList']);
}

/************************************************************/
/* Project not selected when Configuration selected            */
/************************************************************/

if ($project == "All" AND $conf <> "All") {
    echo '<b>PROJECT DASHBOARD:</b><br/><br/>';
    echo "<br/>(Please select a project...)<br/><br/>";
}

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

?>