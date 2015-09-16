<?php
#############################################################################
##
## Copyright (C) 2015 The Qt Company Ltd.
## Contact: http://www.qt.io/licensing/
##
## This file is part of the Quality Assurance module of the Qt Toolkit.
##
## $QT_BEGIN_LICENSE:LGPL21$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and The Qt Company. For licensing terms
## and conditions see http://www.qt.io/terms-conditions. For further
## information use the contact form at http://www.qt.io/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 or version 3 as published by the Free
## Software Foundation and appearing in the file LICENSE.LGPLv21 and
## LICENSE.LGPLv3 included in the packaging of this file. Please review the
## following information to ensure the GNU Lesser General Public License
## requirements will be met: https://www.gnu.org/licenses/lgpl.html and
## http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## As a special exception, The Qt Company gives you certain additional
## rights. These rights are described in The Qt Company LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## $QT_END_LICENSE$
##
#############################################################################

require_once(__DIR__.'/../Factory.php');

/**
 * Database unit test class
 * Some of the tests require the test data as inserted into database with qtmetrics_insert.sql
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @version   0.9
 * @since     21-07-2015
 * @author    Juha Sippola
 */

class DatabaseTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test getProjects
     * @dataProvider testGetProjectsData
     */
    public function testGetProjects($exp_project)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getProjects();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($exp_project, $items);
    }
    public function testGetProjectsData()
    {
        return array(
            array('qtbase'),
            array('Qt5')
        );
    }

    /**
     * Test getBranches
     * @dataProvider testGetBranchesData
     */
    public function testGetBranches($exp_branch)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getBranches();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($exp_branch, $items);
    }
    public function testGetBranchesData()
    {
        return array(
            array('dev')
        );
    }

    /**
     * Test getStates
     * @dataProvider testGetStateData
     */
    public function testGetStates($exp_state)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getStates();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($exp_state, $items);
    }
    public function testGetStateData()
    {
        return array(
            array('state')
        );
    }

    /**
     * Test getProjectsFiltered
     * @dataProvider testGetProjectsFilteredData
     */
    public function testGetProjectsFiltered($filter, $exp_match_count_min)
    {
        $db = Factory::db();
        $result = $db->getProjectsFiltered($filter);
        $this->assertGreaterThanOrEqual($exp_match_count_min, count($result));
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
        }
    }
    public function testGetProjectsFilteredData()
    {
        return array(
            array('base', 1),
            array('ba', 3),
            array('qt', 35),
            array('invalid-name', 0)
        );
    }

    /**
     * Test getTestsetsFiltered
     * @dataProvider testGetTestsetsFilteredData
     */
    public function testGetTestsetsFiltered($filter, $exp_match_count_min)
    {
        $db = Factory::db();
        $result = $db->getTestsetsFiltered($filter);
        $this->assertGreaterThanOrEqual($exp_match_count_min, count($result));
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
        }
    }
    public function testGetTestsetsFilteredData()
    {
        return array(
            array('ftp', 1),
            array('ft', 2),
            array('tst', 3),
            array('invalid-name', 0)
        );
    }

    /**
     * Test getTestsetProject
     * @dataProvider testGetTestsetProjectData
     */
    public function testGetTestsetProject($testset, $exp_project, $exp_count)
    {
        $projects = array();
        $db = Factory::db();
        $result = $db->getTestsetProject($testset);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $projects[] = $row['project'];
        }
        $this->assertEquals($exp_count, count($projects));
        if ($exp_count > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_project, $projects);
        }
    }
    public function testGetTestsetProjectData()
    {
        return array(
            array('tst_qfont', 'qtbase', 1),
            array('tst_qftp', 'qtbase', 2),
            array('tst_qftp', 'Qt5', 2),
            array('invalid-name', '', 0)
        );
    }

    /**
     * Test getTargetPlatformOs
     * @dataProvider testGetTargetPlatformOsData
     */
    public function testGetTargetPlatformOs($exp_os, $exp_count)
    {
        $db = Factory::db();
        $result = $db->getTargetPlatformOs();
        $this->assertNotEmpty($result);
        $osCount = 0;
        foreach($result as $row) {
            $this->assertArrayHasKey('os', $row);
            if ($row['os'] === $exp_os)
                $osCount++;
        }
        $this->assertGreaterThanOrEqual($exp_count, $osCount);
    }
    public function testGetTargetPlatformOsData()
    {
        return array(
            array('linux', 1),
            array('windows', 1),
            array('invalid', 0)
        );
    }

    /**
     * Test getLatestProjectBranchBuildKeys
     * @dataProvider testGetLatestProjectBranchBuildKeysData
     */
    public function testGetLatestProjectBranchBuildKeys($project, $state, $exp_branch, $exp_build_key)
    {
        $branches = array();
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildKeys($project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            if ($row['name'] === $exp_branch) {
                $this->assertArrayHasKey('name', $row);
                $this->assertArrayHasKey('key', $row);
                $this->assertEquals($exp_build_key, $row['key']);
                $branches[] = $row['name'];
            }
        }
        $this->assertContains($exp_branch, $branches);
    }
    public function testGetLatestProjectBranchBuildKeysData()
    {
        return array(
            array('Qt5', 'state', 'master', '4777'),                    // based on test data
            array('Qt5', 'state', 'dev', 'BuildKeyInStringFormat12345'),
            array('Qt5', 'state', 'release', '157'),
            array('Qt5', 'state', 'stable', '1348')
        );
    }

    /**
     * Test getLatestProjectBranchBuildKey
     * @dataProvider testGetLatestProjectBranchBuildKeyData
     */
    public function testGetLatestProjectBranchBuildKey($project, $branch, $state, $exp_build_key)
    {
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildKey($project, $branch, $state);
        $this->assertNotEmpty($result);
        $this->assertEquals($exp_build_key, $result);
    }
    public function testGetLatestProjectBranchBuildKeyData()
    {
        return array(
            array('Qt5', 'master', 'state', '4777'),                    // based on test data
            array('Qt5', 'dev', 'state', 'BuildKeyInStringFormat12345'),
            array('Qt5', 'release', 'state', '157'),
            array('Qt5', 'stable', 'state', '1348')
        );
    }

    /**
     * Test getLatestProjectBranchBuildResults
     * @dataProvider testGetLatestProjectBranchBuildResultsData
     */
    public function testGetLatestProjectBranchBuildResults($project, $state, $exp_branch, $exp_results)
    {
        $branches = array();
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildResults($project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertContains($row['result'], $exp_results);
            $branches[] = $row['name'];
        }
        $this->assertContains($exp_branch, $branches);
    }
    public function testGetLatestProjectBranchBuildResultsData()
    {
        return array(
            array('Qt5', 'state', 'dev', array('SUCCESS', 'FAILURE', 'ABORTED'))
        );
    }

    /**
     * Test getLatestConfBranchBuildResults
     * @dataProvider testGetLatestConfBranchBuildResultsData
     */
    public function testGetLatestConfBranchBuildResults($conf, $project, $state, $exp_branch, $exp_results)
    {
        $branches = array();
        $db = Factory::db();
        $result = $db->getLatestConfBranchBuildResults($conf, $project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('forcesuccess', $row);
            $this->assertArrayHasKey('insignificant', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertContains($row['result'], $exp_results);
            $branches[] = $row['name'];
        }
        $this->assertContains($exp_branch, $branches);
    }
    public function testGetLatestConfBranchBuildResultsData()
    {
        return array(
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'Qt5', 'state', 'dev', array('SUCCESS', 'FAILURE', 'ABORTED')),
            array('win32-msvc2010_developer-build_angle_Windows_7', 'Qt5', 'state', 'master', array('SUCCESS', 'FAILURE', 'ABORTED')),
            array('win32-msvc2010_developer-build_angle_Windows_7', 'Qt5', 'state', 'release', array('SUCCESS', 'FAILURE', 'ABORTED')),
            array('win32-msvc2010_developer-build_angle_Windows_7', 'Qt5', 'state', 'stable', array('SUCCESS', 'FAILURE', 'ABORTED'))
        );
    }

    /**
     * Test getLatestProjectBranchTestsetResults
     * @dataProvider testGetLatestProjectBranchTestsetResultsData
     */
    public function testGetLatestProjectBranchTestsetResults($runProject, $runState, $exp_branches)
    {
        $confs = array();
        $db = Factory::db();
        $result = $db->getLatestProjectBranchTestsetResults($runProject, $runState);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $this->assertContains($row['branch'], $exp_branches);
        }
    }
    public function testGetLatestProjectBranchTestsetResultsData()
    {
        return array(
            array('Qt5', 'state', array('dev', 'stable', 'master'))
        );
    }

    /**
     * Test getLatestTestsetProjectBranchTestsetResults
     * @dataProvider testGetLatestTestsetProjectBranchTestsetResultsData
     */
    public function testGetLatestTestsetProjectBranchTestsetResults($testsetProject, $runProject, $runState, $exp_branches)
    {
        $confs = array();
        $db = Factory::db();
        $result = $db->getLatestTestsetProjectBranchTestsetResults($testsetProject, $runProject, $runState);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $this->assertContains($row['branch'], $exp_branches);
        }
    }
    public function testGetLatestTestsetProjectBranchTestsetResultsData()
    {
        return array(
            array('qtbase', 'Qt5', 'state', array('dev', 'stable', 'master'))
        );
    }

    /**
     * Test getLatestTestsetConfBuildResults
     * @dataProvider testGetLatestTestsetConfBuildResultsData
     */
    public function testGetLatestTestsetConfBuildResults($testset, $testsetProject, $runProject, $runState, $exp_conf, $exp_branches, $exp_results)
    {
        $confs = array();
        $db = Factory::db();
        $result = $db->getLatestTestsetConfBuildResults($testset, $testsetProject, $runProject, $runState);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertContains($row['branch'], $exp_branches);
            $this->assertContains($row['result'], $exp_results);
            $confs[] = $row['name'];
        }
        $this->assertContains($exp_conf, $confs);
    }
    public function testGetLatestTestsetConfBuildResultsData()
    {
        return array(
            array('tst_qftp', 'qtbase', 'Qt5', 'state', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed')),
            array('tst_qftp', 'qtbase', 'Qt5', 'state', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed')),
            array('tst_qfont', 'qtbase', 'Qt5', 'state', 'macx-clang_developer-build_OSX_10.8', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed'))
        );
    }

    /**
     * Test getTestsetsResultCounts
     * @dataProvider testGetTestsetsResultCountsData
     */
    public function testGetTestsetsResultCounts($runProject, $runState, $date, $limit, $exp_testset, $exp_excluded_testset, $exp_testset_count_min, $exp_failed_min)
    {
        $testsets = array();
        $failed = 0;
        $db = Factory::db();
        $result = $db->getTestsetsResultCounts($runProject, $runState, $date, $limit);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $testsets[] = $row['name'];
            $failed += $row['failed'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_excluded_testset, $testsets);
            $this->assertGreaterThanOrEqual($exp_failed_min, $failed);
        }
    }
    public function testGetTestsetsResultCountsData()
    {
        return array(
            array('Qt5', 'state', '2013-05-01', 10, 'tst_qftp', 'tst_networkselftest', 2, 1),   // in test data only tst_qfont and tst_qftp have failures
            array('Qt5', 'state', '2013-05-01', 1, 'tst_qftp', 'tst_networkselftest', 1, 1),
            array('Qt5', 'state', '2013-05-28', 10, 'tst_qftp', 'tst_networkselftest', 2, 1),
            array('Qt5', 'state', '2013-05-29', 10, '', '', 0, 0),
            array('Qt5', 'state', '2999-05-29', 10, '', '', 0, 0)
        );
    }

    /**
     * Test getTestsetResultCounts
     * @dataProvider testGetTestsetResultCountsData
     */
    public function testGetTestsetResultCounts($testset, $testsetProject, $runProject, $runState, $date, $exp_project, $exp_testset_count_min, $exp_failed_min)
    {
        $testsets = array();
        $failed = 0;
        $db = Factory::db();
        $result = $db->getTestsetResultCounts($testset, $testsetProject, $runProject, $runState, $date);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $testsets[] = $row['name'];
            $projects[] = $row['project'];
            $failed += $row['failed'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($testset, $testsets);
            $this->assertContains($exp_project, $projects);
            $this->assertGreaterThanOrEqual($exp_failed_min, $failed);
        }
    }
    public function testGetTestsetResultCountsData()
    {
        return array(
            array('tst_qftp', 'qtbase', 'Qt5', 'state', '2013-05-01', 'qtbase', 1, 1),
            array('tst_qftp', 'qtbase', 'Qt5', 'state', '2013-05-28', 'qtbase', 1, 1),
            array('tst_qftp', 'qtbase', 'Qt5', 'state', '2013-05-29', 'qtbase', 0, 0),
            array('tst_qftp', 'qtbase', 'Qt5', 'state', '2999-05-29', 'qtbase', 0, 0),
            array('tst_qftp', 'qtbase', 'qtbase', 'state', '2013-05-01', '', 0, 0),               // QtBase build not run (Qt5 only)
            array('tst_networkselftest', 'qtbase', 'Qt5', 'state', '2013-05-01', 'qtbase', 1, 0), // tst_networkselftest has been run but not failed
            array('invalid-name', 'qtbase', 'Qt5', 'state', '2013-05-29', '', 0, 0),
            array('tst_qftp', 'invalid-name', 'Qt5', 'state', '2013-05-29', '', 0, 0)
        );
    }

    /**
     * Test getTestsetsFlakyCounts
     * @dataProvider testGetTestsetsFlakyCountsData
     */
    public function testGetTestsetsFlakyCounts($date, $limit, $exp_testset, $exp_excluded_testset, $exp_testset_count_min, $exp_flaky_min)
    {
        $testsets = array();
        $flaky = 0;
        $db = Factory::db();
        $result = $db->getTestsetsFlakyCounts($date, $limit);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('flaky', $row);
            $this->assertArrayHasKey('total', $row);
            $testsets[] = $row['name'];
            $flaky += $row['flaky'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_excluded_testset, $testsets);
            $this->assertGreaterThanOrEqual($exp_flaky_min, $flaky);
        }
    }
    public function testGetTestsetsFlakyCountsData()
    {
        return array(
            array('2013-05-01', 10, 'tst_qfont', 'tst_networkselftest', 2, 1),  // in test data only tst_qfont and tst_qftp are flaky
            array('2013-05-28', 10, 'tst_qfont', 'tst_networkselftest', 1, 1),
            array('2013-05-01', 1, 'tst_qftp', 'tst_networkselftest', 1, 2),
            array('2013-05-29', 10, '', '', 0, 0),
            array('2999-05-29', 10, '', '', 0, 0)
        );
    }

    /**
     * Test getTestsetFlakyCounts
     * @dataProvider testGetTestsetFlakyCountsData
     */
    public function testGetTestsetFlakyCounts($testset, $testsetProject, $date, $exp_project, $exp_testset_count_min, $exp_flaky_min)
    {
        $testsets = array();
        $flaky = 0;
        $db = Factory::db();
        $result = $db->getTestsetFlakyCounts($testset, $testsetProject, $date);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('flaky', $row);
            $this->assertArrayHasKey('total', $row);
            $testsets[] = $row['name'];
            $projects[] = $row['project'];
            $flaky += $row['flaky'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($testset, $testsets);
            $this->assertContains($exp_project, $projects);
            $this->assertGreaterThanOrEqual($exp_flaky_min, $flaky);
        }
    }
    public function testGetTestsetFlakyCountsData()
    {
        return array(
            array('tst_qfont', 'qtbase', '2013-05-01', 'qtbase', 1, 1),
            array('tst_qfont', 'qtbase', '2013-05-28', 'qtbase', 1, 1),
            array('tst_qfont', 'qtbase', '2013-05-29', 'qtbase', 0, 0),
            array('tst_qfont', 'qtbase', '2999-05-29', 'qtbase', 0, 0),
            array('tst_networkselftest', 'qtbase', '2013-05-01', 'qtbase', 1, 0), // tst_networkselftest has been run but not flaky
            array('invalid-name', 'qtbase', '2013-05-29', '', 0, 0),
            array('tst_qfont', 'invalid-name', '2013-05-29', '', 0, 0)
        );
    }

    /**
     * Test getProjectBuildsByBranch
     * @dataProvider testGetProjectBuildsByBranchData
     */
    public function testGetProjectBuildsByBranch($runProject, $runState, $exp_branch, $exp_key, $has_data)
    {
        $branches = array();
        $keys = array();
        $db = Factory::db();
        $result = $db->getProjectBuildsByBranch($runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $branches[] = $row['branch'];
            $keys[] = $row['buildKey'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_key, $keys);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetProjectBuildsByBranchData()
    {
        return array(
            array('Qt5', 'state', 'dev', '1023', 1),
            array('Qt5', 'state', 'stable', '1348', 1),
            array('Qt5', 'state', 'stable', '1348', 1),
            array('Qt5', 'state', 'stable', '1348', 1),
            array('Qt5', 'state', 'dev', 'BuildKeyInStringFormat12345', 1),
            array('Qt5', 'invalid', '', '', 0)
        );
    }

    /**
     * Test getConfBuildsByBranch
     * @dataProvider testGetConfBuildsByBranchData
     */
    public function testGetConfBuildsByBranch($runProject, $runState, $exp_branch, $exp_conf, $exp_key, $exp_result, $has_data)
    {
        $branches = array();
        $confs = array();
        $keys = array();
        $results = array();
        $db = Factory::db();
        $result = $db->getConfBuildsByBranch($runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('conf', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('forcesuccess', $row);
            $this->assertArrayHasKey('insignificant', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $branches[] = $row['branch'];
            $confs[] = $row['conf'];
            $keys[] = $row['buildKey'];
            $results[] = $row['result'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_conf, $confs);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_result, $results);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetConfBuildsByBranchData()
    {
        return array(
            array('Qt5', 'state', 'dev', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', '1023', 'FAILURE', 1),
            array('Qt5', 'state', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 'SUCCESS', 1),
            array('Qt5', 'state', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'SUCCESS', 1),
            array('Qt5', 'state', 'dev', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'BuildKeyInStringFormat12345', 'FAILURE', 1),
            array('Qt5', 'invalid', '', '', '', '', 0)
        );
    }

    /**
     * Test getConfOsBuildsByBranch
     * @dataProvider testGetConfOsBuildsByBranchData
     */
    public function testGetConfOsBuildsByBranch($runProject, $runState, $targetOs, $exp_branch, $exp_conf, $exp_key, $exp_result, $has_data, $has_conf)
    {
        $branches = array();
        $confs = array();
        $keys = array();
        $results = array();
        $db = Factory::db();
        $result = $db->getConfOsBuildsByBranch($runProject, $runState, $targetOs);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('conf', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('forcesuccess', $row);
            $this->assertArrayHasKey('insignificant', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $branches[] = $row['branch'];
            $confs[] = $row['conf'];
            $keys[] = $row['buildKey'];
            $results[] = $row['result'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            if ($has_conf)
                $this->assertContains($exp_conf, $confs);
            else
                $this->assertNotContains($exp_conf, $confs);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_result, $results);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetConfOsBuildsByBranchData()
    {
        return array(
            array('Qt5', 'state', 'linux', 'dev', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', '1023', 'FAILURE', 1, 1),
            array('Qt5', 'state', 'linux', 'dev', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'BuildKeyInStringFormat12345', 'FAILURE', 1, 1),
            array('Qt5', 'state', 'windows', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 'SUCCESS', 1, 1),
            array('Qt5', 'state', 'osx', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'SUCCESS', 1, 1),
            array('Qt5', 'state', 'linux', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'SUCCESS', 1, 0),
            array('Qt5', 'state', 'invalid', '', '', '', '', 0, 0)
        );
    }

    /**
     * Test getConfBuildByBranch
     * @dataProvider testGetConfBuildByBranchData
     */
    public function testGetConfBuildByBranch($runProject, $runState, $conf, $exp_branch, $exp_conf, $exp_key, $exp_result, $has_data, $has_conf)
    {
        $branches = array();
        $confs = array();
        $keys = array();
        $results = array();
        $db = Factory::db();
        $result = $db->getConfBuildByBranch($runProject, $runState, $conf);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('conf', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('forcesuccess', $row);
            $this->assertArrayHasKey('insignificant', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $branches[] = $row['branch'];
            $confs[] = $row['conf'];
            $keys[] = $row['buildKey'];
            $results[] = $row['result'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            if ($has_conf)
                $this->assertContains($exp_conf, $confs);
            else
                $this->assertNotContains($exp_conf, $confs);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_result, $results);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetConfBuildByBranchData()
    {
        return array(
            array('Qt5', 'state', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', 'dev', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', '1023', 'FAILURE', 1, 1),
            array('Qt5', 'state', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'dev', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'BuildKeyInStringFormat12345', 'FAILURE', 1, 1),
            array('Qt5', 'state', 'win32-msvc2010_developer-build_angle_Windows_7', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 'SUCCESS', 1, 1),
            array('Qt5', 'state', 'macx-clang_developer-build_OSX_10.8', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'SUCCESS', 1, 1),
            array('Qt5', 'state', 'win32-msvc2010_developer-build_angle_Windows_7', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'SUCCESS', 1, 0),
            array('Qt5', 'state', 'invalid', '', '', '', '', 0, 0)
        );
    }

    /**
     * Test getTestsetResultsByBranchConf
     * @dataProvider testGetTestsetResultsByBranchConfData
     */
    public function testGetTestsetResultsByBranchConf($testset, $testsetProject, $runProject, $runState, $exp_branch, $exp_conf, $exp_key, $exp_result, $has_data)
    {
        $branches = array();
        $confs = array();
        $keys = array();
        $results = array();
        $db = Factory::db();
        $result = $db->getTestsetResultsByBranchConf($testset, $testsetProject, $runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('conf', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertArrayHasKey('run', $row);
            $branches[] = $row['branch'];
            $confs[] = $row['conf'];
            $keys[] = $row['buildKey'];
            $results[] = $row['result'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_conf, $confs);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_result, $results);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestsetResultsByBranchConfData()
    {
        return array(
            array('tst_qftp', 'Qt5', 'Qt5', 'state', '', '', '', '', 0),
            array('tst_qftp', 'QtBase', 'Qt5', 'state', 'dev', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', '1023', 'ifailed', 1),
            array('tst_qftp', 'QtBase', 'Qt5', 'state', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 'ipassed', 1),
            array('tst_qfont', 'QtBase', 'Qt5', 'state', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 'failed', 1),
            array('tst_qfont', 'QtBase', 'Qt5', 'state', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 'passed', 1),
            array('tst_qfont', 'QtBase', 'Qt5', 'state', 'dev', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'BuildKeyInStringFormat12345', 'failed', 1)
        );
    }

    /**
     * Test getTestsetProjectResultsByBranchConf
     * @dataProvider testGetTestsetProjectResultsByBranchConfData
     */
    public function testGetTestsetProjectResultsByBranchConf($testsetProject, $runProject, $runState, $exp_branch, $exp_conf, $exp_key, $has_data)
    {
        $branches = array();
        $confs = array();
        $keys = array();
        $db = Factory::db();
        $result = $db->getTestsetProjectResultsByBranchConf($testsetProject, $runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('conf', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('ipassed', $row);
            $this->assertArrayHasKey('failed', $row);
            $this->assertArrayHasKey('ifailed', $row);
            $branches[] = $row['branch'];
            $confs[] = $row['conf'];
            $keys[] = $row['buildKey'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_conf, $confs);
            $this->assertContains($exp_key, $keys);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestsetProjectResultsByBranchConfData()
    {
        return array(
            array('QtBase', 'Qt5', 'state', 'dev', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', '1023', 1),
            array('QtBase', 'Qt5', 'state', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 1),
            array('QtBase', 'Qt5', 'state', 'stable', 'macx-clang_developer-build_OSX_10.8', '1348', 1),
            array('QtBase', 'Qt5', 'state', 'stable', 'win32-msvc2010_developer-build_angle_Windows_7', '1348', 1),
            array('QtBase', 'Qt5', 'state', 'dev', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'BuildKeyInStringFormat12345', 1),
            array('Qt5', 'Qt5', 'invalid', '', '', '', '', 0)
        );
    }

    /**
     * Test getTestsetConfResultsByBranch
     * @dataProvider testGetTestsetConfResultsByBranchData
     */
    public function testGetTestsetConfResultsByBranch($conf, $runProject, $runState, $exp_branch, $exp_testset, $exp_testset_excluded, $exp_project, $exp_key, $has_data)
    {
        $branches = array();
        $keys = array();
        $testsets = array();
        $projects = array();
        $db = Factory::db();
        $result = $db->getTestsetConfResultsByBranch($conf, $runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('testset', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertArrayHasKey('run', $row);
            $branches[] = $row['branch'];
            $keys[] = $row['buildKey'];
            $testsets[] = $row['testset'];
            $projects[] = $row['project'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_testset_excluded, $testsets);
            $this->assertContains($exp_project, $projects);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestsetConfResultsByBranchData()
    {
        return array(
            array('linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', 'Qt5', 'state', 'dev', 'tst_qftp', 'na', 'qtbase', '1023', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'Qt5', 'state', 'stable', 'tst_qftp', 'na', 'qtbase', '1348', 1),
            array('macx-clang_developer-build_OSX_10.8', 'Qt5', 'state', 'stable', 'tst_qfont', 'tst_networkselftest', 'qtbase', '1348', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'Qt5', 'state', 'dev', 'tst_qftp', 'na', 'qtbase', 'BuildKeyInStringFormat12345', 1),
            array('invalid', 'Qt5', 'state', '', '', '', '', '', 0)
        );
    }

    /**
     * Test getTestsetConfProjectResultsByBranch
     * @dataProvider testGetTestsetConfProjectResultsByBranchData
     */
    public function testGetTestsetConfProjectResultsByBranch($conf, $testsetProject, $runProject, $runState, $exp_branch, $exp_testset, $exp_testset_excluded, $exp_project, $exp_key, $has_data)
    {
        $branches = array();
        $keys = array();
        $testsets = array();
        $projects = array();
        $db = Factory::db();
        $result = $db->getTestsetConfProjectResultsByBranch($conf, $testsetProject, $runProject, $runState);
        foreach($result as $row) {
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('buildKey', $row);
            $this->assertArrayHasKey('testset', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertArrayHasKey('run', $row);
            $branches[] = $row['branch'];
            $keys[] = $row['buildKey'];
            $testsets[] = $row['testset'];
            $projects[] = $row['project'];
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_key, $keys);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_testset_excluded, $testsets);
            $this->assertContains($exp_project, $projects);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestsetConfProjectResultsByBranchData()
    {
        return array(
            array('linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', 'qtbase', 'Qt5', 'state', 'dev', 'tst_qftp', 'na', 'qtbase', '1023', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'qtbase', 'Qt5', 'state', 'stable', 'tst_qftp', 'na', 'qtbase', '1348', 1),
            array('macx-clang_developer-build_OSX_10.8', 'qtbase', 'Qt5', 'state', 'stable', 'tst_qfont', 'tst_networkselftest', 'qtbase', '1348', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'qtbase', 'Qt5', 'state', 'dev', 'tst_qftp', 'na', 'qtbase', 'BuildKeyInStringFormat12345', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'invalid', 'Qt5', 'state', '', '', '', '', '', 0),
            array('invalid', 'qtbase', 'Qt5', 'state', '', '', '', '', '', 0)
        );
    }

    /**
     * Test getDbRefreshed
     */
    public function testGetDbRefreshed()
    {
        $db = Factory::db();
        $timestamp = $db->getDbRefreshed();
        $this->assertNotEmpty($timestamp);
        $this->assertStringStartsWith('20', $timestamp);
        $this->assertEquals(19, strlen($timestamp));                // e.g. "2015-05-04 10:00:00"
    }

}

?>
