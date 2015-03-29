<?php

/**
 * Throttler -- Simple rate limiter and usage tracker component.
 * @version 0.1.1
 * @author Francesco Pezzella <franzpezzella@gmail.com>
 * @link https://github.com/franzip/throttler
 * @copyright Copyright 2015 Francesco Pezzella
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Throttler
 */

namespace Franzip\Throttler;
use Franzip\Throttler\Helpers\ThrottlerHelper;

/**
 * TOMODIFY
 * This class implements a dead simple usage/time tracker.
 * Usage is capped with a global threshold and, if supplied, with a component based
 * threshold. In the specified timeframe (1 minute, 30 seconds, etc),
 * you can perform at most M updates globally and N updates on each components,
 * where M > 0, N > 0, M > N.
 * Client code cannot modify any of the Throttler object attributes if the object
 * is active (if tracking is on): it will need to explicitly stop tracking before
 * applying any modifications.
 * If tracking is turned off, the updateComponent() method will always return true
 * and the object state will be left untouch.
 * If a threshold is reached, the updateComponent() method will always return false.
 * On each call, updateComponent() checks if the object timespan is elapsed: if
 * it does, a new timespan will be computed and the method will return true.
 * @package Throttler
 */
class Throttler
{
    // map array keys to timespans expressed in seconds.
    private static $timeFactor = array('sec' => 1,
                                       'min' => 60,
                                       'hrs' => 3600);
    // name the stuff you want to track usage of
    private $name;
    // time scale to use (seconds, minutes, hours)
    private $metric;
    // time factor to use (i.e. 2 seconds, 10 minutes, etc). Defaults to 1
    private $metricFactor;
    // global hit limit
    private $globalThreshold;
    // per component hit limit
    private $componentThreshold;
    // maps components to their counters
    private $components;
    // retain original object configs
    private $origStatus;
    // global hit counter
    private $counter;
    // timestamp to keep track of when the object was activated
    private $startedAt;
    // computed expiration timestamp based on the chosen time frame ($metric * $metricFactor)
    private $expiresAt;
    // flag tracking status
    private $active;

    /**
     * Instantiate a Throttler object.
     * Tracking will NOT be active immediately after the instantiation.
     * If no components to track were provided through the canonical constructor,
     * at least one component must be added through addComponent() in order to
     * turn tracking on.
     * A specific time window can be obtained by setting both $metric and
     * $metricFactor i.e. ('hrs', 24), ('min', 30), ('sec', 30), etc.
     * All state variables will be reset after the specified time window expires.
     *
     * @param string  $name
     * @param int     $globalThreshold
     * @param string  $metric
     * @param int     $metricFactor
     * @param int     $componentThreshold
     * @param array   $components
     */
    public function __construct($name, $globalThreshold, $metric, $metricFactor = 1,
                                $componentThreshold = null, $components = array())
    {
        // allow case insensitivity
        $metric = strtolower($metric);
        // perform basic validation
        ThrottlerHelper::validateConstructorArgs($name, $globalThreshold, $metric,
                                                 $metricFactor, $componentThreshold,
                                                 $components, self::$timeFactor);
        // instance vars
        $this->name               = $name;
        $this->globalThreshold    = $globalThreshold;
        $this->metric             = $metric;
        $this->metricFactor       = $metricFactor;
        $this->componentThreshold = $componentThreshold;
        $this->setupComponents($components);
        // allow resetting the object to its starting state
        $this->origStatus = array("name"               => $name,
                                  "metric"             => $metric,
                                  "globalThreshold"    => $globalThreshold,
                                  "componentThreshold" => $componentThreshold,
                                  "metricFactor"       => $metricFactor,
                                  "components"         => $components);
        // instance statuses
        $this->counter    = null;
        $this->startedAt  = null;
        $this->expiresAt  = null;
        $this->active     = false;
    }

