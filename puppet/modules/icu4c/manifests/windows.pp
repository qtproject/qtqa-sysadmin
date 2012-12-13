# Downloads the given $version of icu4c from icu-project.org and installs to the specified $path.
# If a different icu4c version is already installed there, it is uninstalled first.
class icu4c::windows(
    $version = '49.1.2',
    $path = 'C:\utils\icu4c'
) {
    # Version number with underscores, for example '49_1_2'
    $version_underscore = regsubst($version, '\.', '_', "G")

    $os_bits = $::architecture ? {
        x64 => "Win64",
        default => "Win32"
    }

    $bin_folder = $::architecture ? {
        x64 => "bin64",
        default => "bin"
    }

    # installer file URL
    $url = "http://download.icu-project.org/files/icu4c/${version}/icu4c-${version_underscore}-${os_bits}-msvc10.zip"

    windows::zip_package { "icu4c":
        url => $url,
        version => $version,
        path => $path,
        binary => "$path\\icu\\${bin_folder}\\icuinfo.exe"
    }
}
