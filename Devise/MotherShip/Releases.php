<?php


namespace Devise\MotherShip;


use Carbon\Carbon;
use Devise\Models\DvsMigration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Releases
{
  /**
   * @var Api
   */
  private $api;
  /**
   * @var DvsMigration
   */
  private $dvsMigration;

  /**
   * Releases constructor.
   * @param Api $api
   * @param DvsMigration $dvsMigration
   */
  public function __construct(Api $api, DvsMigration $dvsMigration)
  {
    $this->api = $api;
    $this->dvsMigration = $dvsMigration;
  }

  public function initWithMotherShip()
  {
    $congif = config('database.connections.' . config('database.default'));

    $dump = shell_exec('mysqldump -u ' . $congif['username'] . ' -p' . $congif['password'] . ' ' . $congif['database']);

    $file = time() . '.sql';

    File::put(storage_path() . '/' . $file, $dump);

    $response = $this->api->init($this->getCurrentCommitHash(), storage_path() . '/' . $file);

    $newRelease = new DvsRelease();
    $newRelease->model_id = $response->id;
    $newRelease->model_name = 'Release';
    $newRelease->msh_id = $response->id;
    $newRelease->created_at = $response->created_at;
    $newRelease->updated_at = $response->updated_at;
    $newRelease->save();

    unlink(storage_path() . '/' . $file);

    return $newRelease;
  }

  public function getForDeviseFlow()
  {
    $currentRelease = $this->getCurrentRelease();

    $rows = $this->getNewRows($currentRelease);

    $grouped = $rows->groupBy(function ($item, $key) {
      return class_basename($item->top_level_model) . '-' . $item->top_level_model->id;
    });

    $results = [];

    foreach ($grouped as $group)
    {
      $first = $group->first();
      $model = $first->top_level_model;
      $model->name = class_basename($model);
      $model->releases = $group;
      $results[] = $model;
    }

    return collect($results);
  }

  public function send($toBeReleased)
  {
    $migrationDate = $this->getCurrentMigrationDate();
    dd($migrationDate);
    $currentRelease = $this->getCurrentRelease();

    $rows = $this->getNewRows($currentRelease);

    if ($rows->count())
    {
      $migrationDate = $this->getCurrentMigrationDate();
      dd($migrationDate);

      DependenciesMap::newRelease();
      $rows->each(function ($item, $key) {
        $item->type = ($item->msh_id) ? 'update' : 'create';
        $item->model->prepRelease();
      });

      $data = [
        'commit'      => trim(shell_exec('git rev-parse --verify HEAD')),
        'environment' => App::environment(),
        'rows'        => $rows,
        'release_ids' => $toBeReleased,
        'release'     => $currentRelease ? $currentRelease->msh_id : 0,
        'migrations'  => $migrations
      ];

      $responseData = $this->api->store($data);

      $releases = collect($responseData->releases);
      $rows = collect($responseData->rows);

      $releaseIds = $releases->pluck('id')->toArray();
      array_pop($releaseIds);

      dd($responseData);

      /**
       * !!!!!
       * !!!!!
       * !!!!!
       * We should no longer be going back to the api. the sql for each release should come in the server response
       * we need to step through each release run migrations and run through each query
       * !!!!!
       * !!!!!
       * !!!!!
       */
      if ($releaseIds)
      {
        $query = $this->api->store($releaseIds);
      }

      $lastRelease = $releases->last();

      $newRelease = new DvsRelease();
      $newRelease->model_id = $lastRelease->id;
      $newRelease->model_name = 'Release';
      $newRelease->msh_id = $lastRelease->id;
      $newRelease->created_at = $lastRelease->created_at;
      $newRelease->updated_at = $lastRelease->updated_at;
      $newRelease->save();

      shell_exec('git pull');
      shell_exec('git checkout ' . $lastRelease->commit_hash);

      DB::unprepared('UPDATE dvs_releases LEFT JOIN shirts on shirts.id = dvs_releases.model_id SET dvs_releases.model_id = dvs_releases.model_id + 100, shirts.id = shirts.id + 100 WHERE msh_id = 0 and dvs_releases.id NOT IN (' . $rows->pluck('id')->implode(',') . ');');

      foreach ($rows as $row)
      {
        $record = DvsRelease::with('model')
          ->find($row->id);

        $record->modelRecord->saveRelease = false;
        $record->modelRecord->id = $row->msh_id;
        $record->modelRecord->save();

        $record->timestamps = false;
        $record->model_id = $row->msh_id;
        $record->msh_id = $row->msh_id;
        $record->save();

      }

      if ($query)
      {
        $queries = explode("\n", $query);
        foreach ($queries as $query)
        {
          if ($query)
          {
            DB::unprepared($query);
          }
        }
      }

      echo $query;
    }
  }

  public function getCurrentRelease()
  {
    return DvsRelease::where('model_name', 'Release')
      ->orderBy('created_at', 'desc')
      ->first();
  }

  private function getNewRows($currentRelease)
  {
    $currentReleaseDate = $currentRelease ? $currentRelease->created_at : '00-00-00 00:00:00';

    return DvsRelease
      ::with([
        'changes' => function ($query) use ($currentReleaseDate) {
          $query->where('created_at', '>', $currentReleaseDate);
        }
      ])
      ->where(function ($query) use ($currentReleaseDate) {
        $query->where('msh_id', 0)
          ->orWhere('updated_at', '>', $currentReleaseDate)
          ->orWhere('deleted_at', '>', $currentReleaseDate);
      })
      ->orderBy('created_at')
      ->get();
  }

  private function getCurrentMigrationDate()
  {
    $newest = $this->dvsMigration
      ->orderBy('migration', 'desc')
      ->first();

    return $newest->date;
  }

  private function getNewestReleasedDate()
  {
    $newestOld = DvsRelease::with('model')
      ->where('model_name', '!=', 'Release')
      ->where('msh_id', '!=', 0)
      ->orderBy('created_at', 'desc')
      ->first();

    return $newestOld ? $newestOld->created_at : date('Y-m-d H:i:s', strtotime('now -1 year'));
  }

  private function getCurrentCommitHash()
  {
    return trim(shell_exec('git rev-parse --verify HEAD'));
  }
}