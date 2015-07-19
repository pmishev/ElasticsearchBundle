<?php

namespace Sineflow\ElasticsearchBundle\Document;

use Sineflow\ElasticsearchBundle\Result\DocumentHighlight;

/**
 * Interface for ES Documents.
 */
interface DocumentInterface
{
    /**
     * Sets document unique id.
     *
     * @param string $documentId
     *
     * @return $this
     */
    public function setId($documentId);

    /**
     * Returns document id.
     *
     * @return string
     */
    public function getId();

    /**
     * Sets document score.
     *
     * @param string $documentScore
     *
     * @return $this
     */
    public function setScore($documentScore);

    /**
     * Gets document score.
     *
     * @return string
     */
    public function getScore();

    /**
     * Sets parent document id.
     *
     * @param string $parent
     *
     * @return $this
     */
    public function setParent($parent);

    /**
     * Returns parent document id.
     *
     * @return null|string
     */
    public function getParent();

    /**
     * Checks if document has a parent.
     *
     * @return bool
     */
    public function hasParent();

    /**
     * Sets time to live timestamp.
     *
     * @param int $ttl
     *
     * @return $this
     */
    public function setTtl($ttl);

    /**
     * Returns time to live value.
     *
     * @return int
     */
    public function getTtl();

    /**
     * Returns highlight.
     *
     * @throws \UnderflowException
     *
     * @return DocumentHighlight
     */
    public function getHighlight();

    /**
     * Sets highlight.
     *
     * @param DocumentHighlight $highlight
     */
    public function setHighlight(DocumentHighlight $highlight);
}
