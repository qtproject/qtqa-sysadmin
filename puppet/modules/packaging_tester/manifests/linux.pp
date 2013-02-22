class packaging_tester::linux inherits packaging_tester::base {
    include ccache
    include crosscompilers

    if $packaging_tester::vmware_enabled {
        include vmware_tools
    }

    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan

    # Allow test machines to install python modules with pip or easy_install
    # to $HOME/python26
    include homedir_virtualenv

    if $packaging_tester::icecc_enabled {
        class { "icecc":
            scheduler_host => $packaging_tester::icecc_scheduler_host
        }
    }

}
