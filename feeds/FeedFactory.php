<?php

namespace Carbontwelve\Widgets\SocialFeeds\Feeds;

class FeedFactory
{
    /**
     * Path to directory where feed scrapers may be found.
     *
     * @var string
     */
    private $lookupPath;

    /**
     * An array of all feed scrapers as identified by the identifyFeeds method.
     *
     * @var array
     */
    private $feedScrapers = [];

    public function __construct($lookupPath = null)
    {
        $this->lookupPath = $lookupPath ?: __DIR__.DIRECTORY_SEPARATOR.'FeedScrapers';
        $this->identifyFeeds();
    }

    /**
     * @param $feedName
     *
     * @return \Carbontwelve\Widgets\SocialFeeds\Feeds\FeedInterface
     */
    public function getFeed($feedName)
    {
        if ($this->hasFeed($feedName)) {
            /** @noinspection PhpIncludeInspection */
            include_once $this->feedScrapers[$feedName]['includePath'];
            $className = $this->feedScrapers[$feedName]['className'];

            return new $className();
        }

        return;
    }

    /**
     * Format the feed for drop-downs.
     *
     * @return array
     */
    public function getFeedsForDropDown()
    {
        $output = [
            '' => 'Please choose one',
        ];

        foreach ($this->feedScrapers as $key => $scraper) {
            $output[$key] = $scraper['name'];
        }

        return $output;
    }

    /**
     * @param array      $instance Plugin instance
     * @param \WP_Widget $widget
     *
     * @return array
     */
    public function getFeedFields(array $instance, \WP_Widget $widget)
    {
        $fields = [];
        foreach (array_keys($this->feedScrapers) as $key) {
            /** @var FeedInterface $tmp */
            $tmp = $this->getFeed($key);

            foreach ($tmp->getUniqueFields() as $fieldKey => $fieldTitle) {
                $fields[$key][$fieldKey] = [
                    'key'   => $fieldKey,
                    'id'    => 'widget-'.$widget->id_base.'-'.$widget->number.'-'.$key.'-'.strtolower($fieldKey),
                    'name'  => 'widget-'.$widget->id_base.'['.$widget->number.'][metaFields]['.$key.']['.$fieldKey.']',
                    'title' => $fieldTitle,
                    'value' => null,
                ];

                if (isset($instance['metaFields'][$key]) && isset($instance['metaFields'][$key][$fieldKey])) {
                    $fields[$key][$fieldKey]['value'] = $instance['metaFields'][$key][$fieldKey];
                }
            }
        }

        return $fields;
    }

    /**
     * Check to see if a feed can be generated by the factory.
     *
     * @param $feedName
     *
     * @return bool
     */
    public function hasFeed($feedName)
    {
        return isset($this->feedScrapers[$feedName]);
    }

    private function identifyFeeds()
    {
        $classes = array_filter(scandir($this->lookupPath), function ($value) {
            if ($value === '.' || $value === '..' || strpos($value, '_') !== false) {
                return false;
            }

            return true;
        });

        foreach ($classes as $class) {
            /** @noinspection PhpIncludeInspection */
            include_once $this->lookupPath.DIRECTORY_SEPARATOR.$class;

            $className = 'Carbontwelve\\Widgets\\SocialFeeds\\Feeds\\FeedScrapers\\'.str_replace('.php', '', $class);

            /** @var FeedInterface $tmp */
            $tmp = new $className();
            $this->feedScrapers[ $this->slugifyString($tmp->getName()) ] = [
                'name'        => $tmp->getName(),
                'includePath' => $this->lookupPath.DIRECTORY_SEPARATOR.$class,
                'className'   => $className,
            ];
        }
    }

    private function slugifyString($string)
    {
        return str_replace(' ', '_', strtolower(trim($string)));
    }
}
