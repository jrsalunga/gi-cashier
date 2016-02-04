<?php namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

abstract class BaseModel extends Model {

	public $timestamps = false;
	public $incrementing = false;



	/******* this is a substitute from Illuminate\Database\Eloquent\Model ******
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $options
     * @return bool
     */
    protected function performInsert(Builder $query, array $options = [])
    {
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
        $this->setAttribute('id', $attributes['id']);

        if ($this->incrementing) {
            $this->insertAndSetId($query, $attributes);
        } 

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

	

	public static function get_uid(){
		$id = \DB::select('SELECT UUID() as id');
		$id = array_shift($id);
		return strtoupper(str_replace("-", "", $id->id));
	}

	public function getUuid(){
		return strtoupper(md5(uniqid()));
	}


	public function next($fields = ['id']) {
		$class = get_called_class();
		$res = $class::where('id', '>', $this->id)->orderBy('id', 'ASC')->get($fields)->first();
		return !empty($res) ? $res : 'false';
	}

	public function previous($fields = ['id']) {
		$class = get_called_class();
		$res = $class::where('id', '<', $this->id)->orderBy('id', 'DESC')->get($fields)->first();
		return !empty($res) ? $res : 'false';
	}

	public function lid(){
		return strtolower($this->id);
	}

	public function nextByField($field = 'id'){
		$res = $this->query()->where($field, '>', $this->{$field})->orderBy($field, 'ASC')->get()->first();
		return $res==null ? 'false':$res;
	}

	public function previousByField($field = 'id'){
		$res = $this->query()->where($field, '<', $this->{$field})->orderBy($field, 'DESC')->get()->first();
		return $res==null ? 'false':$res;
	}

	public function firstRecord($field = 'id'){
		$res = $this->query()->orderBy($field, 'ASC')->get()->first();
		return $res==null ? 'false':$res;
	}

	public function lastRecord($field = 'id'){
		$res = $this->query()->orderBy($field, 'DESC')->get()->first();
		return $res==null ? 'false':$res;
	}


	

	


	public function getDaysByWeekNo($weekno='', $year=''){
  	$weekno = (empty($weekno) || $weekno > $this->lastWeekOfYear()) ? date('W', strtotime('now')) : $weekno;
  	$year = empty($year) ?  date('Y', strtotime('now')) : $year;
		for($day=1; $day<=7; $day++) {
		    $arr[$day-1] = date('Y-m-d', strtotime($year."W".str_pad($weekno,2,'0',STR_PAD_LEFT).$day));
		}
		return $arr;
  }

	public function lastWeekOfYear($year='') {
		$year = empty($year) ? date('Y', strtotime('now')):$year;
    $date = new \DateTime;
    $date->setISODate($year, 53);
    return ($date->format("W") === "53" ? 53 : 52);
	}

	public function firstDayOfWeek($weekno='', $year=''){
		$weekno = empty($weekno) ? date('W', strtotime('now')) : $weekno;
		$year = empty($year) ? date('Y', strtotime('now')) : $year;
		$dt = new DateTime();
		$dt->setISODate($year, $weekno);
		return $dt;
	}

	public function carbonDate($date=''){
		return Carbon::parse(date('Y-m-d', strtotime($date)));
	}
	



	public static function getLastDayLastWeekOfYear($year=""){
			
			$year = empty($year) ?  date('Y', strtotime('now')) : $year;
			$day = 31;
			$init_weekno = date("W", mktime(0,0,0,12,$day,$year));
			//echo $init_weekno.'<br>';

			$weekno = 0;
			while ($init_weekno == '01') {
				$weekno = $init_weekno;
				$init_weekno = date("W", mktime(0,0,0,12,$day,$year));
				//echo '12/'.$day.'/'.$year.'<br>';
				$day--;
			}
			$weekno = date("W", strtotime($year.'-12-'.$day));
			return ['date' => $year.'-12-'.$day, 'weekno' => $weekno];
		}


	public function getRefno($len = 8){
 		return str_pad((intval(\DB::table($this->table)->max('refno')) + 1), $len, '0', STR_PAD_LEFT);
 	}
	
}
