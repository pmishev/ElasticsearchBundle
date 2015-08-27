<?php

namespace Sineflow\ElasticsearchBundle\DSL;

/**
 * A trait which handles elasticsearch aggregation script.
 */
trait ScriptAwareTrait
{
    /**
     * @var string
     */
    private $script;

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @param string $script
     */
    public function setScript($script)
    {
        $this->script = $script;
    }
}
