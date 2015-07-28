<?php

namespace Sineflow\ElasticsearchBundle\Event;

/**
 * Static class for events names constants.
 */
final class Events
{
    /**
     * Event dispatched before any document is persisted.
     *
     * The event listener receives an ElasticsearchPersistEvent instance.
     */
    const PRE_PERSIST = 'sfes.pre_persist';

    /**
     * Event dispatched after any document is persisted.
     *
     * The event listener receives an ElasticsearchPersistEvent instance.
     */
    const POST_PERSIST = 'sfes.post_persist';

    /**
     * Event dispatched before data are committed.
     *
     * The event listener receives an ElasticsearchCommitEvent instance.
     */
    const PRE_COMMIT = 'sfes.pre_commit';

    /**
     * Event dispatched after data are committed.
     *
     * The event listener receives an ElasticsearchCommitEvent instance.
     */
    const POST_COMMIT = 'sfes.post_commit';
}
