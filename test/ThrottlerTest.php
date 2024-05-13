<?php

namespace Franzip\Throttler\Test;
use Franzip\Throttler\Throttler as Throttler;
use \PHPUnit\Framework\TestCase as TestCase;

final class ThrottlerTest extends TestCase
{
  protected $args;

  protected function setUp(): void
  {
    $withDefaults    = array('test', 2, 'min');
    $withComponents  = array('test', 11, 'MIn', 2, 10, array('foo', 'bar'));
    $withComponents1 = array('foo', 11, 'hrs', 1, 5, array('foo', 'bar'));
    $withComponents2 = array('foo', 6, 'hrs', 1, 1, array('foo', 'bar',
                                                          'foobar', 'foobaz',
                                                          'barfoo'));
    $this->args      = array('withDefault'     => $withDefaults,
                             'withComponents'  => $withComponents,
                             'withComponents1' => $withComponents1,
                             'withComponents2' => $withComponents2);
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
    $this->assertFalse($withDefaults->updateComponent('bar', 'baz'));
    $this->assertFalse($withDefaults->updateComponent('bar', 0));
    $this->assertTrue($withDefaults->updateComponent('foo',
                      $withDefaults->getGlobalThreshold()));
    $this->assertFalse($withDefaults->updateComponent('foo'));
    $this->assertFalse($withDefaults->updateComponent('bar'));
    $this->assertEquals($withDefaults->getComponentCounter('foo'), 2);
    $this->assertEquals($withDefaults->getCounter(), 2);
    $this->assertTrue($withDefaults->stop());
    $this->assertTrue($withDefaults->addComponents('foobar'));
    $this->assertTrue($withDefaults->setGlobalThreshold(10));
    $this->assertTrue($withDefaults->start());
    $this->assertTrue($withDefaults->updateComponent('foobar',
                      $withDefaults->getGlobalThreshold()));
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
    $this->assertTrue($withComponents->updateComponent('foo',
                      $withComponents->getComponentThreshold()));
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
    $this->assertTrue($withComponents->updateComponent('foobar',
                      $withComponents->getComponentThreshold()));
    $this->assertTrue($withComponents->updateComponent('foo',
                      $withComponents->getComponentThreshold()));
    $this->assertTrue($withComponents->updateComponent('bar',
                      $withComponents->getComponentThreshold()));
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
    $this->assertTrue($withComponents->updateComponent('foo',
                      $withComponents->getComponentThreshold()));
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

    $withComponents1 = new Throttler($this->args['withComponents1'][0],
                                     $this->args['withComponents1'][1],
                                     $this->args['withComponents1'][2],
                                     $this->args['withComponents1'][3],
                                     $this->args['withComponents1'][4],
                                     $this->args['withComponents1'][5]);
    $this->assertFalse($withComponents1->stop());
    $this->assertTrue($withComponents1->start());
    $this->assertFalse($withComponents1->updateComponent('baz'));
    $this->assertTrue($withComponents1->updateComponent('foo',
                      $withComponents1->getComponentThreshold()));
    $this->assertTrue($withComponents1->updateComponent('bar',
                      $withComponents1->getComponentThreshold()));
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

    $withComponents2 = new Throttler($this->args['withComponents2'][0],
                                     $this->args['withComponents2'][1],
                                     $this->args['withComponents2'][2],
                                     $this->args['withComponents2'][3],
                                     $this->args['withComponents2'][4],
                                     $this->args['withComponents2'][5]);
    $this->assertFalse($withComponents2->stop());
    $this->assertTrue($withComponents2->start());
    $this->assertTrue($withComponents2->updateComponent('foo',
                      $withComponents2->getComponentThreshold()));
    $this->assertTrue($withComponents2->updateComponent('bar',
                      $withComponents2->getComponentThreshold()));
    $this->assertTrue($withComponents2->updateComponent('foobar',
                      $withComponents2->getComponentThreshold()));
    $this->assertTrue($withComponents2->updateComponent('foobaz',
                      $withComponents2->getComponentThreshold()));
    $this->assertTrue($withComponents2->updateComponent('barfoo',
                      $withComponents2->getComponentThreshold()));
    $this->assertFalse($withComponents2->updateComponent('foo',
                      $withComponents2->getComponentThreshold()));
    $this->assertFalse($withComponents2->updateComponent('bar',
                      $withComponents2->getComponentThreshold()));
    $this->assertFalse($withComponents2->updateComponent('foobar',
                      $withComponents2->getComponentThreshold()));
    $this->assertFalse($withComponents2->updateComponent('foobaz',
                      $withComponents2->getComponentThreshold()));
    $this->assertFalse($withComponents2->updateComponent('barfoo',
                      $withComponents2->getComponentThreshold()));
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
    $this->assertTrue($withDefaults->updateComponent('foo',
                      $withDefaults->getGlobalThreshold()));
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
