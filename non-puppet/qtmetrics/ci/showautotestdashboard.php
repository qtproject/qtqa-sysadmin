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
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCONF]);
$conf = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERAUTOTEST]);
$autotest = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALETYPE]);
$timescaleType = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALEVALUE]);
$timescaleValue = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERSORTBY]);
$sortBy = $arrayFilter[1];

/* Sort field definitions */
define("AUTOTESTSORTBYNOTSET", 0);
define("AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF", 1);
define("AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF", 2);
define("AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF", 3);
define("AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF", 4);
define("AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONFALL", 5);
define("AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONFALL", 6);
define("AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONFALL", 7);
define("AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONFALL", 8);
if ($sortBy == AUTOTESTSORTBYNOTSET)
    $sortBy = AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF;                                 // Default if not set

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

/* Check the latest Build number for the Project */
if ($project <> "All") {
    foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
        if ($project == $projectValue)
            $latestProjectBuild = $_SESSION['arrayProjectBuildLatest'][$projectKey];
    }
}

/*************************************************************/
/* NESTED LEVEL 1: No autotest filtering done (default view) */
/*************************************************************/

if ($autotest == "All") {
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";    // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel1.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>AUTOTEST DASHBOARD:</b> Select Autotest<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {

        /* Get all (failing) Autotest names and required Project data */
        $arrayFailingAutotestNames = array();
        $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
        $autotestCount = 0;
        foreach($arrayFailingAutotestNames as $key => $value) {
            $autotestCount++;
        }
        $arrayProjectName = $_SESSION['arrayProjectName'];
        $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
        $arrayProjectBuildLatestTimestamp = $_SESSION['arrayProjectBuildLatestTimestamp'];

        /* Arrays for number and names Configurations for each Autotest in latest Build (categorised as significant/insignificant) */
        define("SIGNAUTOTESTBLOCKINGCONF", 0);
        $arrayFailingSignAutotestBlockingConfCount = array();
        $arrayFailingSignAutotestBlockingConfNames = array();
        $arrayFailingSignAutotestBlockingConfProjects = array();
        define("SIGNAUTOTESTINSIGNCONF", 1);
        $arrayFailingSignAutotestInsignConfCount = array();
        $arrayFailingSignAutotestInsignConfNames = array();
        $arrayFailingSignAutotestInsignConfProjects = array();
        define("INSIGNAUTOTESTBLOCKINGCONF", 2);
        $arrayFailingInsignAutotestBlockingConfCount = array();
        $arrayFailingInsignAutotestBlockingConfNames = array();
        $arrayFailingInsignAutotestBlockingConfProjects = array();
        define("INSIGNAUTOTESTINSIGNCONF", 3);
        $arrayFailingInsignAutotestInsignConfCount = array();
        $arrayFailingInsignAutotestInsignConfNames = array();
        $arrayFailingInsignAutotestInsignConfProjects = array();

        /* Step 1: Read failing Autotests for latest Build (for each Project and Configuration) */
        $timeLatestStart = microtime(true);
        $maxCount = 0;                                                                // Max count of Autotests in any category (used for sorting the lists)
        $latestAutotests = 0;                                                         // Total count of Autotests in any category (used to identify if any was found)
        $projectFilter = "";
        if ($project <> "All")                                                        // Project filtering
            $projectFilter = "WHERE test_latest.project=\"$project\"";
        $confFilter = "";
        if ($conf <> "All") {                                                         // Conf filtering
            if ($projectFilter == "")
                $confFilter = "WHERE test_latest.cfg=\"$conf\"";
            else
                $confFilter = " AND test_latest.cfg=\"$conf\"";
        }
        $sql = "SELECT name, test_latest.insignificant, test_latest.timestamp, cfg_latest.cfg, cfg_latest.insignificant
                FROM test_latest left join cfg_latest on (test_latest.project = cfg_latest.project AND
                                                        test_latest.cfg = cfg_latest.cfg AND
                                                        test_latest.build_number = cfg_latest.build_number)
                $projectFilter $confFilter";                                          // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
        $dbColumnTestName = 0;
        $dbColumnTestInsignificant = 1;
        $dbColumnTestTimestamp = 2;
        $dbColumnCfgCfg = 3;
        $dbColumnCfgInsignificant = 4;
        $timeSelectLatestStart = microtime(true);
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $numberOfRows = mysqli_num_rows($result);
        } else {
            $selectdb="USE $db";
            $result = mysql_query($selectdb) or die (mysql_error());
            $result = mysql_query($sql) or die (mysql_error());
            $numberOfRows = mysql_num_rows($result);
        }
        $timeSelectLatestEnd = microtime(true);
        for ($j=0; $j<$numberOfRows; $j++) {                                 // Loop the queried Autotests
            if ($useMysqli)
                $resultRow = mysqli_fetch_row($result);
            else
                $resultRow = mysql_fetch_row($result);
            if ($timescaleType == "Since") {                                  // When Timescale filtered ...
                if ($resultRow[$dbColumnTestTimestamp] < $timescaleValue) {   // ... and this is not within the Timescale ...
                    continue;                                                 // ... skip to the next Autotest (in the for loop)
                }
            }
            if ($resultRow[$dbColumnCfgInsignificant] == 0) {                 // Check the Autotest failing category
                if ($resultRow[$dbColumnTestInsignificant] == 0) {
                    $autotestFailureCategory = SIGNAUTOTESTBLOCKINGCONF;
                } else {
                    $autotestFailureCategory = INSIGNAUTOTESTBLOCKINGCONF;
                }
            } else {
                if ($resultRow[$dbColumnTestInsignificant] == 0) {
                    $autotestFailureCategory = SIGNAUTOTESTINSIGNCONF;
                } else {
                    $autotestFailureCategory = INSIGNAUTOTESTINSIGNCONF;
                }
            }
            for ($k=0; $k<$autotestCount; $k++) {                             // Loop all the available Autotests to collect data per one autotest
                if ($arrayFailingAutotestNames[$k] == $resultRow[$dbColumnTestName]) {
                    switch ($autotestFailureCategory) {
                        case SIGNAUTOTESTBLOCKINGCONF:
                            if (!strpos($arrayFailingSignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingSignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestBlockingConfNames[$k]
                                    = $arrayFailingSignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestBlockingConfCount[$k];
                            }
                            $arrayFailingSignAutotestBlockingConfProjects[$k]
                                = $arrayFailingSignAutotestBlockingConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case SIGNAUTOTESTINSIGNCONF:
                            if (!strpos($arrayFailingSignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {     // Each Conf to be listed only once
                                $arrayFailingSignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestInsignConfNames[$k]
                                    = $arrayFailingSignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestInsignConfCount[$k];
                            }
                            $arrayFailingSignAutotestInsignConfProjects[$k]
                                = $arrayFailingSignAutotestInsignConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case INSIGNAUTOTESTBLOCKINGCONF:
                            if (!strpos($arrayFailingInsignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) { // Each Conf to be listed only once
                                $arrayFailingInsignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestBlockingConfNames[$k]
                                    = $arrayFailingInsignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestBlockingConfCount[$k];
                            }
                            $arrayFailingInsignAutotestBlockingConfProjects[$k]
                                = $arrayFailingInsignAutotestBlockingConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case INSIGNAUTOTESTINSIGNCONF:
                            if (!strpos($arrayFailingInsignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingInsignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestInsignConfNames[$k]
                                    = $arrayFailingInsignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestInsignConfCount[$k];
                            }
                            $arrayFailingInsignAutotestInsignConfProjects[$k]
                                = $arrayFailingInsignAutotestInsignConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                    }
                    break;                                                    // Match found, skip the rest
                }
            }            // Endfor all available Autotests
        }                // Endfor queried Autotests

        /* Save data to session variables to be able to use them in nested level 2 below */
        $_SESSION['arrayFailingSignAutotestBlockingConfCount'] = $arrayFailingSignAutotestBlockingConfCount;
        $_SESSION['arrayFailingSignAutotestBlockingConfNames'] = $arrayFailingSignAutotestBlockingConfNames;
        $_SESSION['arrayFailingSignAutotestBlockingConfProjects'] = $arrayFailingSignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingSignAutotestInsignConfCount'] = $arrayFailingSignAutotestInsignConfCount;
        $_SESSION['arrayFailingSignAutotestInsignConfNames'] = $arrayFailingSignAutotestInsignConfNames;
        $_SESSION['arrayFailingSignAutotestInsignConfProjects'] = $arrayFailingSignAutotestInsignConfProjects;
        $_SESSION['arrayFailingInsignAutotestBlockingConfCount'] = $arrayFailingInsignAutotestBlockingConfCount;
        $_SESSION['arrayFailingInsignAutotestBlockingConfNames'] = $arrayFailingInsignAutotestBlockingConfNames;
        $_SESSION['arrayFailingInsignAutotestBlockingConfProjects'] = $arrayFailingInsignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingInsignAutotestInsignConfCount'] = $arrayFailingInsignAutotestInsignConfCount;
        $_SESSION['arrayFailingInsignAutotestInsignConfNames'] = $arrayFailingInsignAutotestInsignConfNames;
        $_SESSION['arrayFailingInsignAutotestInsignConfProjects'] = $arrayFailingInsignAutotestInsignConfProjects;

        if ($useMysqli) {
            mysqli_free_result($result);                                              // Free result set
        }
        $timeLatestEnd = microtime(true);

        /* Step 2: Read failing Autotests for all Builds with possible timescale filtering (ONLY ON SECOND ROUND AND WHEN SOME FILTER USED) */
        $arrayFailingSignAutotestBlockingConfCountAll = array();
        $arrayFailingSignAutotestInsignConfCountAll = array();
        $arrayFailingInsignAutotestBlockingConfCountAll = array();
        $arrayFailingInsignAutotestInsignConfCountAll = array();

        $allAutotestsFromDb = 0;
        $allAutotestsCount = 0;
        $printAllBuildsTitle = FALSE;
        $printAllBuildsData = FALSE;
        if ($project <> "All" OR $conf <> "All" OR $timescaleType <> "All")
            $printAllBuildsTitle = TRUE;                                              // All Builds title printed only when a Project, Configuration or Timescale filtered (database/server performance issue with huge data amount)
        if ($round == 2 AND ($project <> "All" OR $conf <> "All" OR $timescaleType <> "All"))
            $printAllBuildsData = TRUE;                                               // All Builds data printed only on 2nd round and when a Project, Configuration or Timescale filtered (database/server performance issue with huge data amount)
        if ($printAllBuildsData) {
            $timeAllStart = microtime(true);

            /* Read Autotests from the database */
            $projectFilter = "";
            if ($project <> "All")
                $projectFilter = "WHERE test.project=\"$project\"";
            $confFilter = "";
            if ($conf <> "All")
                if ($projectFilter == "")
                    $confFilter = "WHERE test.cfg=\"$conf\"";
                else
                    $confFilter = " AND test.cfg=\"$conf\"";
            $timeSelectAllStart = microtime(true);
            $sql = "SELECT name, test.insignificant, test.timestamp, cfg.insignificant
                    FROM test left join cfg on (test.project=cfg.project AND test.cfg=cfg.cfg AND test.build_number=cfg.build_number)
                    $projectFilter $confFilter";                                      // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
            $dbColumnTestName = 0;
            $dbColumnTestInsignificant = 1;
            $dbColumnTestTimestamp = 2;
            $dbColumnTestConfInsignificant = 3;
            if ($useMysqli) {
                $result2 = mysqli_query($conn, $sql);
                $numberOfRows2 = mysqli_num_rows($result2);
            } else {
                $selectdb="USE $db";
                $result2 = mysql_query($selectdb) or die (mysql_error());
                $result2 = mysql_query($sql) or die (mysql_error());
                $numberOfRows2 = mysql_num_rows($result2);
            }
            $allAutotestsFromDb = $numberOfRows2;
            $timeSelectAllEnd = microtime(true);

            /* Save the counts for each the Autotest */
            for ($j=0; $j<$numberOfRows2; $j++) {
                if ($useMysqli)
                    $resultRow2 = mysqli_fetch_row($result2);
                else
                    $resultRow2 = mysql_fetch_row($result2);
                if ($timescaleType == "Since") {                                      // When Timescale filtered ...
                    if ($resultRow2[$dbColumnTestTimestamp] < $timescaleValue) {      // ... and this is not within the Timescale ...
                        continue;                                                     // ... skip to the next Autotest (in the for loop)
                    }
                }
                if ($resultRow2[$dbColumnTestConfInsignificant] == 0) {               // Check the Autotest failing category
                    if ($resultRow2[$dbColumnTestInsignificant] == 0) {
                        $autotestFailureCategory = SIGNAUTOTESTBLOCKINGCONF;
                    } else {
                        $autotestFailureCategory = INSIGNAUTOTESTBLOCKINGCONF;
                    }
                } else {
                    if ($resultRow2[$dbColumnTestInsignificant] == 0) {
                        $autotestFailureCategory = SIGNAUTOTESTINSIGNCONF;
                    } else {
                        $autotestFailureCategory = INSIGNAUTOTESTINSIGNCONF;
                    }
                }
                for ($k=0; $k<$autotestCount; $k++) {                                 // Loop all the Autotests to collect the counts for each one
                    if ($arrayFailingAutotestNames[$k] == $resultRow2[$dbColumnTestName]) {
                        switch ($autotestFailureCategory) {
                            case SIGNAUTOTESTBLOCKINGCONF:
                                    $arrayFailingSignAutotestBlockingConfCountAll[$k]++;
                                    // When sorted by this field increase the maxCount for sorting (optimized the sorting loop count); same for all cases below
                                    if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONFALL AND $arrayFailingSignAutotestBlockingConfCountAll[$k] > $maxCount)
                                        $maxCount = $arrayFailingSignAutotestBlockingConfCountAll[$k];
                                break;
                            case SIGNAUTOTESTINSIGNCONF:
                                    $arrayFailingSignAutotestInsignConfCountAll[$k]++;
                                    if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONFALL AND $arrayFailingSignAutotestInsignConfCountAll[$k] > $maxCount)
                                        $maxCount = $arrayFailingSignAutotestInsignConfCountAll[$k];
                                break;
                            case INSIGNAUTOTESTBLOCKINGCONF:
                                    $arrayFailingInsignAutotestBlockingConfCountAll[$k]++;
                                    if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONFALL AND $arrayFailingInsignAutotestBlockingConfCountAll[$k] > $maxCount)
                                        $maxCount = $arrayFailingInsignAutotestBlockingConfCountAll[$k];
                                break;
                            case INSIGNAUTOTESTINSIGNCONF:
                                    $arrayFailingInsignAutotestInsignConfCountAll[$k]++;
                                    if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONFALL AND $arrayFailingInsignAutotestInsignConfCountAll[$k] > $maxCount)
                                        $maxCount = $arrayFailingInsignAutotestInsignConfCountAll[$k];
                                break;
                        }
                        $allAutotestsCount++;
                        break;                                                        // Match found, skip the rest
                    }
                }
            }
            $timeAllEnd = microtime(true);
        }

        if ($useMysqli)
            mysqli_free_result($result2);                                             // Free result set

        /* Print the used filters */
        if ($project <> "All" OR $conf <> "All" OR $timescaleType <> "All") {
            echo '<table>';
            if ($project <> "All")
                echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
            if ($conf <> "All")
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
            if ($timescaleType == "Since")
                echo '<tr><td>Since:</td><td class="tableCellBackgroundTitle">' . $timescaleValue . '</td></tr>';
            if ($project <> "All")
                echo '<tr><td>Latest Build:</td><td>' . $latestProjectBuild . '</td></tr>';
            echo '</table>';
            echo '<br>';
        }

        /* Print the titles */
        echo '<table class="fontSmall">';
        echo '<tr>';
        echo '<th></th>';
        if ($timescaleType == "All")
            echo '<th colspan="4" class="tableBottomBorder tableSideBorder">LATEST BUILD (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
        if ($timescaleType == "Since")
            echo '<th colspan="4" class="tableBottomBorder tableSideBorder">LATEST BUILD SINCE ' . $timescaleValue . '</th>';
        if ($printAllBuildsTitle) {
            if ($round == 1) {
                echo '<th colspan="4" class="tableBottomBorder tableSideBorder">Loading All Builds <span class="loading"><span>.</span><span>.</span><span>.</span></span></th>';
            } else {
                if ($timescaleType == "All")
                    echo '<th colspan="4" class="tableBottomBorder tableSideBorder">ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
                if ($timescaleType == "Since")
                    echo '<th colspan="4" class="tableBottomBorder tableSideBorder">ALL BUILDS SINCE ' . $timescaleValue . '</th>';
            }
        }
        echo '</tr>';
        echo '<tr>';
        echo '<th></th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Significant Autotests</th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Insignificant Autotests</th>';
        if ($printAllBuildsTitle) {
            echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Significant Autotests</th>';
            echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Insignificant Autotests</th>';
        }
        echo '</tr>';
        echo '<tr class="tableBottomBorder">';
        echo '<th class="tableCellAlignRight"><a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgfailuredescription.html\')"> Failure category</a></th>';
        echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedDark">';
        if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF)
            echo '1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF . ')">
                  1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
        echo '</td>';
        echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRed">';
        if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF)
            echo '2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF . ')">
                  2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
        echo '</td>';
        echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedLight">';
        if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF)
            echo '3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF . ')">
                  3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
        echo '</td>';
        echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRedLight">';
        if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF)
            echo '4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF . ')">
                  4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
        echo '</td>';
        if ($printAllBuildsTitle) {
            echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedDark">';
            if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONFALL)
                echo '1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONFALL . ')">
                      1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
            echo '</td>';
            echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRed">';
            if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONFALL)
                echo '2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONFALL . ')">
                      2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
            echo '</td>';
            echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedLight">';
            if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONFALL)
                echo '3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONFALL . ')">
                      3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
            echo '</td>';
            echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRedLight">';
            if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONFALL)
                echo '4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONFALL . ')">
                      4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&raquo;</b></a>';
            echo '</td>';
        }
        echo '</tr>';

        /* Print list of Autotests */
        $k = 0;
        $listCutMode = FALSE;
        for ($countOrder=$maxCount; $countOrder>=0; $countOrder--) {                   // Sort the list by looping from the highest count
            for ($i=0; $i<$autotestCount; $i++) {                                      // Loop the Autotests
                switch ($sortBy) {                                                     // Check the next value to print in sorting
                    case AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingSignAutotestBlockingConfCount[$i];
                        break;
                    case AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF:
                        $sortFieldValue = $arrayFailingSignAutotestInsignConfCount[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestBlockingConfCount[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestInsignConfCount[$i];
                        break;
                    case AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONFALL:
                        $sortFieldValue = $arrayFailingSignAutotestBlockingConfCountAll[$i];
                        break;
                    case AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONFALL:
                        $sortFieldValue = $arrayFailingSignAutotestInsignConfCountAll[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONFALL:
                        $sortFieldValue = $arrayFailingInsignAutotestBlockingConfCountAll[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONFALL:
                        $sortFieldValue = $arrayFailingInsignAutotestInsignConfCountAll[$i];
                        break;
                }
                if ($sortFieldValue == $countOrder) {                                  // Print the ones that are next in the sorting order
                    if ($arrayFailingSignAutotestBlockingConfCount[$i]
                        + $arrayFailingSignAutotestInsignConfCount[$i]
                        + $arrayFailingInsignAutotestBlockingConfCount[$i]
                        + $arrayFailingInsignAutotestInsignConfCount[$i]
                        + $arrayFailingSignAutotestBlockingConfCountAll[$i]
                        + $arrayFailingSignAutotestInsignConfCountAll[$i]
                        + $arrayFailingInsignAutotestBlockingConfCountAll[$i]
                        + $arrayFailingInsignAutotestInsignConfCountAll[$i] > 0) {     // Skip if not any failures in Latest Build
                        if ($k % 2 == 0)
                            echo '<tr>';
                        else
                            echo '<tr class="tableBackgroundColored">';

                        /* Autotest name */
                        echo '<td><a href="javascript:void(0);" onclick="filterAutotest(\'' . $arrayFailingAutotestNames[$i] . '\')">' . $arrayFailingAutotestNames[$i] . '</a></td>';

                        /* Latest Build: Significant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestBlockingConfCount[$i] > 0)
                            echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingSignAutotestBlockingConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                          // Skip leading '<br>' set above
                        else
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                        /* Latest Build: Significant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestInsignConfCount[$i] > 0)
                            echo '<td class="tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingSignAutotestInsignConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                          // Skip leading '<br>' set above
                        else
                            echo '<td class="tableCellCentered">-</td>';

                        /* Latest Build: Insignificant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestBlockingConfCount[$i] > 0)
                            echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestBlockingConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                          // Skip leading '<br>' set above
                        else
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                        /* Latest Build: Insignificant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestInsignConfCount[$i] > 0)
                            echo '<td class="tableRightBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestInsignConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                          // Skip leading '<br>' set above
                        else
                            echo '<td class="tableRightBorder tableCellCentered">-</td>';

                        if ($printAllBuildsTitle) {
                            if ($printAllBuildsData) {
                                /* All Builds: Significant Autotests in blocking Configuration (with names as a popup) */
                                if ($arrayFailingSignAutotestBlockingConfCountAll[$i] > 0)
                                    echo '<td class="tableLeftBorder tableCellCentered">'
                                        . $arrayFailingSignAutotestBlockingConfCountAll[$i] . '</td>';
                                else
                                    echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                                /* All Builds: Significant Autotests in insignificant Configuration (with names as a popup) */
                                if ($arrayFailingSignAutotestInsignConfCountAll[$i] > 0)
                                    echo '<td class="tableCellCentered">'
                                        . $arrayFailingSignAutotestInsignConfCountAll[$i] . '</td>';
                                else
                                    echo '<td class="tableCellCentered">-</td>';

                                /* All Builds: Insignificant Autotests in blocking Configuration (with names as a popup) */
                                if ($arrayFailingInsignAutotestBlockingConfCountAll[$i] > 0)
                                    echo '<td class="tableLeftBorder tableCellCentered">'
                                        . $arrayFailingInsignAutotestBlockingConfCountAll[$i] . '</td>';
                                else
                                    echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                                /* All Builds: Insignificant Autotests in insignificant Configuration (with names as a popup) */
                                if ($arrayFailingInsignAutotestInsignConfCountAll[$i] > 0)
                                    echo '<td class="tableRightBorder tableCellCentered">'
                                        . $arrayFailingInsignAutotestInsignConfCountAll[$i] . '</td>';
                                else
                                    echo '<td class="tableRightBorder tableCellCentered">-</td>';
                            } else {
                                echo '<td class="tableLeftBorder tableCellCentered"></td>';
                                echo '<td class="tableCellCentered"></td>';
                                echo '<td class="tableLeftBorder tableCellCentered"></td>';
                                echo '<td class="tableRightBorder tableCellCentered"></td>';
                            }
                        }

                        echo '</tr>';
                        $k++;
                    }
                    if ($k > 12 AND !isset($_SESSION['failingAutotestsShowFullList'])) {     // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
                        $listCutMode = TRUE;
                        break;
                    }
                }         // Endif sorting order
            }             // Endfor Autotests
        }                 // Endfor sorting
        $printedAutotests = $k;

        /* Print Totals summary row */
        if ($listCutMode == FALSE) {
            $failingSignAutotestBlockingConfCount = 0;
            $failingSignAutotestInsignConfCount = 0;
            $failingInsignAutotestBlockingConfCount = 0;
            $failingInsignAutotestInsignConfCount = 0;
            $failingSignAutotestBlockingConfCountAll = 0;
            $failingSignAutotestInsignConfCountAll = 0;
            $failingInsignAutotestBlockingConfCountAll = 0;
            $failingInsignAutotestInsignConfCountAll = 0;
            for ($i=0; $i<$autotestCount; $i++) {                                 // Loop the Autotests
                $failingSignAutotestBlockingConfCount = $failingSignAutotestBlockingConfCount + $arrayFailingSignAutotestBlockingConfCount[$i];
                $failingSignAutotestInsignConfCount = $failingSignAutotestInsignConfCount + $arrayFailingSignAutotestInsignConfCount[$i];
                $failingInsignAutotestBlockingConfCount = $failingInsignAutotestBlockingConfCount + $arrayFailingInsignAutotestBlockingConfCount[$i];
                $failingInsignAutotestInsignConfCount = $failingInsignAutotestInsignConfCount + $arrayFailingInsignAutotestInsignConfCount[$i];
                $failingSignAutotestBlockingConfCountAll = $failingSignAutotestBlockingConfCountAll + $arrayFailingSignAutotestBlockingConfCountAll[$i];
                $failingSignAutotestInsignConfCountAll = $failingSignAutotestInsignConfCountAll + $arrayFailingSignAutotestInsignConfCountAll[$i];
                $failingInsignAutotestBlockingConfCountAll = $failingInsignAutotestBlockingConfCountAll + $arrayFailingInsignAutotestBlockingConfCountAll[$i];
                $failingInsignAutotestInsignConfCountAll = $failingInsignAutotestInsignConfCountAll + $arrayFailingInsignAutotestInsignConfCountAll[$i];
            }
            echo '<tr>';
            echo '<td class="tableRightBorder tableTopBorder">total (' . $printedAutotests . ')</td>';
            echo '<td class="tableCellCentered tableTopBorder">' . $failingSignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingSignAutotestInsignConfCount . '</td>';
            echo '<td class="tableCellCentered tableTopBorder">' . $failingInsignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingInsignAutotestInsignConfCount . '</td>';
            if ($printAllBuildsTitle) {
                if ($printAllBuildsData) {
                    echo '<td class="tableCellCentered tableTopBorder">' . $failingSignAutotestBlockingConfCountAll . '</td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingSignAutotestInsignConfCountAll . '</td>';
                    echo '<td class="tableCellCentered tableTopBorder">' . $failingInsignAutotestBlockingConfCountAll . '</td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingInsignAutotestInsignConfCountAll . '</td>';
                } else {
                    echo '<td class="tableCellCentered tableTopBorder"></td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered"></td>';
                    echo '<td class="tableCellCentered tableTopBorder"></td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered"></td>';
                }
            }
            echo '</tr>';
        }
        echo '</table>';

        if ($round == 2 AND !isset($_SESSION['failingAutotestsShowFullList'])) {
            echo '<br/><a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Show full list...</a><br/><br/>';  // List cut mode: If only first n items shown, add a link to see all
            $_SESSION['failingAutotestsShowFullList'] = TRUE;                                                             // List cut mode: After refreshing the metrics box, show all items instead (set below to return the default 'cut mode')
        }

    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }

    /* Elapsed time */
    if ($showElapsedTime) {
        $timeEnd = microtime(true);
        $time = round($timeEnd - $timeStart, 4);
        $timeDbConnect = round($timeConnect - $timeStart, 4);
        $timeLatest = round($timeLatestEnd - $timeLatestStart, 4);
        $dbTimeSelectLatest = round($timeSelectLatestEnd - $timeSelectLatestStart, 4);
        $timeCalculationLatest = round($timeLatest - $dbTimeSelectLatest, 4);
        if (isset($timeAllEnd))
            $timeAll = round($timeAllEnd - $timeAllStart, 4);
        if (isset($timeSelectAllEnd))
            $dbTimeSelectAll = round($timeSelectAllEnd - $timeSelectAllStart, 4);
        if (isset($timeAll))
            $timeCalculationAll = round($timeAll - $dbTimeSelectAll, 4);
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "<b>Total time:</b>&nbsp $time s (round $round)<br>";
        echo "Latest builds: $timeLatest s (database connect time: $timeDbConnect s, database read time: $dbTimeSelectLatest s, calculation: $timeCalculationLatest s)<br>";
        if (isset($timeAll))
            echo "All builds:&nbsp&nbsp&nbsp&nbsp&nbsp $timeAll s (database connect time: $timeDbConnect s, database read time: $dbTimeSelectAll s, calculation: $timeCalculationAll s)<br>";
        echo "</li></ul>";
        echo "</div>";
    }

}

