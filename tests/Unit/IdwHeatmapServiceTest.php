<?php
namespace Tests\Unit;use App\Services\IdwHeatmapService;use PHPUnit\Framework\TestCase;
class IdwHeatmapServiceTest extends TestCase{public function test_placeholder_for_service_contract():void{$this->assertTrue(class_exists(IdwHeatmapService::class));}}
