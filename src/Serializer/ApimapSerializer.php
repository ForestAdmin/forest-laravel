<?php

namespace ForestAdmin\ForestLaravel\Serializer;

use ForestAdmin\ForestLaravel\Model\Collection;
use ForestAdmin\ForestLaravel\Schema\CollectionSchema;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;

class ApimapSerializer {
    protected $collections;
    protected $meta;

    public function __construct($collections = null, $meta = array()) {
        if ($collections) {
            $this->setApimap($collections);
        }
        $this->meta = $meta;
    }

    protected function setApimap($collections) {
        foreach ($collections as $className => $collection) {
            $collection->convertForApimap();
            $collections[$className] = $collection;
        }
        $this->collections = $collections;
    }

    public function serialize() {
        $encoder = Encoder::instance(
            array(Collection::class => CollectionSchema::class),
            new EncoderOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
              '')
        );
        $apimap = $encoder
                ->withMeta($this->meta)
                ->encodeData($this->collections)
                .PHP_EOL;

        return $apimap;
    }
}
