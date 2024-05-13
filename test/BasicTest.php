<?php

use Franzip\Throttler\Throttler as Throttler;
use \PHPUnit\Framework\TestCase as TestCase;

final class BasicTest extends TestCase
{
  protected $args;

  protected function setUp(): void
  {
    $withDefaults   = array('test', 2, 'min');
    $withComponents = array('test', 11, 'MIn', 2, 10, array('foo', 'bar'));
    $this->args     = array('withDefault'    => $withDefaults,
                            'withComponents' => $withComponents);
  }

  public function testSettersAndGetters()
  {
    $withDefaults = new Throttler($this->args['withDefault'][0],
                                  $this->args['withDefault'][1],
                                  $this->args['withDefault'][2]);
    $this->assertInstanceOf('\Franzip\Throttler\Throttler', $withDefaults);
    $this->assertNull($withDefaults->getCounter());
    $this->assertNull($withDefaults->getTimeStart());
    $this->assertEquals($withDefaults->getName(), 'test');
    $this->assertEquals($withDefaults->getGlobalThreshold(), 2);
    $this->assertEquals($withDefaults->getMetric(), 'min');
    $this->assertEquals($withDefaults->getMetricFactor(), 1);
    $this->assertNull($withDefaults->getComponentThreshold());
    $this->assertEquals($withDefaults->getComponents(), array());

    $this->assertFalse($withDefaults->setName(2));
    $this->assertTrue($withDefaults->setName('foo'));
    $this->assertEquals($withDefaults->getName(), 'foo');
    $this->assertFalse($withDefaults->setGlobalThreshold(-2));
    $this->assertTrue($withDefaults->setGlobalThreshold(25));
    $this->assertEquals($withDefaults->getGlobalThreshold(), 25);
    $this->assertFalse($withDefaults->setMetric('bar'));
    $this->assertTrue($withDefaults->setMetric('sec'));
    $this->assertEquals($withDefaults->getMetric(), 'sec');
    $this->assertFalse($withDefaults->setMetricFactor('foo'));
    $this->assertFalse($withDefaults->setMetricFactor(-2));
    $this->assertTrue($withDefaults->setMetricFactor(2));
    $this->assertEquals($withDefaults->getMetricFactor(), 2);
    $this->assertFalse($withDefaults->setComponentThreshold('foo'));
    $this->assertFalse($withDefaults->setComponentThreshold(222));
    $this->assertFalse($withDefaults->setComponentThreshold(-2));
    $this->assertTrue($withDefaults->setComponentThreshold(12));
    $this->assertEquals($withDefaults->getComponentThreshold(), 12);
    $this->assertFalse($withDefaults->setComponents('foo'));
    $this->assertTrue($withDefaults->setComponents(array('foo')));
    $this->assertEquals($withDefaults->getComponents(), array('foo'));

    $this->assertTrue($withDefaults->start());
    $this->assertFalse($withDefaults->start());
    $this->assertFalse($withDefaults->setName('bar'));
    $this->assertFalse($withDefaults->setGlobalThreshold(30));
    $this->assertFalse($withDefaults->setMetric('hrs'));
    $this->assertFalse($withDefaults->setMetricFactor(15));
    $this->assertFalse($withDefaults->setComponentThreshold(3));
    $this->assertFalse($withDefaults->setComponents(array('foo')));

    $withComponents = new Throttler($this->args['withComponents'][0],
                                    $this->args['withComponents'][1],
                                    $this->args['withComponents'][2],
                                    $this->args['withComponents'][3],
                                    $this->args['withComponents'][4],
                                    $this->args['withComponents'][5]);
    $this->assertInstanceOf('\Franzip\Throttler\Throttler', $withComponents);
    $this->assertNull($withComponents->getCounter());
    $this->assertNull($withComponents->getTimeStart());
    $this->assertEquals($withComponents->getName(), 'test');
    $this->assertEquals($withComponents->getGlobalThreshold(), 11);
    $this->assertEquals($withComponents->getMetric(), 'min');
    $this->assertEquals($withComponents->getMetricFactor(), 2);
    $this->assertEquals($withComponents->getComponentThreshold(), 10);
    $this->assertEquals($withComponents->getComponents(), array('foo' => 0, 'bar' => 0));

    $this->assertFalse($withComponents->setName(2));
    $this->assertTrue($withComponents->setName('foo'));
    $this->assertEquals($withComponents->getName(), 'foo');
    $this->assertFalse($withComponents->setGlobalThreshold(-2));
    $this->assertTrue($withComponents->setGlobalThreshold(25));
    $this->assertEquals($withComponents->getGlobalThreshold(), 25);
    $this->assertFalse($withComponents->setMetric('bar'));
    $this->assertTrue($withComponents->setMetric('sec'));
    $this->assertEquals($withComponents->getMetric(), 'sec');
    $this->assertFalse($withComponents->setMetricFactor('foo'));
    $this->assertFalse($withComponents->setMetricFactor(-2));
    $this->assertTrue($withComponents->setMetricFactor(2));
    $this->assertEquals($withComponents->getMetricFactor(), 2);
    $this->assertFalse($withComponents->setComponentThreshold('foo'));
    $this->assertFalse($withComponents->setComponentThreshold(-2));
    $this->assertFalse($withDefaults->setComponentThreshold(222));
    $this->assertTrue($withComponents->setComponentThreshold(12));
    $this->assertEquals($withComponents->getComponentThreshold(), 12);
    $this->assertFalse($withComponents->setComponents('foo'));
    $this->assertTrue($withComponents->setComponents(array('foo')));
    $this->assertEquals($withComponents->getComponents(), array('foo'));

    $this->assertTrue($withComponents->start());
    $this->assertFalse($withComponents->start());
    $this->assertFalse($withComponents->setName('bar'));
    $this->assertFalse($withComponents->setGlobalThreshold(30));
    $this->assertFalse($withComponents->setMetric('hrs'));
    $this->assertFalse($withComponents->setMetricFactor(15));
    $this->assertFalse($withComponents->setComponentThreshold(3));
    $this->assertFalse($withComponents->setComponents(array('foo')));
  }

