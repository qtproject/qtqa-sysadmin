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

/**
 * Header section of html page including the meta data and style sheets
 * @version   0.2
 * @since     17-06-2015
 * @author    Juha Sippola
 */

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="Qt Metrics">
    <meta name="author" content="jusippol">
    <link rel="icon" href="images/favicon.ico">

    <title>Qt Metrics</title>

    <!-- base directory for inclusions -->
    <base href="<?php echo Slim\Slim::getInstance()->urlFor('root'); ?>" />

    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="lib/jQuery-UI-themes/themes/smoothness/jquery-ui.css">

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="lib/Bootstrap/css/bootstrap.min.css">

    <!-- Custom styles for the Bootstrap templates used -->
    <link rel="stylesheet" href="styles/bootstrap_custom.css">

    <!-- Own styles -->
    <link rel="stylesheet" href="styles/qtmetrics.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                    <a class="navbar-brand" href="">
                        <span>
                            <img src="images/Qt-logo-small.png" alt="Qt"> &nbsp; Qt Metrics
                        </span>
                    </a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <nav>
                    <ul class="nav nav-pills pull-right">
                        <li role="presentation" class=" navbar-btn dropdown">
                            <a id="doc-drop" href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">
                                Documentation
                                <span class="caret"></span>
                            </a>
                            <ul id="menu1" class="dropdown-menu" role="menu" aria-labelledby="doc-drop">
                                <li role="presentation"><a role="menuitem" tabindex="-1" href="doc/db_design.png" target="_blank">Database design</a></li>
                                <li role="presentation"><a role="menuitem" tabindex="-1" href="doc/apigen" target="_blank">Class definitions</a></li>
                            </ul>
                        </li>
                        <li role="presentation" class="navbar-btn"><a href="https://wiki.qt.io/User:Juha" target="_blank">Contact</a></li>
                        <li role="presentation" class="navbar-btn"><a href="" data-toggle="modal" data-target="#aboutModal">About</a></li>
                        <li role="presentation" class="navbar-btn"><a href="http://www.qt.io/" target="_blank">qt.io</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </nav>

    <!-- Modal for About -->
    <div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">About</h4>
                </div>
                <div class="modal-body">
                    <div id="about"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Open the content (closed in close.php) -->
    <div class="container">
