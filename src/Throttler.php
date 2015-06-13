<?php

/**
 * Throttler -- Simple rate limiter and usage tracker component.
 * @version 0.2.1
 * @author Francesco Pezzella <franzpezzella@gmail.com>
 * @link https://github.com/franzip/throttler
 * @copyright Copyright 2015 Francesco Pezzella
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Throttler
 */

namespace Franzip\Throttler;
use Franzip\Throttler\Helpers\ThrottlerHelper;

/**
 * This class implements a dead simple usage/time tracker.
 * Usage is capped with a global threshold and, if supplied, with a component
 * based threshold. In the specified timeframe (1 minute, 30 seconds, etc),
 * you can perform at most M updates globally and N updates on each components,
 * where M > 0, N > 0, M > N.
 * Client code cannot modify any of the Throttler object attributes if the object
 * is active (i.e. if tracking is turned on): it will need to explicitly stop
 * tracking before applying any modifications.
 * If tracking has been turned off, the updateComponent() method will always
 * return true to the client code and the Throttler states won't be touched.
 * If a threshold hit (global or per-component) is reached, the updateComponent()
 * method will always return false.
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
  // array to map components to their counters
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
   * at least one component must be added through addComponents() in order to
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
    // initialize components array properly
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
    if (ThrottlerHelper::componentsAreSet($this->components)
        && !$this->isActive())
    {
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
   * If the timeframe is expired, the update is to be allowed and the instance
   * will simply be refreshed and a new time window will be set accordingly.
   * If tracking has been stopped, the method will simply return true without
   * updating the instance counters.
   * Otherwise, it will be checked whether the update for a given $hits is to
   * be allowed and the instance counters will be updated accordingly.
   * @param  string $component
   * @param  int    $hits
   * @return bool
   */
  public function updateComponent($component, $hits = 1)
  {
    if (!$this->inComponents($component) || !is_int($hits) || $hits < 1)
      return false;
    if (!$this->isActive())
      return true;
    if ($this->timeExpired()) {
      $this->refreshInstance();
      return true;
    }
    if ($this->allowedUpdate($component, $hits)) {
      $this->increaseGlobalCounter($hits);
      $this->increaseComponentCounter($component, $hits);
      return true;
    }
    return false;
  }

  /**
   * Restart all counters and state variables.
   */
  public function refreshInstance()
  {
    $this->counter    = 0;
    $this->startedAt  = microtime(true);
    $this->expiresAt  = $this->computeExpiration();
    $this->setUpComponents($this->components);
  }

  /**
   * Add entries to the components array.
   * Allow single add (with a string arg) and bulk adding (with an array arg).
   * @param  string|array $components
   * @return bool
   */
  public function addComponents($components)
  {
    // if a string was supplied, check for valid component name
    if (ThrottlerHelper::validateName($components)) {
      $components = array($components);
    }
    // ensure elements to add are not already in components
    if (is_array($components) && !$this->allInComponents($components) &&
        ThrottlerHelper::validateComponentsName($components))
    {
      for ($i = 0; $i < count($components); $i++) {
        $this->addComponent($components[$i]);
      }
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
    if (ThrottlerHelper::validateGlobalThreshold($threshold)
        && ThrottlerHelper::compareThresholds($this->getComponentThreshold(), $threshold)
        && !$this->isActive())
    {
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
        && !$this->isActive())
    {
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
    if (ThrottlerHelper::validateMetricFactor($times)
        && !$this->isActive())
    {
      $this->metricFactor = $times;
      return true;
    }
    return false;
  }

  /**
   * Component threshold getter.
   * @return int|null
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
    if (ThrottlerHelper::compareThresholds($threshold, $this->getGlobalThreshold())
        && ThrottlerHelper::validateComponentThreshold($threshold)
        && !$this->isActive())
    {
      $this->componentThreshold = $threshold;
      return true;
    }
    return false;
  }

  /**
   * Components getter.
   * @return array
   */
  public function getComponents()
  {
    return $this->components;
  }

  /**
   * Components setter. It will overwrite $components array completely.
   * @param  array $components
   * @return bool
   */
  public function setComponents($components)
  {
    if (ThrottlerHelper::validateComponents($components)
        && !$this->isActive())
    {
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
   * Get the counter for a given component.
   * @param  string $component
   * @return int
   */
  public function getComponentCounter($component)
  {
    return $this->components[$component];
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
   * Add a component to the components array.
   * @param string $component
   */
  private function addComponent($component)
  {
    $this->components[$component] = 0;
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
   * Check that all components in supplied array are being tracked.
   * @param  array $components
   * @return bool
   */
  private function allInComponents($components)
  {
    return !in_array(false, array_map(array($this, "inComponents"), $components));
  }

  /**
   * Increase the global counter.
   * @param  int $hits
   */
  private function increaseGlobalCounter($hits)
  {
    $this->counter += $hits;
  }

  /**
   * Increase the counter for a given component.
   * @param  string $component
   * @param  int    $hits
   */
  private function increaseComponentCounter($component, $hits)
  {
    $this->components[$component] += $hits;
  }

  /**
   * Turn tracking off.
   */
  private function turnOff()
  {
    $this->active = false;
  }

  /**
   * Turn tracking on.
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
  private function allowedUpdate($component, $hits)
  {
    $globalCheck    = ($this->getCounter() + $hits) <= $this->getGlobalThreshold();
    $componentCheck = true;
    if (null !== $this->getComponentThreshold()) {
      $componentCheck = ($this->getComponentCounter($component) + $hits) <= $this->getComponentThreshold();
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
   * Set up the components counter array.
   * This is a bit hacky since $components can be an empty array, a sequential
   * array (on instantiation) and an associative one (when the object has been
   * already started).
   * @param array $components
   */
  private function setUpComponents($components)
  {
    if (empty($components)) {
      $this->components = $components;
    // sequential
    } elseif (array_keys($components) === range(0, count($components) - 1)) {
      for ($i = 0; $i < count($components); $i++) {
        $this->components[$components[$i]] = 0;
      }
    // associative
    } else {
      foreach ($components as $component => $value) {
        $this->components[$component] = 0;
      }
    }
  }
}
