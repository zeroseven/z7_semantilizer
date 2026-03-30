# CORS Setup for Multi-Site Usage

## When is this needed?

This configuration is **ONLY** required if you want to use the Semantilizer across different domains in a multi-site TYPO3 setup. For example:

- TYPO3 Backend running on `backend.example.com`
- Site A running on `site-a.example.com`
- Site B running on `site-b.example.com`
- You want to use Semantilizer in the backend to check content on Site A and Site B

**If you're using Semantilizer only on the same domain, you don't need this setup.**

## Why is webserver configuration needed?

When the browser detects a cross-origin request with custom headers (like `X-Semantilizer`), it automatically sends a **CORS preflight request** using the OPTIONS HTTP method.

This preflight request is handled by your webserver (nginx/Apache) **before** it reaches PHP or TYPO3. By default, most webservers block or reject OPTIONS requests, which causes the "CORS policy" error.

The PHP middleware in this extension handles the CORS response headers for the actual GET requests, but it **cannot** handle the OPTIONS preflight - that must be done at the webserver level.

## Setup Instructions

### For Apache

1. Ensure `mod_headers` and `mod_rewrite` are enabled
2. Add the configuration from `.htaccess.example` to your document root's `.htaccess` file
3. Restart Apache: `sudo service apache2 restart`

### For Nginx

1. Add the configuration from `nginx-cors.conf.example` to your server block
2. Test the configuration: `nginx -t`
3. Reload nginx: `sudo service nginx reload`

### For DDEV

**Option 1: nginx_full (recommended for development)**

1. Copy the nginx configuration to `.ddev/nginx_full/nginx-site.conf`
2. Remove the `#ddev-generated` line at the top
3. Integrate the CORS handling into the `location /` block
4. Run `ddev restart`

**Option 2: Custom nginx snippet**

1. Create `.ddev/nginx/semantilizer-cors.conf` with the CORS configuration
2. Run `ddev restart`

## Testing

After applying the configuration:

1. Clear browser cache and cookies
2. Open Firefox/Chrome DevTools → Network tab
3. Use Semantilizer on a cross-origin site
4. You should see:
   - An OPTIONS request with status 204 (success)
   - The GET request with status 200 and CORS headers

## Troubleshooting

**Still getting CORS errors?**

1. Verify the webserver configuration is active (check error logs)
2. Ensure `trustedHostsPattern` in TYPO3 includes your domains
3. Check that site configurations have proper `base` URLs defined
4. Clear browser cache completely (CORS responses are often cached)

**OPTIONS request returns 405?**

The webserver configuration is not active or not correctly placed. The OPTIONS request should never reach PHP - it should be answered by the webserver directly with 204.

## Security Considerations

The middleware checks allowed origins against:
1. Site configuration base URLs
2. TYPO3's `trustedHostsPattern` configuration

Make sure your `trustedHostsPattern` is properly configured and not too permissive (avoid wildcards like `.*` in production).
