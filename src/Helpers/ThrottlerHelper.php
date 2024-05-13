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

namespace Franzip\Throttler\Helpers;

/**
 * Namespace for various Throttler helper methods.
 * @package Throttler
 */
class ThrottlerHelper
{
  /**
   * Validate Throttler constructor args.
   * @param  string $name
   * @param  int    $globalThreshold
   * @param  string $metric
   * @param  int    $metricFactor
   * @param  int    $componentThreshold
   * @param  array  $components
   */
  public static function validateConstructorArgs($name, $globalThreshold, $metric,
                                                 $metricFactor, $componentThreshold,
                                                 $components, $validMetrics)
  {
    if (!ThrottlerHelper::class::validateName($name))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $name: please supply a valid non-empty string.');

    if (!ThrottlerHelper::class::validateGlobalThreshold($globalThreshold))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $globalThreshold: please supply a positive integer.');

    if (!ThrottlerHelper::class::validateMetric($metric, $validMetrics))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $metric. Valid choices are "sec", "min", "hrs".');

    if (!ThrottlerHelper::class::validateMetricFactor($metricFactor))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $metricFactor: please supply a positive integer.');

    if (!ThrottlerHelper::class::validateComponentThreshold($componentThreshold))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $componentThreshold: please supply a positive integer or null.');

    if (!ThrottlerHelper::class::compareThresholds($componentThreshold, $globalThreshold))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $componentThreshold: $componentThreshold must be lower than $globalThreshold.');

    if (!ThrottlerHelper::class::validateComponents($components))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $components: $components must be an array.');

    if (!ThrottlerHelper::class::validateComponentsName($components))
      throw new \Franzip\Throttler\Exceptions\InvalidArgumentException('Invalid Throttler $components: $components entries must be non empty strings.');
    }

  /**
   * Validate Throttler name.
   * @param  string $name
   * @return bool
   */
  public static function validateName($name)
  {
    return is_string($name) && !empty($name);
  }

  /**
   * Validate Throttler global threshold.
   * @param  int $globalThreshold
   * @return bool
   */
  public static function validateGlobalThreshold($globalThreshold)
  {
    return is_int($globalThreshold) && $globalThreshold > 0;
  }

  /**
   * Validate Throttler metric against an array of supported metrics.
   * @param  string $metric
   * @param  array  $validMetrics
   * @return bool
   */
  public static function validateMetric($metric, $validMetrics)
  {
    return array_key_exists(strtolower($metric), $validMetrics);
  }

  /**
   * Validate Throttler metric factor.
   * @param  int $metricFactor
   * @return bool
   */
  public static function validateMetricFactor($metricFactor)
  {
    return is_int($metricFactor) && $metricFactor > 0;
  }

  /**
   * Validate Throttler components threshold.
   * @param  int|null $componentThreshold
   * @return bool
   */
  public static function validateComponentThreshold($componentThreshold)
  {
    return ($componentThreshold == null) || (is_int($componentThreshold) && $componentThreshold > 0);
  }

  /**
   * Ensure Throttler per-component threshold is lower than the global threshold.
   * @param  int $componentThreshold
   * @param  int $globalThreshold
   * @return bool
   */
  public static function compareThresholds($componentThreshold, $globalThreshold)
  {
    return $componentThreshold < $globalThreshold;
  }

  /**
   * Validate Throttler components array.
   * @param  array $components
   * @return bool
   */
  public static function validateComponents($components)
  {
    return is_array($components);
  }

  /**
   * Check that all components entries are valid.
   * @param  array $components
   * @return bool
   */
  public static function validateComponentsName($components)
  {
    return !in_array(false, array_map(self::class . '::validateName', $components));
  }

  /**
   * Check that at least a component to track has been added.
   * @param  array $components
   * @return bool
   */
  public static function componentsAreSet($components)
  {
    return !empty($components);
  }

    private function __construct() {}
}
