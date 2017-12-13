<?php
namespace JClaveau\CustomFilter\Rule;

use JClaveau\VisibilityViolator\VisibilityViolator;

class AboveRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_combineWith()
    {
        $above2 = new AboveRule(2);
        $above3 = new AboveRule(3);
        $above5 = new AboveRule(5);

        $above3
            ->combineWith($above5)
            ->combineWith($above2);

        $this->assertEquals(5, $above3->getMinimum());
    }

    /**/
}
