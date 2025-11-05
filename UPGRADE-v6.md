# Upgrade Guide: v5 to v6

ChallongePHP v6.0 brings major updates to modernize the package with the latest PHP standards and Challonge API v2.1 support.

## Breaking Changes Summary

1. **API Version**: Upgraded from Challonge API v1 to v2.1
2. **Data Mapper**: Replaced `spatie/data-transfer-object` with `cuyz/valinor`
3. **Authentication**: New OAuth 2.0 support alongside v1 API keys
4. **Request Format**: Changed from form-data to JSON API format
5. **PHP Version**: Now requires PHP 8.1-8.4 (was 8.1-8.2)
6. **Dependencies**: Updated to Laravel 12, PHPUnit 11, and modern PSR standards

## Migration Steps

### 1. Update Dependencies

Update your `composer.json`:

```json
{
    "require": {
        "team-reflex/challonge-php": "^6.0"
    }
}
```

Then run:

```bash
composer update team-reflex/challonge-php
```

### 2. Update Instantiation

#### Before (v5.x):
```php
use GuzzleHttp\Client;
use Reflex\Challonge\Challonge;

$http = new Client();
$challonge = new Challonge($http, 'your_api_key', true);
```

#### After (v6.x) - API Key:
```php
use GuzzleHttp\Client;
use Reflex\Challonge\Challonge;

$http = new Client();
// String API key works for backwards compatibility
$challonge = new Challonge($http, 'your_api_key', false); // Note: mapOptions is now false by default

// OR use the new ApiKeyAuth class
use Reflex\Challonge\Auth\ApiKeyAuth;

$auth = new ApiKeyAuth('your_api_key');
$challonge = new Challonge($http, $auth);
```

#### After (v6.x) - OAuth:
```php
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\Auth\OAuth\OAuthConfig;
use Reflex\Challonge\Auth\OAuth\OAuthTokenAuth;
use Reflex\Challonge\Auth\OAuth\AuthorizationCodeFlow;

$http = new Client();
$requestFactory = new Psr17Factory();
$streamFactory = new Psr17Factory();

// Configure OAuth
$oauthConfig = new OAuthConfig(
    clientId: 'your_client_id',
    clientSecret: 'your_client_secret',
    redirectUri: 'https://yourapp.com/callback',
    scopes: [
        OAuthConfig::SCOPE_TOURNAMENTS_READ,
        OAuthConfig::SCOPE_TOURNAMENTS_WRITE,
        OAuthConfig::SCOPE_PARTICIPANTS_READ,
        OAuthConfig::SCOPE_PARTICIPANTS_WRITE,
    ]
);

// Get authorization URL
$authFlow = new AuthorizationCodeFlow($oauthConfig, $http, $requestFactory, $streamFactory);
$authUrl = $authFlow->getAuthorizationUrl(['state' => 'random_state']);

// After user authorizes and you receive the code
$accessToken = $authFlow->exchangeCodeForToken($_GET['code']);

// Use the access token
$auth = new OAuthTokenAuth($accessToken);
$challonge = new Challonge($http, $auth);
```

### 3. Update Tournament Creation

#### Before (v5.x):
```php
$tournament = $challonge->createTournament([
    'name' => 'My Tournament',
    'url' => 'my_tournament',
    'tournament_type' => 'single elimination',
]);
```

#### After (v6.x):
```php
// v2.1 uses JSON API format internally, but the interface is cleaner
$tournament = $challonge->createTournament([
    'name' => 'My Tournament',
    'url' => 'my_tournament',
    'tournament_type' => 'single_elimination', // Note: underscores instead of spaces
]);
```

### 4. Update Tournament Methods

Most methods remain the same, but response handling has changed:

#### Before (v5.x):
```php
// Delete returned the deleted tournament
$deleted = $tournament->delete();
```

#### After (v6.x):
```php
// Delete now returns void
$tournament->delete(); // No return value
```

### 5. Update Filtering

The new v2.1 API supports more robust filtering:

```php
// Get tournaments with filters
$tournaments = $challonge->getTournaments([
    'state' => 'pending', // pending, in_progress, ended
    'type' => 'single_elimination',
    'created_after' => '01/01/2024',
    'created_before' => '12/31/2024',
    'page' => 1,
    'per_page' => 25,
]);
```

### 6. OAuth Flows

v6.0 introduces three OAuth flows:

#### Authorization Code Flow (Web Applications)
```php
use Reflex\Challonge\Auth\OAuth\AuthorizationCodeFlow;

$flow = new AuthorizationCodeFlow($oauthConfig, $httpClient, $requestFactory, $streamFactory);

// Step 1: Redirect user to authorization URL
header('Location: ' . $flow->getAuthorizationUrl(['state' => 'random_state']));

// Step 2: Exchange code for token (in callback)
$token = $flow->exchangeCodeForToken($_GET['code']);

// Step 3: Refresh expired token
if ($token->isExpired() && $token->hasRefreshToken()) {
    $newToken = $flow->refreshToken($token->getRefreshToken());
}
```

#### Device Authorization Flow (Games/Consoles)
```php
use Reflex\Challonge\Auth\OAuth\DeviceAuthorizationFlow;

$flow = new DeviceAuthorizationFlow($oauthConfig, $httpClient, $requestFactory, $streamFactory);

// Step 1: Request device code
$deviceAuth = $flow->requestDeviceCode();

// Step 2: Display to user
echo "Go to {$deviceAuth['verification_uri']} and enter code: {$deviceAuth['user_code']}";

// Step 3: Poll for token
$token = null;
while ($token === null) {
    sleep($deviceAuth['interval']);
    $token = $flow->pollForToken($deviceAuth['device_code']);
}
```

