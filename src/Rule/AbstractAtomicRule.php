<?php
namespace JClaveau\LogicalFilter\Rule;

/**
 * Atomic rules are those who cannot be simplified (so already are):
 * + null
 * + not null
 * + equal
 * + above
 * + below
 * Atomic rules are related to a field.
 */
abstract class AbstractAtomicRule extends AbstractRule
{
    /** @var string $field The field to apply the rule on */
    protected $field;

    /** @var array $cache */
    protected $cache = [
        'array'       => null,
        'string'      => null,
        'semantic_id' => null,
    ];

    /**
     * @return string $field
     */
    public final function getField()
    {
        return $this->field;
    }

    /**
     * @return string $field
     */
    public final function setField( $field )
    {
        if (!is_scalar($field)) {
            throw new \InvalidArgumentEXception(
                "\$field property of a logical rule must be a scalar contrary to: "
                .var_export($field, true)
            );
        }

        if ($this->field != $field) {
            $this->field = $field;
            $this->flushCache();
        }

        return $this;
    }

    /**
     * @param  array|callable Associative array of renamings or callable
     *                        that would rename the fields.
     *
     * @return AbstractAtomicRule $this
     */
    public final function renameField($renamings)
    {
        if (is_callable($renamings)) {
            $this->setField( call_user_func($renamings, $this->field) );
        }
        elseif (is_array($renamings)) {
            if (isset($renamings[$this->field]))
                $this->setField( $renamings[$this->field] );
        }
        else {
            throw new \InvalidArgumentException(
                "\$renamings MUST be a callable or an associative array "
                ."instead of: " . var_export($renamings, true)
            );
        }

        return $this;
    }

    /**
     * @param array $options   + show_instance=false Display the operator of the rule or its instance id
     *
     * @return array
     */
    public function toArray(array $options=[])
    {
        $default_options = [
            'show_instance' => false,
        ];
        foreach ($default_options as $default_option => &$default_value) {
            if (!isset($options[ $default_option ]))
                $options[ $default_option ] = $default_value;
        }
        extract($options);

        if (!empty($this->cache['array']))
            return $this->cache['array'];

        $class = get_class($this);

        return $this->cache['array'] = [
            $this->getField(),
            $show_instance ? $this->getInstanceId() : $class::operator,
            $this->getValues(),
        ];
    }

    /**
     */
    public function toString(array $options=[])
    {
        if (!empty($this->cache['string']))
            return $this->cache['string'];

        $class = get_class($this);
        $operator = $class::operator;

        $stringified_value = var_export($this->getValues(), true);

        return $this->cache['string'] = "['{$this->getField()}', '$operator', $stringified_value]";
    }

    /**
     * Returns an id corresponding to the meaning of the rule.
     *
     * @return string
     */
    public function getSemanticId()
    {
        if ($this->cache['semantic_id'])
            return $this->cache['semantic_id'];

        return parent::getSemanticId();
    }

    /**
     */
    protected function flushCache()
    {
        $this->cache = [
            'array'       => null,
            'string'      => null,
            'semantic_id' => null,
        ];
    }

    /**/
}
