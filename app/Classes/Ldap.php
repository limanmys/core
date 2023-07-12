<?php

namespace App\Classes;

use Exception;
use Throwable;

/**
 * PHP8 Compatible LDAP class
 *
 * This class is used to connect to LDAP servers.
 *
 * @version 2.0.0
 *
 * @author Doğukan Öksüz
 */
class Ldap
{
    // Variables
    private $connection;

    private string $dn;

    private string $domain;

    public function __construct(
        private string $ip_address,
        private string $username,
        private string $password,

        // default port
        private int $port = 636,
    ) {
        // First, we check if LDAP server is alive
        ! $this->isAlive() ? throw new LDAPException('LDAP server is not alive.', LDAPException::THROW_CONNECTION_ERROR) : null;

        // Then, we check if the connection is established
        ! $this->checkConnection() ? throw new LDAPException('LDAP server connection failed.') : null;

        // We finally connect to the LDAP server with our authentication
        $connection = $this->createConnection();
        if (! $connection) {
            throw new LDAPException('LDAP server bind failed, might be caused by wrong username or password.', LDAPException::THROW_BIND_ERROR);
        }
        $this->connection = $connection;
    }

    /**
     * Sets LDAP pagination controls.
     */
    public function controlPagedResult($con, int $pageSize, string $cookie)
    {
        return [
            [
                'oid' => \LDAP_CONTROL_PAGEDRESULTS,
                'isCritical' => true,
                'value' => [
                    'size' => $pageSize,
                    'cookie' => $cookie,
                ],
            ],
        ];
    }

    /**
     * Retrieve LDAP pagination cookie.
     */
    public function controlPagedResultResponse($con, $result, string $cookie = ''): string
    {
        ldap_parse_result($con, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);

        return $controls[\LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
    }

    /**
     * LDAP Search with pagination
     *
     * @param  string  $filter
     * @param  LDAPSearchOptions  $options
     * @return array
     */
    public function search($filter, $options = new LDAPSearchOptions())
    {
        $filter = html_entity_decode($filter);

        // Variables
        $cookie = '';
        $size = 0;
        $entries = [];

        $loop = 0;
        do {
            // Break if enough items has been retrieved
            if ($options->getStopOn() != '-1' && $size > $options->getStopOn()) {
                break;
            }
            $loop++;

            // Search on LDAP with specified controls object
            $controls = $this->controlPagedResult($this->connection, $options->getPerPage(), $cookie);
            $search = ldap_search(
                $this->connection,
                $this->domain,
                $filter,
                $options->getAttributes(),
                0,
                -1,
                -1,
                LDAP_DEREF_NEVER,
                $controls
            );

            // Get entries from search object
            if ($loop == intval($options->getPage()) || $options->getPage() == '-1') {
                $entries = array_merge(ldap_get_entries($this->connection, $search), $entries);
            }

            // Update cookie and total size
            $cookie = $this->controlPagedResultResponse($this->connection, $search, $cookie);
            $size += $entries['count'];

            unset($entries['count']);
        } while ($cookie !== null && $cookie != '');

        $entries = collect($entries)->map(function ($item) use ($options) {
            if (count($options->getAttributes()) > 1) {
                $temp = $item;
                $item = [];
                foreach ($options->getAttributes() as $attribute) {
                    if (isset($temp[$attribute])) {
                        if (isset($temp[$attribute]['count'])) {
                            unset($temp[$attribute]['count']);
                        }
                        $item[$attribute] = is_array($temp[$attribute]) && count($temp[$attribute]) === 1 ? $temp[$attribute][0] : $temp[$attribute];
                    }
                }

                return $item;
            } else {
                $attr = $options->getAttributes()[0];
                if (isset($item[$attr])) {
                    unset($item[$attr]['count']);

                    return count($item[$attr]) === 1
                        ? $item[$attr][0]
                        : $item[$attr];
                }
            }
        })->toArray();

        return $entries;
    }

    /**
     * Get domain
     */
    public function getDomain()
    {
        $domain = str_replace('dc=', '', strtolower($this->domain));

        return str_replace(',', '.', $domain);
    }

    /**
     * Check if the LDAP server is alive
     */
    private function isAlive()
    {
        $alive = @fsockopen($this->ip_address, $this->port, $errno, $errstr, 0.2);
        if (! $alive) {
            return false;
        }
        fclose($alive);

        return true;
    }

    /**
     * Check if LDAP can be connectable
     */
    private function checkConnection()
    {
        // We check if the connection is already established
        if ($this->connection) {
            return true;
        }

        // We try to connect to the LDAP server
        $connection = ldap_connect($this->ip_address, 389);

        // We check if the connection is established
        if (! $connection) {
            throw new LDAPException('LDAP server connection failed.', LDAPException::THROW_CONNECTION_ERROR);
        }

        // We set the LDAP options
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        // We try to bind to the LDAP server
        $bind = @ldap_bind($connection);

        // We check if the bind is successful
        if (! $bind) {
            throw new LDAPException('LDAP server bind failed.', LDAPException::THROW_BIND_ERROR);
        }

        // Search LDAP root with anonymous bind
        $anonymousBindReader = ldap_read($connection, '', 'objectclass=*');
        $entries = ldap_get_entries($connection, $anonymousBindReader);
        if (! isset($entries[0])) {
            return false;
        }
        $entries = $entries[0];

        // Set domain CN
        $this->domain = (array_key_exists('rootdomainnamingcontext', $entries)) ? $entries['rootdomainnamingcontext'][0] : '';

        // Set DN value
        if (substr($this->username, 0, 2) == 'cn' || substr($this->username, 0, 2) == 'CN') {
            $this->dn = $this->username;
        } else {
            $this->dn = $this->username.'@'.$this->getDomain();
        }

        return true;
    }

    /**
     * Create LDAP connection object with provided credentials
     */
    private function createConnection(): \LDAP\Connection|false
    {
        $connection = ldap_connect('ldaps://'.$this->ip_address.':'.$this->port);

        // Connection options
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        try {
            ldap_bind($connection, $this->dn, $this->password);
        } catch (\Throwable $e) {
            throw new LDAPException('LDAP server bind failed, username password might be wrong, '.$e->getMessage(), LDAPException::THROW_BIND_WRONG_USERNAME_PASSWORD_ERROR);
        }

        return $connection;
    }
}

class LDAPSearchOptions
{
    public function __construct(
        private int $page = 1,
        private int $per_page = 500,
        private array $attributes = ['dn'],
        private int $stop_on = -1
    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->per_page;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getStopOn(): int
    {
        return $this->stop_on;
    }
}

class LDAPException extends Exception
{
    const THROW_CONNECTION_ERROR = 0;

    const THROW_BIND_ERROR = 1;

    const THROW_BIND_WRONG_USERNAME_PASSWORD_ERROR = 10;

    const THROW_SEARCH_ERROR = 2;

    const THROW_ATTRIBUTE_ERROR = 3;

    const THROW_ENTRY_ERROR = 4;

    const THROW_MODIFY_ERROR = 5;

    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}
