<?php

namespace Franzip\Throttler\Test;
use Franzip\Throttler\Throttler as Throttler;
use \PHPUnit_Framework_TestCase as PHPUnit_Framework_TestCase;

class ExceptionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $name: please supply a valid non-empty string.
     */
    public function testVoidName()
    {
        $voidName = new Throttler('', 1, 'hrs');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $name: please supply a valid non-empty string.
     */
    public function testNullName()
    {
        $nullName = new Throttler(null, 1, 'hrs');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $name: please supply a valid non-empty string.
     */
    public function testInvalidName()
    {
        $invalidName = new Throttler(1, 1, 'hrs');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $globalThreshold: please supply a positive integer.
     */
    public function testInvalidGlobalThreshold()
    {
        $invalidThreshold = new Throttler('test', 'foo', 'hrs');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $globalThreshold: please supply a positive integer.
     */
    public function testInvalidGlobalThreshold1()
    {
        $invalidThreshold = new Throttler('test', -3, 'hrs');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $metric. Valid choices are "sec", "min", "hrs".
     */
    public function testInvalidMetric()
    {
        $invalidMetric = new Throttler('test', 2, 'foo');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $metric. Valid choices are "sec", "min", "hrs".
     */
    public function testInvalidMetric1()
    {
        $invalidMetric = new Throttler('test', 2, 2);
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $metricFactor: please supply a positive integer.
     */
    public function testinvalidMetricTimes()
    {
        $invalidMetricTimes = new Throttler('test', 2, 'hrs', 0);
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $metricFactor: please supply a positive integer.
     */
    public function testinvalidMetricTimes1()
    {
        $invalidMetricTimes = new Throttler('test', 2, 'hrs', 'bar');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $componentThreshold: please supply a positive integer or null.
     */
    public function testInvalidComponentThreshold()
    {
        $invalidComponentThreshold = new Throttler('test', 2, 'hrs', 1,
                                                   $componentThreshold = 'foo');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $componentThreshold: please supply a positive integer or null.
     */
    public function testInvalidComponentThreshold1()
    {
        $invalidComponentThreshold = new Throttler('test', 2, 'hrs', 1,
                                                   $componentThreshold = -1);
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $componentThreshold: $componentThreshold must be lower than $globalThreshold.
     */
    public function testInvalidComponentThreshold2()
    {
        $invalidComponentThreshold = new Throttler('test', 10, 'hrs', 1, 25);
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $components: $components must be an array.
     */
    public function testInvalidComponents()
    {
        $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = 'foo');
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $components: $components must be an array.
     */
    public function testInvalidComponents1()
    {
        $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = 1);
    }

    /**
     * @expectedException        \Franzip\Throttler\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Invalid Throttler $components: $components entries must be non empty strings.
     */
    public function testInvalidComponents2()
    {
        $invalidComponents = new Throttler('test', 2, 'hrs', 1, 1, $components = array(1, 2, ''));
    }
}

class ThrottleTest extends PHPUnit_Framework_TestCase
{
    protected $args;

    protected function setUp()
    {
        $withDefaults   = array('test', 2, 'min');
        $withComponents = array('test', 11, 'MIn', 2, 10, array('foo', 'bar'));
        $this->args = array('withDefault'    => $withDefaults,
                            'withComponents' => $withComponents);
    }

    public function testSettersAndGetters()
    {
        $withDefaults = new Throttler($this->args['withDefault'][0],
                                      $this->args['withDefault'][1],
                                      $this->args['withDefault'][2]);
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

    public function testGlobalThrottling()
    {
        $withDefaults = new Throttler($this->args['withDefault'][0],
                                      $this->args['withDefault'][1],
                                      $this->args['withDefault'][2]);
        $this->assertFalse($withDefaults->updateComponent('foo'));
        $this->assertFalse($withDefaults->start());
        $this->assertTrue($withDefaults->addComponents('foo'));
        $this->assertTrue($withDefaults->start());
        $this->assertFalse($withDefaults->updateComponent('bar'));
        for ($i = 0; $i < $withDefaults->getGlobalThreshold(); $i++)
        {
            $this->assertTrue($withDefaults->updateComponent('foo'));
        }
        $this->assertFalse($withDefaults->updateComponent('foo'));
        $this->assertFalse($withDefaults->updateComponent('bar'));
        $this->assertEquals($withDefaults->getComponentCounter('foo'), 2);
        $this->assertEquals($withDefaults->getCounter(), 2);
        $this->assertTrue($withDefaults->stop());
        $this->assertTrue($withDefaults->addComponents('foobar'));
        $this->assertTrue($withDefaults->setGlobalThreshold(10));
        $this->assertTrue($withDefaults->start());
        for ($i = 0; $i < $withDefaults->getGlobalThreshold(); $i++)
        {
            $this->assertTrue($withDefaults->updateComponent('foobar'));
        }
        $this->assertFalse($withDefaults->updateComponent('foobar'));
        $this->assertEquals($withDefaults->getCounter(), 10);
        $withDefaults->reset();
        $this->assertEquals($withDefaults->getGlobalThreshold(), 2);
        $this->assertEquals($withDefaults->getComponents(), array());
        $withComponents = new Throttler($this->args['withComponents'][0],
                                        $this->args['withComponents'][1],
                                        $this->args['withComponents'][2],
                                        $this->args['withComponents'][3],
                                        $this->args['withComponents'][4],
                                        $this->args['withComponents'][5]);
        $this->assertFalse($withComponents->updateComponent('baz'));
        $this->assertTrue($withComponents->start());
        for ($i = 0; $i < $withComponents->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents->updateComponent('foo'));
        }
        $this->assertTrue($withComponents->updateComponent('bar'));
        $this->assertFalse($withComponents->updateComponent('foo'));
        $this->assertFalse($withComponents->updateComponent('bar'));
        $this->assertEquals($withComponents->getCounter(), 11);
        $this->assertEquals($withComponents->getComponentCounter('foo'), 10);
        $this->assertEquals($withComponents->getComponentCounter('bar'), 1);
        $this->assertTrue($withComponents->stop());
        $this->assertTrue($withComponents->addComponents('foobar'));
        $this->assertTrue($withComponents->setGlobalThreshold(15));
        $this->assertTrue($withComponents->setComponentThreshold(5));
        $this->assertTrue($withComponents->start());
        for ($i = 0; $i < $withComponents->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents->updateComponent('foobar'));
        }
        for ($i = 0; $i < $withComponents->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents->updateComponent('foo'));
        }
        for ($i = 0; $i < $withComponents->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents->updateComponent('bar'));
        }
        $this->assertFalse($withComponents->updateComponent('foobar'));
        $this->assertFalse($withComponents->updateComponent('bar'));
        $withComponents->reset();
        $this->assertEquals($withComponents->getGlobalThreshold(), 11);
        $this->assertEquals($withComponents->getComponentThreshold(), 10);
        $this->assertEquals($withComponents->getComponents(), array('foo', 'bar'));
    }

    public function testComponentsThrottling()
    {
        $withComponents = new Throttler($this->args['withComponents'][0],
                                        $this->args['withComponents'][1],
                                        $this->args['withComponents'][2],
                                        $this->args['withComponents'][3],
                                        $this->args['withComponents'][4],
                                        $this->args['withComponents'][5]);
        $this->assertFalse($withComponents->stop());
        $this->assertTrue($withComponents->updateComponent('foo'));
        $this->assertFalse($withComponents->updateComponent('barz'));
        $this->assertTrue($withComponents->start());
        for ($i = 0; $i < $withComponents->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents->updateComponent('foo'));
        }
        $this->assertEquals($withComponents->getCounter(), 10);
        $this->assertEquals($withComponents->getComponentCounter('foo'), 10);
        $this->assertFalse($withComponents->updateComponent('foo'));
        $this->assertTrue($withComponents->updateComponent('bar'));
        $this->assertEquals($withComponents->getComponentCounter('bar'), 1);
        $this->assertEquals($withComponents->getCounter(), 11);
        $this->assertFalse($withComponents->updateComponent('foo'));
        $this->assertFalse($withComponents->updateComponent('bar'));
        $this->assertTrue($withComponents->stop());
        $this->assertTrue($withComponents->setComponentThreshold(1));
        $this->assertTrue($withComponents->setGlobalThreshold(2));
        $this->assertTrue($withComponents->addComponents('foobar'));
        $this->assertTrue($withComponents->start());
        $this->assertTrue($withComponents->updateComponent('foobar'));
        $this->assertFalse($withComponents->updateComponent('foobar'));
        $withComponents->reset();
        $this->assertEquals($withComponents->getComponents(), array('foo', 'bar'));
        $this->assertEquals($withComponents->getGlobalThreshold(), 11);
        $this->assertEquals($withComponents->getComponentThreshold(), 10);


        $withComponents1 = new Throttler('foo', 11, 'hrs', 1, 5, array('foo', 'bar'));
        $this->assertFalse($withComponents1->stop());
        $this->assertTrue($withComponents1->start());
        $this->assertFalse($withComponents1->updateComponent('baz'));
        for ($i = 0; $i < $withComponents1->getComponentThreshold(); $i++)
        {
            $this->assertTrue($withComponents1->updateComponent('foo'));
            $this->assertTrue($withComponents1->updateComponent('bar'));

        }
        $this->assertEquals($withComponents1->getComponentCounter('foo'), 5);
        $this->assertEquals($withComponents1->getComponentCounter('bar'), 5);
        $this->assertEquals($withComponents1->getCounter(), 10);
        $this->assertFalse($withComponents1->updateComponent('foo'));
        $this->assertFalse($withComponents1->updateComponent('bar'));
        $this->assertTrue($withComponents1->stop());
        $this->assertTrue($withComponents1->setComponentThreshold(1));
        $this->assertTrue($withComponents1->setGlobalThreshold(2));
        $this->assertTrue($withComponents1->addComponents('foobar'));
        $this->assertTrue($withComponents1->start());
        $this->assertTrue($withComponents1->updateComponent('foobar'));
        $this->assertFalse($withComponents1->updateComponent('foobar'));
        $withComponents1->reset();
        $this->assertEquals($withComponents1->getComponents(), array('foo', 'bar'));
        $this->assertEquals($withComponents1->getGlobalThreshold(), 11);
        $this->assertEquals($withComponents1->getComponentThreshold(), 5);
        $withComponents2 = new Throttler('foo', 6, 'hrs', 1, 1, array('foo',
                                                                      'bar',
                                                                      'foobar',
                                                                      'foobaz',
                                                                      'barfoo'));
        $this->assertFalse($withComponents2->stop());
        $this->assertTrue($withComponents2->start());
        for ($i = 0; $i < $withComponents2->getComponentThreshold(); $i++) {
            $this->assertTrue($withComponents2->updateComponent('foo'));
            $this->assertTrue($withComponents2->updateComponent('bar'));
            $this->assertTrue($withComponents2->updateComponent('foobar'));
            $this->assertTrue($withComponents2->updateComponent('foobaz'));
            $this->assertTrue($withComponents2->updateComponent('barfoo'));
        }
        for ($i = 0; $i < $withComponents2->getComponentThreshold(); $i++) {
            $this->assertFalse($withComponents2->updateComponent('foo'));
            $this->assertFalse($withComponents2->updateComponent('bar'));
            $this->assertFalse($withComponents2->updateComponent('foobar'));
            $this->assertFalse($withComponents2->updateComponent('foobaz'));
            $this->assertFalse($withComponents2->updateComponent('barfoo'));
        }
        $this->assertTrue($withComponents2->stop());
        $this->assertTrue($withComponents2->resume());
        $this->assertFalse($withComponents2->resume());
        $this->assertTrue($withComponents2->stop());
        $this->assertTrue($withComponents2->setComponentThreshold(1));
        $this->assertTrue($withComponents2->setGlobalThreshold(2));
        $this->assertTrue($withComponents2->addComponents('bazboo'));
        $this->assertTrue($withComponents2->start());
        $this->assertTrue($withComponents2->updateComponent('bazboo'));
        $this->assertFalse($withComponents2->updateComponent('bazboo'));
        $withComponents2->reset();
        $this->assertEquals($withComponents2->getComponents(), array('foo', 'bar',
                                                                     'foobar',
                                                                     'foobaz',
                                                                     'barfoo'));
        $this->assertEquals($withComponents2->getGlobalThreshold(), 6);
        $this->assertEquals($withComponents2->getComponentThreshold(), 1);
    }

    public function testTimeTracking()
    {
        $withDefaults = new Throttler($this->args['withDefault'][0],
                                      $this->args['withDefault'][1],
                                      $this->args['withDefault'][2]);
        $this->assertTrue($withDefaults->addComponents('foo'));
        $this->assertTrue($withDefaults->setMetric('sec'));
        $this->assertTrue($withDefaults->start());
        $this->assertEquals($withDefaults->getTimeExpiration(),
                            $withDefaults->getTimeStart() + 1);
        for ($i = 0; $i < $withDefaults->getGlobalThreshold(); $i++) {
            $this->assertTrue($withDefaults->updateComponent('foo'));
        }
        $this->assertFalse($withDefaults->updateComponent('foo'));
        $this->assertEquals($withDefaults->getCounter(),
                            $withDefaults->getGlobalThreshold());
        $this->assertEquals($withDefaults->getComponentCounter('foo'),
                            $withDefaults->getCounter());
        sleep(1);
        $this->assertTrue($withDefaults->updateComponent('foo'));
        $this->assertEquals($withDefaults->getCounter(), 0);
        $this->assertEquals($withDefaults->getComponentCounter('foo'), 0);
        $this->assertEquals($withDefaults->getTimeExpiration(),
                            $withDefaults->getTimeStart() + 1);
    }
}
