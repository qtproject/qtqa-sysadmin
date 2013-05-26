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

/* Following 'input' variabes must be set prior to including this file */
    // $_SESSION['arrayProjectName']
    // $_SESSION['arrayProjectBuildLatest']
    // $_SESSION['arrayProjectBuildScopeMin']
    // $project
    // $projectFilter
    // $confFilter
    // $showElapsedTime
    // $timeStart
    // $timeConnect

/* Check the latest Build number for the Project */
foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
    if ($project == $projectValue)
        $latestProjectBuild = $_SESSION['arrayProjectBuildLatest'][$projectKey];
}

/* Check the blocking (non-insignificant) Configurations (to skip printing significant Autotests for insignificant Configurations) */
$arrayBlockingConfs = array();
$sql = "SELECT cfg
        FROM cfg
        WHERE insignificant=0 $projectFilter $confFilter AND build_number=$latestProjectBuild";
$dbColumnCfgCfg = 0;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
for ($i=0; $i<$numberOfRows; $i++) {                                          // Loop the Configurations
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    $arrayBlockingConfs[] = $resultRow[$dbColumnCfgCfg];
}

/* Read Autotest data from database */
$sql = "SELECT name,project,build_number,cfg
        FROM test
        WHERE insignificant=0 $projectFilter $confFilter AND build_number=$latestProjectBuild
        ORDER BY name, project, build_number DESC";
$dbColumnTestName = 0;
$dbColumnTestProject = 1;
$dbColumnTestBuild = 2;
$dbColumnTestCfg = 3;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
$timeRead = microtime(true);

/* Result storages to be printed */
$arrayAutotestNames = array();
$arrayAutotestTotals = array();
$arrayAutotestConfLinks = array();

/* Get the the significant Autotests */
$j = -1;                                                                      // Counter for resulting metrics rows (one per each autotest)
$itemname="empty";
$failedAutotestCount = 0;
for ($i=0; $i<$numberOfRows; $i++) {                                          // Loop the rows (each autotest may appears several times i.e. for several Configurations)
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    if($itemname <> "empty" AND $resultRow[$dbColumnTestName] <> $itemname) { // STEP 3: New Autotest name in the list ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
                                                                              // (this means the results of one autotest is now calculated, therefore save the results)
        $arrayAutotestTotals[$j] = $autotestConfCount;                        // -> Save the calculated totals as a new row for one autotest
    }
    if ($itemname == "empty" OR $resultRow[$dbColumnTestName] <> $itemname) { // STEP 1: First or new Autotest name ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
        $j++;
        $arrayAutotestNames[$j] = $resultRow[$dbColumnTestName];              // -> Save new name
        $arrayAutotestTotals[$j] = 0;                                         // Initialize
        $autotestConfCount = 0;                                               // Initialize
        $arrayAutotestConfLinks[$j] = "";                                     // Initialize
        $itemname = $resultRow[$dbColumnTestName];
    }
    foreach($arrayBlockingConfs as $key => $value) {                          // Loop all blocking Configurations
        if ($resultRow[$dbColumnTestCfg] == $value) {                         // If the Configuration for this Autotest is a blocking one
            $autotestConfCount++;                                             // STEP 2: Save data for the Autotest
            $failedAutotestCount++;
            $buildstring = $resultRow[$dbColumnTestBuild];                    // Create the link url to build directory...
            if ($resultRow[$dbColumnTestBuild] < 10000)
                $buildstring = '0' . $resultRow[$dbColumnTestBuild];
            if ($resultRow[$dbColumnTestBuild] < 1000)
                $buildstring = '00' . $resultRow[$dbColumnTestBuild];
            if ($resultRow[$dbColumnTestBuild] < 100)
                $buildstring = '000' . $resultRow[$dbColumnTestBuild];
            if ($resultRow[$dbColumnTestBuild] < 10)
                $buildstring = '0000' . $resultRow[$dbColumnTestBuild];
            $link = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildstring
                . '/' . $resultRow[$dbColumnTestCfg] . '" target="_blank">' . $resultRow[$dbColumnTestCfg] . '</a>';  // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86
            $arrayAutotestConfLinks[$j] = $arrayAutotestConfLinks[$j] . ', ' . $link;
        }
    }
}
$arrayAutotestTotals[$j] = $autotestConfCount;                                // STEP 4: All Autotests checked: Save the calculated totals for the last autotest

if ($useMysqli)
    mysqli_free_result($result);                                              // Free result set

/* Print the data */
echo '<b>Failed Autotests that caused Build failure</b> (significant Autotests in blocking Configurations)<br/><br/>';
if ($failedAutotestCount > 0) {
    echo '<table class="fontSmall">';
    echo '<tr>';
    echo '<th></th>';
    echo '<th colspan="5" class="tableBottomBorder tableSideBorder">LATEST BUILD</th>';
    echo '</tr>';
    echo '<tr class="tableBottomBorder">';
    echo '<td></td>';
    echo '<td class="tableSideBorder">List of Configurations (link to testresults directory)</td>';
    echo '</tr>';
    $j = 0;
    for ($i=0; $i<$failedAutotestCount; $i++) {                               // Loop to print autotests
        if ($arrayAutotestTotals[$i] > 0) {
            if ($j % 2 == 0)
                echo '<tr>';
            else
                echo '<tr class="tableBackgroundColored">';
            echo '<td>'. $arrayAutotestNames[$i] . '</td>';
            echo '<td class="tableSideBorder">'. substr($arrayAutotestConfLinks[$i],2) . '</td>';     // Skip leading ", "
            echo "</tr>";
            $j++;
        }
    }
    echo "</table>";
} else {
    echo "(Not any Failed Autotests)<br/>";
}

/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $timeDbConnect = round($timeConnect - $timeStart, 2);
    $timeDbRead = round($timeRead - $timeConnect, 2);
    $timeCalc = round($timeEnd - $timeRead, 2);
    $time = round($timeEnd - $timeStart, 2);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "Total time: $time s (database connect time: $timeDbConnect s, database read time: $timeDbRead s, calculation time: $timeCalc s)";
    echo "</li></ul>";
    echo "</div>";
} else {
    echo "<br>";
}

?>