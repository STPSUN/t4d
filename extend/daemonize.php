<?php

//daemonize.php

function daemonize()
{
    $pid = pcntl_fork();

    if ($pid > 0) {
        //main process
        exit(0);
    } elseif ($pid < 0) {
        //fork failed
        echo "fork failed";
    }

    posix_setsid();

    $pid = pcntl_fork();

    if ($pid > 0) {
        exit(0);
    } elseif ($pid < 0) {
        echo "fork failed";
    }

    fclose(STDOUT);
}


daemonize();
exec("geth --datadir ~/ethClient --nodiscover --networkid 156456456 --rpc --rpccorsdomain \"127.0.0.1\" --rpcport '8545' --rpcapi \"db,eth,net,web3,personal,admin\" console");