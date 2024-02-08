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
    private \LDAP\Connection $connection;

    private string $dn;

    private string $domain;

    /**
     * @param string $ip_address
     * @param string $username
     * @param string $password
     * @param int $port
     * @throws LDAPException
     */
    public function __construct(
        private readonly string $ip_address,
        private readonly string $username,
        private readonly string $password,

        // default port
        private readonly int    $port = 636,
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
    public function controlPagedResult($con, int $pageSize, string $cookie): array
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
     * @param string $filter
     * @param LDAPSearchOptions $options
     * @return array
     */
    public function search(string $filter, LDAPSearchOptions $options = new LDAPSearchOptions()): array
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
            $size += $entries['count'] ?? 0;

            unset($entries['count']);
        } while ($cookie !== null && $cookie != '');

        return collect($entries)->map(function ($item) use ($options) {
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
    }

    /**
     * Get domain
     */
    public function getDomain(): array|string
    {
        $domain = str_replace('dc=', '', strtolower($this->domain));

        return str_replace(',', '.', $domain);
    }

    /**
     * Check if the LDAP server is alive
     */
    private function isAlive(): bool
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
     * @throws LDAPException
     */
    private function checkConnection(): bool
    {
        // We check if the connection is already established
        if (isset($this->connection)) {
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
        if (str_starts_with($this->username, 'cn') || str_starts_with($this->username, 'CN')) {
            $this->dn = $this->username;
        } else {
            $this->dn = $this->username.'@'.$this->getDomain();
        }

        return true;
    }

    /**
     * Create LDAP connection object with provided credentials
     * @throws LDAPException
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

/**
 * LDAP Search Options
 *
 * Build dynamic search options easily
 */
class LDAPSearchOptions
{
    /**
     * @param int $page
     * @param int $per_page
     * @param array $attributes
     * @param int $stop_on
     */
    public function __construct(
        private int $page = 1,
        private int $per_page = 500,
        private array $attributes = ['dn'],
        private int $stop_on = -1
    ) {
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->per_page;
    }

    /**
     * @return array|string[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return int
     */
    public function getStopOn(): int
    {
        return $this->stop_on;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param int $per_page
     * @return $this
     */
    public function setPerPage(int $per_page): self
    {
        $this->per_page = $per_page;

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param int $stop_on
     * @return $this
     */
    public function setStopOn(int $stop_on): self
    {
        $this->stop_on = $stop_on;

        return $this;
    }
}

/**
 * Custom LDAP Exceptions
 */
class LDAPException extends Exception
{
    const THROW_CONNECTION_ERROR = 0;

    const THROW_BIND_ERROR = 1;

    const THROW_BIND_WRONG_USERNAME_PASSWORD_ERROR = 10;

    const THROW_SEARCH_ERROR = 2;

    const THROW_ATTRIBUTE_ERROR = 3;

    const THROW_ENTRY_ERROR = 4;

    const THROW_MODIFY_ERROR = 5;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * String representation of the exception
     * @link https://php.net/manual/en/exception.tostring.php
     * @return string the string representation of the exception.
     */
    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}
