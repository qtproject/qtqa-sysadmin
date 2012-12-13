# Downloads the given $version of strawberryperl from http://strawberryperl.com and installs to the specified $path.
# If a different strawberryperl version is already installed there, it is uninstalled first.

# Major caveat: Changing $version alone does not work because uninstalling $path when it is used fails
class strawberryperl::windows(
    $version = '5.14.2.1',
    $path = 'C:\strawberry'
) {
    # 64-bit perl fails to compile AnyEvent CPAN module -> Let's use 32-bit perl for now also on 64-bit host
    $bits = "32bit"

    # installer file URL
    $url = "http://strawberry-perl.googlecode.com/files/strawberry-perl-${version}-${$bits}.msi"

    # perl versions without build part
    $version_no_buildpart = regsubst($version, '^(\d+)\.(\d+)\.(\d+).(\d+)$', '\1.\2.\3')

    windows::msi_package { "strawberryperl":
        url => $url,
        version => $version,
        version_expression => $version_no_buildpart,
        install_flags => "/QB",
        path => $path,
        binary => "$path\\perl\\bin\\perl.exe"
    }
}