/*************************************************************/
/* NESTED LEVEL 2: Autotest filtered                         */
/*************************************************************/

if ($autotest <> "All") {
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";    // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel2.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>AUTOTEST DASHBOARD:</b> <a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Select Autotest</a> -> ' . $autotest . '<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {
        $i = 0;
        foreach($_SESSION['arrayAutotestName'] as $key => $value) {
            if ($autotest == $value) {
                $timeAutotestStart = microtime(true);

                /* Read Autotest history data from the database */
                $projectFilter = "";
                if ($project <> "All")
                    $projectFilter = "AND project=\"$project\"";
                $confFilter = "";
                if ($conf <> "All")
                    $confFilter = " AND cfg=\"$conf\"";
                $sql = "SELECT name, project, build_number, cfg, insignificant, timestamp
                        FROM test
                        WHERE name=\"$autotest\" $projectFilter $confFilter
                        ORDER BY project, build_number, cfg";                             // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
                $dbColumnTestName = 0;
                $dbColumnTestProject = 1;
                $dbColumnTestBuildNumber = 2;
                $dbColumnTestCfg = 3;
                $dbColumnTestInsignificant = 4;
                $dbColumnTestTimestamp = 5;
                if ($useMysqli) {
                    $result = mysqli_query($conn, $sql);
                    $numberOfRows = mysqli_num_rows($result);
                } else {
                    $selectdb="USE $db";
                    $result = mysql_query($selectdb) or die (mysql_error());
                    $result = mysql_query($sql) or die (mysql_error());
                    $numberOfRows = mysql_num_rows($result);
                }

                /* Save the counts for each the Autotest */
                $arrayFailingAutotestProjectNames = array();
                $arrayFailingAutotestProjectNames = $_SESSION['arrayProjectName'];
                $arrayFailingAutotestProjectConfNames = array();
                $arrayFailingAutotestProjectConfBuilds = array();
                $arrayFailingAutotestConfNames = array();
                $arrayFailingAutotestConfBuilds = array();
                $checkedProject = "";
                for ($j=0; $j<$numberOfRows; $j++) {
                    if ($useMysqli)
                        $resultRow = mysqli_fetch_row($result);
                    else
                        $resultRow = mysql_fetch_row($result);
                    if ($resultRow[$dbColumnTestProject] <> $checkedProject) {                          // Clear Project specific Conf list when Project changes (the database list is in Project order)
                        $arrayFailingAutotestConfNames = array();
                        $arrayFailingAutotestConfBuilds = array();
                        $checkedProject = $resultRow[$dbColumnTestProject];
                    }
                    foreach($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {        // Find the correct Project
                        if ($projectValue == $resultRow[$dbColumnTestProject]) {
                            foreach($_SESSION['arrayConfName'] as $confKey => $confValue) {             // Find the correct Configuration
                                if ($confValue == $resultRow[$dbColumnTestCfg]) {
                                    $arrayFailingAutotestConfNames[$confKey] = $confValue;
                                    $confString = ',' . $resultRow[$dbColumnTestBuildNumber]
                                        . '-' . $resultRow[$dbColumnTestInsignificant]
                                        . '-' . $resultRow[$dbColumnTestTimestamp] . ',';               // Format is ",buildNumber-testInsign" (where testInsign = 0/1); This will be used later for search usage when printing
                                    $arrayFailingAutotestConfBuilds[$confKey] = $arrayFailingAutotestConfBuilds[$confKey] . $confString;
                                    break;                                                              // Match found, skip the rest
                                }
                            }
                            $arrayFailingAutotestProjectConfNames[$projectKey] = $arrayFailingAutotestConfNames;     // Save Project specific Conf list (it uses the Project and Conf ids as saved in the initial loading of the page)
                            $arrayFailingAutotestProjectConfBuilds[$projectKey] = $arrayFailingAutotestConfBuilds;
                            break;                                                                      // Match found, skip the rest
                        }
                    }
                }
                $timeAutotestEnd = microtime(true);

                if ($useMysqli)
                    mysqli_free_result($result);                                              // Free result set

                /* Print the used filters */
                echo '<table>';
                echo '<tr><td>Autotest: </td><td class="tableCellBackgroundTitle">' . $autotest . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Project: </td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
                if ($conf <> "All")
                    echo '<tr><td>Configuration: </td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
                if ($timescaleType == "Since")
                    echo '<tr><td>Since:</td><td class="tableCellBackgroundTitle">' . $timescaleValue . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Latest Build:</td><td>' . $latestProjectBuild . '</td></tr>';
                echo '</table>';

                /* Latest Build title */
                echo '<br/><b>Projects and Configurations (their latest Build) by
                      <a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgfailuredescription.html\')">failure category</a>
                      </b><br/><br/>';
                echo '<table>';

                /* Latest Build: Significant Autotests in blocking Configuration (saved in the nested level 1) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingSignAutotestBlockingConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Significant Failures:</td>
                          <td>Not in any Blocking Configurations</td>
                          <td></td>';
                else
                    echo '<td>Significant Failures:</td>
                          <td>In ' . $count . ' Blocking Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestBlockingConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestBlockingConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                /* Latest Build: Significant Autotests in insignificant Configuration with names as a popup (saved in the nested level 1) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingSignAutotestInsignConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not in any Insignificant Configurations</td>
                          <td></td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Insignificant Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestInsignConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestInsignConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';
                echo '<tr><td><br></td><td><br></td><td><br></td></tr>';               // Empty row (3 columns)

                /* Latest Build: Insignificant Autotests in blocking Configuration with names as a popup (saved in the nested level 1) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingInsignAutotestBlockingConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Insignificant Failures:</td>
                          <td>Not in any Blocking Configurations</td>
                          <td></td>';
                else
                    echo '<td>Insignificant Failures:</td>
                          <td>In ' . $count . ' Blocking Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestBlockingConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestBlockingConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                /* Latest Build: Insignificant Autotests in insignificant Configuration with names as a popup (saved in the nested level 1) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingInsignAutotestInsignConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not in any Insignificant Configurations</td>
                          <td></td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Insignificant Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestInsignConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestInsignConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';
                echo '</table><br/>';

                /* Autotest history data (saved here in nested level 2) */
                echo '<br/><b>Result history by Project Configuration</b> (last ' . HISTORYBUILDCOUNT . ' Builds)<br/><br/>';
                echo '<table class="fontSmall">';
                echo '<tr class="tableCellAlignLeft">';
                echo '<th class="tableBottomBorder">Project</th>';
                echo '<th class="tableBottomBorder">Configuration</th>';
                echo '<th colspan="' . HISTORYBUILDCOUNT . '" class="tableBottomBorder tableSideBorder">Results in Builds';
                if ($timescaleType == "Since")
                    echo ' (since ' . $timescaleValue . ')';
                echo ' - see <a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgautotestresultdescription.html\')">notation</a>';
                echo '</th>';
                echo '</tr>';
                $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
                $k = 0;
                $previousProject = "";
                foreach($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {
                    foreach($_SESSION['arrayConfName'] as $confKey => $confValue) {
                        if ($arrayFailingAutotestProjectConfNames[$projectKey][$confKey] <> "") {
                            if ($k % 2 == 0)
                                echo '<tr>';
                            else
                                echo '<tr class="tableBackgroundColored">';
                            if ($projectValue == $previousProject) {                             // For better readability print the Project (and a line) only when it changes
                                echo '<td></td>';
                                echo '<td>' . $confValue . '</td>';
                            } else {
                                echo '<td class="tableTopBorder">' . $projectValue . '</td>';
                                echo '<td class="tableTopBorder">' . $confValue . '</td>';
                            }
                            $previousProject = $projectValue;
                            $lastPrintedBuild = $arrayProjectBuildLatest[$projectKey];
                            $firstPrintedBuild = 1;
                            if ($lastPrintedBuild > HISTORYBUILDCOUNT)                           // Limit number of Builds printed (the last HISTORYBUILDCOUNT ones)
                                $firstPrintedBuild = $lastPrintedBuild - HISTORYBUILDCOUNT + 1;
                            if ($lastPrintedBuild <= HISTORYBUILDCOUNT) {                        // If latest Build number is less than the HISTORYBUILDCOUNT ...
                                for ($i=1; $i<=HISTORYBUILDCOUNT-$lastPrintedBuild; $i++) {
                                    if (HISTORYBUILDCOUNT - $lastPrintedBuild >= $i)
                                        echo '<td class="tableSingleBorder"></td>';              // ... print empty cells to the left
                                }
                            }
                            for ($i=$firstPrintedBuild; $i<=$lastPrintedBuild; $i++) {           // Print the Builds
                                /* Check Configuration result and significance from database (both for failed and successful Autotests) */
                                $sql = "SELECT result, insignificant, timestamp
                                        FROM cfg
                                        WHERE project=\"$projectValue\" AND cfg=\"$confValue\" AND build_number=$i";     // Will return one row
                                $dbColumnCfgResult = 0;
                                $dbColumnCfgInsignificant = 1;
                                $dbColumnCfgTimestamp = 2;
                                if ($useMysqli) {
                                    $result2 = mysqli_query($conn, $sql);
                                    $resultRow2 = mysqli_fetch_row($result2);
                                } else {
                                    $selectdb="USE $db";
                                    $result2 = mysql_query($selectdb) or die (mysql_error());
                                    $result2 = mysql_query($sql) or die (mysql_error());
                                    $resultRow2 = mysql_fetch_row($result2);
                                }
                                $buildResult = "other";
                                if ($resultRow2[$dbColumnCfgResult] == "SUCCESS")
                                    $buildResult = "SUCCESS";
                                if ($resultRow2[$dbColumnCfgResult] == "FAILURE")
                                    $buildResult = "FAILURE";
                                $booBuildSign = FALSE;
                                if ($resultRow2[$dbColumnCfgInsignificant] == 0)
                                    $booBuildSign = TRUE;
                                $booBuildOutOfTimescale = FALSE;
                                if ($timescaleType == "Since")
                                    if ($resultRow2[$dbColumnCfgTimestamp] < $timescaleValue)
                                        $booBuildOutOfTimescale = TRUE;
                                /* Check Autotest result and significance from the array saved above */
                                $booSignAutotest = strpos(',' . $arrayFailingAutotestProjectConfBuilds[$projectKey][$confKey], ',' . strval($i) . '-0');
                                $booFailedAutotest = strpos(',' . $arrayFailingAutotestProjectConfBuilds[$projectKey][$confKey], ',' . strval($i) . '-');
                                $buildNumberString = createBuildNumberString($i);
                                /* Print the failed Build */
                                if ($booFailedAutotest) {
                                    if ($booSignAutotest) {
                                        if ($booBuildSign) {
                                            if ($booBuildOutOfTimescale) {
                                                // Dark red background (and bold) to indicate significant failure in blocking Conf, grey font color to indicate out of Timescale
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedDark fontColorGrey"><b>' . $i . '</b></td>';
                                            } else {
                                                // Dark red background (and bold) to indicate significant failure in blocking Conf; link to log file
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedDark"><b>
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></b></td>';
                                            }
                                        } else {
                                            if ($booBuildOutOfTimescale) {
                                                // Red background to indicate significant failure in insignificant Conf, grey font color to indicate out of Timescale
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed fontColorGrey">' . $i . '</td>';
                                            } else {
                                                // Red background to indicate significant failure in insignificant Conf; link to log file
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed">
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                            }
                                        }
                                    } else {
                                        if ($booBuildOutOfTimescale) {
                                            // Light red background to indicate insignificant failure, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedLight fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // Light red background to indicate insignificant failure; link to log file
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedLight">
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    }
                                /* Print the successful or not run Build */
                                } else {
                                    if ($buildResult == "SUCCESS") {
                                        if ($booBuildOutOfTimescale) {
                                            // Green background to indicate success, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // Green background to indicate success; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                  . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    } else {       // It's not checked whether the failed Build has some failed Autotests or not i.e. to interpret if the Autotest here was SUCCESS or not run at all i.e. the Build was aborted
                                        if ($booBuildOutOfTimescale) {
                                            // White background to indicate not a failure, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // White background to indicate not a failure; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                  . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    }
                                }
                            }
                            echo '</tr>';
                            $k++;
                        }
                    }
                }
                echo '<tr class="tableTopBorder"><td></td><td></td><td colspan="' . HISTORYBUILDCOUNT . '"></td></tr>';    // Print bottom line to the end of the table
                echo '</table><br/>';

                if ($useMysqli)
                    mysqli_free_result($result2);                       // Free result set

                break;                                                  // Match found, skip the rest
            }
            $i++;
        }
    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }

    /* Elapsed time */
    if ($showElapsedTime) {
        $timeEnd = microtime(true);
        $timeDbConnect = round($timeConnect - $timeStart, 2);
        $timeDbRead = round($timeEnd - $timeConnect, 2);
        $time = round($timeEnd - $timeStart, 2);
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "Total time: $time s (database connect time: $timeDbConnect s, database read time: $timeDbRead s)";
        echo "</li></ul>";
        echo "</div>";
    }

}

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

?>
