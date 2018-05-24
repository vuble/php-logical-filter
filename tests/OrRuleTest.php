<?php
namespace JClaveau\LogicalFilter\Rule;

// use JClaveau\VisibilityViolator\VisibilityViolator;

class OrRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_hasSolution()
    {
        $below = new BelowRule('field_name', 1);
        $equal = new EqualRule('field_name', 2);
        $above = new AboveRule('field_name', 3);

        $this->assertFalse(
            (new OrRule([
                new AndRule([$below, $equal]),
                new AndRule([$above, $equal]),
                new AndRule([$above, $below]),
            ]))->hasSolution()
        );

        $this->assertTrue(
            (new OrRule([
                new AndRule([$below, $equal]),
                new AndRule([$above, $equal]),
                new AndRule([$above]),
            ]))->hasSolution()
        );

        $this->assertTrue(
            (new OrRule([
                $above,
            ]))->hasSolution()
        );

        $this->assertFalse(
            (new OrRule([]))->hasSolution()
        );
    }

    /**/
}
