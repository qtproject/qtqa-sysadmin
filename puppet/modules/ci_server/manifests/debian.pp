 class ci_server::debian inherits ci_server
{
 # ============================== qtqa setup in jenkins homedir ======================

    file { "/var/lib/jenkins/.profile":
        ensure => present,
        source => "puppet:///modules/ci_server/dot.profile",
        require => Package["jenkins", "liblocal-lib-perl"],
        owner => "jenkins",
        group => "nogroup",
    }

    # all packages needed for qtqa repo setup.
    # most of these are to support a working homedir CPAN setup
    # (able to compile and install XS modules)
    $qtqa_packages = [
        "git",
        "libwww-perl",
        "liblocal-lib-perl",
        "libc6-dev",
        "libexpat1-dev",
        "make"
    ]

    package { $qtqa_packages: ensure => installed; }

    exec { "clone qtqa into jenkins homedir":
        command => "/bin/su -c \"   \
            \
            rm -rf qtqa.cloning && \
            git clone git://qt.gitorious.org/qt/qtqa qtqa.cloning && \
            mv -v qtqa.cloning qtqa && \
            eval \$(perl -Mlocal::lib) && \
            qtqa/scripts/setup.pl --install \
            \
            \" - jenkins",
        require => Package[
            $qtqa_packages,
            "jenkins"       # jenkins package creates jenkins user
        ],
        timeout => 360,     # allow 1 hour for installation (can be slow)
        creates => "/var/lib/jenkins/qtqa/scripts/setup.pl",
        logoutput => true,
    }

    cron { "update qtqa":
        command =>
            "( \
                source \$HOME/.profile && \
                cd qtqa && \
                git fetch --quiet origin && \
                git reset --quiet --hard origin/master && \
                git clean -dqffx . \
            ) 2>&1 | logger -t jenkins-qtqa-update",
        user => "jenkins",
        hour => "*/2",
        minute => "20",
        require => Exec["clone qtqa into jenkins homedir"],
    }

    # ======================== 'reliable' versions of git, scp =========================
    $reliable = "/var/lib/jenkins/qtqa/scripts/generic/reliable.pl"
    $reliable_bin = "/var/lib/jenkins/reliable-bin"

    file {
        $reliable_bin:
            ensure => directory,
            mode => 0755,
            owner => "jenkins",
            group => "nogroup",
        ;

        # currently we don't deploy a reliable ssh because we'll want to run some ssh
        # commands which read from STDIN and we don't have a decent way to automatically
        # retry those
        ["$reliable_bin/git", "$reliable_bin/scp"]:
            ensure => link,
            target => $reliable,
            owner => "jenkins",
            group => "nogroup",
            require => [
                File[$reliable_bin],
                Exec["clone qtqa into jenkins homedir"],
            ],
        ;
    }


    # ======================= ssh setup ================================================

    Sshkey { type => "ssh-rsa" }

    sshkey { "[codereview.qt-project.org]:29418":
        key => "AAAAB3NzaC1yc2EAAAADAQABAAAAgQCvXdApmCFiAyXDiYU5+z6762Qv8+vrmM3+9YrxDKByyphaxblLJC9txPv3D/w7rzSyiMMHL/5ssCemwz+6QBqnemFl4B+FNv81fpZFsqCg5afrTi62WFllGWIQAiYb2JZmkmSAbxm+sAxLE1ritp+Syxz8Gb8WR27G/3TSHerdBQ==",
    }
    sshkey { "[dev-codereview.qt-project.org]:29418":
        key => "AAAAB3NzaC1yc2EAAAADAQABAAAAgQDSl0SfLVrmQf5lxz8/Xo5IYa8DSymJkc8lNDQx0ZHySzveR5RxLtAqhxKN8HXYyz22xImOkr9Lu8tt4OKx7+SsN/LXV9zARdK9enJk7pEatmD/9GhwhhgKLtCKGuGrSxiTvDyesg6TVL59pdyXom+E8lU/fOhf2Qv6+8+Ow7EGow==",
    }
    sshkey { "testresults.qt.io":
        key => "AAAAB3NzaC1yc2EAAAADAQABAAABAQDE6+70RZyZdK4nUwXb5O/IYZjNpHC8OKp7+3NCMqKYIFxzyDrb8BgPu5utqcolJ6rPYppE+PD4ZBKkA4+sebGyJD54kszj9emhpNDB7say1kd7Xdwy2hEjUawdcTkKxVkGXDQQQULCL0tvBPthmj8doWbFarmpxfnpTvwQdaj2aRK1Get2g2CTnmoNGnH4KoSVoa7/Ge+nkCN+Ub8Qfk/UboBRGdSAqSYAuPz/x+bfpNz0spKL2VY2f/Yg3IxjQBTB/Z4Jpj3Hi+ckj4DUiYj7lDnYEw/IsMyU5p0VZzy22ZV7cIkfkeOuYOyvSLLoyXHJrZKte5wuddMtBvnyFqGx",
    }

    # make sure ssh_known_hosts is world-readable
    file { "/etc/ssh/ssh_known_hosts":
        mode => 0644,
    }

    file {
        "/var/lib/jenkins/.ssh":
            ensure => directory,
            mode => 0755,
            owner => "jenkins",
            group => "nogroup",
            require => Package["jenkins"],
        ;
        "/var/lib/jenkins/.ssh/config":
            ensure => present,
            source => "puppet:///modules/ci_server/ssh_config",
            mode => 0644,
            owner => "jenkins",
            group => "nogroup",
            require => File["/var/lib/jenkins/.ssh"],
        ;
    }

    # generate a warning each time we are run until this key is set up.
    $pubkey = 'AAAAB3NzaC1yc2EAAAADAQABAAABAQCx2Xb8YE0AMFF/BEODFQgxVZmJdR5rTukX5PwDweJLik3YUCl9Ja6DMgBCSjuJWSPNlFnJoAUQXE2J/zOcp0RK9n1m1nVcraw5kuHDrnocuL6e+e9OHyBaYMoBFo7VYZgg/pBEuwL1Spn+KYFP60gbZm5aQw81t/jcwrVn60YtbGypsNzLd97knY7eamBEhId9B4CVF79/deUa+SoNiZ46hO7mNtXmTiJBPc4ilsm3Fy99sO5VSY/wJTsiltRWaWxnJrS2Ww29VfPzJksAo4c5S6gBnOLPIs/TLMwYSCEbUnwn/NPE3WGG/psvhy0X1Y/Acjtl/inxhoOVIF1yt+2J'

    exec { "warn about jenkins ssh key":
        command => "/bin/echo 'WARNING: manual installation of Jenkins ssh key is required, matching public key: $pubkey'",
        logoutput => true,
        require => File["/var/lib/jenkins/.ssh"],
        unless => "/bin/grep -q -F '$pubkey' /var/lib/jenkins/.ssh/id_rsa.pub",
    }

    # ================================= git setup ======================================

    Git::Config {
        require => Package["git","jenkins"],
        file => "/var/lib/jenkins/.gitconfig",
    }

    git::config { "jenkins user.name":
        key => "user.name",
        content => "Qt Project Jenkins",
        user => "jenkins",
    }

    git::config { "jenkins user.email":
        key => "user.email",
        content => "jenkins@qt-project.org",
        user => "jenkins",
    }

    git::object_cache { "jenkins git object cache":
        cache_path => "/var/lib/jenkins/git-objects",
        git_path => [
            # default workspace path
            "/var/lib/jenkins/jobs/*/workspace",

            # custom shorter workspace path used by some jobs
            "/var/lib/jenkins/ci/*",
            "/var/lib/jenkins/ci/*/*",

            # other repos, not created by jenkins
            "/var/lib/jenkins/qtqa",
        ],
        require => Package["jenkins"],  # jenkins package creates jenkins user
        owner => "jenkins",
        group => "nogroup",
    }

    # ================================= gerrit -> jenkins integrator ============
    # environment; warnings and worse go to syslog
    $env = "/usr/bin/env PERL_ANYEVENT_VERBOSE=5 PERL_ANYEVENT_LOG=log=syslog"

    # start-stop-daemon base cmd (for /usr/bin/perl)
    $start_stop_daemon_perl = "start-stop-daemon --chuid jenkins:nogroup --background --user jenkins --exec /usr/bin/perl --make-pidfile --startas /bin/sh"

    # script cmd
    $sh_args = "exec perl /var/lib/jenkins/qtqa/scripts/jenkins/qt-jenkins-integrator.pl --config /var/lib/jenkins/ci.cfg"

    # pid file base
    $pidfile = "/var/run/qt-jenkins-integrator.pid"

    exec { "qt-jenkins-integrator":
        command => "$env $start_stop_daemon_perl --pidfile $pidfile --start -- -l -c '$sh_args'",
        onlyif => "$env $start_stop_daemon_perl --pidfile $pidfile --test --start",
        require => Cron["update qtqa"],
    }

    # ============================= port forward for remote API ========================
    # start-stop-daemon base cmd (for /usr/bin/ssh)
    $start_stop_daemon_ssh = "start-stop-daemon --chuid jenkins:nogroup --background --user jenkins --exec /usr/bin/ssh --make-pidfile --startas /bin/sh"

    # ssh base cmd (user@hostname omitted)
    $sh_args_ssh = "exec ssh -oServerAliveInterval=30 -R 7181:127.0.0.1:7181 -N"

    # pid file base
    $pidfile_base_ssh = "/var/run/ssh-qt-ci-remote-api"

    exec { "ssh fwd for testresults remote API":
        command => "$env $start_stop_daemon_ssh --pidfile $pidfile_base_ssh-testresults.pid --start -- -l -c '$sh_args_ssh qtintegration@testresults.qt.io'",
        onlyif => "$env $start_stop_daemon_ssh --pidfile $pidfile_base_ssh-testresults.pid --test --start",
    }

}
