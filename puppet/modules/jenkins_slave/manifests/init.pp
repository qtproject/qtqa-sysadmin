class jenkins_slave (
    $user = $baselayout::testuser,
    $group = $baselayout::testgroup,
    $server,
    $set_online = true,
    $slave_name = $::hostname
) {
    include java
    case $::operatingsystem {
        Ubuntu:     { include jenkins_slave::ubuntu }
        OpenSuSE:   { include jenkins_slave::opensuse }
        Darwin:     { include jenkins_slave::mac }
        windows:    { include jenkins_slave::windows }
    }
    if $set_online == true {
        $cli_log = "jenkins_cli_log.txt"
        case $::operatingsystem {
            Ubuntu:     { include jenkins_slave::register_online::linux }
            OpenSuSE:   { include jenkins_slave::register_online::linux }
            Darwin:     { include jenkins_slave::register_online::mac }
            windows:    { include jenkins_slave::register_online::windows }
        }
    }
}
