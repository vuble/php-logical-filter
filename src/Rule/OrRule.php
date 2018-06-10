<?php
namespace JClaveau\LogicalFilter\Rule;

/**
 * Logical inclusive disjunction
 *
 * This class represents a rule that expect a value to be one of the list of
 * possibilities only.
 */
class OrRule extends AbstractOperationRule
{
    /** @var string operator */
    const operator = 'or';

    /**
     * Replace all the OrRules of the RuleTree by one OrRule at its root.
     *
     * @todo renjame as RootifyDisjunjctions?
     * @todo return $this (implements a Rule monad?)
     *
     * @return $this
     */
    public function rootifyDisjunctions()
    {
        $this->moveSimplificationStepForward( self::rootify_disjunctions );

        $upLiftedOperands = [];
        foreach ($this->getOperands() as $operand) {
            $operand = $operand->copy();
            if ($operand instanceof AbstractOperationRule)
                $operand = $operand->rootifyDisjunctions();

            if ($operand instanceof OrRule) {
                foreach ($operand->getOperands() as $subOperand)
                    $upLiftedOperands[] = $subOperand;
            }
            else {
                $upLiftedOperands[] = $operand;
            }
        }

        return new OrRule($upLiftedOperands);
    }

    /**
     * @param bool $debug=false
     */
    public function toArray($debug=false)
    {
        $operandsAsArray = [
            $debug ? $this->getInstanceId() : self::operator,
        ];
        foreach ($this->operands as $operand)
            $operandsAsArray[] = $operand->toArray($debug);

        return $operandsAsArray;
    }

    /**
     * This is called by the unifyAtomicOperands() method to choose which AboveRule
     * to keep for a given field.
     *
     * It's used as a usort() parameter.
     *
     * @return int -1|0|1
     */
    protected function aboveRuleUnifySorter( AboveRule $a, AboveRule $b)
    {
        if ($a->getMinimum() < $b->getMinimum())
            return -1;

        return 1;
    }

    /**
     * This is called by the unifyAtomicOperands() method to choose which BelowRule
     * to keep for a given field.
     *
     * It's used as a usort() parameter.
     *
     * @return int -1|0|1
     */
    protected function belowRuleUnifySorter( BelowRule $a, BelowRule $b)
    {
        if ($a->getMaximum() > $b->getMaximum())
            return -1;

        return 1;
    }

    /**
     * Removes rule branches that cannot produce result like:
     * A = 1 && ( (B < 2 && B > 3) || (C = 8 && C = 10) ) <=> A = 1
     *
     * @return OrRule
     */
    public function removeInvalidBranches()
    {
        $this->moveSimplificationStepForward(self::remove_invalid_branches);

        foreach ($this->operands as $i => $operand) {

            if ($operand instanceof AbstractOperationRule) {
                $this->operands[$i] = $operand->removeInvalidBranches();
                if (!$this->operands[$i]->getOperands()) {
                    unset($this->operands[$i]);
                    continue;
                }
            }
            else {
                if (!$this->operands[$i]->hasSolution())
                    unset($this->operands[$i]);
            }
        }

        return $this;
    }

    /**
     * Checks if the tree below the current OperationRule can have solutions
     * or if it contains contradictory rules.
     *
     * @return bool If the rule can have a solution or not
     */
    public function hasSolution()
    {
        if (!$this->simplicationStepReached(self::simplified)) {
            throw new \LogicException(
                "hasSolution has no sens if the rule is not simplified instead of being at: "
                .var_export($this->current_simplification_step, true)
            );
        }

        // If there is no remaining operand in an OrRule, it means it has
        // no solution.
        return !empty($this->getOperands());
    }

    /**/
}
