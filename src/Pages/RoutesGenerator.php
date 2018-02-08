<?php namespace Devise\Pages;

use Devise\Support\Framework;

class RoutesGenerator
{
  /**
   * [__construct description]
   */
  public function __construct(Framework $Framework)
  {
    $this->View = $Framework->View;
    $this->Config = $Framework->Config;
    $this->File = $Framework->File;
    $this->Request = $Framework->Request;
    $this->Route = $Framework->Route;
    $this->DB = $Framework->DB;
    $this->App = $Framework->Container;
    $this->Artisan = $Framework->Artisan;
  }

  /**
   * Loads the routes
   *
   */
  public function loadRoutes()
  {
    if ($this->App->runningInConsole())
    {
      return;
    }

    $this->assertRouteCachingValid();

    // laravel (cache) is handling the routes
    if ($this->App->routesAreCached()) return;

    // laravel has no cache and there is no
    // devise routes file, therefore we just
    // load the routes directly from the DB
    $routes = $this->findDvsPageRoutes();

    $routesBySite = $routes->groupBy('site_id');
    $domains = $this->DB->table('dvs_sites')->pluck('domain', 'id');

    foreach ($routesBySite as $siteId => $routes)
    {
      if ($siteId > 0)
      {
        $overwrite = env('SITE_' . $siteId . '_DOMAIN');
        $domain = (!$overwrite) ? $domains[$siteId] : $overwrite;
        $this->Route->group(['domain' => $domain], function () use ($routes) {

          foreach ($routes as $route)
          {
            $verb = $route->http_verb;
            $uses = ['as' => $route->route_name, 'uses' => $route->uses];

            if ($route->middleware)
            {
              $uses['middleware'] = explode('|', $route->middleware);
            }

            $this->Route->$verb($route->slug, $uses);
          }

        });
      } else
      {

        foreach ($routes as $route)
        {
          $verb = $route->http_verb;
          $uses = ['as' => $route->route_name, 'uses' => $route->uses];

          if ($route->middleware)
          {
            $uses['middleware'] = explode('|', $route->middleware);
          }

          $this->Route->$verb($route->slug, $uses);
        }
      }
    }
  }

  /**
   * Uses the routes array and spits out
   * a string of laravel routes
   * @return string
   */
  public function cacheRoutes()
  {
    // routes are not enabled, so we should not
    // cache. furthermore we should disable the
    // laravel cache and clear the devise routes
    // file if it exists
    if (!$this->Config->get('devise.routes.enabled'))
    {
      $this->clearLaravelCache();
      $this->clearDeviseRoutes();

      return false;
    }

    $routeCachePath = $this->Config->get('devise.routes.cache');

    $routes = $this->findDvsPageRoutes();

    $routesAsString = $this->View->make('devise::layouts.routes', ['routes' => $routes])->render();

    $this->File->put($routeCachePath, $routesAsString);

    $this->Artisan->call('route:cache');

    return true;
  }

  /**
   * Clear the route cache in Laravel
   * Devis Pages won't work properly if
   * these routes are cached
   *
   */
  protected function clearLaravelCache()
  {
    $this->Artisan->call('route:clear');
  }

  /**
   * Remove the temporary devise routes file
   *
   */
  protected function clearDeviseRoutes()
  {
    $routeCachePath = $this->Config->get('devise.routes.cache');

    if ($this->File->exists($routeCachePath)) $this->File->delete($routeCachePath);
  }

  /**
   * Returns the route cache path if the file exists
   *
   * @return [type]
   */
  protected function deviseRoutesFile()
  {
    $routeCachePath = $this->Config->get('devise.routes.cache');

    if ($this->File->exists($routeCachePath)) return $routeCachePath;

    return null;
  }

  /**
   * Returns the dvs page routes in this system
   *
   * @return array
   */
  protected function findDvsPageRoutes()
  {
    $pages = $this->DB->table('dvs_pages')->select('http_verb', 'slug', 'route_name', 'middleware', 'site_id')->get();

    foreach ($pages as $page)
    {
      // ensure that route slugs are all lower case
      $slugRegex = '/([^[\}]+)(?:$|\{)/';

      $page->slug = preg_replace_callback($slugRegex, function ($matches) {
        return strtolower($matches[0]);
      }, $page->slug, -1, $count);

      $page->uses = 'Devise\Http\Controllers\PagesController@show';
    }

    return $pages;
  }

  /**
   * Asserts that the route cache is not in a weird
   * state
   *
   * @throws \Exception
   */
  protected function assertRouteCachingValid()
  {
    if ($this->App->routesAreCached() && !$this->Config->get('devise.routes.enabled'))
    {
      throw new \Exception('Devise will not work proprely when Laravel routes are cached and DEVISE_CACHE_ENABLED=false. For more information on how to address this error please visit, http://devisephp.com/docs/common-errors#route-caching');
    }
  }
}