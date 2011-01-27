class homedir_cpan::ubuntu inherits homedir_cpan::linux {
    package {
        # Need gcc to build CPAN modules
        "gcc":                  ensure  =>  installed;
        "g++":                  ensure  =>  installed;

        # We use the local::lib module to implement $HOME/perl5
        "liblocal-lib-perl":    ensure  =>  installed;
    }

    file { "/etc/profile.d/local-lib-perl.sh":
        ensure  =>  present,
        source  =>  "puppet:///modules/homedir_cpan/profile.d/local-lib-perl.sh",
        require =>  Package["liblocal-lib-perl"],
    }
}

