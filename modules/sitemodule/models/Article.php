<?php

namespace modules\sitemodule\models;

use Craft;
use DateTime;
use craft\base\Model;

/**
 * Article model
 */
class Article extends Model
{
    // Properties
    // =========================================================================

    // Our Model is just for internal use, so let's type it!

     /**
     * @var string Unique identifier
     */
    public string $id;

    /**
     * @var string Liveblog or Article
     */
    public string $type;

    /**
     * @var string Section of the publication, e.g. News / Culture / Lifestyle
     */
    public string $section;

    /**
     * @var DateTime The date on which the article was created in
     * [ISO 8601 date format](http://schema.org/Date), e.g.: `2023-08-09T22:27:05Z`
     */
    public DateTime $dateCreated;

    /**
     * @var DateTime The date on which the article was last modified in
     * [ISO 8601 date format](http://schema.org/Date), e.g.: `2023-08-09T22:27:05Z`
     */
    public DateTime $dateModified;

    /**
     * @var string Headline of the article
     */
    public string $headline;

    /**
     * @var string An alternative title or subheadinf or the article
     */
    public string $alternativeHeadline;

    /**
     * @var string URL of the live article
     */
    public string $url;

    /**
     * @var string Name (or names) of the author(s); not always present
     */
    public ?string $author;

    /**
     * @var string The thumbnail published with the article
     */
    public string $thumbnailUrl;

    /**
     * @var string The publisher of the article
     * (Should exist within $providers array)
     */
    public string $provider;

    // Craft Generator adds the following, but I think this is only respected
    // when saving to the database.
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [
                'id',
            ],
            'string'
        ]);
    }
}
