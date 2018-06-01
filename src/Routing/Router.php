<?php

namespace Devio\Permalink\Routing;

use Devio\Permalink\Permalink;
use Illuminate\Routing\Router as LaravelRouter;
use Illuminate\Contracts\Foundation\Application;

class Router
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Router constructor.
     *
     * @param Application $app
     * @param LaravelRouter $router
     */
    public function __construct(Application $app, LaravelRouter $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * Load the given set of routes.
     *
     * @param $pages
     */
    public function load()
    {
        $callback = function ($router) {
            foreach ($this->getPermalinkTree() as $permalink) {
                Node::make($router, $permalink);
            }
        };

        $this->router->group(['middleware' => 'web'], $callback);
    }

    /**
     * Get all the permalinks in a tree structure.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getPermalinkTree()
    {
        // We will query all the root permalinks and then load all their children
        // relationships recursively. This way we will obtain a tree structured
        // collection in which we can easily iterate from parents to children.
        return Permalink::with('children')
                        ->whereNull('parent_id')
                        ->get();
    }
}