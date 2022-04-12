<?php
if(preg_match('/139\.17\.[81|29]/', $_SERVER["REMOTE_ADDR"])){
  phpinfo();
} else {
    # Selected Subnets only
    echo sprintf("<html><code>%s: Intranet only, sorry. - mDIS.</code></html>", $_SERVER["REMOTE_ADDR"]);
}
