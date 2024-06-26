[![Build Status](https://travis-ci.org/franzip/throttler.svg?branch=master)](https://travis-ci.org/franzip/throttler)
[![Coverage Status](https://coveralls.io/repos/franzip/throttler/badge.svg?branch=master)](https://coveralls.io/r/franzip/throttler?branch=master)

# Throttler
A simple, general purpose, rate limiter and usage tracker library.

## Installing via Composer (recommended)

Install composer in your project:
```
curl -s http://getcomposer.org/installer | php
```

Create a composer.json file in your project root:
```
{
    "require": {
        "franzip/throttler": "1.0.*"
    }
}
```

Install via composer
```
php composer.phar install
```

## Description
Once instantiated, the object must always be explicitly activated with the provided
`start()` method.

Basically, you set a timeframe (it can be 24 hours, 2 seconds, 30 minutes, etc.),
a global limit and the things you want to keep track of, and you're ready to go.

You can also set an additional limit that will applied as limit to each single
component you are tracking.

You can change the given parameters only after you stop the current instance from
tracking with the provided `stop()` method.

The `Throttler` will detect when a given timeframe is expired: all its internal
states will be reset and a new timeframe will be computed.

If your code need to access any `Throttler` attributes, just use the provided
getters (getName, getGlobalThreshold, etc.). See the constructor.

## Constructor
```php
$throttler = new Throttler($name, $globalThreshold, $metric, $metricFactor = 1,
                           $componentThreshold = null, $components = array());
```

## Allowed $metric
- 'sec': Compute the timeframe in (seconds * $metricFactor)
- 'min': Compute the timeframe in (minutes * $metricFactor)
- 'hrs': Compute the timeframe in (hours   * $metricFactor)


## Basic Usage (default arguments)
Example 1: using `Throttler` to cap incoming requests.
The total amount of requests will be capped to 30 per hour.

```php
use Franzip\Throttler\Throttler;

// Setting things up
// Max 30 total requests per hour
$throttler = new Throttler('requests', 30, 'hrs');

// Nothing happens and the call will return false since there is nothing to track
$throttler->start();

// Add remote addresses to track...
$throttler->addComponents('AddressToTrack1');
$throttler->addComponents('AddressToTrack2');
$throttler->addComponents('AddressToTrack3');
// Bulk adding
$throttler->addComponents(array('AddressToTrack4',
                                'AddressToTrack5',
                                'AddressToTrack6',
                                ...));
...

// Start tracking (timeframe starts now)
$throttler->start();

if ($throttler->updateComponent('AddressToTrack1')) {
    // handle update success
} else {
    // handle update failure
}

$numOfRequests = 31;

// will return false since the global limit is 30
$throttler->updateComponent('AddressToTrack1', $numOfRequests);

...

// Remove all stuff to track
$throttler->stop();
$throttler->setComponents(array());

```


## Usage (custom arguments)

Example 2: using `Throttler` to cap incoming requests.

The total amount of requests will be capped to 100 per day.

The amount of incoming requests from each address will be capped to 10 per day.

```php
use Franzip\Throttler\Throttler;

// Setting things up
// Max 100 total requests per day
// Max 10 requests from each tracked address per day
$throttler = new Throttler('requests', 100, 'hrs', 24,
                           10, array('AddressToTrack1',
                                     'AddressToTrack2',
                                     ...));

// Start tracking (timeframe starts now)
$throttler->start();

if ($throttler->updateComponent('AddressToTrack1')) {
    // handle success
} else {
    // handle failure
}

```

## isActive()

Return whether tracking is turned on.

```php
use Franzip\Throttler\Throttler;

$throttler = new Throttler('requests', 100, 'hrs');

// false
$throttler->isActive();

// false, there's nothing to track yet.
$throttler->start();

// false
$throttler->isActive();

$throttler->addComponents(array('foo', 'bar'));

// true, we have something to track
$throttler->start();

// true
$throttler->isActive();

// reset the instance (this will also stop tracking)
$throttler->reset();

// false
$throttler->isActive();

```

## reset()

You can revert a `Throttler` object status to when it was instantiated.
Just use the `reset()` method at anytime.

```php
use Franzip\Throttler\Throttler;

$throttler = new Throttler('requests', 100, 'hrs');
// Change time cap to 2 hours
$throttler->setMetricFactor(2);
// Change time cap to 2 minutes
$throttler->setMetric('min');
// Change global limit to 50
$throttler->setGlobalThreshold(50);

...

$throttler->reset();
// reverted to 100
$throttler->getGlobalThreshold();
// reverted to 'hrs'
$throttler->getMetric();

```

## TODOs

- [x] A decent exceptions system.
- [x] Move validation to external class.
- [x] Allow bulk components adding.
- [ ] Refactoring messy tests.
- [ ] Refactoring update-checking methods.

## License
[MIT](http://opensource.org/licenses/MIT/ "MIT") Public License.

