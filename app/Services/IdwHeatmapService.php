<?php
namespace App\Services;use App\Models\Survey;use Illuminate\Validation\ValidationException;
class IdwHeatmapService{
 public function generate(Survey $survey,array $options=[]):array{
  $width=(int)($options['grid_width']??80);$height=(int)($options['grid_height']??60);$power=(float)($options['power']??2);$threshold=(float)($options['weak_threshold']??-75);$maxDistance=(float)($options['max_distance']??0.30);
  $rows=$survey->points()->where('validation_status','valid')->with(['measurements'=>fn($q)=>$q->whereNotNull('rssi_dbm')->where('is_simulated',false)->latest('captured_at')])->get();
  $points=[];$ids=[];foreach($rows as $point){$measurement=$point->measurements->first();if(!$measurement)continue;$points[]=['x'=>(float)$point->x_normalized,'y'=>(float)$point->y_normalized,'value'=>(float)$measurement->rssi_dbm,'id'=>$measurement->id];$ids[]=$measurement->id;}
  if(count($points)<max(3,$survey->minimum_points))throw ValidationException::withMessages(['heatmap'=>"At least {$survey->minimum_points} valid non-simulated RSSI measurements are required."]);
  $boundary=$survey->floorPlan->usable_boundary;if(!$boundary||count($boundary)<3)throw ValidationException::withMessages(['heatmap'=>'A usable floor boundary is required.']);
  $grid=[];$confidence=[];$covered=0;$validCells=0;$estimatedCells=0;
  for($gy=0;$gy<$height;$gy++){ $gridRow=[];$confidenceRow=[];$y=$height===1?0:$gy/($height-1);for($gx=0;$gx<$width;$gx++){ $x=$width===1?0:$gx/($width-1);if(!$this->inside($x,$y,$boundary)){$gridRow[]=null;$confidenceRow[]=null;continue;}$validCells++;[$value,$distance]=$this->estimate($x,$y,$points,$power);$conf=max(0,min(1,1-($distance/$maxDistance)));if($distance>$maxDistance){$gridRow[]=null;$confidenceRow[]=0;continue;}$estimatedCells++;$rounded=round($value,2);$gridRow[]=$rounded;$confidenceRow[]=round($conf,3);if($rounded>=$threshold)$covered++;}$grid[]=$gridRow;$confidence[]=$confidenceRow;}
  return ['grid'=>$grid,'confidence'=>$confidence,'source_points'=>$points,'source_measurement_ids'=>$ids,'source_point_count'=>count($points),'coverage_percent'=>$estimatedCells?round(($covered/$estimatedCells)*100,3):null,'estimated_area_percent'=>$validCells?round(($estimatedCells/$validCells)*100,3):0,'parameters'=>compact('width','height','power','threshold','maxDistance')];
 }
 private function estimate(float $x,float $y,array $points,float $power):array{$sum=0;$weights=0;$nearest=INF;foreach($points as $p){$d=sqrt((($x-$p['x'])**2)+(($y-$p['y'])**2));$nearest=min($nearest,$d);if($d<0.000001)return[$p['value'],0];$w=1/($d**$power);$sum+=$w*$p['value'];$weights+=$w;}return[$sum/$weights,$nearest];}
 private function inside(float $x,float $y,array $polygon):bool{$inside=false;$count=count($polygon);for($i=0,$j=$count-1;$i<$count;$j=$i++){$xi=(float)$polygon[$i]['x'];$yi=(float)$polygon[$i]['y'];$xj=(float)$polygon[$j]['x'];$yj=(float)$polygon[$j]['y'];$cross=(($yi>$y)!==($yj>$y))&&($x<(($xj-$xi)*($y-$yi)/(($yj-$yi)?:1e-12)+$xi));if($cross)$inside=!$inside;}return$inside;}
}
