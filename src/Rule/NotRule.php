<?php
namespace JClaveau\LogicalFilter\Rule;

/**
 * Logical negation:
 * @see https://en.wikipedia.org/wiki/Negation
 */
class NotRule extends AbstractOperationRule
{
    /** @var string operator */
    const operator = 'not';

    /**
     */
    // public function __construct( AbstractRule $operand=null )
    public function __construct( $operand=null )
    {
        if (!$operand)
            return;

        if (!$operand instanceof AbstractRule) {
            throw new \InvalidArgumentException(
                "Operand of NOT must be an instance of AbstractRule instead of: "
                .var_export($operand, true)
            );
        }

        // negation has only one operand
        $this->operands = [$operand];
    }

    /**
     * Transforms all composite rules in the tree of operands into
     * atomic rules.
     *
     * @todo use get_class instead of instanceof to avoid order issue
     *       in the conditions.
     *
     * @return array
     */
    public function negateOperand($remove_generated_negations=false)
    {
        if (!$this->operands)
            return $this;

        $operand = $this->operands[0];

        if (!$operand instanceof AbstractOperationRule)
            $field = $operand->getField();

        if ($operand instanceof AboveRule) {
            $new_rule = new OrRule([
                new BelowRule($field, $operand->getMinimum()),
                new EqualRule($field, $operand->getMinimum()),
            ]);
        }
        elseif ($operand instanceof BelowRule) {
            // ! (v >  a) : v <= a : (v < a || a = v)
            $new_rule = new OrRule([
                new AboveRule($field, $operand->getMaximum()),
                new EqualRule($field, $operand->getMaximum()),
            ]);
        }
        elseif ($operand instanceof NotRule) {
            // ! (  !  a) : a
            $new_rule = $operand->getOperands()[0];
        }
        elseif ($operand instanceof EqualRule && $operand->getValue() === null) {
            $new_rule = new NotEqualRule($field, null);
        }
        elseif ($operand instanceof EqualRule) {
            // ! (v =  a) : (v < a) || (v > a)
            $new_rule = new OrRule([
                new AboveRule($field, $operand->getValue()),
                new BelowRule($field, $operand->getValue()),
            ]);
        }
        elseif ($operand instanceof AndRule) {
            // ! (B && A) : (!B && A) || (B && !A) || (!B && !A)
            // TODO :
            // ! (A && B && C) :
            //    (!A && !B && !C)
            // || (!A && B && C) || (!A && !B && C) || (!A && B && !C)
            // || (A && !B && C) || (!A && !B && C) || (A && !B && !C)
            // || (A && B && !C) || (!A && B && !C) || (A && !B && !C)
            $child_operands = $operand->getOperands();
            if (count($child_operands) > 2) {
                throw new \ErrorException(
                     'NotRule resolution of AndRule with more than 2 '
                    .'operands is not implemented'
                );
            }


            $new_rule = new OrRule([
                new AndRule([
                    $child_operands[0]->copy(),
                    new NotRule($child_operands[1]->copy()),
                ]),
                new AndRule([
                    new NotRule($child_operands[0]->copy()),
                    $child_operands[1]->copy(),
                ]),
                new AndRule([
                    new NotRule($child_operands[0]->copy()),
                    new NotRule($child_operands[1]->copy()),
                ]),
            ]);
        }
        elseif ($operand instanceof OrRule) {
            // ! (A || B) : !A && !B
            // ! (A || B || C || D) : !A && !B && !C && !D
            $new_rule = new AndRule;
            foreach ($operand->getOperands() as $operand)
                $new_rule->addOperand( new NotRule($operand->copy()) );
        }
        else {
            throw new \ErrorException(
                'Removing NotRule of ' . get_class($operand)
                . ' not implemented'
            );
        }

        return $new_rule;
    }

    /**
     * Not rules can only have one operand.
     *
     * @return $this
     */
    public function unifyAtomicOperands($unifyDifferentOperands = true)
    {
        $this->moveSimplificationStepForward( self::unify_atomic_operands );
        return $this;
    }

    /**
     * Replace all the OrRules of the RuleTree by one OrRule at its root.
     *
     * @todo rename as RootifyDisjunjctions?
     * @todo return $this (implements a Rule monad?)
     *
     * @return OrRule copied operands with one OR at its root
     * /
    public function rootifyDisjunctions()
    {
        $this->moveSimplificationStepForward( self::rootify_disjunctions );
        if ()
    }


    /**
     */
    public function toArray($debug=false)
    {
        return [
            $debug ? $this->getInstanceId() : self::operator,
            $this->operands[0]->toArray($debug)
        ];
    }

    /**/
}
