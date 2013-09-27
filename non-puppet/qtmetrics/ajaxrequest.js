/****************************************************************************
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
****************************************************************************/

var metricRequest = new Array();
var filterRequest;

/* Create metric instance as required by the browser */
function createMetricRequestObject(id)
{
    if (window.XMLHttpRequest)
        metricRequest[id] = new XMLHttpRequest();                     // for IE7+, Firefox, Chrome, Opera, Safari
    else
        metricRequest[id] = new ActiveXObject("Microsoft.XMLHTTP");   // for IE6 and IE5
}

/* Create filter instance as required by the browser */
function createFilterRequestObject()
{
    if (window.XMLHttpRequest)
        filterRequest = new XMLHttpRequest();                         // for IE7+, Firefox, Chrome, Opera, Safari
    else
        filterRequest = new ActiveXObject("Microsoft.XMLHTTP");       // for IE6 and IE5
}

/* Request metric data (e.g. from database) */
function getMetricData(metricId, filepath, project, conf, autotest, timescaleType, timescaleValue, sortBy)
{
    document.getElementById("metricsBox"+metricId).innerHTML = "<img src=\"images/ajax-loader.gif\" alt=\"loading\"> Loading...";    // Div content during the Ajax call
    if (project == "") {
        document.getElementById("metricsBox"+metricId).innerHTML = "";
        return;
    }
    if (typeof sortBy == "undefined")                                 // sortBy is optional, set 0 as a default
        var sortBy = 0;
    project = encodeURIComponent(project);                            // Encode the parameters to follow correct URL encoding (e.g. possible "+" characters)
    conf = encodeURIComponent(conf);                                  // -,,-
    autotest = encodeURIComponent(autotest);                          // -,,-
    createMetricRequestObject(metricId);
    metricRequest[metricId].open("GET",filepath+"?project="+project+"&conf="+conf+"&autotest="+autotest+"&tstype="
                                      +timescaleType+"&tsvalue="+timescaleValue+"&sort="+sortBy,true);
    metricRequest[metricId].send();
    metricRequest[metricId].onreadystatechange = function(index)
    {
        return function()
        {
            showMetricData(index);
        };
    } (metricId);
}

/* Show metric result in the related metrics box (div) */
function showMetricData(metricId)
{
    if (metricRequest[metricId].readyState == 4 && metricRequest[metricId].status == 200) {
        var response = metricRequest[metricId].responseText;
        document.getElementById("metricsBox"+metricId).innerHTML = response;
        loadDatabaseStatus(0);                                        // Load the database status every time a metrics box is updated (to keep status updated when user uses the page)
    }
}

/* Request filters (from database) */
function getFilters(div, filepath)
{
    createFilterRequestObject();
    filterRequest.open("GET",filepath,true);
    filterRequest.send();
    filterRequest.onreadystatechange = function(index)
    {
        return function()
        {
            showFilters(index);
        };
    } (div);
}

/* Show filters in the related div */
function showFilters(div)
{
    if (filterRequest.readyState == 4 && filterRequest.status == 200) {
        var response = filterRequest.responseText;
        document.getElementById(div).innerHTML = response;
        loadMetricsboxes();                                           // When filters are ready, load the metrics boxes next
        return;
    }
}

/* Request database status (initial loading of the page) */
function getDatabaseStatusInitial(div, filepath, initial, timeOffset)
{
    createFilterRequestObject();
    filterRequest.open("GET",filepath+"?initial="+initial+"&timeoffset="+timeOffset,true);
    filterRequest.send();
    filterRequest.onreadystatechange = function(index)
    {
        return function()
        {
            showDatabaseStatusInitial(index);
        };
    } (div);
}

/* Request database status (normal use of the page) */
function getDatabaseStatus(div, filepath, initial, timeOffset)
{
    createFilterRequestObject();
    filterRequest.open("GET",filepath+"?initial="+initial+"&timeoffset="+timeOffset,true);
    filterRequest.send();
    filterRequest.onreadystatechange = function(index)
    {
        return function()
        {
            showDatabaseStatus(index);
        };
    } (div);
}

/* Show database status in the related div (initial loading of the page) */
function showDatabaseStatusInitial(div)
{
    if (filterRequest.readyState == 4 && filterRequest.status == 200) {
        var response = filterRequest.responseText;
        document.getElementById(div).innerHTML = response;
        getFilters("filters", "ci/getfilters.php");                   // Continue to load the filters next (initial loading of the page)
        return;
    }
}

/* Show database status in the related div (normal use of the page) */
function showDatabaseStatus(div)
{
    if (filterRequest.readyState == 4 && filterRequest.status == 200) {
        var response = filterRequest.responseText;
        document.getElementById(div).innerHTML = response;
        return;
    }
}