#### Client Credentials Flow (Server-to-Server)
```php
use Reflex\Challonge\Auth\OAuth\ClientCredentialsFlow;

$flow = new ClientCredentialsFlow($oauthConfig, $httpClient, $requestFactory, $streamFactory);
$token = $flow->getAccessToken();
```

### 7. Error Handling

Error responses now follow the v2.1 JSON API format:

```php
try {
    $tournament = $challonge->fetchTournament('invalid');
} catch (\Reflex\Challonge\Exceptions\ValidationException $e) {
    // New: Get detailed error information
    $errors = $e->getErrors();

    // Error format:
    // [
    //     'errors' => [
    //         [
    //             'status' => 422,
    //             'detail' => 'Tournament Format is invalid',
    //             'source' => ['pointer' => '/data/attributes/tournament_format']
    //         ]
    //     ]
    // ]
}
```

### 8. Data Transfer Objects

DTOs now use Valinor instead of Spatie:

```php
// All DTO properties are now readonly and nullable
$tournament = $challonge->fetchTournament('my_tournament');

echo $tournament->name; // Still works the same
echo $tournament->id;   // Still works the same

// Properties are now readonly - this will throw an error:
// $tournament->name = 'New Name'; // ❌ Error
```

## New Entities

v6.0 includes full implementation of all Challonge API v2.1 entities:

### Previously Available:
- **Tournament** - Standard tournament management
- **Participant** - Tournament participants
- **Match** - Match management and score reporting

### New in v6.0:
- **Race** - Time-trial/racing tournaments with timing
- **Round** - Race rounds with elapsed time tracking
- **ElapsedTime** - Participant time records for races
- **Attachment** - Match attachments (images, files, URLs)
- **Community** - Community management and scoped tournaments
- **User** - Authenticated user profile information

All entities are fully implemented with complete CRUD operations where applicable.

## New Features

### 1. Complete API Coverage

v6.0 implements 100% of the Challonge API v2.1 swagger specification:
- All 9 entity types
- All endpoints from swagger.json
- Community-scoped tournament operations
- Race/time-trial tournament support
- Match attachment management
- User profile access

### 2. Modern Type Hints

All classes now use PHP 8.1+ features:

- `readonly` properties
- Union types (`AuthenticationInterface|string`)
- Named arguments
- Constructor property promotion

### 2. OAuth Scopes

Available OAuth scopes:

```php
OAuthConfig::SCOPE_ME                      // Read user details
OAuthConfig::SCOPE_APPLICATION_ORGANIZER   // Full access to user's resources
OAuthConfig::SCOPE_APPLICATION_PLAYER      // Read resources, register, report scores
OAuthConfig::SCOPE_TOURNAMENTS_READ        // Read tournaments
OAuthConfig::SCOPE_TOURNAMENTS_WRITE       // Create, update, delete tournaments
OAuthConfig::SCOPE_MATCHES_READ            // Read matches
OAuthConfig::SCOPE_MATCHES_WRITE           // Update matches
OAuthConfig::SCOPE_ATTACHMENTS_READ        // Read attachments
OAuthConfig::SCOPE_ATTACHMENTS_WRITE       // Create, update, delete attachments
OAuthConfig::SCOPE_PARTICIPANTS_READ       // Read participants
OAuthConfig::SCOPE_PARTICIPANTS_WRITE      // Create, update, delete participants
OAuthConfig::SCOPE_COMMUNITIES_MANAGE      // Manage communities
```

### 3. Token Management

```php
use Reflex\Challonge\Auth\OAuth\AccessToken;

// Store token
$tokenData = $accessToken->toArray();
file_put_contents('token.json', json_encode($tokenData));

// Restore token
$tokenData = json_decode(file_get_contents('token.json'), true);
$accessToken = AccessToken::fromArray($tokenData);

// Check expiration
if ($accessToken->isExpired()) {
    // Refresh token
    $newToken = $flow->refreshToken($accessToken->getRefreshToken());
}
```

## Testing Considerations

1. **Test Fixtures**: Update test fixtures to match v2.1 JSON API format
2. **Response Structure**: Responses now include `data` wrapper: `$response['data']`
3. **Mocking**: Update mocks to return v2.1 format responses

## Common Issues

### Issue: `mapOptions` Parameter

**Problem**: Code relies on automatic option mapping.

**Solution**: v2.1 uses JSON API format internally, so mapping is no longer needed. Set `mapOptions` to `false` (default in v6).

### Issue: Delete Methods Return Values

**Problem**: Code expects deleted objects to be returned.

**Solution**: Update code to not expect return values from delete operations.

```php
// Before
$deleted = $tournament->delete();
processDeleted($deleted);

// After
$tournament->delete();
processDeleted($tournament); // Use the object before deleting
```

### Issue: DTOs Are Immutable

**Problem**: Code tries to modify DTO properties.

**Solution**: DTOs are now immutable with `readonly` properties. Use update methods instead:

```php
// Before
$tournament->name = 'New Name';

// After
$tournament = $tournament->update(['name' => 'New Name']);
```

## Need Help?

- Check the [examples directory](examples/) for working code samples
- Review the [API documentation](https://api.challonge.com/docs/v2.1)
- Open an [issue on GitHub](https://github.com/teamreflex/ChallongePHP/issues)

## Rollback

If you need to rollback to v5:

```bash
composer require team-reflex/challonge-php:^5.0
```

Note that v5 will only receive security updates going forward.
