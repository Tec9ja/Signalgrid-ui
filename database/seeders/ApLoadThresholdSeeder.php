<?php
namespace Database\Seeders;use App\Models\SystemThreshold;use Illuminate\Database\Seeder;
class ApLoadThresholdSeeder extends Seeder{public function run():void{foreach([['latency_poor_ms',100,'ms'],['packet_loss_poor_percent',3,'percent'],['throughput_poor_mbps',10,'Mbps']] as[$key,$value,$unit])SystemThreshold::updateOrCreate(['key'=>$key],compact('value','unit'));}}
