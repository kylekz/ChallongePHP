# ChallongePHP

![Test](https://github.com/teamreflex/ChallongePHP/workflows/Test/badge.svg?branch=master)
[![Latest Version](https://img.shields.io/packagist/v/team-reflex/challonge-php.svg)](https://packagist.org/packages/team-reflex/challonge-php)
[![Downloads](https://img.shields.io/packagist/dt/team-reflex/challonge-php.svg)](https://packagist.org/packages/team-reflex/challonge-php)

Modern, PSR-18 compliant PHP library for the [Challonge](https://challonge.com) tournament management API v2.1, with full OAuth 2.0 support.

## Features

- 🚀 **Challonge API v2.1** - Latest API version with all modern features
- 🔐 **OAuth 2.0** - Full OAuth support (Authorization Code, Device, Client Credentials flows)
- 🔑 **API Key Auth** - Backwards compatible with v1 API keys
- 📦 **Modern PHP** - PHP 8.1-8.4 with strict types and readonly properties
- ✅ **Type Safe** - Powered by Valinor for robust data mapping
- 🎯 **PSR Compliant** - PSR-18 (HTTP Client), PSR-17 (HTTP Factories), PSR-3 (Logger)
- 🧪 **Well Tested** - Comprehensive test coverage with PHPUnit 11

## Installation

### Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- A PSR-18 compatible HTTP client (Guzzle, Symfony HttpClient, etc.)

### Install via Composer

```bash
composer require team-reflex/challonge-php:^6.0
```

## Version Compatibility

| ChallongePHP | PHP Version | Challonge API | Status |
|--------------|-------------|---------------|---------|
| ^6.0 | 8.1 - 8.4 | v2.1 | ✅ Active |
| ^5.0 | 8.1 - 8.2 | v1 | ❌ Deprecated |
| ^4.0 | 8.0 - 8.1 | v1 | ❌ Deprecated |
| ^3.0 | 7.4 - 8.0 | v1 | ❌ Deprecated |

## Quick Start

### With API Key (Simplest)

```php
use GuzzleHttp\Client;
use Reflex\Challonge\Challonge;

$http = new Client();
$challonge = new Challonge($http, 'your_api_key');

// Get all tournaments
$tournaments = $challonge->getTournaments();

// Create a new tournament
$tournament = $challonge->createTournament([
    'name' => 'My Tournament',
    'url' => 'my_tournament',
    'tournament_type' => 'single_elimination',
]);
```

### With OAuth (Recommended for User Applications)

```php
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\Auth\OAuth\{OAuthConfig, OAuthTokenAuth, AuthorizationCodeFlow};

$http = new Client();
$factory = new Psr17Factory();

// Configure OAuth
$config = new OAuthConfig(
    clientId: 'your_client_id',
    clientSecret: 'your_client_secret',
    redirectUri: 'https://yourapp.com/callback',
    scopes: [
        OAuthConfig::SCOPE_TOURNAMENTS_READ,
        OAuthConfig::SCOPE_TOURNAMENTS_WRITE,
    ]
);

// Authorization flow
$flow = new AuthorizationCodeFlow($config, $http, $factory, $factory);

// Step 1: Redirect user to authorize
header('Location: ' . $flow->getAuthorizationUrl(['state' => 'random_state']));

// Step 2: Exchange code for token (in your callback handler)
$accessToken = $flow->exchangeCodeForToken($_GET['code']);

// Step 3: Use the token
$auth = new OAuthTokenAuth($accessToken);
$challonge = new Challonge($http, $auth);
```

## Usage Examples

### Tournaments

```php
// Get all tournaments with filters
$tournaments = $challonge->getTournaments([
    'state' => 'in_progress',
    'type' => 'single_elimination',
]);

// Fetch a specific tournament
$tournament = $challonge->fetchTournament('my_tournament');

// Update a tournament
$tournament = $tournament->update([
    'name' => 'Updated Tournament Name',
    'description' => 'New description',
]);

// Start a tournament
$tournament = $tournament->start();

// Delete a tournament
$tournament->delete();
```

### Participants

```php
// Add a participant
$participant = $tournament->addParticipant([
    'name' => 'Player 1',
    'seed' => 1,
]);

// Bulk add participants
$participants = $tournament->bulkAddParticipant([
    ['name' => 'Player 2'],
    ['name' => 'Player 3'],
    ['name' => 'Player 4'],
]);

// Get all participants
$participants = $challonge->getParticipants('my_tournament');

// Check in a participant
$participant = $participant->checkin();

// Update a participant
$participant = $participant->update([
    'misc' => 'Some additional info',
]);
```

### Matches

```php
// Get all matches
$matches = $challonge->getMatches('my_tournament');

// Get a specific match
$match = $challonge->getMatch('my_tournament', 12345);

// Report match score
$match = $match->update([
    'scores_csv' => '3-2',
    'winner_id' => 67890,
]);

// Reopen a match
$match = $match->reopen();

// Mark match as underway
$match = $match->markAsUnderway();
```

### Standings/Leaderboard

```php
// Get tournament standings with calculated stats
$standings = $challonge->getStandings('my_tournament');

// Access progress
echo "Tournament is {$standings['progress']}% complete";

// Access final standings
foreach ($standings['final'] as $standing) {
    echo "{$standing['name']}: {$standing['win']}-{$standing['lose']}\n";
}
```

### Races (Time-Trial Tournaments)

```php
// Create a race
$race = $challonge->createRace([
    'name' => 'Speed Run Championship',
    'race_type' => 'time_trial',
    'target_round_count' => 3,
]);

// Start the race
$race = $race->changeState('start');

// Create a round
$round = $race->createRound(['number' => 1]);

// Record elapsed times
$time = $round->createElapsedTime([
    'participant_id' => 123,
    'elapsed_time_millis' => 125430,
]);

// Bulk update times
$times = $round->bulkUpdateElapsedTimes([
    ['participant_id' => 123, 'elapsed_time_millis' => 125430],
    ['participant_id' => 456, 'elapsed_time_millis' => 128920],
]);
```

### Communities

```php
// Get a community
$community = $challonge->getCommunity('my-community');

// List community tournaments
$tournaments = $community->getTournaments(['state' => 'in_progress']);

// Create a tournament in the community
$tournament = $community->createTournament([
    'name' => 'Community Championship',
    'tournament_type' => 'double_elimination',
]);

// Get participants
$participants = $community->getTournamentParticipants($tournament->id);

// Get matches
$matches = $community->getTournamentMatches($tournament->id);
```

### Attachments

```php
// Add an attachment to a match
$attachment = $challonge->createMatchAttachment('my_tournament', 12345, [
    'url' => 'https://example.com/match-screenshot.png',
    'description' => 'Final game screenshot',
]);

// Get all match attachments
$attachments = $challonge->getMatchAttachments('my_tournament', 12345);

// Update an attachment
$attachment = $attachment->update([
    'description' => 'Updated description',
]);

// Delete an attachment
$attachment->delete();
```

### User Profile

```php
// Get the authenticated user's profile
$user = $challonge->getMe();

echo "Welcome, {$user->display_name}!";
echo "You've organized {$user->tournaments_count} tournaments";
```

## OAuth Flows

### 1. Authorization Code Flow (Web Applications)

For web applications where users can authorize via browser:

```php
$flow = new AuthorizationCodeFlow($config, $http, $requestFactory, $streamFactory);

// Get authorization URL
$authUrl = $flow->getAuthorizationUrl(['state' => 'csrf_token']);

// Exchange code for token
$token = $flow->exchangeCodeForToken($_GET['code']);

// Refresh expired token
if ($token->isExpired()) {
    $newToken = $flow->refreshToken($token->getRefreshToken());
}
```

### 2. Device Authorization Flow (Games/Consoles)

For devices without easy browser access:

```php
$flow = new DeviceAuthorizationFlow($config, $http, $requestFactory, $streamFactory);

// Request device code
$deviceAuth = $flow->requestDeviceCode();

echo "Go to {$deviceAuth['verification_uri']} and enter: {$deviceAuth['user_code']}";

// Poll for token
$token = null;
while ($token === null) {
    sleep($deviceAuth['interval']);
    $token = $flow->pollForToken($deviceAuth['device_code']);
}
```

### 3. Client Credentials Flow (Server-to-Server)

For backend services and scheduled tasks:

```php
$flow = new ClientCredentialsFlow($config, $http, $requestFactory, $streamFactory);
$token = $flow->getAccessToken();
```

## OAuth Scopes

```php
OAuthConfig::SCOPE_ME                      // Read user details
OAuthConfig::SCOPE_APPLICATION_ORGANIZER   // Full access to user's resources
OAuthConfig::SCOPE_APPLICATION_PLAYER      // Read resources, register, report scores
OAuthConfig::SCOPE_TOURNAMENTS_READ        // Read tournaments
OAuthConfig::SCOPE_TOURNAMENTS_WRITE       // Create, update, delete tournaments
OAuthConfig::SCOPE_MATCHES_READ            // Read matches
OAuthConfig::SCOPE_MATCHES_WRITE           // Update matches
OAuthConfig::SCOPE_PARTICIPANTS_READ       // Read participants
OAuthConfig::SCOPE_PARTICIPANTS_WRITE      // Create, update, delete participants
OAuthConfig::SCOPE_ATTACHMENTS_READ        // Read attachments
OAuthConfig::SCOPE_ATTACHMENTS_WRITE       // Manage attachments
OAuthConfig::SCOPE_COMMUNITIES_MANAGE      // Manage communities
```

## Error Handling

```php
use Reflex\Challonge\Exceptions\{
    ValidationException,
    NotFoundException,
    UnauthorizedException,
    ServerException
};

try {
    $tournament = $challonge->fetchTournament('invalid');
} catch (ValidationException $e) {
    // Get detailed validation errors
    $errors = $e->getErrors();
    foreach ($errors['errors'] as $error) {
        echo "{$error['detail']} at {$error['source']['pointer']}\n";
    }
} catch (NotFoundException $e) {
    echo "Tournament not found\n";
} catch (UnauthorizedException $e) {
    echo "Invalid credentials or insufficient permissions\n";
} catch (ServerException $e) {
    echo "Challonge server error\n";
}
```

## Token Storage

```php
use Reflex\Challonge\Auth\OAuth\AccessToken;

// Save token
$tokenData = $accessToken->toArray();
file_put_contents('token.json', json_encode($tokenData));

// Load token
$tokenData = json_decode(file_get_contents('token.json'), true);
$accessToken = AccessToken::fromArray($tokenData);

// Check expiration
if ($accessToken->isExpired()) {
    // Refresh...
}
```

## HTTP Client Configuration

ChallongePHP is PSR-18 compliant and works with any PSR-18 HTTP client:

### Guzzle
```php
$http = new GuzzleHttp\Client([
    'timeout' => 30,
    'connect_timeout' => 10,
]);
```

### Symfony HttpClient
```php
$http = Symfony\Component\HttpClient\Psr18Client::create([
    'timeout' => 30,
]);
```

## Development

### Running Tests
```bash
composer test
```

### Code Style
```bash
composer lint
```

### Static Analysis
```bash
composer analyse
```

## Upgrading from v5

See [UPGRADE-v6.md](UPGRADE-v6.md) for detailed migration instructions.

Key changes:
- API v1 → v2.1
- `spatie/data-transfer-object` → `cuyz/valinor`
- New OAuth support
- Immutable DTOs with `readonly` properties
- JSON API request/response format

## Documentation

- [Challonge API v2.1 Documentation](https://api.challonge.com/docs/v2.1)
- [Swagger Docs](https://connect.challonge.com/docs.json)
- [Upgrade Guide](UPGRADE-v6.md)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for new features
4. Ensure all tests pass
5. Submit a pull request

## License

MIT License. See [LICENSE.md](https://github.com/teamreflex/ChallongePHP/blob/master/LICENSE.md) for details.
