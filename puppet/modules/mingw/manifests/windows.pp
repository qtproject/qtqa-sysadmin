# Downloads the given version of mingw from sourceforge.net and installs to the specified $path.
# If a different mingw version is already installed there, it is uninstalled first.
class mingw::windows(
    $version = '4.7.2',
    $path = 'C:\mingw',

    # Additional options for installed MinGW version, See also: http://qt-project.org/wiki/MinGW-64-bit
    # Note: Changing any of these won't trigger MinGW re-installation.
    #
    # either 'posix' or 'win32', we use 'posix' since it is more popular
    $threading = 'posix',

    # either 'sjlj' or 'dwarf', we use 'sjlj' since it is more popular
    $exceptions = 'sjlj',

    # installed revision
    $revision = 'rev8'
) {
    $bits = $::architecture ? {
        x64 => "64",
        default => "32"
    }

    # Match revision, match with both 'r8' and 'rev8' style patterns.
    # Just for checking if correct revision is already installed.
    $match_revision = regsubst($revision, 'rev', 'r(ev)?')

    # installer file URL
    $url = "http://sourceforge.net/projects/mingwbuilds/files/host-windows/releases/${version}/${bits}-bit/threads-${threading}/${exceptions}/x${bits}-${version}-release-${threading}-${exceptions}-${revision}.7z"

    windows::zip_package { "mingw":
        url => $url,
        version => $version,
        version_flags => "-print-search-dirs",
        version_expression => "x${bits}-${version}(-release)?-${threading}-${exceptions}-${match_revision}",
        path => $path,
        binary => "$path\\mingw\\bin\\g++.exe"
    }
}