    /**
     * Start time/usage tracking for the current instance.
     * Fails if the object is already active.
     * @return bool
     */
    public function start()
    {
        // prevent starting if there is nothing to track
        if ($this->componentsAreSet() && !$this->isActive()) {
            $this->turnOn();
            $this->refreshInstance();
            return true;
        }
        return false;
    }

    /**
     * Stop time/usage tracking keeping other states intact.
     * Fails if the object is not active.
     * @return bool
     */
    public function stop()
    {
        if ($this->isActive()) {
            $this->turnOff();
            return true;
        }
        return false;
    }

    /**
     * Allow resuming after a stop without refreshing the whole instance.
     * All the object status will be retained.
     * @return bool
     */
    public function resume()
    {
        if (!$this->isActive()) {
            $this->turnOn();
            return true;
        }
        return false;
    }

    /**
     * Stop time/usage tracking and rollback to the original object status.
     * Allow usage at any given time.
     */
    public function reset()
    {
        $this->turnOff();
        $this->counter            = null;
        $this->startedAt          = null;
        $this->expiresAt          = null;
        $this->name               = $this->origStatus['name'];
        $this->metric             = $this->origStatus['metric'];
        $this->globalThreshold    = $this->origStatus['globalThreshold'];
        $this->componentThreshold = $this->origStatus['componentThreshold'];
        $this->metricFactor       = $this->origStatus['metricFactor'];
        $this->components         = $this->origStatus['components'];
    }

    /**
     * This is the main class method.
     * If the timeframe is expired, the object will simply be refreshed and a
     * new time window will be set accordingly.
     * If tracking has been stopped, the method will simply return true.
     * Otherwise, check if the update is allowed.
     * @param  string $component
     * @return bool
     */
    public function updateComponent($component)
    {
        if (!$this->inComponents($component))
            return false;
        if (!$this->isActive())
            return true;
        if ($this->timeExpired()) {
            $this->refreshInstance();
            return true;
        }
        if ($this->allowedUpdate($component)) {
            $this->increaseGlobalCounter();
            $this->increaseComponentCounter($component);
            return true;
        }
        return false;
    }

    /**
     * Add a component to the components array.
     * @param   string $component
     * @return  bool
     */
    public function addComponent($component)
    {
        if (ThrottlerHelper::validateName($component) && !$this->isActive()
            && !$this->inComponents($component)) {
            $this->components[$component] = 0;
            return true;
        }
        return false;
    }

    /**
     * Check if the instance is active.
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Name getter.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Name setter.
     * @param  string $name
     * @return bool
     */
    public function setName($name)
    {
        if (ThrottlerHelper::validateName($name) && !$this->isActive()) {
            $this->name = $name;
            return true;
        }
        return false;
    }

    /**
     * Global threshold getter.
     * @return int
     */
    public function getGlobalThreshold()
    {
        return $this->globalThreshold;
    }

    /**
     * Global threshold setter. The argument $threshold must be greater than the
     * component threshold.
     * @param  int $threshold
     * @return bool
     */
    public function setGlobalThreshold($threshold)
    {
        if (ThrottlerHelper::validateGlobalThreshold($threshold) && !$this->isActive()
            && $threshold > $this->getComponentThreshold()) {
            $this->globalThreshold = $threshold;
            return true;
        }
        return false;
    }

    /**
     * Metric getter.
     * @return string
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * Metric setter.
     * @param  string
     * @return bool
     */
    public function setMetric($metric)
    {
        if (ThrottlerHelper::validateMetric($metric, self::$timeFactor)
            && !$this->isActive()) {
            $this->metric = strtolower($metric);
            return true;
        }
        return false;
    }

    /**
     * Metric unit getter.
     * @return int
     */
    public function getMetricFactor()
    {
        return $this->metricFactor;
    }

    /**
     * Metric unit setter.
     * @param  int $times
     * @return bool
     */
    public function setMetricFactor($times)
    {
        if (ThrottlerHelper::validateMetricFactor($times) && !$this->isActive()) {
            $this->metricFactor = $times;
            return true;
        }
        return false;
    }

