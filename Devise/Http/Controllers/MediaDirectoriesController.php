<?php namespace Devise\Http\Controllers;

use Devise\Media\Categories\CategoryAlreadyExistsException;
use Devise\Media\Categories\Manager;

use Devise\Media\Files\Repository;
use Devise\Support\Framework;
use Illuminate\Http\Request;

/**
 * Class ResponseHandler handles the controller side
 * of managing categories. These methods are likely
 * referenced in dvs_pages table
 *
 * @package Devise\Media\Categories
 */
class MediaDirectoriesController
{
  /**
   * @var Manager
   */
  protected $Manager;

  /**
   * Construct a new response handler for categories
   *
   * @param Manager $Manager
   */
  public function __construct(Manager $Manager, Repository $Repository, Framework $Framework)
  {
    $this->Manager = $Manager;
    $this->Repository = $Repository;
    $this->Redirect = $Framework->Redirect;
  }

  public function all(Request $request, $folderPath = '')
  {
    $input = $request->all();
    $input['category'] = $folderPath;
    $results = $this->Repository->compileIndexData($input, ['categories']);

    return $results['categories'];
  }

  /**
   * Request a category be stored
   *
   * @param Request $request
   * @return mixed
   */
  public function store(Request $request)
  {
    try
    {
      $this->Manager->storeNewCategory($request->all());
    } catch (CategoryAlreadyExistsException $e)
    {
      \Session::flash('dvs-error-message', "The category {$request->input('name')} already exists!");
    }

    return $this->Redirect->back();
  }

  /**
   * Request a category be destroyed
   *
   * @param Request $request
   * @return mixed
   */
  public function remove(Request $request)
  {
    $this->Manager->destroyCategory($request->all());

    return $this->Redirect->back();
  }
}