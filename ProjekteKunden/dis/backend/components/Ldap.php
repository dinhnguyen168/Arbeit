<?php
namespace app\components;

/**
 * Zugriff auf Ldap
 * Kapselung von PHP LDAP Funktionen
 *
 */
class Ldap extends \yii\base\Component {


    /**
     * The base dn for your domain
     *
     * If this is set to null then adLDAP will attempt to obtain this automatically from the rootDSE
     *
     * @var string
     */
    public $baseDn = "DC=mydomain,DC=net";

    public $accountSuffix = "";

    /**
     * Port used to talk to the domain controllers.
     *
     * @var int
     */
    public $port = 389;

    /**
     * Array of domain controllers. Specifiy multiple controllers if you
     * would like the class to balance the LDAP queries amongst multiple servers
     *
     * @var array
     */
    public $servers = array("ldap1.mydomain.net");

    /**
     * Optional account with higher privileges for searching
     * This should be set to a domain admin account
     *
     * @var string
     * @var string
     */
    public $username = NULL;
    public $password = NULL;


    /**
     * Use SSL (LDAPS), your server needs to be setup, please see
     * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
     *
     * @var bool
     */
    public $useSSL = false;


    /**
     * Use TLS
     * If you wish to use TLS you should ensure that $useSSL is set to false and vice-versa
     *
     * @var bool
     */
    public $useTLS = false;


    /**
     * Use SSO
     * To indicate to adLDAP to reuse password set by the brower through NTLM or Kerberos
     *
     * @var bool
     */
    public $useSSO = false;


    /**
     * When querying group memberships, do it recursively
     * eg. User Fred is a member of Group A, which is a member of Group B, which is a member of Group C
     * user_ingroup("Fred","C") will returns true with this option turned on, false if turned off
     *
     * @var bool
     */
    public $recursiveGroups = true;


    /**
     * When a query returns a referral, follow it.
     *
     * @var int
     */
    public $followReferrals = 0;

    public $usernameAttribute = "cn";

    public $cacheDuration = 3600;

    public $personMailAttribute = "mail";

    /**
     * @var string If the user shall be checked  with another filter condition, use these variables.
     * You can use all the fields used in searchUser; add a colon before the values, i.e. (memberUid=:uid).
     * In this case ":uid" will be replaced by the uid of the found user.
     */
    public $searchUserExtraConditionFilter='';

    /**
     * @var string If a different baseDn shall be used for the searchUserExtraConditionFilter enter it here.
     * Otherwise the baseDn is used.
     */
    public $searchUserExtraConditionDn = '';


    protected $connection = null;
    protected $bind = null;

    /**
     * Get the active LDAP Connection
     *
     * @return resource
     */
    public function getconnection() {
        return $this->connection;
    }

    /**
     * Get the bind status
     *
     * @return bool
     */
    public function getbind() {
        return $this->bind;
    }

    /**
     * Connects and Binds to the Domain Controller
     *
     * @return bool
     */
    public function connect() {
        if ($this->connection) return true;

        if (!function_exists('ldap_connect')) return false;
        if (sizeof($this->servers) == 0) return false;

        // Connect to the LDAP server as the username/password
        $server = $this->servers[array_rand($this->servers)];

        if ($this->useSSL) {
            $this->connection = ldap_connect("ldaps://" . $server, $this->port);
        } else {
            $this->connection = ldap_connect("ldap://" . $server, $this->port);
        }
        if ($this->connection === false) throw new \Exception("Cannot connect to ldap server " . $server);

        // Set some ldap options for talking to AD
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, $this->followReferrals);

        if ($this->useTLS) {
            ldap_start_tls($this->connection);
        }

