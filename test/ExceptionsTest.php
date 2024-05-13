<?php

use Franzip\Throttler\Throttler as Throttler;
use \PHPUnit\Framework\TestCase as TestCase;

final class ExceptionsTest extends TestCase
{
  public function testVoidName()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$name: please supply a valid non-empty string.");
    $voidName = new Throttler('', 1, 'hrs');
  }

  public function testNullName()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$name: please supply a valid non-empty string.");
    $nullName = new Throttler(null, 1, 'hrs');
  }

  public function testInvalidName()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$name: please supply a valid non-empty string.");
    $invalidName = new Throttler(1, 1, 'hrs');
  }

  public function testInvalidGlobalThreshold()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$globalThreshold: please supply a positive integer.");
    $invalidThreshold = new Throttler('test', 'foo', 'hrs');
  }

  public function testInvalidGlobalThreshold1()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$globalThreshold: please supply a positive integer.");
    $invalidThreshold = new Throttler('test', -3, 'hrs');
  }

  public function testInvalidMetric()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$metric. Valid choices are \"sec\", \"min\", \"hrs\".");
    $invalidMetric = new Throttler('test', 2, 'foo');
  }

  public function testInvalidMetric1()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$metric. Valid choices are \"sec\", \"min\", \"hrs\".");
    $invalidMetric = new Throttler('test', 2, 2);
  }

  public function testinvalidMetricTimes()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$metricFactor: please supply a positive integer.");
    $invalidMetricTimes = new Throttler('test', 2, 'hrs', 0);
  }

  public function testinvalidMetricTimes1()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$metricFactor: please supply a positive integer.");
    $invalidMetricTimes = new Throttler('test', 2, 'hrs', 'bar');
  }

  public function testInvalidComponentThreshold()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$componentThreshold: please supply a positive integer or null");
    $invalidComponentThreshold = new Throttler('test', 2, 'hrs', 1,
                                               $componentThreshold = 'foo');
  }

  public function testInvalidComponentThreshold1()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$componentThreshold: please supply a positive integer or null");
    $invalidComponentThreshold = new Throttler('test', 2, 'hrs', 1,
                                               $componentThreshold = -1);
  }

  public function testInvalidComponentThreshold2()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$componentThreshold: \$componentThreshold must be lower than \$globalThreshold.");
    $invalidComponentThreshold = new Throttler('test', 10, 'hrs', 1, 25);
  }

  public function testInvalidComponents()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$components: \$components must be an array.");
    $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = 'foo');
  }

  public function testInvalidComponents1()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$components: \$components must be an array.");
    $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = 1);
  }

  public function testInvalidComponents2()
  {
    $this->expectException(\Franzip\Throttler\Exceptions\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid Throttler \$components: \$components entries must be non empty strings.");
    $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = array(1, 2, ''));
  }
}
