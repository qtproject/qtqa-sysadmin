import "*"

class pulseagent {
    include java
    case $operatingsystem {
        Darwin:     { include pulseagent::mac }
        Ubuntu:     { include pulseagent::linux }
        Linux:      { include pulseagent::linux }
        Solaris:    { include pulseagent::solaris }
    }
}

