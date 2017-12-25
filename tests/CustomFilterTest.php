<?php
namespace JClaveau\CustomFilter;

use JClaveau\VisibilityViolator\VisibilityViolator;

use JClaveau\CustomFilter\Rule\OrRule;
use JClaveau\CustomFilter\Rule\AndRule;
use JClaveau\CustomFilter\Rule\InRule;
use JClaveau\CustomFilter\Rule\EqualRule;
use JClaveau\CustomFilter\Rule\AboveRule;
use JClaveau\CustomFilter\Rule\BelowRule;

class CustomFilterTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        //
        // ini_set('xdebug.max_nesting_level', 10000);
    }

    /**
     */
    public function test_addSimpleRule()
    {
        $filter = new Filter();

        $filter->addSimpleRule('field', 'in', ['a', 'b', 'c']);
        // $filter->addRule('field', 'not_in', ['a', 'b', 'c']);
        $filter->addSimpleRule('field', 'above', 3);
        $filter->addSimpleRule('field', 'below', 5);

        $rules = VisibilityViolator::getHiddenProperty(
            $filter,
            'rules'
        );

        $this->assertEquals(
            new AndRule([
                new InRule('field', ['a', 'b', 'c']),
                // new NotInRule(['a', 'b', 'c']),
                new AboveRule('field', 3),
                new BelowRule('field', 5)
            ]),
            $rules
        );
    }

    /**
     */
    public function test_addOrRule()
    {
        $filter = new Filter();

        $filter->addCompositeRule([
            ['field', 'in', ['a', 'b', 'c']],
            'or',
            ['field', 'equal', 'e']
        ]);

        $this->assertEquals(
            new AndRule([
                new OrRule([
                    new InRule('field', ['a', 'b', 'c']),
                    new EqualRule('field', 'e')
                ]),
            ]),
            $filter->getRules()
        );
    }

    /**
     */
    public function test_addRule_with_nested_operations()
    {
        $filter = new Filter();

        $filter->addCompositeRule([
            ['field', 'in', ['a', 'b', 'c']],
            'or',
            [
                ['field', 'in', ['d', 'e']],
                'and',
                [
                    ['field_2', 'above', 3],
                    'or',
                    ['field_3', 'below', -2],
                ],
            ],
        ]);

        $this->assertEquals(
            new AndRule([
                new OrRule([
                    new InRule('field', ['a', 'b', 'c']),
                    new AndRule([
                        new InRule('field', ['d', 'e']),
                        new OrRule([
                            new AboveRule('field_2', 3),
                            new BelowRule('field_3', -2),
                        ]),
                    ]),
                ]),
            ]),
            $filter->getRules()
        );
    }

    /**
     */
    public function test_getRules()
    {
        $filter = new Filter();

        $filter->addSimpleRule('field', 'in', ['a', 'b', 'c']);

        $this->assertEquals(
            new AndRule([
                new InRule('field', ['a', 'b', 'c'])
            ]),
            $filter->getRules()
        );
    }

    /**
     * @todo
     */
    public function test_removeNegations()
    {
    }

    /**/
}
