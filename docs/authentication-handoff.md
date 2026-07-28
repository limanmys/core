# Trusted application authentication handoff

Liman can complete its existing OIDC flow for a confidential application on a
different browser origin without placing the Liman JWT in a redirect URL.

## Client registration

Configure an exact callback URI and a cryptographically random secret of at
least 32 characters:

```env
AUTH_HANDOFF_CLIENTS='{"netex":{"secret":"<random-secret>","redirect_uris":["https://netex.example/auth/liman/callback"]}}'
AUTH_HANDOFF_CODE_TTL=60
```

HTTP callbacks are rejected. For local loopback development only, set
`AUTH_HANDOFF_ALLOW_INSECURE_LOOPBACK=true`.

## Flow

1. The confidential application generates `state`, an RFC 7636 verifier, and
   its S256 challenge.
2. Its backend calls `POST /api/auth/login` with:

   ```json
   {
     "type": "oidc",
     "handoff": {
       "client_id": "netex",
       "redirect_uri": "https://netex.example/auth/liman/callback",
       "state": "<application-state>",
       "code_challenge": "<base64url-sha256>",
       "code_challenge_method": "S256"
     }
   }
   ```

3. The browser follows Liman's `redirect_url`. Liman owns the provider
   callback, validates the OIDC response, provisions the user, maps roles, and
   creates its JWT.
4. Liman stores the JWT payload behind an encrypted, short-lived, single-use
   code and redirects to the registered application callback with `code` and
   the original application `state`.
5. The application backend exchanges the code using HTTP Basic client
   authentication:

   ```http
   POST /api/auth/handoff/exchange
   Authorization: Basic base64(client_id:client_secret)
   Content-Type: application/json

   {
     "grant_type": "authorization_code",
     "code": "<one-time-code>",
     "code_verifier": "<original-verifier>",
     "redirect_uri": "https://netex.example/auth/liman/callback"
   }
   ```

The exchange response contains the Liman access token, expiry, and user
identity. It has `Cache-Control: no-store`. Codes are encrypted in Liman's
cache, keyed by a hash, protected by a distributed lock, bound to the client,
redirect URI, and PKCE challenge, and deleted after the first successful
exchange.

Multi-replica Liman deployments must use a shared cache backend that supports
atomic locks (for example Redis) so OIDC state and one-time codes are available
and consumed consistently across replicas.

Never put the Liman JWT in a query string, fragment, browser storage, or
cross-origin message.
