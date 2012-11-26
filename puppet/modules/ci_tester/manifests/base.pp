class ci_tester::base {
    # ci_tester setup common to all operating systems
    class { 'baselayout':
        testuser => $ci_tester::testuser,
        qt_gerrit_mirror => $ci_tester::qt_gerrit_mirror
    }
    include puppet
    include sshkeys
    include icu4c

    if $ci_tester::vmware_enabled {
        include vmware_deployment
    }

    class { 'qt_prereqs': network_test_server_ip => $ci_tester::network_test_server_ip }

    if $ci_tester::jenkins_enabled {
         class { 'jenkins_slave':
            server => $ci_tester::jenkins_server,
            slave_name => $ci_tester::jenkins_slave_name
         }
    }
}