    /**
     * Component threshold getter.
     * @return int
     */
    public function getComponentThreshold()
    {
        return $this->componentThreshold;
    }

    /**
     * Component threshold setter. The $threshold must be smaller than the global
     * threshold.
     * @param   int $threshold
     * @return  bool
     */
    public function setComponentThreshold($threshold)
    {
        if ($threshold < $this->getGlobalThreshold()
            && ThrottlerHelper::validateComponentThreshold($threshold)
            && !$this->isActive()) {
            $this->componentThreshold = $threshold;
            return true;
        }
        return false;
    }

    /**
     * Components getter.
     * @return array | null
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * Components setter.
     * @param  array $components
     * @return bool
     */
    public function setComponents($components)
    {
        if (ThrottlerHelper::validateComponents($components) && !$this->isActive()) {
            $this->components = $components;
            return true;
        }
        return false;
    }

    /**
     * Counter getter.
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Time getter.
     * @return float
     */
    public function getTimeStart()
    {
        return $this->startedAt;
    }

    /**
     * Time expiration getter.
     * @return float.
     */
    public function getTimeExpiration()
    {
        return $this->expiresAt;
    }

    /**
     * Check if the computed timeframe is expired.
     * @return bool
     */
    public function timeExpired()
    {
        return microtime(true) > $this->getTimeExpiration();
    }

    /**
     * Get the counter for a given component.
     * @param  string $component
     * @return int
     */
    public function getComponentCounter($component)
    {
        return $this->components[$component];
    }

    /**
     * Check if a given component is being tracked.
     * @param  string $component
     * @return bool
     */
    private function inComponents($component)
    {
        return array_key_exists($component, $this->getComponents());
    }

    /**
     * Increase the global counter
     */
    private function increaseGlobalCounter()
    {
        $this->counter++;
    }

    /**
     * Increase the counter for a given component.
     * @param  string $component
     */
    private function increaseComponentCounter($component)
    {
        $this->components[$component]++;
    }

    /**
     * Turn tracking off
     */
    private function turnOff()
    {
        $this->active = false;
    }

    /**
     * Turn tracking on
     */
    private function turnOn()
    {
        $this->active = true;
    }

    /**
     * Check the tracked components to see if an update is allowed.
     * @param  string $component
     * @return bool
     */
    private function allowedUpdate($component)
    {
        $globalCheck    = $this->getCounter() < $this->getGlobalThreshold();
        $componentCheck = true;
        if (null !== $this->getComponentThreshold()) {
            $componentCheck = $this->getComponentCounter($component) < $this->getComponentThreshold();
        }
        return $globalCheck && $componentCheck;
    }

    /**
     * Compute expiration date.
     * @return float
     */
    private function computeExpiration()
    {
        $delta = self::$timeFactor[$this->getMetric()] * $this->getMetricFactor();
        return $this->getTimeStart() + $delta;
    }

    /**
     * Restart all counters and state variables when the timeframe is expired.
     */
    private function refreshInstance()
    {
        $this->counter    = 0;
        $this->startedAt  = microtime(true);
        $this->expiresAt  = $this->computeExpiration();
        $this->setUpComponents($this->components);
    }

    /**
     * Set up the components counter array.
     * This is a bit hacky since $components can be an empty array or a sequential
     * array (on instantiation) and an associative one (when the object has been
     * already started).
     * @param array $components
     */
    private function setUpComponents($components)
    {
        if (empty($components)) {
            $this->components = $components;
        } elseif (array_keys($components) === range(0, count($components) - 1)) {
            for ($i = 0; $i < count($components); $i++) {
                $this->components[$components[$i]] = 0;
            }
        } else {
            foreach ($components as $component => $value) {
                $this->components[$component] = 0;
            }
        }
    }

    /**
     * Check if any components were added.
     * @return bool
     */
    private function componentsAreSet()
    {
        return ThrottlerHelper::validateComponents($this->components)
               && !empty($this->components);
    }
}
