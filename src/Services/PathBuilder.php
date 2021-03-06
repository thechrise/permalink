<?php

namespace Devio\Permalink\Services;

use Devio\Permalink\Permalink;
use Devio\Permalink\Contracts\PathBuilder as Builder;

class PathBuilder implements Builder
{
    protected $cache = [];

    public function build($model)
    {
        if (! $model->exists) {
            return $this->single($model);
        } elseif ($model->isDirty('slug')) {
            return $this->recursive($model);
        }
    }

    public function single($model)
    {
        $model->final_path = $this->getFullyQualifiedPath($model);
    }

    public function all()
    {
        $permalinks = Permalink::withTrashed()->get();

        $permalinks->each(function ($permalink) {
            $this->single($permalink);
            $permalink->save();
        });
    }

    public function recursive($model)
    {
        $path = $model->isDirty('slug') ?
            $model->getOriginal('final_path') : $model->final_path;

        $nested = Permalink::withTrashed()
                           ->where('final_path', 'LIKE', $path . '/%')
                           ->get();

        $this->single($model);

        $nested->each(function ($permalink) use ($model) {
            $permalink->final_path = $model->final_path . '/' . $permalink->slug;
            $permalink->save();
        });
    }

    /**
     * @param $model
     * @return string
     */
    public function getFullyQualifiedPath($model)
    {
        $path = ($model->isNested() && $model->parent) ? $model->parent->final_path : '';

        return trim($path . '/' . $model->slug, '/');
    }

    /**
     * Find the parent for the given model.
     *
     * @param $model
     * @return mixed
     */
    public static function parentFor($model)
    {
        if (is_null($model) || (! is_object($model) && ! class_exists($model))) {
            return null;
        }

        if (! is_object($model)) {
            $model = new $model;
        }

        $model = $model->getMorphClass();

        return Permalink::where('parent_for', $model)->first();
    }

    /**
     * Get the parent route path.
     *
     * @param $model
     * @return array
     */
    public static function parentPath($model)
    {
        if ($model instanceof Permalink) {
            $model = $model->entity;
        }

        $slugs = [];

        $callable = function ($permalink) use (&$callable, &$slugs) {
            if (is_null($permalink)) {
                return;
            }

            array_push($slugs, $permalink->slug);

            if ($permalink->parent) {
                $callable($permalink->parent);
            }
        };

        $callable(static::parentFor($model));

        return array_reverse($slugs);
    }
}