  public function testStateChanging()
  {
    $withDefaults = new Throttler($this->args['withDefault'][0],
                                  $this->args['withDefault'][1],
                                  $this->args['withDefault'][2]);
    $this->assertFalse($withDefaults->stop());
    $this->assertFalse($withDefaults->start());
    $this->assertFalse($withDefaults->addComponents(1));
    $this->assertTrue($withDefaults->addComponents('foo'));
    $this->assertFalse($withDefaults->addComponents('foo'));
    $this->assertTrue($withDefaults->start());
    $this->assertEquals($withDefaults->getTimeExpiration(),
                        $withDefaults->getTimeStart() + 60);
    $this->assertTrue($withDefaults->isActive());
    $this->assertEquals($withDefaults->getCounter(), 0);
    $this->assertCount(1, $withDefaults->getComponents());
    $this->assertEquals($withDefaults->getComponentCounter('foo'), 0);
    $this->assertTrue(is_float($withDefaults->getTimeStart()));
    $this->assertTrue($withDefaults->stop());
    $this->assertTrue($withDefaults->resume());
    $this->assertTrue($withDefaults->isActive());
    $this->assertTrue($withDefaults->stop());
    $this->assertFalse($withDefaults->stop());
    $this->assertTrue($withDefaults->setName('foo'));
    $this->assertTrue($withDefaults->setGlobalThreshold(50));
    $this->assertFalse($withDefaults->setMetric('foo'));
    $this->assertTrue($withDefaults->setMetric('sec'));
    $this->assertTrue($withDefaults->setMetricFactor(3));
    $this->assertTrue($withDefaults->setComponentThreshold(15));
    $this->assertFalse($withDefaults->setComponentThreshold(222));
    $this->assertTrue($withDefaults->setComponents(array('bar', 'foo')));
    $this->assertTrue($withDefaults->start());
    $this->assertEquals($withDefaults->getTimeExpiration(),
                        $withDefaults->getTimeStart() + 3);

    $withDefaults->reset();
    $this->assertFalse($withDefaults->isActive());
    $this->assertNull($withDefaults->getCounter());
    $this->assertNull($withDefaults->getTimeStart());
    $this->assertEquals($withDefaults->getName(), 'test');
    $this->assertEquals($withDefaults->getMetric(), 'min');
    $this->assertEquals($withDefaults->getGlobalThreshold(), 2);
    $this->assertNull($withDefaults->getComponentThreshold());
    $this->assertEquals($withDefaults->getMetricFactor(), 1);
    $this->assertEquals($withDefaults->getComponents(), array());

    $withComponents = new Throttler($this->args['withComponents'][0],
                                    $this->args['withComponents'][1],
                                    $this->args['withComponents'][2],
                                    $this->args['withComponents'][3],
                                    $this->args['withComponents'][4],
                                    $this->args['withComponents'][5]);
    $this->assertFalse($withComponents->stop());
    $this->assertTrue($withComponents->start());
    $this->assertEquals($withComponents->getTimeExpiration(),
                        $withComponents->getTimeStart() + 120);
    $this->assertFalse($withComponents->addComponents('foo'));
    $this->assertTrue($withComponents->isActive());
    $this->assertEquals($withComponents->getCounter(), 0);
    $this->assertCount(2, $withComponents->getComponents());
    $this->assertEquals($withComponents->getComponentCounter('foo'), 0);
    $this->assertEquals($withComponents->getComponentCounter('bar'), 0);
    $this->assertTrue(is_float($withComponents->getTimeStart()));
    $this->assertTrue($withComponents->stop());
    $this->assertTrue($withComponents->resume());
    $this->assertFalse($withComponents->resume());
    $this->assertTrue($withComponents->isActive());
    $this->assertTrue($withComponents->stop());
    $this->assertFalse($withComponents->stop());
    $this->assertTrue($withComponents->setName('foo'));
    $this->assertTrue($withComponents->setGlobalThreshold(50));
    $this->assertFalse($withComponents->setMetric('foo'));
    $this->assertTrue($withComponents->setMetric('SEC'));
    $this->assertTrue($withComponents->setMetricFactor(3));
    $this->assertTrue($withComponents->setComponentThreshold(15));
    $this->assertFalse($withDefaults->setComponentThreshold(222));
    $this->assertTrue($withComponents->setComponents(array('bar', 'foo')));
    $this->assertTrue($withComponents->start());
    $this->assertEquals($withComponents->getTimeExpiration(),
                        $withComponents->getTimeStart() + 3);

    $withComponents->reset();
    $this->assertFalse($withComponents->isActive());
    $this->assertNull($withComponents->getCounter());
    $this->assertNull($withComponents->getTimeStart());
    $this->assertEquals($withComponents->getName(), 'test');
    $this->assertEquals($withComponents->getMetric(), 'min');
    $this->assertEquals($withComponents->getGlobalThreshold(), 11);
    $this->assertEquals($withComponents->getComponentThreshold(), 10);
    $this->assertEquals($withComponents->getMetricFactor(), 2);
    $this->assertEquals($withComponents->getComponents(), array('foo', 'bar'));
  }
}
