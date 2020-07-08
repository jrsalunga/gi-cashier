<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Salesmtd extends BaseModel {

	protected $table = 'salesmtd';
  protected $fillable = ['tblno', 'wtrno', 'ordno', 'product_id', 'qty', 'uprice', 'grsamt', 
                        'disc', 'netamt', 'orddate', 'ordtime', 'recno', 'cslipno', 'custcount', 'paxloc', 
                        'group', 'group_cnt', 'remarks', 'cashier', 'branch_id'];
	//protected $guarded = ['id'];
  //protected $appends = ['transdate'];
  protected $dates = ['orddate', 'ordtime'];
	protected $casts = [
    'qty' => 'float',
    'uprice' => 'float',
    'grsamt' => 'float',
    'disc' => 'float',
    'netamt' => 'float',
    'ordno' => 'integer',
    'group_cnt' => 'integer',
    'recno' => 'integer',
    'cslipno' => 'integer',
    'custcount' => 'integer',
  ];

  public function branch() {
    return $this->belongsTo('App\Models\Branch');
  }

  public function product() {
    return $this->belongsTo('App\Models\Product');
  }

  /*
  public function getTransdateAttribute(){
    return Carbon::parse($this->orddate.' '.$this->ordtime);
  }
  */


  /*
  * Activate only if salesmtd has a searate table per year
  * e.g. salesmtd, salesmtd2019, salesmtd2020
  *      
  protected function performInsert(Builder $query, array $options = []) {
    if ($this->fireModelEvent('creating') === false) {
      return false;
    }

    // First we'll need to create a fresh query instance and touch the creation and
    // update timestamps on this model, which are maintained by us for developer
    // convenience. After, we will just continue saving these model instances.
    if ($this->timestamps && Arr::get($options, 'timestamps', true)) {
       $this->updateTimestamps();
    }

    // If the model has an incrementing key, we can use the "insertGetId" method on
    // the query builder, which will give us back the final inserted ID for this
    // table from the database. Not all tables have to be incrementing though.
    $attributes = $this->attributes;
    $attributes['id'] = $this->get_uid();

    if (Carbon::parse($attributes['orddate'])->gte(Carbon::parse('2020-07-01'))) {
      $this->setTable('salesmtd2020');
    }

    $this->setAttribute('id', $attributes['id']);

    if ($this->incrementing) {
      $this->insertAndSetId($query, $attributes);
    } 

    // If the table isn't incrementing we'll simply insert these attributes as they
    // are. These attribute arrays must contain an "id" column previously placed
    // there by the developer as the manually determined key for these models.
    else {
      $this->insert($attributes);
      // $query->insert($attributes);
    }

    // We will go ahead and set the exists property to true, so that it is set when
    // the created event is fired, just in case the developer tries to update it
    // during the event. This will allow them to do so and run an update here.
    $this->exists = true;

    $this->wasRecentlyCreated = true;

    $this->fireModelEvent('created', false);

    return true;
  }

  protected function performDeleteOnModel()
    {
            test_log('delete');
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }

  public function delete()
  {
    if (is_null($this->getKeyName())) {
        throw new Exception('No primary key defined on model.');
    }

    if ($this->exists) {
        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        $this->touchOwners();


        $attributes = $this->attributes;
        if (Carbon::parse($attributes['orddate'])->gte(Carbon::parse('2020-07-01'))) {
          $this->setTable('salesmtd2020');
        }

        $this->performDeleteOnModel();

        $this->exists = false;

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        $this->fireModelEvent('deleted', false);

        return true;
    }
  }
  */




 



}
