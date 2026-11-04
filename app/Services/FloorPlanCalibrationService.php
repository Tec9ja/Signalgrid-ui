<?php
namespace App\Services;
use App\Models\FloorPlan;
use InvalidArgumentException;
class FloorPlanCalibrationService {
 public function calibrate(FloorPlan $plan,array $data,int $userId):FloorPlan{
  foreach(['x1','y1','x2','y2'] as $key)if($data[$key]<0||$data[$key]>1)throw new InvalidArgumentException('Calibration coordinates must be between 0 and 1.');
  $width=$plan->original_width; $height=$plan->original_height;
  if(!$width||!$height)throw new InvalidArgumentException('Image dimensions are required before calibration.');
  $dx=($data['x2']-$data['x1'])*$width; $dy=($data['y2']-$data['y1'])*$height; $pixels=sqrt(($dx*$dx)+($dy*$dy));
  if($pixels<5)throw new InvalidArgumentException('Calibration points are too close together.');
  $ppm=$pixels/$data['distance_metres'];
  $plan->update(['calibration_x1'=>$data['x1'],'calibration_y1'=>$data['y1'],'calibration_x2'=>$data['x2'],'calibration_y2'=>$data['y2'],'calibration_distance_metres'=>$data['distance_metres'],'pixels_per_metre'=>$ppm,'calibrated_at'=>now(),'calibrated_by'=>$userId]);
  return $plan->refresh();
 }
}
