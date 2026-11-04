<?php
namespace App\Services;
class MeasurementQualityService{public function flags(array $d):array{$f=[];if(isset($d['rssi_dbm'])&&($d['rssi_dbm']<-120||$d['rssi_dbm']>0))$f[]='invalid_rssi';if(isset($d['packet_loss_percent'])&&($d['packet_loss_percent']<0||$d['packet_loss_percent']>100))$f[]='invalid_packet_loss';if(($d['rssi_dbm']??null)===null&&($d['download_mbps']??null)===null&&($d['latency_ms']??null)===null)$f[]='insufficient_metrics';return $f;}public function validationStatus(array $flags):string{return $flags?'rejected':'valid';}}
