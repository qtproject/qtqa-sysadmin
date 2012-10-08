class jenkins_slave::register_online::mac {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/bin/sh",
        arguments => [
            "-c",
            "/Users/$jenkins_slave::user/jenkins/jenkins-cli.pl --retry -- online-node $::hostname 2>&1 | tee /Users/$jenkins_slave::user/jenkins/$jenkins_slave::cli_log | logger -t jenkins"
        ],
        require =>  File["jenkins cli script"],
        user    =>  $jenkins_slave::user,
    }
}
