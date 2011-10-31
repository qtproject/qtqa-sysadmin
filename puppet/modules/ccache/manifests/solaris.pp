class ccache::solaris
{
    if $zone == false {
        csw_package { "ccache":
            ensure      =>  present,
        }
    }

    if $testuser {
        exec { "/bin/sh -c 'echo ''/opt/csw/bin/ccache -M 2G'' | /usr/bin/su - $testuser'":
        }

        ccache_link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "cc-5.0":  command => "cc-5.0";
                "CC":      command => "CC";
        }
    }
}