        // Bind as a domain admin if they've set it up
        if ($this->username !== NULL && $this->password !== NULL) {
            $this->bind = @ldap_bind($this->connection, $this->username, $this->password);
            if (!$this->bind) {
                if ($this->useSSL && !$this->useTLS) {
                    // If you have problems troubleshooting, remove the @ character from the ldapbind command above to get the actual error message
                    throw new \Exception('Bind failed. Either the LDAPs connection failed or the login credentials are incorrect. Server said: ' . $this->getLastError());
                }
                else {
                    throw new \Exception('Bind failed. Check the login credentials and/or server details. Server said: ' . $this->getLastError());
                }
            }
        }
        if ($this->useSSO && $_SERVER['REMOTE_USER'] && $this->username === null && $_SERVER['KRB5CCNAME']) {
            putenv("KRB5CCNAME=" . $_SERVER['KRB5CCNAME']);
            $this->bind = @ldap_sasl_bind($this->connection, NULL, NULL, "GSSAPI");
            if (!$this->bind) {
                throw new \Exception('Rebind to LDAP server failed. Server said: ' . $this->getLastError());
            }
            else {
                return true;
            }
        }

        return true;
    }


    /**
     * Closes the LDAP connection
     *
     * @return void
     */
    public function disconnect() {
        if ($this->connection) {
            @ldap_close($this->connection);
        }
    }


    /**
     * Validate a user's login credentials
     *
     * @param string $username A user's AD username
     * @param string $password A user's AD password
     * @param bool optional $preventRebind
     * @return bool
     */
    public function authenticate($username, $password, $preventRebind = true) {
        // Prevent null binding
        if ($username === NULL || $password === NULL) { return false; }
        if (empty($username) || empty($password)) { return false; }
        if (!$this->connection) return false;

        // Allow binding over SSO for Kerberos
        if ($this->useSSO && $_SERVER['REMOTE_USER'] && $_SERVER['REMOTE_USER'] == $username && $this->username === NULL && $_SERVER['KRB5CCNAME']) {
            putenv("KRB5CCNAME=" . $_SERVER['KRB5CCNAME']);
            $this->bind = @ldap_sasl_bind($this->connection, NULL, NULL, "GSSAPI");
            if (!$this->bind) {
                throw new \Exception('Rebind to Active Directory failed. AD said: ' . $this->getLastError());
            }
            else {
                return true;
            }
        }

        // Bind as the user
        $ret = true;
        $this->bind = @ldap_bind($this->connection, $username . $this->accountSuffix, $password);
        if (!$this->bind) {
            $ret = false;
        }

        // Cnce we've checked their details, kick back into admin mode if we have it
        if ($this->username !== NULL && !$preventRebind) {
            $this->bind = @ldap_bind($this->connection, $this->username , $this->password);
            if (!$this->bind) {
                // This should never happen in theory
                throw new \Exception('Rebind to LDAP failed. Server said: ' . $this->getLastError());
            }
        }
        return $ret;
    }



    public function search($filter, $fields = []) {
        $cCacheID = "LdapSearch_" . $filter . "_" . implode(",", $fields);
        $aResults = \Yii::$app->cache->get($cCacheID);
        if (!$aResults) {
            if (!$this->connection) return [];

            if (!$this->bind) {
                $this->bind = @ldap_bind($this->connection);
                if (!$this->bind) {
                    throw new \Exception('Anonymous bind to LDAP server failed. Server said: ' . $this->getLastError());
                }
            }

            $sr = ldap_search($this->connection, $this->baseDn, $filter, $fields, 0);
            $entries = ldap_get_entries($this->getconnection(), $sr);

            $aResults = [];
            for ($i=0; $i < $entries["count"]; $i++) {
                $aResults[] = $this->getEntry($entries[$i]);
            }
            \Yii::$app->cache->set($cCacheID, $aResults, $this->cacheDuration);
        }
        return $aResults;
    }

    public function searchUser ($username, $fields = []) {
        $filter = $this->usernameAttribute . "=" . $username;
        $aResults = $this->search($filter, $fields);

        /** If the found user shall be checked against an extra condition filter ... */
        if (sizeof($aResults) > 0 && $this->searchUserExtraConditionFilter > '') {
            $aTempResults = $aResults;
            $aResults = [];
            $dn = $this->searchUserExtraConditionDn ? $this->searchUserExtraConditionDn : $this->baseDn;
            foreach ($aTempResults as $aUser) {
                $extraFilter = $this->searchUserExtraConditionFilter;
                foreach ($aUser as $key => $value) {
                    if (is_string($value)) $extraFilter = str_replace(":" . $key, $value, $extraFilter);
                }
                $sr = ldap_search($this->connection, $dn, $extraFilter, ["dn"]);
                $entries = ldap_get_entries($this->getconnection(), $sr);
                if (isset($entries["count"]) && $entries["count"] > 0) {
                    $aResults[] = $aUser;
                }
            }
            $cCacheID = "LdapSearch_" . $filter . "_" . implode(",", $fields);
            \Yii::$app->cache->set($cCacheID, $aResults, $this->cacheDuration);
        }

        return $aResults;
    }


    public function read($dn, $fields = []) {
        $cCacheID = "LdapRead_" . $dn . "_" . implode(",", $fields);
        $aResult = \Yii::$app->cache->get($cCacheID);
        if (!$aResult) {
            if (!$this->connection) return [];

            if (!$this->bind) {
                $this->bind = @ldap_bind($this->connection);
                if (!$this->bind) {
                    throw new \Exception('Anonymous bind to LDAP server failed. Server said: ' . $this->getLastError());
                }
            }

            $sr = ldap_read($this->connection, $dn, "objectClass=*", $fields, 0);
            $entries = ldap_get_entries($this->getconnection(), $sr);

            $aResult = null;
            if ($entries["count"] > 0) {
                $aResult = $this->getEntry($entries[0]);
                \Yii::$app->cache->set($cCacheID, $aResult, $this->cacheDuration);
            }
        }
        return $aResult ? $aResult : null;
    }

    protected function getEntry($aData){
        $aEntry = [];
        for($i=0; $i < $aData["count"]; $i++) {
            $cAttribute = $aData[$i];
            $aEntry[$cAttribute] = $this->getValue($aData[$cAttribute]);
        }
        if (isset($aData["dn"])) $aEntry["dn"] = $aData["dn"];
        return $aEntry;
    }

    protected function getValue($aData) {
        if ($aData["count"] == 0)
            return null;
        else if ($aData["count"] == 1)
            return $aData[0];
        else {
            $aValues = [];
            for($i=0; $i < $aData["count"]; $i++) {
                $aValues[] = $aData[$i];
            }
            return $aValues;
        }
    }


    public function getLastError() {
        return @ldap_error($this->connection);
    }



    public function getEmail($username) {
        $cCacheID = "LdapGetEmail_" . $username;
        $cResults = \Yii::$app->cache->get($cCacheID);
        // return strtolower(str_replace(" ", "-", $username)) . "@informationsgesellschaft.com";
        if (!$cResults) {
            $bConnected = $this->connect();
            if (!$this->connection) return null;

            $cResults="";
            if ($username == trim($username) && strpos($username, ' ') !== false) {
                $filter="(|(displayName=$username*))";
                $aInfos = $this->search($filter, ["mail"]);

                if ($aInfos && sizeof($aInfos)) {
                    $user = $aInfos[0];
                    $cResults = $user["mail"];
                    \Yii::$app->cache->set($cCacheID, $cResults, $this->cacheDuration);
                    return $cResults;
                }
                else {
                    $username = implode(" ", array_reverse(explode(" ", $username)));
                    $filter="(|(displayName=$username*))";
                    $aInfos = $this->search($filter, ["mail"]);
                    if ($aInfos && sizeof($aInfos)) {
                        $user = $aInfos[0];
                        $cResults = $user["mail"];
                        \Yii::$app->cache->set($cCacheID, $cResults, $this->cacheDuration);
                        return $cResults;
                    }
                }
            }
            else {
                $aInfos = $this->searchUser($username, []);
                if ($aInfos && sizeof($aInfos)) {
                    $user = $aInfos[0];
                    $cResults = $user["mail"];
                    \Yii::$app->cache->set($cCacheID, $cResults, $this->cacheDuration);
                    return $cResults;
                }
            }
        }
        return $cResults;
    }

    public function getUserDN($username) {
        return "cn=" . $username . ",OU=E-Mail Benutzer," . $this->baseDn;
    }

}
